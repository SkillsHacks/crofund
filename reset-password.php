<?php
session_start();
require_once __DIR__ . '/config/config.php';

$success = '';
$error = '';
$showForm = true;

// Validate token from URL
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
        // Find the reset request
        global $pdo; // Use the PDO object from config.php
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($reset && strtotime($reset['expires_at']) > time()) {
            // Update user password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $reset['user_id']]);

            // Delete the reset token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            $success = "Your password has been reset successfully. <a href='login.php' class='text-emerald-600 underline'>Login</a>";
            $showForm = false;
        } else {
            $error = "This reset link is invalid or has expired.";
            $showForm = false;
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
</head>
<body class="bg-gray-100 font-sans">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-2xl p-8 sm:p-10 max-w-md w-full border border-gray-100">
            <div class="mb-6 text-center">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-emerald-100 mb-3">
                    <svg class="h-7 w-7 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m0-6v2m-6 4v-4a9 9 0 1118 0v4"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Reset Password</h1>
                <p class="mt-2 text-gray-500 text-sm">Enter your new password below.</p>
            </div>
            <?php if ($success): ?>
                <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded text-center text-sm"><?php echo $success; ?></div>
            <?php elseif ($error): ?>
                <div class="mb-4 p-3 bg-red-50 text-red-700 rounded text-center text-sm"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($showForm): ?>
            <form method="post" action="reset-password.php" class="space-y-6">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <input type="password" id="password" name="password" required minlength="6"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
                <div>
                    <label for="confirm" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="confirm" name="confirm" required minlength="6"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                </div>
                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                    Reset Password
                </button>
            </form>
            <?php endif; ?>
            <div class="mt-6 text-center">
                <a href="login.php" class="text-emerald-600 hover:underline text-sm">Back to Login</a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>