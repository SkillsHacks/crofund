<?php

define('BASE_URL', 'https://crofund.org/new');
define('SITE_NAME', 'CroFund');


// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'crofundo_admin');
define('DB_PASS', 'crofundo_admin');
define('DB_NAME', 'crofundo_donations');

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);


define('MIN_WITHDRAWAL_AMOUNT', 100.00);
define('MAX_WITHDRAWAL_AMOUNT', 100.00);
define('MAX_WITHDRAWAL_ATTEMPTS', 3);
define('CSRF_TOKEN_LENGTH', 32);


if (!defined('PAYPAL_SANDBOX_MODE')) {
    define('PAYPAL_SANDBOX_MODE', false);
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}

define('PAYPAL_CLIENT_ID', getSetting('paypal_client_id'));
define('PAYPAL_CLIENT_SECRET', getSetting('paypal_client_secret'));
define('PAYPAL_MODE', getSetting('paypal_mode'));

// Stripe Configuration
define('STRIPE_PUBLISHABLE_KEY', getSetting('stripe_publishable_key') ?: 'pk_live_51Rx7ePFKSGgzQnUoWNAjdtTHyb0EkfvwgcIZNqAgBEX023B8ivkBUfnVSeitwCNAFIypL1XE44DtAUnrx8LeKcnI004Fcj5HpE');
define('STRIPE_SECRET_KEY', getSetting('stripe_secret_key') ?: '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');
define('STRIPE_WEBHOOK_SECRET', getSetting('stripe_webhook_secret') ?: 'whsec_your_webhook_secret_here'); 

define('DODO_PAYMENTS_API_KEY', getSetting('dodo_payments_api_key') ?: '');
define('DODO_PAYMENTS_PRODUCT_ID', getSetting('dodo_payments_product_id') ?: '');
define('DODO_PAYMENTS_MODE', getSetting('dodo_payments_mode') ?: 'live'); // 'test' or 'live'

define('AVATAR_UPLOAD_PATH', __DIR__ . '/../uploads/avatars/');
define('AVATAR_URL_PATH', 'uploads/avatars/');

define('MAIL_HOST', 'mail.crofund.org');
define('MAIL_USERNAME', 'support@crofund.org');
define('MAIL_PASSWORD', 'Ihateu123#@!000');
define('MAIL_FROM', 'support@crofund.org');
define('MAIL_FROM_NAME', 'CroFunding');
define('MAIL_PORT', 587);
define('MAIL_SECURE', 'tls');


?>