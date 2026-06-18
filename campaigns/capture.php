<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;


try {
    // Load PayPal credentials
    $clientId = PAYPAL_CLIENT_ID;
    $clientSecret = PAYPAL_CLIENT_SECRET;
    $environment = new ProductionEnvironment($clientId, $clientSecret);
    $client = new PayPalHttpClient($environment);

    // Parse input
    $inputRaw = file_get_contents('php://input');
    $data = json_decode($inputRaw, true);

    if (!isset($data['orderID'])) throw new Exception('Missing order ID');

    $orderId = $data['orderID'];
    $donorName = $data['donor_name'] ?? '';
    $donorEmail = $data['donor_email'] ?? '';
    $campaignId = (int)($data['campaign_id'] ?? 0);

    // Capture the payment
    $request = new OrdersCaptureRequest($orderId);
    $response = $client->execute($request);

    if ($response->statusCode === 201 || $response->statusCode === 200) {
        $capture = $response->result->purchase_units[0]->payments->captures[0];

        // Save donation
        $campaign = new Campaign();
        $donationId = $campaign->recordDonation(
            $campaignId,
            $donorName,
            $donorEmail,
            [
                'amount' => $capture->amount->value,
                'payment_id' => $capture->id,
                'status' => $capture->status
            ]
        );
        
        if ($donationId) {
            // Store success message in session
            $_SESSION['success'] = 'Thank you! Your PayPal donation has been processed successfully.';
        } else {
            $_SESSION['error'] = 'Payment processed but there was an issue recording your donation. Please contact support.';
        }

        echo json_encode([
            'success' => true,
            'redirectUrl' => BASE_URL . '/thank-you.php?campaign_id=' . $campaignId . '&amount=' . $capture->amount->value . '&donor=' . urlencode($donorName) . '&gateway=paypal'
        ]);
    } else {
        throw new Exception('Capture failed');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
