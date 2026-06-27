<?php
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!isset($input['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $input['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$sessionId = $input['session_id'] ?? '';
$donationData = $_SESSION['stripe_donation_' . $sessionId] ?? null;

if (!$donationData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid session ID']);
    exit;
}

try {
    // Check if donation was already processed
    if (isset($_SESSION['donation_processed_' . $sessionId])) {
        echo json_encode([
            'success' => true,
            'message' => 'Donation already processed',
            'donation_id' => $_SESSION['donation_processed_' . $sessionId],
            'redirect_url' => BASE_URL . '/completed.php?id=' . $_SESSION['donation_processed_' . $sessionId]
        ]);
        exit;
    }
    
    // Initialize campaign object
    $campaign = new Campaign();
    
    
    // Convert country code to full name for database storage
    $countryNames = [
        'US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom',
        'DE' => 'Germany', 'FR' => 'France', 'AU' => 'Australia',
        'JP' => 'Japan', 'CN' => 'China', 'IN' => 'India',
        'BR' => 'Brazil', 'MX' => 'Mexico', 'ZA' => 'South Africa',
        'NG' => 'Nigeria', 'KE' => 'Kenya', 'UG' => 'Uganda',
        'GH' => 'Ghana', 'TZ' => 'Tanzania', 'ET' => 'Ethiopia',
        'RW' => 'Rwanda', 'EG' => 'Egypt', 'SA' => 'Saudi Arabia',
        'AE' => 'United Arab Emirates'
    ];
    
    $countryFullName = $countryNames[$donationData['country']] ?? $donationData['country'];
    
    // Create donation record
    $donationRecord = [
        'campaign_id' => $donationData['campaign_id'],
        'donor_name' => $donationData['full_name'],
        'donor_email' => $donationData['email'],
        'amount' => $donationData['amount'],
        'payment_gateway' => 'stripe',
        'status' => 'completed',
        'country' => $countryFullName,
        'postal_code' => $donationData['postal_code'],
        'comment' => $donationData['comment'],
        'anonymous' => $donationData['anonymous'],
        'created_at' => $donationData['created_at']
    ];
    
    $donationId = $campaign->createDonation($donationRecord);
    
    if ($donationId) {
        // Mark donation as processed
        $_SESSION['donation_processed_' . $sessionId] = $donationId;
        
        // Clear session data
        unset($_SESSION['stripe_donation_' . $sessionId]);
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Donation processed successfully',
            'donation_id' => $donationId,
            'redirect_url' => BASE_URL . '/completed.php?id=' . $donationId
        ]);
    } else {
        throw new Exception('Failed to create donation record');
    }
    
} catch (Exception $e) {
    error_log("Payment completion error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to complete payment: ' . $e->getMessage()]);
}
?> 