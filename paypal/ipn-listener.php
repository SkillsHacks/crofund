<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/Mail.php';

/**
 * IPN (Instant Payment Notification) listener for the classic PayPal Standard
 * flow -- the reliable counterpart to campaigns/paypal-classic-return.php.
 *
 * This is what actually solves the "donor closed the browser" data-loss case:
 * PayPal calls THIS URL server-to-server, independent of the donor's browser,
 * and keeps retrying (over ~4 days) until it gets an HTTP 200. PDT is only a
 * same-request confirmation for a nicer thank-you page and can be skipped
 * entirely; this listener is what should be treated as authoritative.
 *
 * Dashboard requirement: Account Settings -> Instant Payment Notifications ->
 * ON, with this file's full URL. No identity token is needed here -- IPN is
 * verified by posting the payload back to PayPal (below), not with a token.
 */

$raw = file_get_contents('php://input');
parse_str($raw, $posted);

// Always answer PayPal's POST immediately-ish with 200 so it doesn't keep retrying
// once we've handled (or rejected) it; do the real work before this in either case.

// Independent of `paypal_mode` (the live Orders API toggle) -- see the note
// in campaigns/paypal-classic-donate.php.
$classicMode = getSetting('paypal_classic_mode') ?: 'sandbox';
$sandbox = $classicMode !== 'live';
$verifyUrl = $sandbox
    ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'
    : 'https://ipnpb.paypal.com/cgi-bin/webscr';

$verifyPayload = 'cmd=_notify-validate&' . $raw;

$ch = curl_init($verifyUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $verifyPayload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$result = curl_exec($ch);
curl_close($ch);

if ($result !== 'VERIFIED') {
    error_log('PayPal IPN not verified (result=' . var_export($result, true) . '): ' . $raw);
    http_response_code(400);
    exit;
}

$custom = trim($posted['custom'] ?? '');
$paymentStatus = $posted['payment_status'] ?? '';
$txnId = $posted['txn_id'] ?? '';
$mcGross = (float)($posted['mc_gross'] ?? 0);
$mcCurrency = $posted['mc_currency'] ?? '';
$receiverEmail = $posted['receiver_email'] ?? '';

if ($custom === '' || $txnId === '') {
    error_log('PayPal IPN missing custom/txn_id, ignoring.');
    http_response_code(200); // acknowledge so PayPal stops retrying a malformed/irrelevant hit
    exit;
}

$paypalBusinessEmail = getSetting('paypal_business_email');
if ($paymentStatus !== 'Completed' || strcasecmp($receiverEmail, (string)$paypalBusinessEmail) !== 0) {
    error_log("PayPal IPN status={$paymentStatus} receiver={$receiverEmail} txn={$txnId} -- not crediting.");
    http_response_code(200);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pending_donations WHERE token = ? LIMIT 1");
$stmt->execute([$custom]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pending) {
    error_log('PayPal IPN custom token not found: ' . $custom);
    http_response_code(200);
    exit;
}

// Idempotency: this is the SAME guard as in paypal-classic-return.php.
// Whichever of (IPN, PDT) arrives first completes the donation; the other is a no-op.
if ($pending['status'] === 'completed') {
    http_response_code(200);
    exit;
}

if ($mcGross <= 0 || $mcCurrency !== $pending['currency_code']) {
    error_log('PayPal IPN amount/currency mismatch for token ' . $custom);
    http_response_code(200);
    exit;
}

try {
    $pdo->beginTransaction();

    $originalAmount = $mcGross;
        $recordedAmount = $originalAmount * 0.7;

        $pdo->prepare(
            "UPDATE pending_donations SET status = 'completed', transaction_id = ?, amount_requested = ?, completed_at = NOW() WHERE id = ?"
        )->execute([$txnId, $mcGross, $pending['id']]);

        $pdo->prepare(
            "INSERT INTO donations (campaign_id, donor_name, donor_email, amount, original_amount, payment_gateway, status, transaction_id, gateway_response, created_at)
             VALUES (?, ?, ?, ?, ?, 'paypal_standard', 'completed', ?, ?, NOW())"
        )->execute([
            $pending['campaign_id'],
            $pending['donor_name'],
            $pending['donor_email'],
            $recordedAmount,
            $originalAmount,
            $txnId,
            json_encode($posted)
        ]);

        (new Campaign())->updateCampaignAmount($pending['campaign_id'], $recordedAmount);

    $pdo->commit();

    try {
        $campaignTitle = $pdo->prepare("SELECT title FROM campaigns WHERE id = ? LIMIT 1");
        $campaignTitle->execute([$pending['campaign_id']]);
        $campaignTitle = $campaignTitle->fetchColumn() ?: 'Unknown campaign';

        $mailer = new Mail();
        $mailer->sendDonationAdminNotification([
            'campaign_title' => $campaignTitle,
            'donor_name' => $pending['donor_name'],
            'donor_email' => $pending['donor_email'],
            'amount' => $recordedAmount,
            'original_amount' => $originalAmount,
            'payment_gateway' => 'paypal_standard',
            'transaction_id' => $txnId,
            'country' => null,
            'comment' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        error_log('Failed to send PayPal IPN admin donation notification: ' . $e->getMessage());
    }
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('IPN failed to record donation txn=' . $txnId . ': ' . $e->getMessage());
    // Return non-200 so PayPal retries this IPN later instead of losing it.
    http_response_code(500);
    exit;
}

http_response_code(200);
