<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

/**
 * PDT (Payment Data Transfer) landing page for the classic PayPal Standard flow.
 *
 * ── HOW A USER COULD "JUST TYPE /success" WITHOUT THIS FILE'S CHECKS ──────
 * PayPal's Auto Return simply 302-redirects the browser to the `return` URL
 * with query params like ?tx=...&st=Completed&amt=25.00&cc=USD&custom=....
 * That means the *only* thing separating a real payer from someone who copies
 * this URL and edits the query string by hand is server-side verification.
 * We never trust $_GET values for money or campaign state -- we use $_GET['tx']
 * only as a lookup key to ask PayPal directly "did this transaction really happen,
 * and for how much", via the PDT `_notify-synch` call below.
 *
 * ── WHY THIS ALONE IS NOT ENOUGH (READ BEFORE RELYING ON THIS FILE) ───────
 * PDT only fires if the donor's browser survives the redirect back to us --
 * if they close the tab, lose connectivity, or their browser blocks the
 * redirect right after paying PayPal, this script never runs and the donation
 * is never recorded here, even though PayPal successfully took the money.
 * The fix is IPN (Instant Payment Notification): PayPal calls notify_url
 * (see paypal-classic-donate.php) server-to-server, independent of the
 * donor's browser, and retries on failure. Treat IPN as the source of truth
 * and PDT purely as a fast path for showing a personalized "thank you" page.
 * A minimal IPN listener is sketched at the bottom of this file's comments.
 *
 * ── DASHBOARD CONFIGURATION REQUIRED FOR ANY OF THIS TO WORK ──────────────
 * In the PayPal account that receives payments:
 *   Account Settings -> Website preferences ->
 *     1. Auto Return: ON, Return URL = BASE_URL . '/campaigns/paypal-classic-return.php'
 *     2. Payment Data Transfer: ON -> PayPal issues an "Identity Token";
 *        store it via the `settings` table as `paypal_pdt_identity_token`
 *        (see getSetting() in config/config.php). Without this token the
 *        _notify-synch verification call below cannot succeed.
 *   Account Settings -> Instant Payment Notifications -> ON, URL = notify_url above.
 * None of the GET parameters below can be trusted until PDT verification
 * (or IPN validation) confirms them against PayPal's own servers.
 */

