<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Withdrawal.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PremiumAccountService.php';
require_once __DIR__ . '/../config/Mail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $method = $_POST['withdrawal_method'] ?? '';
    $amount = floatval($_POST['amount']);

    // Premium membership required
    $db = Database::getInstance()->getConnection();
    $premiumService = new PremiumAccountService($db);
    if (!$premiumService->hasPremiumAccess((int) $user_id)) {
        $_SESSION['error_message'] = 'You need an active premium membership to make withdrawals. <a href="' . BASE_URL . '/account/premium.php" class="text-emerald-700 underline font-semibold">Upgrade to premium</a> to continue.';
        header("Location: initiate.php");
        exit();
    }

    // Validate amount
    if ($amount < MIN_WITHDRAWAL_AMOUNT) {
        $_SESSION['error_message'] = "Minimum withdrawal amount is $" . number_format(MIN_WITHDRAWAL_AMOUNT, 2);
        header("Location: initiate.php");
        exit();
    }

    if ($amount > MAX_WITHDRAWAL_AMOUNT) {
        $_SESSION['error_message'] = "Maximum withdrawal amount is $" . number_format(MAX_WITHDRAWAL_AMOUNT, 2);
        header("Location: initiate.php");
        exit();
    }

    // Initialize and sanitize withdrawal details
    $details = [];

    if ($method === 'bank') {
        $details['account_name']   = htmlspecialchars(trim($_POST['account_name'] ?? ''));
        $details['bank_name']      = htmlspecialchars(trim($_POST['bank_name'] ?? ''));
        $details['account_number'] = htmlspecialchars(trim($_POST['account_number'] ?? ''));
    } elseif ($method === 'mobile') {
        $details['full_name']       = htmlspecialchars(trim($_POST['full_name'] ?? ''));
        $details['country']         = htmlspecialchars(trim($_POST['country'] ?? ''));
        $details['mobile_provider'] = htmlspecialchars(trim($_POST['mobile_provider'] ?? ''));
        $details['phone_number']    = htmlspecialchars(trim($_POST['phone_number'] ?? ''));
    } elseif ($method === 'paypal') {
        $details['paypal_email'] = htmlspecialchars(trim($_POST['paypal_email'] ?? ''));
    }

    // Filter out empty fields
    $details = array_filter($details, fn($v) => !empty($v));

    $recentRequestKey = md5($user_id . '|' . $method . '|' . $amount . '|' . json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    if (isset($_SESSION['recent_withdrawal_requests'][$recentRequestKey]) && (time() - $_SESSION['recent_withdrawal_requests'][$recentRequestKey]) < 30) {
        $_SESSION['error_message'] = 'This withdrawal request is already being processed. Please wait a moment and try again.';
        header("Location: initiate.php");
        exit();
    }
    $_SESSION['recent_withdrawal_requests'][$recentRequestKey] = time();

    $withdrawal = new Withdrawal();
    try {
        $withdrawal->validateWithdrawal($user_id, $amount, $method);
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: initiate.php");
        exit();
    }

    // Create withdrawal entry
    $result = $withdrawal->createWithdrawal($user_id, $method, $amount, $details);

    if ($result) {
        // Friendly method name for email
        switch ($method) {
            case 'bank':   $methodLabel = 'Bank Transfer'; break;
            case 'mobile': $methodLabel = 'Mobile Money'; break;
            case 'paypal': $methodLabel = 'PayPal'; break;
            default:       $methodLabel = ucfirst($method); break;
        }

        // Fetch user email
        $userObj = new User();
        $user = $userObj->getUserById($user_id);

        if ($user && !empty($user['email'])) {
            try {
                (new Mail())->sendWithdrawalInitiatedEmail(
                    (string) $user['email'],
                    (string) ($user['name'] ?? 'User'),
                    (float) $amount,
                    $methodLabel
                );
            } catch (Throwable $e) {
                error_log('Withdrawal confirmation email failed for user ' . (int) $user_id . ': ' . $e->getMessage());
            }
        }

        // Redirect to form with success message
        header("Location: initiate.php?success=1");
        exit();
    } else {
        echo "Failed to create withdrawal. Please try again.";
    }
}
?>
