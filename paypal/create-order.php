<?php
ini_set('display_errors', 0);

// Check if cURL is available
if (!function_exists('curl_init')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'cURL extension is not installed']);
    exit;
}

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

header('Content-Type: application/json');

if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$requiredFields = ['amount', 'full_name', 'email', 'campaign_id'];
$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: ' . implode(', ', $missingFields)]);
    exit;
}

$amount = floatval($_POST['amount']);
$fullName = trim($_POST['full_name']);
$email = trim($_POST['email']);
$campaignId = intval($_POST['campaign_id']);
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$postalCode = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : '';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$anonymous = isset($_POST['anonymous']) ? 1 : 0;

if ($amount < 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Minimum donation amount is $5']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

$campaign = new Campaign();
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
    if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_CLIENT_SECRET)) {
        throw new Exception('PayPal credentials are not configured');
    }

    function getAccessToken($clientId, $clientSecret, $mode) {
        $url = $mode === 'live' ? 'https://api-m.paypal.com/v1/oauth2/token' : 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get PayPal access token. HTTP code: ' . $httpCode . '. Response: ' . $response . '. Curl error: ' . $curlError);
        }
        
        $data = json_decode($response, true);
        return $data['access_token'];
    }

    $accessToken = getAccessToken(PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET, PAYPAL_MODE);
    
    $url = PAYPAL_MODE === 'live' ? 'https://api-m.paypal.com/v2/checkout/orders' : 'https://api-m.sandbox.paypal.com/v2/checkout/orders';
    $orderData = [
        "intent" => "CAPTURE",
        "purchase_units" => [[
            "reference_id" => "campaign_" . $campaignId,
            "description" => "Donation to: " . $campaignDetails['title'],
            "amount" => [
                "currency_code" => "USD",
                "value" => number_format($amount, 2, '.', '')
            ]
        ]],
        "application_context" => [
            "cancel_url" => BASE_URL . "/paypal/cancel.php",
            "return_url" => BASE_URL . "/paypal/success.php"
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 201) {
        throw new Exception('Failed to create PayPal order: ' . $response);
    }

    $order = json_decode($response, true);
    $approvalUrl = '';
    foreach ($order['links'] as $link) {
        if ($link['rel'] === 'approve') {
            $approvalUrl = $link['href'];
            break;
        }
    }

    if (!$approvalUrl) {
        throw new Exception('Failed to get PayPal approval URL');
    }

    $_SESSION['donation_data'] = [
        'campaign_id' => $campaignId,
        'amount' => $amount,
        'full_name' => $fullName,
        'email' => $email,
        'country' => $country,
        'postal_code' => $postalCode,
        'comment' => $comment,
        'anonymous' => $anonymous
    ];

    echo json_encode([
        'success' => true,
        'orderId' => $order['id'],
        'approvalUrl' => $approvalUrl
    ]);
} catch (Exception $e) {
    error_log('PayPal error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>