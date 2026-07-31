<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/User.php';
require_once __DIR__ . '/config/Mail.php';
require_once __DIR__ . '/vendor/autoload.php';

$email = $_GET['email'] ?? '';
$message = '';

if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $userObj = new User();
    $userData = $userObj->getUserByEmail($email);

    if ($userData && !$userData['is_verified']) {
        // Generate new token
        $token = bin2hex(random_bytes(32));
        $userObj->updateVerificationToken($userData['id'], $token);

        // Send email
        $verificationLink = BASE_URL . "/account/verify.php?token=" . $token;
        $mailer = new Mail();
        try {
            $mailer->sendWelcomeEmail($email, [
                'name' => $userData['name'],
                'verificationLink' => $verificationLink
            ]);
            $message = "A new verification link has been sent to your email.";
        } catch (Exception $e) {
            $message = "Failed to send verification email. Please try again later.";
        }
    } else {
        $message = "This email is already verified or does not exist.";
    }
} else {
    $message = "Invalid email address.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Resend Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-xl shadow-md max-w-md w-full text-center">
        <h1 class="text-2xl font-bold mb-4">Resend Verification</h1>
        <p class="text-gray-700"><?php echo htmlspecialchars($message); ?></p>
        <a href="login.php" class="mt-6 inline-block text-emerald-600 underline">Go to Login</a>
    </div>
</body>
</html>