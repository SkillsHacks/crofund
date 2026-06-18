<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/Mail.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create User instance
$userObj = new User();

// Check if user is logged in
if (!$userObj->isLoggedIn()) {
    $_SESSION['error'] = "Please login to access this page.";
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Get current user data
$userData = $userObj->getCurrentUser();

// Handle verification request
if (isset($_POST['send_verification'])) {
    if (!$userData['is_verified']) {
        try {
            // Generate new verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Update token using the proper method
            $userObj->updateVerificationToken($userData['id'], $verificationToken);

            // Send verification email using Mail class
            $verificationLink = BASE_URL . "/account/verify.php?token=" . $verificationToken;
            $mailer = new Mail();
            
            if ($mailer->sendVerificationEmail($userData['email'], $verificationLink)) {
                $_SESSION['success'] = "Verification email sent successfully!";
            } else {
                $_SESSION['error'] = "Failed to send verification email. Please try again.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        }
        
        header("Location: verification.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include '../components/header.php'; ?>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="lg:pl-64 flex flex-col flex-1">
        <!-- Top Navigation -->
        <div class="sticky top-0 z-10 flex-shrink-0 h-16 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center h-full px-4 sm:px-6 lg:px-8">
                <h1 class="text-2xl font-semibold text-gray-900">Email Verification</h1>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 pb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6">
                        <?php include '../includes/alerts.php'; ?>

                        <div class="space-y-4">
                            <?php if ($userData['is_verified']): ?>
                                <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-green-800">Verification Status</h3>
                                            <p class="mt-2 text-sm text-green-700">Your email address has been successfully verified!</p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-yellow-800">Verification Required</h3>
                                            <p class="mt-2 text-sm text-yellow-700">Your email address needs to be verified to access all features.</p>
                                        </div>
                                    </div>
                                    <form method="POST" class="mt-6">
                                        <button type="submit" 
                                                name="send_verification" 
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200">
                                            <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            Send Verification Email
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>