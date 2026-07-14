<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$redirect = $_POST['redirect'] ?? '/dashboard.php';

try {
    $userObj = new User();
    $user = $userObj->login($email, $password);

    if (!is_array($user) || empty($user['id']) || empty($user['email'])) {
        throw new Exception("Login failed. Please check your credentials.");
    }

    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
    }

    // Store redirect path in session for after 2FA verification
    $_SESSION['2fa_redirect'] = $redirect;

    // Redirect to 2FA verification page
    header('Location: ' . BASE_URL . '/verify-2fa.php');
    exit();
} catch (Exception $e) {
        // Check if the error is about verification
        if (strpos($e->getMessage(), "Your email is not verified") !== false) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: " . BASE_URL . "/verify-email.php");
            exit();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }

return $user;