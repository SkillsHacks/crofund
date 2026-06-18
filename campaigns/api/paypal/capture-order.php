<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../../config/Campaign.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['orderID']) || !isset($data['campaignId']) || !isset($data['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

// Verify CSRF token
if (!hash_equals($_SESSION['csrf_token'], $data['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

try {
    // Initialize PayPal client
    require __DIR__ . '/../../../vendor/autoload.php';
    
    $paypalClient = new PayPalCheckoutSdk\Core\PayPalHttpClient(
        new PayPalCheckoutSdk\Core\SandboxEnvironment(
            PAYPAL_CLIENT_ID,
            PAYPAL_CLIENT_SECRET
        )
    );

    // Capture the order
    $request = new PayPalCheckoutSdk\Orders\OrdersCaptureRequest($data['orderID']);
    $response = $paypalClient->execute($request);

    if ($response->result->status === 'COMPLETED') {
        $db = new Database();
        $campaign = new Campaign();
        
        // Get donation amount from PayPal response
        $amount = $response->result->purchase_units[0]->payments->captures[0]->amount->value;
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Update campaign amount
            $success = $campaign->updateCampaignAmount($data['campaignId'], $amount);
            
            // Record donation in donations table
            $stmt = $db->prepare("INSERT INTO donations (campaign_id, user_id, amount, payment_id, payment_status) 
                                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['campaignId'],
                $_SESSION['user_id'],
                $amount,
                $data['orderID'],
                'completed'
            ]);
            
            $db->commit();
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Payment processing error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to process payment']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Payment not completed']);
    }
    
} catch (Exception $e) {
    error_log("PayPal API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Payment processing failed']);
}