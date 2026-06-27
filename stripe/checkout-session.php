<?php
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate required fields
$requiredFields = ['amount', 'full_name', 'email', 'campaign_id'];
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
    // Create Stripe checkout session
    $stripeSession = createStripeCheckoutSession($amount, $fullName, $email, $campaignId, $country, $postalCode, $comment, $anonymous);
    
    if ($stripeSession && isset($stripeSession['url'])) {
        echo json_encode([
            'success' => true,
            'url' => $stripeSession['url'],
            'session_id' => $stripeSession['id']
        ]);
    } else {
        throw new Exception('Failed to create Stripe checkout session');
    }
    
} catch (Exception $e) {
    error_log("Stripe checkout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create payment session: ' . $e->getMessage()]);
}

/**
 * Create Stripe checkout session
 */
function createStripeCheckoutSession($amount, $fullName, $email, $campaignId, $country, $postalCode, $comment, $anonymous) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    try {
        // Get campaign details for better product description
        $campaign = new Campaign();
        $campaignDetails = $campaign->getCampaignById($campaignId);
        $campaignTitle = $campaignDetails ? $campaignDetails['title'] : 'Campaign Donation';
        
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Donation to ' . $campaignTitle,
                        'description' => 'Thank you for your generous donation!',
                    ],
                    'unit_amount' => intval($amount * 100), // Convert to cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => BASE_URL . '/stripe/success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => BASE_URL . '/stripe/cancel.php?session_id={CHECKOUT_SESSION_ID}',
            'customer_email' => $email,
            'allow_promotion_codes' => false,
            'billing_address_collection' => 'auto',
            'metadata' => [
                'campaign_id' => $campaignId,
                'donor_name' => $fullName,
                'country' => $country,
                'postal_code' => $postalCode,
                'comment' => $comment,
                'anonymous' => $anonymous
            ]
        ]);
        
        return [
            'id' => $session->id,
            'url' => $session->url
        ];
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        throw new Exception('Stripe API error: ' . $e->getMessage());
    }
}

/**
 * If you want to use actual Stripe SDK, uncomment and modify this:
 */
/*
function createStripeCheckoutSession($amount, $fullName, $email, $campaignId, $country, $postalCode, $comment, $anonymous) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Donation to Campaign',
                        'description' => 'Thank you for your donation!',
                    ],
                    'unit_amount' => intval($amount * 100), // Convert to cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => BASE_URL . '/stripe/success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => BASE_URL . '/stripe/cancel.php?session_id={CHECKOUT_SESSION_ID}',
            'customer_email' => $email,
            'metadata' => [
                'campaign_id' => $campaignId,
                'donor_name' => $fullName,
                'country' => $country,
                'postal_code' => $postalCode,
                'comment' => $comment,
                'anonymous' => $anonymous
            ]
        ]);
        
        return [
            'id' => $session->id,
            'url' => $session->url
        ];
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        throw new Exception('Stripe API error: ' . $e->getMessage());
    }
}
*/
?> 