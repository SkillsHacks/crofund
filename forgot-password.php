<?php
session_start();
require_once __DIR__ . '/config/config.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php'; // Adjust path if needed

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Use the PDO object from config.php
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
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
            $company_name = "CroFunding";
            $current_year = date('Y');
            $disclaimer = '<p style="font-size:12px;color:#888;margin-top:20px;">If you did not request a password reset, please ignore this email or contact us.</p>';

            $emailBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; color: #333; }
        .banner { background-color: #059669; text-align: center; padding: 30px; color: white; }
        .banner h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .content { max-width: 600px; margin: 0 auto; padding: 30px 20px; }
        .btn {
            display: inline-block;
            background-color: #059669;
            color: #fff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer { background-color: #f3f4f6; text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="banner">
        <h1>{$company_name}</h1>
    </div>
    <div class="content">
        <p>Dear User,</p>
        <p>We received a request to reset your password for your {$company_name} account.</p>
        <p>To reset your password, please click the button below or copy and paste the link into your browser:</p>
        <p style="text-align:center;">
            <a href="{$resetLink}" class="btn">Reset Password</a>
        </p>
        <p style="word-break:break-all;">{$resetLink}</p>
        <div style="background-color: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin: 20px 0;">
            <p style="margin: 0; color: #1b5e20; font-weight: 500;">
                This link will expire in 1 hour for your security.
            </p>
        </div>
        <p>If you did not request a password reset, you can safely ignore this email.</p>
        <p>Thank you,<br>The {$company_name} Team</p>
    </div>
    <div class="footer">
        <div style="margin-bottom: 20px;">
            <a href="https://facebook.com/your-page" style="text-decoration: none; margin: 0 10px;">
                <img src="https://cdn-icons-png.flaticon.com/32/174/174848.png" alt="Facebook" width="32" height="32">
            </a>
            <a href="https://twitter.com/your-handle" style="text-decoration: none; margin: 0 10px;">
                <img src="https://cdn-icons-png.flaticon.com/32/733/733579.png" alt="Twitter" width="32" height="32">
            </a>
            <a href="https://instagram.com/your-profile" style="text-decoration: none; margin: 0 10px;">
                <img src="https://cdn-icons-png.flaticon.com/32/174/174855.png" alt="Instagram" width="32" height="32">
            </a>
            <a href="https://linkedin.com/company/your-company" style="text-decoration: none; margin: 0 10px;">
                <img src="https://cdn-icons-png.flaticon.com/32/174/174857.png" alt="LinkedIn" width="32" height="32">
            </a>
        </div>
        <p>This is an automated message. Please do not reply directly to this email.</p>
        <p>&copy; <?php echo date('Y'); ?> <?php echo $company_name; ?>. All rights reserved.</p>
    </div>
</body>
</html>
HTML;

            // Send email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'mail.crofund.org'; // Set your SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'no-reply@crofund.org';   // SMTP username
                $mail->Password   = 'Ihateu123#@!00!!';     // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                //Recipients
                $mail->setFrom('no-reply@crofund.org', 'CroFunding');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = "Password Reset Request";
                $mail->Body    = $emailBody;

                $mail->send();
            } catch (Exception $e) {
                // Optionally log $mail->ErrorInfo
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