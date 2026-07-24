<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Mail.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Use the PDO object from config.php
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token and expiry in a password_resets table
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)");
            $stmt->execute([$user['id'], $token, $expires]);

            // Prepare reset link
            $resetLink = BASE_URL . "/reset-password.php?token=$token";
            $mailer = new Mail();
            try {
                $mailer->sendPasswordResetEmail($email, $resetLink, $user['name'] ?? null);
            } catch (Throwable $e) {
                error_log('Failed to send password reset email: ' . $e->getMessage());
            }
        }

        // Always show success message for security
        $success = "If an account with that email exists, a password reset link has been sent.";
    } else {
        $error = "Please enter a valid email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password – CroFunding</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-2xl p-8 sm:p-10 max-w-md w-full border border-gray-100">
            <div class="mb-6 text-center">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-emerald-100 mb-3">
                    <svg class="h-7 w-7 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 12v1a4 4 0 01-8 0v-1m8 0V9a4 4 0 10-8 0v3m8 0H8"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Forgot Password</h1>
                <p class="mt-2 text-gray-500 text-sm">Enter your email address and we'll send you a link to reset your password.</p>
            </div>
            <?php if ($success): ?>
                <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded text-center text-sm"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="mb-4 p-3 bg-red-50 text-red-700 rounded text-center text-sm"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" action="forgot-password.php" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" id="email" name="email" required autocomplete="email"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                    Send Reset Link
                </button>
            </form>
            <div class="mt-6 text-center">
                <a href="login.php" class="text-emerald-600 hover:underline text-sm">Back to Login</a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>