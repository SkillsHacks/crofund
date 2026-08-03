<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/TrustRiskService.php';

function redirectToLoginWithError(string $message): void {
    $_SESSION['error'] = $message;
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

register_shutdown_function(function () {
    $error = error_get_last();

    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    if (!headers_sent()) {
        error_log('Fatal error during login: ' . ($error['message'] ?? 'Unknown fatal error'));
        $_SESSION['error'] = 'The authentication service is currently busy. Please try again in a moment.';
        header('Location: ' . BASE_URL . '/login.php');
    }
});

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
        throw new Exception('Login failed. Please check your credentials.');
    }

    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
    }

    $_SESSION['2fa_redirect'] = $redirect;

    header('Location: ' . BASE_URL . '/verify-2fa.php');
    exit();
} catch (Throwable $e) {
    if (strpos($e->getMessage(), 'Your email is not verified') !== false) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: ' . BASE_URL . '/verify-email.php');
        exit();
    }

    error_log('Login failure: ' . $e->getMessage());

    $userMessage = trim($e->getMessage());
    $isInternalFailure = $e instanceof Error
        || stripos($userMessage, 'authentication service') !== false
        || stripos($userMessage, 'SQLSTATE') !== false
        || stripos($userMessage, 'PDO') !== false;

    if ($isInternalFailure || $userMessage === '') {
        redirectToLoginWithError('The authentication service is currently busy. Please try again in a moment.');
    }

    redirectToLoginWithError($userMessage);
}

return $user;