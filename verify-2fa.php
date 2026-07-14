<?php
session_start();
require_once __DIR__ . '/config/config.php';

// If user is already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

// Check if we have 2FA session data, if not redirect to login
if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_code'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$message = $_GET['message'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputCode = trim($_POST['code'] ?? '');

    // Check code expiry (5 minutes)
    if (!isset($_SESSION['2fa_code_time']) || (time() - $_SESSION['2fa_code_time'] > 300)) {
        $error = "Verification code expired. Please login again.";
        session_unset();
        session_destroy();
    } else {
        // Limit attempts
        if (!isset($_SESSION['2fa_attempts'])) {
            $_SESSION['2fa_attempts'] = 0;
        }
        if ($_SESSION['2fa_attempts'] >= 3) {
            $error = "Too many failed attempts. Please login again.";
            session_unset();
            session_destroy();
            header('Location: ' . BASE_URL . '/login.php?message=' . urlencode($error));
            exit;
        } elseif ($inputCode == ($_SESSION['2fa_code'] ?? '')) {
            // Set all the proper session variables
            session_regenerate_id(true);
            $_SESSION['user_id'] = $_SESSION['2fa_user_id'];
            $_SESSION['user_name'] = $_SESSION['2fa_user_name'];
            $_SESSION['last_activity'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

            // Get redirect path
            $redirect = $_SESSION['2fa_redirect'] ?? '/dashboard.php';

            // Clean up 2FA session data
            unset($_SESSION['2fa_code'], $_SESSION['2fa_user_id'], $_SESSION['2fa_email'], $_SESSION['2fa_user_name'], $_SESSION['2fa_code_time'], $_SESSION['2fa_attempts'], $_SESSION['2fa_redirect']);

            header('Location: ' . BASE_URL . $redirect);
            exit;
        } else {
            $_SESSION['2fa_attempts']++;
            $error = "Invalid verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Verification - CroFunding</title>
    
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
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-slate-900 mb-3">Two-factor verification</h2>
                <p class="text-lg text-slate-600">
                    Enter the 6-digit code sent to your email
                </p>
            </div>

            <!-- Alert Message -->
            <?php if ($message): ?>
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-yellow-800 font-medium"><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- 2FA Form -->
            <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-8">
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="code" class="block text-sm font-semibold text-slate-700 mb-2">Verification code</label>
                        <input id="code" name="code" type="text" maxlength="6" required autocomplete="one-time-code"
                               class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 text-center text-lg tracking-widest"
                               placeholder="000000">
                    </div>

                    <button type="submit" 
                            class="w-full flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Verify
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-600">
                        Didn’t get the code? 
                        <a href="login.php" class="font-semibold text-emerald-600 hover:text-emerald-700 transition-colors underline">
                            Login again
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
