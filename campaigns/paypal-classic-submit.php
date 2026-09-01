<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Auto-submitting hand-off page. Takes a `token` created by
 * paypal-classic-create.php (from the real donation modal, with the amount
 * and donor info already collected there) and POSTs the donor straight to
 * PayPal's classic webscr endpoint -- no second amount form to fill in.
 *
 * paypal-classic-donate.php remains as a separate, standalone entry point
 * with its own amount field, useful for testing this flow directly by URL.
 */

$token = trim((string)($_GET['token'] ?? ''));
if ($token === '') {
    http_response_code(400);
    exit('Missing token.');
}

$stmt = $pdo->prepare(
    "SELECT pd.*, c.title AS campaign_title, c.status AS campaign_status
     FROM pending_donations pd
     JOIN campaigns c ON c.id = pd.campaign_id
     WHERE pd.token = ? LIMIT 1"
);
$stmt->execute([$token]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pending || $pending['status'] !== 'pending' || $pending['campaign_status'] !== 'active') {
    http_response_code(404);
    exit('This donation link is invalid or has already been used.');
}

$paypalBusinessEmail = getDonationReceiverPaypalEmail((int) $pending['campaign_id']);
error_log('paypal-classic-submit.php: donation campaign_id=' . (int) $pending['campaign_id'] . ' selected receiver=' . $paypalBusinessEmail);

if (!$paypalBusinessEmail) {
    error_log('paypal-classic-submit.php: no valid PayPal receiver configured for campaign ' . (int) $pending['campaign_id']);
    http_response_code(500);
    exit('PayPal donations are temporarily unavailable. Please try another payment method.');
}

$classicMode = getSetting('paypal_classic_mode') ?: 'sandbox';
$paypalCgiUrl = ($classicMode !== 'live')
    ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
    : 'https://www.paypal.com/cgi-bin/webscr';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Redirecting to PayPal…</title>
</head>
<body>
<p>Redirecting you to PayPal to complete your donation…</p>

<form id="paypal-classic-handoff" action="<?php echo htmlspecialchars($paypalCgiUrl); ?>" method="post">
    <input type="hidden" name="cmd" value="_donations">
    <input type="hidden" name="business" value="<?php echo htmlspecialchars($paypalBusinessEmail); ?>">
    <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($pending['campaign_title']); ?>">
    <input type="hidden" name="item_number" value="<?php echo (int)$pending['campaign_id']; ?>">
    <input type="hidden" name="currency_code" value="<?php echo htmlspecialchars($pending['currency_code']); ?>">
    <input type="hidden" name="amount" value="<?php echo htmlspecialchars(number_format((float)$pending['amount_requested'], 2, '.', '')); ?>">
    <input type="hidden" name="custom" value="<?php echo htmlspecialchars($token); ?>">
    <input type="hidden" name="return" value="<?php echo htmlspecialchars(BASE_URL . '/campaigns/paypal-classic-return.php'); ?>">
    <input type="hidden" name="cancel_return" value="<?php echo htmlspecialchars(BASE_URL . '/campaigns/public-view.php?id=' . $pending['campaign_id'] . '&cancelled=1'); ?>">
    <input type="hidden" name="notify_url" value="<?php echo htmlspecialchars(BASE_URL . '/paypal/ipn-listener.php'); ?>">
    <noscript><button type="submit">Continue to PayPal</button></noscript>
</form>

<script>document.getElementById('paypal-classic-handoff').submit();</script>
</body>
</html>
