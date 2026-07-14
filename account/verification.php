<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/Mail.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userObj = new User();
if (!$userObj->isLoggedIn()) {
    $_SESSION['error'] = "Please login to access this page.";
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$userData = $userObj->getCurrentUser();
$mailer = new Mail();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['verify_code'])) {
            $code = trim($_POST['code'] ?? '');
            if (!empty($code)) {
                $userObj->verifyEmailCode($userData['email'], $code);
                $_SESSION['success'] = "Email verified successfully!";
                header("Location: " . BASE_URL . "/account/verification.php");
                exit();
            } else {
                $error = "Please enter the verification code.";
            }
        } elseif (isset($_POST['send_verification'])) {
            if (!$userData['is_verified']) {
                $verificationCode = $userObj->resendVerificationCode($userData['id']);
                $mailer->sendVerificationCode($userData['email'], $verificationCode, $userData['name']);
                $_SESSION['success'] = "Verification email sent successfully!";
                header("Location: " . BASE_URL . "/account/verification.php");
                exit();
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include '../components/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen pt-16">
        <?php include '../components/sidebar.php'; ?>
        
        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <div class="p-3 sm:p-6">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-3 sm:p-6">
                        <?php 
                        $alertPath = __DIR__ . '/../includes/alerts.php';
                        if (file_exists($alertPath)) {
                            include $alertPath; 
                        }
                        ?>

                        <div class="space-y-3 sm:space-y-4">
                            <?php if ($userData['is_verified']): ?>
                                <div class="p-3 sm:p-4 bg-green-50 rounded-lg border border-green-200">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-xs sm:text-sm font-medium text-green-800">Verification Status</h3>
                                            <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-green-700">Your email address has been successfully verified!</p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="p-3 sm:p-4 bg-yellow-50 rounded-lg border border-yellow-200 mb-6">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-xs sm:text-sm font-medium text-yellow-800">Verification Required</h3>
                                            <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-yellow-700">Your email address needs to be verified to access all features.</p>
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($error)): ?>
                                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl">
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="space-y-6">
                                    <div>
                                        <label for="code" class="block text-sm font-semibold text-slate-700 mb-2">Verification Code</label>
                                        <input 
                                            id="code" 
                                            name="code" 
                                            type="text" 
                                            maxlength="6" 
                                            required 
                                            autocomplete="one-time-code"
                                            class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 text-center text-lg tracking-widest"
                                            placeholder="000000"
                                        >
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-4">
                                        <button type="submit" 
                                            name="verify_code" 
                                            class="flex-1 inline-flex items-center justify-center px-4 sm:px-6 py-2 sm:py-3 border border-transparent text-xs sm:text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200">
                                            <svg class="mr-1 sm:mr-2 h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Verify Code
                                        </button>

                                        <button type="submit" 
                                            name="send_verification" 
                                            class="flex-1 inline-flex items-center justify-center px-4 sm:px-6 py-2 sm:py-3 border border-slate-200 text-xs sm:text-sm font-medium rounded-md shadow-sm text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200">
                                            <svg class="mr-1 sm:mr-2 h-4 w-4 sm:h-5 sm:w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            Resend Code
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
