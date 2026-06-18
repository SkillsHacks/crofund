<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$name = ''; // You can collect a name if you want, or leave blank

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

try {
    // Check if already subscribed
    $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'You are already subscribed!']);
        exit;
    }

    // Insert new subscriber
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $stmt->execute([$email]);

    // Prepare the email template
    $company_name = 'CroFunding';
    $current_year = date('Y');
    $disclaimer = '<p style="font-size:12px;color:#888;">You are receiving this email because you subscribed to our newsletter. If you did not subscribe, please ignore this email or contact us.</p>';

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to $company_name Newsletter</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; color: #333; }
        .banner { background-color: #059669; text-align: center; padding: 30px; color: white; }
        .banner h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .content { max-width: 600px; margin: 0 auto; padding: 30px 20px; }
        .footer { background-color: #f3f4f6; text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="banner">
        <h1>$company_name</h1>
    </div>
    <div class="content">
        <p>Dear Subscriber,</p>
        <p>Thank you for subscribing to the <strong>$company_name</strong> newsletter!</p>
        <div style="background-color: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin: 20px 0;">
            <p style="margin: 0; color: #1b5e20; font-weight: 500;">
                What to expect:
                <ul>
                    <li>Latest updates on campaigns and projects</li>
                    <li>Exclusive stories and success highlights</li>
                    <li>Early access to new features and events</li>
                    <li>Special opportunities to get involved</li>
                </ul>
            </p>
        </div>
        <p>We’re excited to have you with us. If you have any questions, feel free to contact our support team at <a href="mailto:support@crofund.org">support@crofund.org</a>.</p>
        <p>Thank you for joining us!<br>
        Sincerely,<br>
        The $company_name Team</p>
    </div>
    $disclaimer
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
        <p>&copy; $current_year $company_name. All rights reserved.</p>
    </div>
</body>
</html>
HTML;

    // Send the welcome email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_SECURE;
    $mail->Port = MAIL_PORT;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = "Welcome to $company_name Newsletter!";
    $mail->Body = $html;

    try {
        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        echo json_encode(['success' => false, 'error' => 'Could not send welcome email.']);
    }
} catch (Exception $e) {
    error_log('Newsletter subscribe error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Subscription failed. Please try again.']);
}