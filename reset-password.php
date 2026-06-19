<?php
session_start();
require_once __DIR__ . '/config/config.php';

$success = '';
$error = '';
$showForm = true;

$token = $_GET['token'] ?? '';
if (!$token || strlen($token) !== 64) {
    $error = "Invalid or missing reset token.";
    $showForm = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$token || strlen($token) !== 64) {
        $error = "Invalid reset token.";
        $showForm = false;
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            global $pdo;
            $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reset && strtotime($reset['expires_at']) > time()) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $reset['user_id']]);

                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);

                $success = "Your password has been reset successfully. <a href='login.php' class='text-emerald-600 hover:text-emerald-700 font-semibold underline'>Login</a>";
                $showForm = false;
            } else {
                $error = "This reset link is invalid or has expired.";
                $showForm = false;
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password – CroFunding</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
    
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
        };
    </script>
    
    <style>
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <div class="min-h-screen flex items-center justify-center py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-8">
                <div class="text-center mb-8">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl bg-emerald-100 mb-4">
                        <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Reset Password</h1>
                    <p class="text-lg text-slate-600">Enter your new password below.</p>
                </div>
                
                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-center"><?php echo $success; ?></div>
                <?php elseif ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-center"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($showForm): ?>
                    <form method="post" action="reset-password.php" class="space-y-6">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">New Password</label>
                            <input type="password" id="password" name="password" required minlength="6"
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200">
                        </div>
                        <div>
                            <label for="confirm" class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                            <input type="password" id="confirm" name="confirm" required minlength="6"
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200">
                        </div>
                        <button type="submit"
                            class="w-full flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                            Reset Password
                        </button>
                    </form>
                <?php endif; ?>
                
                <div class="mt-8 text-center">
                    <a href="login.php" class="text-emerald-600 hover:text-emerald-700 font-semibold transition-colors">Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
