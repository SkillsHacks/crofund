<?php
session_start();
require_once __DIR__ . '/config/config.php';

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
            $_SESSION['user_id'] = $_SESSION['2fa_user_id'];
            unset($_SESSION['2fa_code'], $_SESSION['2fa_user_id'], $_SESSION['2fa_email'], $_SESSION['2fa_code_time'], $_SESSION['2fa_attempts']);
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        } else {
            $_SESSION['2fa_attempts']++;
            $error = "Invalid verification code.";
        }
    }
}

include __DIR__ . '/components/public-header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Login – CroFunding</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #d1fae5 0%, #f0fdf4 100%);
        }
        .verify-card {
            box-shadow: 0 8px 32px 0 rgba(16, 185, 129, 0.15);
            border: 1px solid #d1fae5;
        }
        .verify-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 2px #6ee7b7;
        }
        @media (max-width: 640px) {
            .verify-card {
                padding: 1.5rem !important;
                border-radius: 1rem !important;
            }
            .verify-card h1 {
                font-size: 1.25rem !important;
            }
            .verify-input {
                font-size: 1rem !important;
                padding: 0.75rem !important;
            }
        }
    </style>
</head>
<body>
    <main class="flex items-center justify-center min-h-screen px-2">
        <form method="post" class="verify-card bg-white p-10 rounded-xl max-w-md w-full">
            <div class="flex flex-col items-center mb-6">
                <svg class="h-12 w-12 text-emerald-600 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0-1.105.895-2 2-2s2 .895 2 2-.895 2-2 2-2-.895-2-2zm0 0V7m0 4v4m0 0c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2z"/>
                </svg>
                <h1 class="text-2xl font-bold text-emerald-700 text-center">Two-Factor Verification</h1>
                <p class="text-gray-600 mt-2 text-center text-base sm:text-sm">Enter the 6-digit code sent to your email to continue.</p>
            </div>
            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-center text-sm"><?php echo $error; ?></div>
            <?php endif; ?>
            <input type="text" name="code" maxlength="6" required autocomplete="one-time-code"
                   class="verify-input w-full border px-4 py-3 rounded-lg mb-4 text-lg text-center tracking-widest focus:outline-none transition"
                   placeholder="6-digit code">
            <button type="submit" class="w-full bg-emerald-600 text-white py-3 rounded-lg font-semibold text-lg hover:bg-emerald-700 transition">
                Verify
            </button>
            <p class="mt-6 text-gray-500 text-sm text-center">Didn’t get the code? or Check Your Spam Folder <a href="<?php echo BASE_URL; ?>/login.php" class="text-emerald-600 underline">login again</a>.</p>
        </form>
    </main>
<?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>