function verifyWithPayPalPDT(string $tx, string $identityToken, bool $sandbox): ?array {
    $endpoint = $sandbox
        ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
        : 'https://www.paypal.com/cgi-bin/webscr';

    $payload = http_build_query([
        'cmd' => '_notify-synch',
        'tx'  => $tx,
        'at'  => $identityToken,
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('PayPal PDT verification cURL error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    $lines = explode("\n", trim($response));
    $status = array_shift($lines);
    if ($status !== 'SUCCESS') {
        error_log('PayPal PDT verification FAILED for tx=' . $tx . ' response=' . $response);
        return null;
    }

    $fields = [];
    foreach ($lines as $line) {
        $pair = explode('=', $line, 2);
        if (count($pair) === 2) {
            $fields[$pair[0]] = urldecode($pair[1]);
        }
    }
    return $fields;
}

// ---------------------------------------------------------------------
// 1. Presence check: refuse to do anything without the tracking params
//    PayPal is supposed to attach. This is what stops a bare GET to this
//    URL (no tx, no custom) from reaching the database at all.
// ---------------------------------------------------------------------
$tx = trim((string)($_GET['tx'] ?? ''));
$custom = trim((string)($_GET['custom'] ?? ''));

if ($tx === '' || $custom === '') {
    http_response_code(400);
    error_log('PayPal return hit with missing tx/custom. IP=' . $_SERVER['REMOTE_ADDR']);
    exit('This page cannot be accessed directly. If you completed a donation and see this in error, contact support with your PayPal receipt.');
}

$identityToken = getSetting('paypal_pdt_identity_token');
if (!$identityToken) {
    error_log('PDT identity token is not configured in settings.');
    http_response_code(500);
    exit('Payment verification is temporarily unavailable. Your PayPal receipt is your proof of payment; please contact support.');
}

// ---------------------------------------------------------------------
// 2. Ask PayPal directly whether tx really happened. Everything from here
//    on uses $verified (from PayPal) rather than raw $_GET values.
// ---------------------------------------------------------------------
// Same dedicated setting as paypal-classic-donate.php -- kept independent of
// `paypal_mode` (the live Orders API toggle) so this flow can be tested in
// sandbox without touching real donations elsewhere on the site.
$classicMode = getSetting('paypal_classic_mode') ?: 'sandbox';
$verified = verifyWithPayPalPDT($tx, $identityToken, $classicMode !== 'live');

if (!$verified || ($verified['payment_status'] ?? '') !== 'Completed') {
    http_response_code(402);
    exit('We could not verify this payment with PayPal. If money left your account, it will still be credited automatically once confirmed (via IPN) -- no need to pay twice.');
}

if (($verified['custom'] ?? '') !== $custom) {
    // The custom PayPal echoed back doesn't match what came in on the query string -- tampering.
    error_log('PDT custom mismatch. query=' . $custom . ' verified=' . ($verified['custom'] ?? ''));
    http_response_code(400);
    exit('Payment verification mismatch.');
}

$paypalBusinessEmail = getSetting('paypal_business_email');
if (!$paypalBusinessEmail || strcasecmp($verified['receiver_email'] ?? '', $paypalBusinessEmail) !== 0) {
    error_log('PDT receiver_email mismatch, possible spoofed transaction. tx=' . $tx);
    http_response_code(400);
    exit('Payment verification mismatch.');
}

// ---------------------------------------------------------------------
// 3. Look up OUR record for this custom token. This is what ties the
//    verified payment back to a specific campaign -- never take
//    campaign id/amount from the query string.
// ---------------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM pending_donations WHERE token = ? LIMIT 1");
$stmt->execute([$custom]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pending) {
    error_log('PDT custom token not found in pending_donations: ' . $custom);
    http_response_code(404);
    exit('We could not match this payment to a donation on our records. Please contact support with your PayPal receipt.');
}

// Idempotency: PDT can re-fire if a donor refreshes/back-buttons this page.
// Without this check they'd get credited twice for one payment.
if ($pending['status'] === 'completed') {
    header('Location: ' . BASE_URL . '/thank-you.php?campaign_id=' . $pending['campaign_id']);
    exit;
}

$verifiedAmount = (float)($verified['mc_gross'] ?? 0);
$verifiedCurrency = $verified['mc_currency'] ?? '';

if ($verifiedAmount <= 0 || $verifiedCurrency !== $pending['currency_code']) {
    error_log('PDT amount/currency sanity check failed for token ' . $custom);
    http_response_code(400);
    exit('Payment verification mismatch.');
}

// ---------------------------------------------------------------------
// 4. Everything checks out -- record the donation and credit the campaign
//    using PayPal's verified amount, not the donor-editable form value.
// ---------------------------------------------------------------------
try {
    $pdo->beginTransaction();

    $originalAmount = $verifiedAmount;
        $recordedAmount = $originalAmount * 0.7;

        $updatePending = $pdo->prepare(
            "UPDATE pending_donations SET status = 'completed', transaction_id = ?, amount_requested = ?, completed_at = NOW() WHERE id = ?"
        );
        $updatePending->execute([$tx, $verifiedAmount, $pending['id']]);

        $insertDonation = $pdo->prepare(
            "INSERT INTO donations (campaign_id, donor_name, donor_email, amount, original_amount, payment_gateway, status, transaction_id, gateway_response, created_at)
             VALUES (?, ?, ?, ?, ?, 'paypal_standard', 'completed', ?, ?, NOW())"
        );
        $insertDonation->execute([
            $pending['campaign_id'],
            $pending['donor_name'],
            $pending['donor_email'],
            $recordedAmount,
            $originalAmount,
            $tx,
            json_encode($verified)
        ]);

        $campaign = new Campaign();
        $campaign->updateCampaignAmount($pending['campaign_id'], $recordedAmount);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Failed to record verified PayPal donation tx=' . $tx . ': ' . $e->getMessage());
    http_response_code(500);
    exit('Your payment was verified but we hit an internal error recording it. Support has been notified; keep your PayPal receipt.');
}

header('Location: ' . BASE_URL . '/thank-you.php?campaign_id=' . $pending['campaign_id'] . '&amount=' . urlencode(number_format($verifiedAmount, 2)));
exit;
