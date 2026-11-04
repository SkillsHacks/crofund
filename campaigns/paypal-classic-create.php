<?php
ini_set('display_errors', 0);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

/**
 * Called from the donation modal in public-view.php when the donor picks the
 * "PayPal (Personal Account)" gateway option. Creates a pending donation
 * row (server-side source of truth -- see paypal-classic-return.php for why)
 * and hands back a redirect URL to the auto-submitting hand-off page.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$campaignId = intval($_POST['campaign_id'] ?? 0);
$amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');

if (!$campaignId || $amount === false || $fullName === '' || $email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit;
}

$minDonation = (float)(getSetting('min_donation') ?: 5);
$maxDonation = (float)(getSetting('max_donation') ?: 10000);
if ($amount < $minDonation || $amount > $maxDonation) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => "Donation amount must be between \${$minDonation} and \${$maxDonation}"]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, status FROM campaigns WHERE id = ?");
$stmt->execute([$campaignId]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign || $campaign['status'] !== 'active') {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Campaign not found or not accepting donations']);
    exit;
}

$token = bin2hex(random_bytes(32));

$insert = $pdo->prepare(
    "INSERT INTO donations
        (campaign_id, user_id, amount, payment_gateway, status, donor_name, donor_email, payment_token, created_at, currency)
     VALUES (?, ?, ?, 'paypal_standard', 'pending', ?, ?, ?, NOW(), 'USD')"
);
$insert->execute([
    $campaignId,
    !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
    number_format($amount, 2, '.', ''),
    $fullName,
    $email,
    $token,
]);

echo json_encode([
    'success' => true,
    'redirect' => BASE_URL . '/campaigns/paypal-classic-submit.php?token=' . urlencode($token),
]);
