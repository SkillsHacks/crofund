<?php
session_start();
require_once '../config/config.php';
require_once '../vendor/autoload.php';

use Omnipay\Omnipay;

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify CSRF token
if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Invalid request";
    header('Location: ' . BASE_URL . '/campaigns/browse.php');
    exit();
}

// Validate amount
if (!isset($_POST['amount']) || !is_numeric($_POST['amount']) || $_POST['amount'] <= 0) {
    $_SESSION['error'] = "Invalid amount";
    header('Location: ' . BASE_URL . '/campaigns/view.php?id=' . $_POST['campaign_id']);
    exit();
}

try {
    // Initialize PayPal gateway
    $gateway = Omnipay::create('PayPal_Rest');
    $gateway->setClientId(PAYPAL_CLIENT_ID);
    $gateway->setSecret(PAYPAL_CLIENT_SECRET);
    $gateway->setTestMode(PAYPAL_TEST_MODE);

    // Create purchase request
    $response = $gateway->purchase([
        'amount' => number_format($_POST['amount'], 2, '.', ''),
        'currency' => PAYPAL_CURRENCY,
        'returnUrl' => PAYPAL_RETURN_URL . '?campaign_id=' . $_POST['campaign_id'],
        'cancelUrl' => PAYPAL_CANCEL_URL . '?campaign_id=' . $_POST['campaign_id'],
        'description' => 'Donation to campaign: ' . $_POST['campaign_title']
    ])->send();

    if ($response->isRedirect()) {
        // Log successful redirect
        error_log('PayPal Redirect URL: ' . $response->getRedirectUrl());
        $response->redirect();
    } else {
        // Log error message
        error_log('PayPal Error: ' . $response->getMessage());
        $_SESSION['error'] = "Payment Error: " . $response->getMessage();
        header('Location: ' . BASE_URL . '/campaigns/view.php?id=' . $_POST['campaign_id']);
    }
} catch(Exception $e) {
    // Log detailed error
    error_log('PayPal Exception: ' . $e->getMessage());
    $_SESSION['error'] = "Payment Error: " . $e->getMessage();
    header('Location: ' . BASE_URL . '/campaigns/view.php?id=' . $_POST['campaign_id']);
}
exit();