<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Withdrawal.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/withdrawals/initiate.php");
    exit();
}

// CSRF Token Validation
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    header("Location: " . BASE_URL . "/withdrawals/initiate.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);

if ($withdrawal_id <= 0) {
    $_SESSION['error_message'] = 'Invalid withdrawal ID.';
    header("Location: " . BASE_URL . "/withdrawals/initiate.php");
    exit();
}

try {
    $withdrawal = new Withdrawal();
    
    // Attempt to delete the withdrawal
    $result = $withdrawal->deleteWithdrawal($withdrawal_id, $user_id);
    
    if ($result) {
        $_SESSION['success_message'] = 'Withdrawal cancelled successfully.';
    } else {
        $_SESSION['error_message'] = 'Unable to cancel withdrawal. It may have already been processed or does not exist.';
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'An error occurred while cancelling the withdrawal. Please try again.';
    error_log("Withdrawal deletion error: " . $e->getMessage());
}

// Redirect back to the withdrawal page
header("Location: " . BASE_URL . "/withdrawals/initiate.php");
exit();
?> 