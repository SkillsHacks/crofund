<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/TrustRiskService.php';

/**
 * Legacy PayPal "Standard" donate form (posts to https://www.paypal.com/cgi-bin/webscr).
 *
 * IMPORTANT CONTEXT FOR WHOEVER MAINTAINS THIS:
 * This is the hosted-button / HTML-form integration, distinct from PayPal's
 * Smart Buttons / Orders API.
 * PayPal's own docs mark "Website Payments Standard" as legacy in favor of
 * Smart Buttons / Orders API. Only use this path if you specifically need a
 * no-JS-SDK, personal-account, form-POST integration.
 */

$campaignId = filter_input(INPUT_GET, 'campaign_id', FILTER_VALIDATE_INT);
if (!$campaignId) {
    http_response_code(400);
    exit('Missing or invalid campaign_id.');
}

$stmt = $pdo->prepare("SELECT id, title, goal_amount, current_amount, status FROM campaigns WHERE id = ?");
$stmt->execute([$campaignId]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign || $campaign['status'] !== 'active') {
    http_response_code(404);
    exit('Campaign not found or not accepting donations.');
}

$ownerStmt = $pdo->prepare('SELECT user_id FROM campaigns WHERE id = ? LIMIT 1');
$ownerStmt->execute([$campaignId]);
$campaignOwner = $ownerStmt->fetch(PDO::FETCH_ASSOC);
if (!$campaignOwner) {
    http_response_code(404);
    exit('Campaign not found or not accepting donations.');
}

try {
    $trustRisk = new TrustRiskService();
    $trustRisk->enforceForAction((int) $campaignOwner['user_id'], 'receive_donations');
} catch (RuntimeException $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 403);
    exit($e->getMessage());
}

// The receiving PayPal account. Store this in `settings` (see getSetting() in
// config/config.php) rather than hardcoding a personal email in source control.
$paypalBusinessEmail = getSitePaypalBusinessEmail();
if ($paypalBusinessEmail === '' || !filter_var($paypalBusinessEmail, FILTER_VALIDATE_EMAIL)) {
    error_log('Classic PayPal donation unavailable: site PayPal business email is not configured.');
    http_response_code(503);
    exit('PayPal donations are temporarily unavailable. Please try another payment method.');
}
// Keep this setting dedicated to Classic PayPal so sandbox testing cannot
// accidentally change the mode used by other payment integrations.
$classicMode = getSetting('paypal_classic_mode') ?: 'sandbox';
$paypalCgiUrl = ($classicMode !== 'live')
    ? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
    : 'https://www.paypal.com/cgi-bin/webscr';

/**
 * Create the server-side "source of truth" donation BEFORE the donor leaves
 * our site. `payment_token` is what we hand PayPal as `custom`.
 *
 * Why not just pass campaign_id + amount as `custom` directly? Because
 * anything in the returned GET query string is fully attacker-controlled --
 * see paypal-classic-return.php. Keeping the real amount/campaign server-side
 * and only exchanging an unguessable token closes that hole.
 */
$token = bin2hex(random_bytes(32));

$insert = $pdo->prepare(
    "INSERT INTO donations
        (campaign_id, user_id, amount, payment_gateway, status, donor_name, donor_email, payment_token, created_at, currency)
     VALUES (?, ?, ?, 'paypal_standard', 'pending', ?, ?, ?, NOW(), 'USD')"
);
$insert->execute([
    $campaignId,
    !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
    1.00, // placeholder amount; PayPal's verified amount replaces it on confirmation
    $_SESSION['user_name'] ?? null,
    $_SESSION['user_email'] ?? null,
    $token,
]);

$returnUrl = BASE_URL . '/campaigns/paypal-classic-return.php';
$cancelUrl = BASE_URL . '/campaigns/donate.php?campaign_id=' . $campaignId . '&cancelled=1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Donate to <?php echo htmlspecialchars($campaign['title']); ?></title>
</head>
<body>

<form id="paypal-donate-form" action="<?php echo htmlspecialchars($paypalCgiUrl); ?>" method="post">
    <!-- Standard "Donate" button flow -->
    <input type="hidden" name="cmd" value="_donations">
    <input type="hidden" name="business" value="<?php echo htmlspecialchars($paypalBusinessEmail); ?>">
    <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($campaign['title']); ?>">
    <input type="hidden" name="item_number" value="<?php echo htmlspecialchars($campaignId); ?>">
    <input type="hidden" name="currency_code" value="USD">

    <!-- This is the field the JS below rewrites right before submit -->
    <input type="hidden" name="amount" id="hidden-amount" value="1.00">

    <!-- Round-trips through PayPal untouched; used to look up this pending donation on return -->
    <input type="hidden" name="custom" value="<?php echo htmlspecialchars($token); ?>">

    <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
    <input type="hidden" name="cancel_return" value="<?php echo htmlspecialchars($cancelUrl); ?>">

    <!-- Ask PayPal to also fire an IPN at this URL; belt-and-suspenders alongside PDT, see notes in paypal-classic-return.php -->
    <input type="hidden" name="notify_url" value="<?php echo htmlspecialchars(BASE_URL . '/paypal/ipn-listener.php'); ?>">

    <label for="pledge-amount">Your donation amount (USD)</label>
    <input type="text" id="pledge-amount" inputmode="decimal" placeholder="25.00" required>
    <span id="amount-error" style="color:#c00; display:none;"></span>

    <button type="submit">Donate with PayPal</button>
</form>

<script>
(function () {
    var MIN_AMOUNT = 1.00;

    var form = document.getElementById('paypal-donate-form');
    var visibleInput = document.getElementById('pledge-amount');
    var hiddenAmount = document.getElementById('hidden-amount');
    var errorEl = document.getElementById('amount-error');

    function showError(message) {
        errorEl.textContent = message;
        errorEl.style.display = 'inline';
    }

    function clearError() {
        errorEl.style.display = 'none';
        errorEl.textContent = '';
    }

    form.addEventListener('submit', function (event) {
        clearError();

        // Strip anything that isn't a digit or a decimal point (stray $, commas, spaces, etc.)
        var raw = visibleInput.value.replace(/[^0-9.]/g, '');
        var amount = parseFloat(raw);

        if (isNaN(amount) || amount < MIN_AMOUNT) {
            event.preventDefault();
            showError('Please enter an amount of at least $' + MIN_AMOUNT.toFixed(2) + '.');
            visibleInput.focus();
            return;
        }

        // PayPal expects exactly two decimal places (e.g. "25.00", not "25" or "25.5")
        hiddenAmount.value = amount.toFixed(2);

        // Note: this client-side check only prevents accidental/obviously-bad input.
        // It is NOT a security control -- amt is re-validated from PayPal's own
        // PDT response in paypal-classic-return.php, never trusted from the client.
    });
})();
</script>

</body>
</html>
