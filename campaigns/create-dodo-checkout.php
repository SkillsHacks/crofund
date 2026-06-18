<?php
/**
 * Dodo Payments Checkout Handler
 * 
 * Creates a checkout session and redirects the user to Dodo Payments
 */

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering FIRST to catch any stray output
ob_start();

// Set JSON header immediately
if (!headers_sent()) {
    header('Content-Type: application/json');
}

// Global error handler to catch any errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    // Don't output anything, just log
    return true;
});

// Fatal error handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'error' => 'A fatal error occurred. Please check server logs.',
            'type' => 'FatalError'
        ]);
        exit;
    }
});

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/Campaign.php';
    require_once __DIR__ . '/../config/dodo_config.php';
} catch (Throwable $e) {
    ob_end_clean();
    error_log('Failed to load dependencies: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode(['success' => false, 'error' => 'Server configuration error. Please contact support.']);
    exit;
}

// Check if SDK is available
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    ob_end_clean();
    error_log('Dodo Payments SDK not installed');
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'success' => false, 
        'error' => 'Payment gateway SDK not installed. Please run: composer require dodopayments/client'
    ]);
    exit;
}

try {
    require_once __DIR__ . '/../vendor/autoload.php';
} catch (Throwable $e) {
    ob_end_clean();
    error_log('Failed to load autoloader: ' . $e->getMessage());
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to load SDK autoloader: ' . $e->getMessage()
    ]);
    exit;
}

