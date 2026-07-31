<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/User.php';
require_once __DIR__ . '/config/Mail.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userObj = new User();
$mailer = new Mail();

// Check if we have an email to verify
if (!isset($_SESSION['verify_email'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$email = $_SESSION['verify_email'];

// Handle code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['verify_code'])) {
            $code = trim($_POST['code'] ?? '');
            if (!empty($code)) {
                $userObj->verifyEmailCode($email, $code);
                $_SESSION['success'] = "Email verified successfully! You can now login.";
                unset($_SESSION['verify_email']);
                header("Location: " . BASE_URL . "/login.php");
                exit();
            } else {
                $error = "Please enter the verification code.";
            }
        } elseif (isset($_POST['resend_code'])) {
            // Get user by email
            $user = $userObj->getUserByEmail($email);
            if ($user) {
                $newCode = $userObj->resendVerificationCode($user['id']);
                $mailer->sendVerificationCode($email, $newCode);
                $_SESSION['success'] = "Verification code resent successfully!";
            } else {
                $error = "User not found.";
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/components/public-header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    
    <title>Verify Email - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-slate-900 mb-3">Verify Your Email</h2>
                <p class="text-lg text-slate-600">
                    We've sent a verification code to <strong class="text-slate-800"><?php echo htmlspecialchars($email); ?></strong>
                </p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-8">
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

                    <button type="submit" 
                            name="verify_code"
                            class="w-full flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Verify Code
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-600">
                        Didn't receive the code? 
                        <form method="POST" class="inline">
                            <button type="submit" name="resend_code" class="font-semibold text-emerald-600 hover:text-emerald-700 underline">
                                Resend Code
                            </button>
                        </form>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
