<?php

define('BASE_URL', 'http://localhost/crofund');
define('SITE_NAME', 'CroFund');


// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
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

// Stripe Configuration (TEST MODE - Replace with your actual test keys)
define('STRIPE_PUBLISHABLE_KEY', getSetting('stripe_publishable_key') ?: 'pk_test_51RyDlT2eCSr0a8MYts58DRWjz8nmgKZtDfZHbqpKKIDmZ3xIMmS0BxSusnJHfdFr4m8sNCErBcIFsGTxu4ps09Dm00GfAnw5qg');
define('STRIPE_SECRET_KEY', getSetting('stripe_secret_key') ?: 'sk_test_51RyDlT2eCSr0a8MYGvXjLMvduV7taSpA6Uxfr1EyNUE84CLTAYe9vqj3PE5jHTOSC4PiM4Dcsz5Q8HVUO6r9aE5S00eg5OFNyE');
define('STRIPE_WEBHOOK_SECRET', getSetting('stripe_webhook_secret') ?: 'whsec_your_webhook_secret_here');

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