// Import Dodo Payments classes
use Dodopayments\CheckoutSessions\CheckoutSessionCreateParams\ProductCart;
use Dodopayments\Core\Exceptions\APIConnectionException;
use Dodopayments\Core\Exceptions\RateLimitError;
use Dodopayments\Core\Exceptions\APIStatusError;

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    ob_end_clean();
    error_log("CSRF token validation failed");
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Validate required fields
$required_fields = ['campaign_id', 'amount', 'full_name', 'email'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

$campaign_id = intval($_POST['campaign_id']);
$amount = floatval($_POST['amount']);
$fullName = trim($_POST['full_name']);
$email = trim($_POST['email']);
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$postalCode = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : '';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$anonymous = isset($_POST['anonymous']) ? intval($_POST['anonymous']) : 0;

// Validate amount (minimum $5)
if ($amount < 5) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Minimum donation amount is $5']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

// Get campaign details
try {
    $campaign = new Campaign();
    $campaignDetails = $campaign->getCampaignById($campaign_id);
    
    if (!$campaignDetails) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Campaign not found']);
        exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
    exit;
}

// Store donation details in session for processing after payment
$_SESSION['pending_donation'] = [
    'campaign_id' => $campaign_id,
    'donor_name' => $fullName,
    'donor_email' => $email,
    'amount' => $amount,
    'message' => $comment,
    'anonymous' => $anonymous,
    'donor_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
    'country' => $country,
    'postal_code' => $postalCode
];

try {
    // Initialize Dodo Payments client
    try {
        $client = getDodoClient();
    } catch (Exception $e) {
        ob_end_clean();
        error_log('Dodo Client Initialization Error: ' . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Payment gateway not configured. Please contact the administrator.'
        ]);
        exit;
    }
    
    // Get product ID
    $productId = getDodoProductId();
    
    // Determine the return URL
    $return_url = BASE_URL . '/campaigns/public-view.php?id=' . $campaign_id . '&payment=success';
    
    // Convert amount to cents for Pay What You Want products
    $amountInCents = intval(round($amount * 100));
    
    // Create product cart item using ProductCart::with()
    // For Pay What You Want products, include the amount parameter
    $productCartItem = ProductCart::with(
        productID: $productId,  // Note: productID (capital ID), not product_id
        quantity: 1,
        addons: null,
        amount: $amountInCents  // Amount in cents for Pay What You Want
    );
    
    // Prepare metadata
    $metadata = [
        'campaign_id' => (string)$campaign_id,
        'campaign_title' => substr($campaignDetails['title'], 0, 100),
        'donor_name' => substr($fullName, 0, 100),
        'donor_email' => $email,
        'donation_amount' => (string)$amount,
        'donation_message' => substr($comment, 0, 500),
        'anonymous' => $anonymous ? 'true' : 'false',
        'country' => substr($country, 0, 50),
        'postal_code' => substr($postalCode, 0, 20)
    ];
    
    // Prepare customer information
    $customer = [
        'email' => $email,
        'name' => $fullName
    ];
    
    // Log the request for debugging
    error_log('Dodo Checkout Request: ' . json_encode([
        'product_id' => $productId,
        'return_url' => $return_url,
        'amount' => $amount,
        'amount_in_cents' => $amountInCents,
        'has_metadata' => !empty($metadata)
    ]));
    
    // Call create with named parameters (not an array)
    $checkoutSession = $client->checkoutSessions->create(
        productCart: [$productCartItem],
        returnURL: $return_url,
        customer: $customer,
        metadata: $metadata
    );
    
    // Extract checkout URL and session ID from response
    // Response has checkoutURL (capital URL) and sessionID (capital ID) properties
    $checkoutUrl = null;
    $sessionId = null;
    
    // Log the response type for debugging
    error_log('Checkout session response type: ' . gettype($checkoutSession));
    if (is_object($checkoutSession)) {
        error_log('Response class: ' . get_class($checkoutSession));
        
        // Try different ways to access properties
        // The SDK might use array access or direct properties
        if (method_exists($checkoutSession, 'toArray')) {
            $responseArray = $checkoutSession->toArray();
            $checkoutUrl = $responseArray['checkoutURL'] ?? $responseArray['checkout_url'] ?? null;
            $sessionId = $responseArray['sessionID'] ?? $responseArray['session_id'] ?? null;
        } elseif (isset($checkoutSession['checkoutURL'])) {
            // ArrayAccess interface
            $checkoutUrl = $checkoutSession['checkoutURL'] ?? $checkoutSession['checkout_url'] ?? null;
            $sessionId = $checkoutSession['sessionID'] ?? $checkoutSession['session_id'] ?? null;
        } else {
            // Direct property access
            $checkoutUrl = $checkoutSession->checkoutURL ?? $checkoutSession->checkout_url ?? null;
            $sessionId = $checkoutSession->sessionID ?? $checkoutSession->session_id ?? null;
        }
        
        // If checkout_url is not provided, construct it from session_id
        if (empty($checkoutUrl) && !empty($sessionId)) {
            $checkoutUrl = 'https://checkout.dodopayments.com/session/' . $sessionId;
        }
    } elseif (is_array($checkoutSession)) {
        $checkoutUrl = $checkoutSession['checkoutURL'] ?? $checkoutSession['checkout_url'] ?? null;
        $sessionId = $checkoutSession['sessionID'] ?? $checkoutSession['session_id'] ?? null;
        
        // If checkout_url is not provided, construct it from session_id
        if (empty($checkoutUrl) && !empty($sessionId)) {
            $checkoutUrl = 'https://checkout.dodopayments.com/session/' . $sessionId;
        }
    }
    

    if (empty($checkoutUrl) || empty($sessionId)) {
        ob_end_clean();
        // Try to convert object to array for logging
        $responseForLog = is_object($checkoutSession) && method_exists($checkoutSession, 'toArray') 
            ? $checkoutSession->toArray() 
            : (is_object($checkoutSession) ? (array)$checkoutSession : $checkoutSession);
        error_log('Invalid checkout session response: ' . json_encode($responseForLog));
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid response from payment gateway',
            'response_type' => gettype($checkoutSession),
            'response_class' => is_object($checkoutSession) ? get_class($checkoutSession) : null
        ]);
        exit;
    }
    

    // Store session_id in session for payment verification
    $_SESSION['dodo_session_id'] = $sessionId;
    
    // Return the checkout URL
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkoutUrl,
        'session_id' => $sessionId
    ]);
    exit;

    
} catch (APIConnectionException $e) {
    ob_end_clean();
    error_log('Dodo API Connection Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Payment service is currently unavailable']);
    exit;
} catch (RateLimitError $e) {
    ob_end_clean();
    error_log('Dodo Rate Limit Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Too many requests, please try again later']);
    exit;
} catch (APIStatusError $e) {
    ob_end_clean();
    error_log('Dodo API Status Error: ' . $e->getMessage());
    error_log('Dodo API Status Code: ' . $e->getCode());
    
    // Try to get the response body for more details
    $errorBody = method_exists($e, 'getBody') ? $e->getBody() : null;
    if ($errorBody) {
        error_log('Dodo API Response Body: ' . $errorBody);
    }
    
    echo json_encode([
        'success' => false, 
        'error' => 'Payment API error',
        'details' => $e->getMessage(),
        'debug' => [
            'status_code' => $e->getCode(),
            'message' => $e->getMessage(),
            'type' => get_class($e),
            'response' => $errorBody
        ]
    ]);
    exit;
} catch (JsonException $e) {
    ob_end_clean();
    error_log('JSON Parsing Error from Dodo API: ' . $e->getMessage());
    error_log('This usually means the API returned an error response');
    error_log('Error in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
    
    echo json_encode([
        'success' => false, 
        'error' => 'API Response Error',
        'details' => 'The payment gateway returned an invalid response. This usually means there is an issue with the request parameters.',
        'debug' => [
            'type' => 'JsonException',
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'suggestions' => [
                'Check if the product ID is correct: ' . $productId,
                'Verify your API key has access to this product',
                'Check if the product is active in your Dodo dashboard',
                'Ensure you are using a test API key for test products'
            ]
        ]
    ]);
    exit;
} catch (Exception $e) {
    ob_end_clean();
    error_log('General Error in create-dodo-checkout: ' . $e->getMessage());
    error_log('Error type: ' . get_class($e));
    error_log('Error trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'error' => 'An unexpected error occurred',
        'details' => $e->getMessage(),
        'debug' => [
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
    exit;
}
