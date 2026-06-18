<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    if (isset($_GET['token'])) {
        $token = $_GET['token'];
        
        // Create User instance
        $userObj = new User();
        
        // Verify email
        if ($userObj->verifyEmail($token)) {
            $_SESSION['success'] = "Email verified successfully! You can now access all features.";
        } else {
            $_SESSION['error'] = "Invalid or expired verification token.";
        }
    } else {
        $_SESSION['error'] = "No verification token provided.";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "An error occurred: " . $e->getMessage();
}

// Redirect to verification page
header("Location: verification.php");
exit();