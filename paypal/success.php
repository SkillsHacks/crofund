<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/Database.php';

// Check if we have donation data in session
if (!isset($_SESSION['donation_data']) || !isset($_GET['token'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$donationData = $_SESSION['donation_data'];
$paypalOrderId = $_GET['token'];

try {
    // Function to get PayPal access token
    function getPayPalAccessToken($clientId, $clientSecret, $mode) {
        $url = $mode === 'live' 
            ? 'https://api-m.paypal.com/v1/oauth2/token' 
            : 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get PayPal access token');
        }
        
        $tokenData = json_decode($response, true);
        return $tokenData['access_token'];
    }
    
    // Get access token
    $accessToken = getPayPalAccessToken(PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET, PAYPAL_MODE);
    
    // Capture the order
    $url = PAYPAL_MODE === 'live' 
        ? "https://api-m.paypal.com/v2/checkout/orders/{$paypalOrderId}/capture" 
        : "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$paypalOrderId}/capture";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'Prefer: return=representation'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 201 && $httpCode !== 200) {
        throw new Exception('Failed to capture PayPal order: ' . $response);
    }
    
    $capture = json_decode($response, true);
    
    if (isset($capture['status']) && $capture['status'] === 'COMPLETED') {
        $db = Database::getInstance();
        $campaign = new Campaign();

        // Prevent duplicate processing: check if this order ID was already recorded
        $checkStmt = $db->prepare("SELECT id FROM donations WHERE transaction_id = ? AND payment_gateway = 'paypal' LIMIT 1");
        $checkStmt->execute([$paypalOrderId]);
        if ($checkStmt->fetch()) {
            unset($_SESSION['donation_data']);
            header('Location: ' . BASE_URL . '/thank-you.php');
            exit;
        }

        $db->beginTransaction();

        try {
            $originalAmount = $donationData['amount'];
            $recordedAmount = $originalAmount * 0.8;
            
            $campaign->updateCampaignAmount($donationData['campaign_id'], $recordedAmount);

            $stmt = $db->prepare("INSERT INTO donations (campaign_id, donor_name, donor_email, amount, original_amount, payment_gateway, transaction_id, status, country, postal_code, comment, anonymous) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $donationData['campaign_id'],
                $donationData['full_name'],
                $donationData['email'],
                $recordedAmount,
                $originalAmount,
                'paypal',
                $paypalOrderId,
                'completed',
                $donationData['country'],
                $donationData['postal_code'],
                $donationData['comment'],
                $donationData['anonymous']
            ]);

            $db->commit();

            unset($_SESSION['donation_data']);
            header('Location: ' . BASE_URL . '/thank-you.php');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Donation processing error: " . $e->getMessage());
            $_SESSION['donation_error'] = 'Your payment was received but we had trouble recording it. Please contact support with reference: ' . $paypalOrderId;
        }
    } else {
        $captureStatus = $capture['status'] ?? 'UNKNOWN';
        error_log("PayPal order not completed. Status: " . $captureStatus . " OrderID: " . $paypalOrderId);
        $_SESSION['donation_error'] = 'Your payment could not be completed (status: ' . htmlspecialchars($captureStatus) . '). Please try again or contact support.';
    }
} catch (Exception $e) {
    error_log("PayPal capture error: " . $e->getMessage());
    $_SESSION['donation_error'] = 'An error occurred while processing your payment. Please contact support if you were charged.';
}

unset($_SESSION['donation_data']);
header('Location: ' . BASE_URL . '/');
exit;
?>