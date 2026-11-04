<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Mail.php';
require_once __DIR__ . '/../config/ClassicPayPalDonation.php';

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
 *
 * Receiver check: all Classic PayPal donations must be sent to the site's
 * configured `paypal_business_email`. Payments to any other receiver are ignored.
 */

$raw = file_get_contents('php://input');
parse_str($raw, $posted);

// Use the dedicated Classic PayPal mode; see campaigns/paypal-classic-donate.php.
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
// curl_close() removed on purpose: deprecated in PHP 8.5 and a no-op since PHP 8.0.

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
$receiverEmail = trim($posted['receiver_email'] ?? '');

if ($custom === '' || $txnId === '') {
    error_log('PayPal IPN missing custom/txn_id, ignoring.');
    http_response_code(200); // acknowledge so PayPal stops retrying a malformed/irrelevant hit
    exit;
}

$stmt = $pdo->prepare(
    "SELECT * FROM donations
     WHERE payment_token = ?
       AND payment_gateway = 'paypal_standard'
     LIMIT 1"
);
$stmt->execute([$custom]);
$donation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donation) {
    error_log('PayPal IPN custom token not found in donations: ' . $custom);
    http_response_code(200);
    exit;
}

if ($paymentStatus !== 'Completed') {
    error_log("PayPal IPN status={$paymentStatus} txn={$txnId} -- not crediting.");
    http_response_code(200);
    exit;
}

$sitePaypalEmail = strtolower(getSitePaypalBusinessEmail());
if ($sitePaypalEmail === '' || strtolower($receiverEmail) !== $sitePaypalEmail) {
    error_log("PayPal IPN receiver={$receiverEmail} is not allowed for campaign {$donation['campaign_id']} txn={$txnId} -- not crediting.");
    http_response_code(200);
    exit;
}

// Idempotency: this is the SAME guard as in paypal-classic-return.php.
// Whichever of (IPN, PDT) arrives first completes the donation; the other is a no-op.
if ($donation['status'] === 'completed') {
    http_response_code(200);
    exit;
}

if ($donation['status'] !== 'pending' || $mcGross <= 0 || $mcCurrency !== $donation['currency']) {
    error_log('PayPal IPN amount/currency mismatch for token ' . $custom);
    http_response_code(200);
    exit;
}

try {
    completeClassicPayPalDonation($pdo, $donation, $txnId, $mcGross, $posted);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('IPN failed to record donation txn=' . $txnId . ': ' . $e->getMessage());
    // Return non-200 so PayPal retries this IPN later instead of losing it.
    http_response_code(500);
    exit;
}

http_response_code(200);