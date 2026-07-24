<?php
require_once __DIR__ . '/../vendor/autoload.php';

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

    public function sendVerificationEmail($to, $verificationLink) {
        try {
            return $this->sendEmail(
                $to,
                'Verify Your Email Address - ' . SITE_NAME,
                $this->getVerificationEmailTemplate($verificationLink),
                $this->getVerificationEmailPlainText($verificationLink)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('Verification email could not be sent', $e);
        }
    }

    private function getVerificationEmailTemplate($verificationLink) {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #ffffff;">
        <h2 style="color: #111827; margin-top: 0;">Verify Your Email Address</h2>
        <p>Thank you for registering with ' . SITE_NAME . '. Please click the button below to verify your email address:</p>
        <p style="margin: 30px 0; text-align: center;">
            <a href="' . htmlspecialchars($verificationLink) . '"
               style="background-color: #059669;
                      color: white;
                      padding: 14px 32px;
                      text-decoration: none;
                      border-radius: 8px;
                      display: inline-block;
                      font-weight: 600;
                      font-size: 16px;">
                Verify Email Address
            </a>
        </p>
        <p style="color: #4b5563; font-size: 14px;">If the button doesn\'t work, copy and paste this link in your browser:</p>
        <p style="color: #059669; word-break: break-all; font-size: 14px;">' . htmlspecialchars($verificationLink) . '</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="color: #6b7280; font-size: 13px;">If you didn\'t create an account with ' . SITE_NAME . ', you can safely ignore this email.</p>
    </div>
</body>
</html>';
    }

    private function getVerificationEmailPlainText($verificationLink) {
        return "Verify Your Email Address\n\n" .
               "Thank you for registering with " . SITE_NAME . ". Please click the link below to verify your email address:\n\n" .
               $verificationLink . "\n\n" .
               "If you didn't create an account with " . SITE_NAME . ", you can safely ignore this email.";
    }

    public function sendWelcomeEmail($to, $name) {
        try {
            $resolvedName = is_array($name) ? ($name['name'] ?? null) : $name;

            return $this->sendEmail(
                $to,
                'Welcome to ' . SITE_NAME . '!',
                $this->getWelcomeEmailTemplate($resolvedName),
                $this->getWelcomeEmailPlainText($resolvedName)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('Welcome email could not be sent', $e);
        }
    }

    private function getWelcomeEmailTemplate($name) {
        $greeting = $name ? "Hi $name," : "Hi,";

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ' . SITE_NAME . '</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #ffffff;">
        <h2 style="color: #111827; margin-top: 0;">Welcome to ' . SITE_NAME . '!</h2>
        <p>' . htmlspecialchars($greeting) . '</p>
        <p>Thank you for registering with ' . SITE_NAME . '! Your account has been created successfully.</p>
        <p>You can now log in to your account and start using our platform.</p>
        <p style="margin: 30px 0; text-align: center;">
            <a href="' . htmlspecialchars(BASE_URL) . '/login.php"
               style="background-color: #059669;
                      color: white;
                      padding: 14px 32px;
                      text-decoration: none;
                      border-radius: 8px;
                      display: inline-block;
                      font-weight: 600;
                      font-size: 16px;">
                Log In Now
            </a>
        </p>
        <p style="color: #4b5563; font-size: 14px;">If you have any questions, please don\'t hesitate to contact us.</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="color: #6b7280; font-size: 13px;">If you didn\'t create an account with ' . SITE_NAME . ', you can safely ignore this email.</p>
    </div>
</body>
</html>';
    }

    private function getWelcomeEmailPlainText($name) {
        $greeting = $name ? "Hi $name," : "Hi,";

        return "Welcome to " . SITE_NAME . "!\n\n" .
               $greeting . "\n\n" .
               "Thank you for registering with " . SITE_NAME . "! Your account has been created successfully.\n\n" .
               "You can now log in to your account and start using our platform.\n\n" .
               "Log in here: " . BASE_URL . "/login.php\n\n" .
               "If you have any questions, please don't hesitate to contact us.\n\n" .
               "If you didn't create an account with " . SITE_NAME . ", you can safely ignore this email.";
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
        <h2 style="color: #111827; margin-top: 0;">New User Registration</h2>
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
                'Your Two-Factor Verification Code - ' . SITE_NAME,
                $this->getTwoFactorEmailTemplate($code),
                $this->getTwoFactorEmailPlainText($code)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('2FA code email could not be sent', $e);
        }
    }

    private function getTwoFactorEmailTemplate($code) {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #ffffff;">
        <h2 style="color: #111827; margin-top: 0;">Two-Factor Verification Code</h2>
        <p>Here is your verification code to complete your login:</p>
        <div style="background-color: #f0fdf4; padding: 30px; border-radius: 8px; margin: 30px 0; text-align: center;">
            <span style="font-size: 36px; font-weight: 700; color: #059669; letter-spacing: 0.5em;">' . htmlspecialchars($code) . '</span>
        </div>
        <p style="color: #4b5563;">This code will expire in 5 minutes.</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="color: #6b7280; font-size: 13px;">If you didn\'t request this code, you can safely ignore this email.</p>
    </div>
</body>
</html>';
    }

    private function getTwoFactorEmailPlainText($code) {
        return "Two-Factor Verification Code\n\n" .
               "Here is your verification code to complete your login:\n\n" .
               "Verification Code: {$code}\n\n" .
               "This code will expire in 5 minutes.\n\n" .
               "If you didn't request this code, you can safely ignore this email.";
    }

    public function sendVerificationCode($to, $code, $name = null) {
        try {
            return $this->sendEmail(
                $to,
                'Verify Your Email - ' . SITE_NAME,
                $this->getVerificationCodeEmailTemplate($code, $name),
                $this->getVerificationCodeEmailPlainText($code, $name)
            );
        } catch (Throwable $e) {
            $this->throwUserSafeMailException('Verification code email could not be sent', $e);
        }
    }

    private function getVerificationCodeEmailTemplate($code, $name = null) {
        $greeting = $name ? "Hi $name," : "Hi,";

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #1f2937; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #ffffff;">
        <h2 style="color: #111827; margin-top: 0;">Verify Your Email Address</h2>
        <p>' . htmlspecialchars($greeting) . '</p>
        <p>Thank you for signing up for ' . SITE_NAME . '! Please use the verification code below to verify your email address.</p>
        <div style="background-color: #ecfdf5; padding: 30px; border-radius: 8px; margin: 30px 0; text-align: center;">
            <span style="font-size: 36px; font-weight: 700; color: #059669; letter-spacing: 0.5em;">' . htmlspecialchars($code) . '</span>
        </div>
        <p style="color: #4b5563;">This code will expire in 1 hour.</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <p style="color: #6b7280; font-size: 13px;">If you didn\'t create an account with ' . SITE_NAME . ', you can safely ignore this email.</p>
    </div>
</body>
</html>';
    }

    private function getVerificationCodeEmailPlainText($code, $name = null) {
        $greeting = $name ? "Hi $name," : "Hi,";

        return "Verify Your Email Address\n\n" .
               $greeting . "\n\n" .
               "Thank you for signing up for " . SITE_NAME . "! Please use the verification code below to verify your email address.\n\n" .
               "Verification Code: $code\n\n" .
               "This code will expire in 1 hour.\n\n" .
               "If you didn't create an account with " . SITE_NAME . ", you can safely ignore this email.";
    }
}
