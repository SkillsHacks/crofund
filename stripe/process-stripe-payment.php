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

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Validate required fields
$requiredFields = ['payment_method_id', 'amount', 'full_name', 'email', 'campaign_id'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit;
}

// Sanitize and validate input
$paymentMethodId = trim($_POST['payment_method_id']);
$amount = floatval($_POST['amount']);
$fullName = trim($_POST['full_name']);
$email = trim($_POST['email']);
$campaignId = intval($_POST['campaign_id']);
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$postalCode = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : '';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$anonymous = isset($_POST['anonymous']) ? 1 : 0;

// Validate amount
if ($amount < 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Minimum donation amount is $5']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

// Initialize campaign object
$campaign = new Campaign();

// Check if campaign exists and is active
$campaignDetails = $campaign->getCampaignById($campaignId);
if (!$campaignDetails) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Campaign not found']);
    exit;
}

if ($campaignDetails['status'] !== 'active') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Campaign is not active']);
    exit;
}

try {
    // For now, we'll simulate a successful payment
    // In production, you would use the Stripe PHP SDK to confirm the payment
    
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
    
    $countryFullName = $countryNames[$country] ?? $country;
    
    // Create donation record
    $donationRecord = [
        'campaign_id' => $campaignId,
        'donor_name' => $fullName,
        'donor_email' => $email,
        'amount' => $amount,
        'payment_gateway' => 'stripe',
        'status' => 'completed',
        'country' => $countryFullName,
        'postal_code' => $postalCode,
        'comment' => $comment,
        'anonymous' => $anonymous,
        'transaction_id' => 'stripe_' . uniqid(),
        'gateway_response' => json_encode(['payment_method_id' => $paymentMethodId]),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $donationId = $campaign->createDonation($donationRecord);
    
    if ($donationId) {
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'donation_id' => $donationId,
            'redirect_url' => BASE_URL . '/completed.php?id=' . $donationId
        ]);
    } else {
        throw new Exception('Failed to create donation record');
    }
    
} catch (Exception $e) {
    error_log("Stripe payment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Payment failed: ' . $e->getMessage()]);
}

/**
 * If you want to use actual Stripe SDK, uncomment and modify this:
 */
/*
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Create payment intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => intval($amount * 100), // Convert to cents
        'currency' => 'usd',
        'payment_method' => $paymentMethodId,
        'confirmation_method' => 'manual',
        'confirm' => true,
        'metadata' => [
            'campaign_id' => $campaignId,
            'donor_name' => $fullName,
            'donor_email' => $email
        ]
    ]);
    
    if ($paymentIntent->status === 'succeeded') {
        // Payment successful, create donation record
        $donationRecord = [
            'campaign_id' => $campaignId,
            'donor_name' => $fullName,
            'donor_email' => $email,
            'amount' => $amount,
            'payment_gateway' => 'stripe',
            'status' => 'completed',
            'country' => $country,
            'postal_code' => $postalCode,
            'comment' => $comment,
            'anonymous' => $anonymous,
            'transaction_id' => $paymentIntent->id,
            'gateway_response' => json_encode($paymentIntent),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $donationId = $campaign->createDonation($donationRecord);
        
        if ($donationId) {
            echo json_encode([
                'success' => true,
                'message' => 'Payment processed successfully',
                'donation_id' => $donationId,
                'redirect_url' => BASE_URL . '/completed.php?id=' . $donationId
            ]);
        } else {
            throw new Exception('Failed to create donation record');
        }
    } else {
        throw new Exception('Payment not completed: ' . $paymentIntent->status);
    }
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Stripe API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Payment failed: ' . $e->getMessage()]);
}
*/
?> 