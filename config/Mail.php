<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Resend\Resend;

class Mail {
    private $resend;

    public function __construct() {
        if (!defined('RESEND_API_KEY') || trim((string) RESEND_API_KEY) === '') {
            throw new RuntimeException('Resend is not configured. Please set the Resend API key.');
        }

        $this->resend = Resend::client(RESEND_API_KEY);
    }

    private function throwUserSafeMailException(string $contextMessage, Throwable $e): void {
        error_log($contextMessage . ' | ' . trim($e->getMessage()));
        throw new RuntimeException('We could not send the email right now. Please try again in a few minutes.');
    }

    private function sendEmail(string $to, string $subject, string $html, string $text): bool {
        try {
            $payload = [
                'from' => sprintf('%s <%s>', MAIL_FROM_NAME, MAIL_FROM),
                'to' => [$to],
                'subject' => $subject,
                'html' => $html,
                'text' => $text,
            ];

            if (defined('MAIL_REPLY_TO') && trim((string) MAIL_REPLY_TO) !== '') {
                $payload['reply_to'] = [sprintf('%s <%s>', MAIL_REPLY_TO_NAME, MAIL_REPLY_TO)];
            }

            $this->resend->emails->send($payload);
            error_log('Email sent successfully to: ' . $to);
            return true;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    private function getEmailLogoUrl(): string {
        return rtrim(BASE_URL, '/') . '/assets/images/logo.svg';
    }

    private function getEmailBrandHeader(string $title): string {
        return '<div style="text-align:center; margin-bottom:24px;">'
            . '<img src="' . htmlspecialchars($this->getEmailLogoUrl(), ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') . ' logo" style="width:64px;height:64px;display:block;margin:0 auto 14px auto;">'
            . '<h2 style="color:#111827; margin:0; font-size:24px; font-weight:700;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '</div>';
    }

    public function sendVerificationEmail($to, $verificationLink, $name = null) {
        try {
            return $this->sendEmail(
                $to,
                'Verify your CroFund email address',
                $this->getVerificationEmailTemplate($verificationLink, $name),
                $this->getVerificationEmailPlainText($verificationLink, $name)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('Verification email could not be sent', $e);
        }
    }

    private function getVerificationEmailTemplate($verificationLink, $name = null) {
        $greeting = $name ? 'Hi ' . htmlspecialchars($name) . ',' : 'Hi,';

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your CroFund email address</title>
</head>
<body style="margin:0;padding:24px;background-color:#ffffff;color:#111827;font-family:Arial,sans-serif;line-height:1.6;">
    <div style="max-width:560px;margin:0 auto;">
        ' . $this->getEmailBrandHeader('Verify Your Email Address') . '
        <p style="margin:0 0 16px 0;">' . $greeting . '</p>
        <p style="margin:0 0 16px 0;">Use the link below to verify your CroFund email address:</p>
        <p style="margin:0 0 16px 0;word-break:break-word;">
            <a href="' . htmlspecialchars($verificationLink) . '" style="color:#111827;text-decoration:underline;">' . htmlspecialchars($verificationLink) . '</a>
        </p>
        <p style="margin:0 0 16px 0;">This link will expire in 1 hour.</p>
        <p style="margin:0 0 16px 0;">If you did not request this email, you can safely ignore it.</p>
        <p style="margin:0;">CroFund Security Team</p>
    </div>
</body>
</html>';
    }

    private function getVerificationEmailPlainText($verificationLink, $name = null) {
        $greeting = $name ? "Hi $name," : "Hi,";

        return $greeting . "\n\n" .
               "Use the link below to verify your CroFund email address:\n\n" .
               $verificationLink . "\n\n" .
               "This link will expire in 1 hour.\n\n" .
               "If you did not request this email, you can safely ignore it.\n\n" .
               "CroFund Security Team";
    }

    public function sendWelcomeEmail($to, $name) {
        try {
            $resolvedName = is_array($name) ? ($name['name'] ?? null) : $name;

            return $this->sendEmail(
                $to,
                'Your CroFund account is ready',
                $this->getWelcomeEmailTemplate($resolvedName),
                $this->getWelcomeEmailPlainText($resolvedName)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('Welcome email could not be sent', $e);
        }
    }

    private function getWelcomeEmailTemplate($name) {
        $greeting = $name ? 'Hi ' . htmlspecialchars($name) . ',' : 'Hi,';

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your CroFund account is ready</title>
</head>
<body style="margin:0;padding:24px;background-color:#ffffff;color:#111827;font-family:Arial,sans-serif;line-height:1.6;">
    <div style="max-width:560px;margin:0 auto;">
        ' . $this->getEmailBrandHeader('Welcome to ' . SITE_NAME . '!') . '
        <p style="margin:0 0 16px 0;">' . $greeting . '</p>
        <p style="margin:0 0 16px 0;">Your CroFund account has been created successfully.</p>
        <p style="margin:0 0 16px 0;">You can now sign in and continue using your account.</p>
        <p style="margin:0;">CroFund Security Team</p>
    </div>
</body>
</html>';
    }

    private function getWelcomeEmailPlainText($name) {
        $greeting = $name ? "Hi $name," : "Hi,";

        return $greeting . "\n\n" .
               "Your CroFund account has been created successfully.\n\n" .
               "You can now sign in and continue using your account.\n\n" .
               "CroFund Security Team";
    }

    public function sendAdminRegistrationNotification($userData) {
        try {
            $adminEmail = 'support@crofund.org';

            return $this->sendEmail(
                $adminEmail,
                'New User Registration - ' . SITE_NAME,
                $this->getAdminNotificationEmailTemplate($userData),
                $this->getAdminNotificationAltText($userData)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('Admin registration notification could not be sent', $e);
        }
    }

    private function getAdminNotificationEmailTemplate($userData) {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New User Registration</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #ffffff;">
        ' . $this->getEmailBrandHeader('New User Registration') . '
        <p>A new user has registered on ' . SITE_NAME . ':</p>
        <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 8px 0;"><strong>Full Name:</strong> ' . htmlspecialchars($userData['name']) . '</p>
            <p style="margin: 8px 0;"><strong>Email:</strong> ' . htmlspecialchars($userData['email']) . '</p>
            <p style="margin: 8px 0;"><strong>Phone:</strong> ' . htmlspecialchars($userData['phone']) . '</p>
            <p style="margin: 8px 0;"><strong>Country:</strong> ' . htmlspecialchars($userData['country']) . '</p>
            <p style="margin: 8px 0;"><strong>City:</strong> ' . htmlspecialchars($userData['city']) . '</p>
            <p style="margin: 8px 0;"><strong>National ID (NIN):</strong> ' . htmlspecialchars($userData['nin']) . '</p>
            <p style="margin: 8px 0;"><strong>Newsletter Opt-in:</strong> ' . ($userData['newsletter'] ? 'Yes' : 'No') . '</p>
        </div>
    </div>
</body>
</html>';
    }

    private function getAdminNotificationAltText($userData) {
        return "New User Registration on " . SITE_NAME . "\n\n" .
               "A new user has registered:\n\n" .
               "Full Name: " . $userData['name'] . "\n" .
               "Email: " . $userData['email'] . "\n" .
               "Phone: " . $userData['phone'] . "\n" .
               "Country: " . $userData['country'] . "\n" .
               "City: " . $userData['city'] . "\n" .
               "National ID (NIN): " . $userData['nin'] . "\n" .
               "Newsletter Opt-in: " . ($userData['newsletter'] ? 'Yes' : 'No');
    }

    public function sendTwoFactorCode($to, $code) {
        try {
            return $this->sendEmail(
                $to,
                'Your CroFund verification code',
                $this->getTwoFactorEmailTemplate($code),
                $this->getTwoFactorEmailPlainText($code)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('2FA code email could not be sent', $e);
        }
    }

    private function getTwoFactorEmailTemplate($code) {
        return $this->getCodeEmailHtml(null, $code, '5 minutes');
    }

    private function getTwoFactorEmailPlainText($code) {
        return $this->getCodeEmailText(null, $code, '5 minutes');
    }

    public function sendVerificationCode($to, $code, $name = null) {
        try {
            return $this->sendEmail(
                $to,
                'Your CroFund verification code',
                $this->getVerificationCodeEmailTemplate($code, $name),
                $this->getVerificationCodeEmailPlainText($code, $name)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('Verification code email could not be sent', $e);
        }
    }

    private function getVerificationCodeEmailTemplate($code, $name = null) {
        return $this->getCodeEmailHtml($name, $code, '1 hour');
    }

    private function getVerificationCodeEmailPlainText($code, $name = null) {
        return $this->getCodeEmailText($name, $code, '1 hour');
    }

    public function sendPasswordResetEmail($to, $resetLink, $name = null) {
        try {
            return $this->sendEmail(
                $to,
                'Your CroFund password reset link',
                $this->getPasswordResetEmailTemplate($resetLink, $name),
                $this->getPasswordResetEmailPlainText($resetLink, $name)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('Password reset email could not be sent', $e);
        }
    }

    private function getPasswordResetEmailTemplate($resetLink, $name = null) {
        $greeting = $name ? 'Hi ' . htmlspecialchars($name) . ',' : 'Hi,';

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your CroFund password reset link</title>
</head>
<body style="margin:0;padding:24px;background-color:#ffffff;color:#111827;font-family:Arial,sans-serif;line-height:1.6;">
    <div style="max-width:560px;margin:0 auto;">
        ' . $this->getEmailBrandHeader('Reset Your Password') . '
        <p style="margin:0 0 16px 0;">' . $greeting . '</p>
        <p style="margin:0 0 16px 0;">We received a request to reset your CroFund password.</p>
        <p style="margin:0 0 16px 0;word-break:break-word;">
            <a href="' . htmlspecialchars($resetLink) . '" style="color:#111827;text-decoration:underline;">' . htmlspecialchars($resetLink) . '</a>
        </p>
        <p style="margin:0 0 16px 0;">This link will expire in 1 hour.</p>
        <p style="margin:0 0 16px 0;">If you did not request a password reset, you can safely ignore this email.</p>
        <p style="margin:0;">CroFund Security Team</p>
    </div>
</body>
</html>';
    }

    private function getPasswordResetEmailPlainText($resetLink, $name = null) {
        $greeting = $name ? "Hi $name," : "Hi,";

        return $greeting . "\n\n" .
               "We received a request to reset your CroFund password.\n\n" .
               $resetLink . "\n\n" .
               "This link will expire in 1 hour.\n\n" .
               "If you did not request a password reset, you can safely ignore this email.\n\n" .
               "CroFund Security Team";
    }

    private function getCodeEmailHtml($name, $code, $expiresIn) {
        $greeting = $name ? 'Hi ' . htmlspecialchars($name) . ',' : 'Hi,';

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your CroFund verification code</title>
</head>
<body style="margin:0;padding:24px;background-color:#ffffff;color:#111827;font-family:Arial,sans-serif;line-height:1.6;">
    <div style="max-width:560px;margin:0 auto;">
        ' . $this->getEmailBrandHeader('Verification Code') . '
        <p style="margin:0 0 16px 0;">' . $greeting . '</p>
        <p style="margin:0 0 16px 0;">Your CroFund verification code is:</p>
        <p style="margin:0 0 16px 0;font-size:28px;font-weight:700;letter-spacing:0.2em;">' . htmlspecialchars($code) . '</p>
        <p style="margin:0 0 16px 0;">This code will expire in ' . htmlspecialchars($expiresIn) . '.</p>
        <p style="margin:0 0 16px 0;">If you did not request this code, you can safely ignore this email.</p>
        <p style="margin:0;">CroFund Security Team</p>
    </div>
</body>
</html>';
    }

    private function getCodeEmailText($name, $code, $expiresIn) {
        $greeting = $name ? "Hi $name," : "Hi,";

        return $greeting . "\n\n" .
               "Your CroFund verification code is:\n\n" .
               $code . "\n\n" .
               "This code will expire in $expiresIn.\n\n" .
               "If you did not request this code, you can safely ignore this email.\n\n" .
               "CroFund Security Team";
    }
}
