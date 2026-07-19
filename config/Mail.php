<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail {
    private $mail;
    private $maxRetries = 3;
    private $retryDelay = 2; // seconds between retries

    private function throwUserSafeMailException(string $contextMessage, Exception $e): void {
        $technicalDetails = trim($this->mail->ErrorInfo ?: $e->getMessage());
        error_log($contextMessage . ' | ' . $technicalDetails);
        throw new Exception('We could not send the email right now. Please try again in a few minutes.');
    }

    public function __construct() {
        $this->mail = new PHPMailer(true);

        // Server settings (from config.php)
        $this->mail->isSMTP();
        $this->mail->Host = MAIL_HOST;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = MAIL_USERNAME;
        $this->mail->Password = MAIL_PASSWORD;
        
        // Set encryption type
        if (MAIL_SECURE === 'ssl') {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (MAIL_SECURE === 'tls') {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $this->mail->Port = MAIL_PORT;
        
        // Disable debug logging in production
        $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
        
        // Set default sender - use same as SMTP username to avoid sender verify failures
        $this->mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $this->mail->addReplyTo(MAIL_REPLY_TO, MAIL_REPLY_TO_NAME);
        
        // Enable SSL certificate verification (security best practice)
        $this->mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            )
        );
        
        // Set other deliverability settings
        $this->mail->XMailer = SITE_NAME . ' Mailer';
        $this->mail->CharSet = 'UTF-8';
    }

    /**
     * Send an email with retries for temporary failures
     */
    private function sendWithRetry() {
        $attempt = 0;
        while ($attempt < $this->maxRetries) {
            try {
                $this->mail->send();
                error_log("Email sent successfully to: " . implode(', ', array_keys($this->mail->getAllRecipientAddresses())));
                return true;
            } catch (Exception $e) {
                $attempt++;
                $errorMsg = "Email send attempt {$attempt} failed: " . $this->mail->ErrorInfo;
                error_log($errorMsg);
                
                // Check if it's a temporary failure (we can retry)
                $isTemporary = $this->isTemporaryFailure($this->mail->ErrorInfo);
                
                if ($attempt >= $this->maxRetries || !$isTemporary) {
                    throw new Exception($errorMsg);
                }
                
                // Wait before retrying
                sleep($this->retryDelay * $attempt); // Exponential backoff
            }
        }
        return false;
    }

    /**
     * Determine if an error is temporary and worth retrying
     */
    private function isTemporaryFailure($error) {
        $temporaryKeywords = [
            'temporarily unavailable',
            'try again later',
            'connection timeout',
            'busy',
            '421',
            '450',
            '451',
            '452'
        ];
        
        foreach ($temporaryKeywords as $keyword) {
            if (stripos($error, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate a unique Message-ID
     */
    private function generateMessageId() {
        $domain = parse_url(BASE_URL, PHP_URL_HOST) ?: 'crofund.org';
        return sprintf('<%s.%s@%s>',
            uniqid('', true),
            bin2hex(random_bytes(8)),
            $domain
        );
    }

    /**
     * Send email verification link
     */
    public function sendVerificationEmail($to, $verificationLink) {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();
            
            $this->mail->addAddress($to);
            $this->mail->addReplyTo('no-reply@crofund.org', SITE_NAME);

            $this->mail->Subject = 'Verify Your Email Address - ' . SITE_NAME;
            $this->mail->Body = $this->getVerificationEmailTemplate($verificationLink);
            $this->mail->AltBody = $this->getVerificationEmailPlainText($verificationLink);
            $this->mail->MessageID = $this->generateMessageId();

            return $this->sendWithRetry();
        } catch (Exception $e) {
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
        
        <p style="color: #4b5563; font-size: 14px;">
            If the button doesn\'t work, copy and paste this link in your browser:
        </p>
        <p style="color: #059669; word-break: break-all; font-size: 14px;">
            ' . htmlspecialchars($verificationLink) . '
        </p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <p style="color: #6b7280; font-size: 13px;">
            If you didn\'t create an account with ' . SITE_NAME . ', you can safely ignore this email.
        </p>
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

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($to, $name) {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();
            
            $this->mail->addAddress($to);
            $this->mail->addReplyTo('no-reply@crofund.org', SITE_NAME);

            $this->mail->Subject = 'Welcome to ' . SITE_NAME . '!';
            $this->mail->Body = $this->getWelcomeEmailTemplate($name);
            $this->mail->AltBody = $this->getWelcomeEmailPlainText($name);
            $this->mail->MessageID = $this->generateMessageId();

            return $this->sendWithRetry();
        } catch (Exception $e) {
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
        
        <p style="color: #4b5563; font-size: 14px;">
            If you have any questions, please don\'t hesitate to contact us.
        </p>
        
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <p style="color: #6b7280; font-size: 13px;">
            If you didn\'t create an account with ' . SITE_NAME . ', you can safely ignore this email.
        </p>
    </div>
</body>
</html>';
    }
    
    private function getWelcomeEmailPlainText($name) {
        $greeting = $name ? "Hi $name," : "Hi,";
        
        return "Welcome to " . SITE_NAME . "!\n\n" .
               htmlspecialchars($greeting) . "\n\n" .
               "Thank you for registering with " . SITE_NAME . "! Your account has been created successfully.\n\n" .
               "You can now log in to your account and start using our platform.\n\n" .
               "Log in here: " . BASE_URL . "/login.php\n\n" .
               "If you have any questions, please don't hesitate to contact us.\n\n" .
               "If you didn't create an account with " . SITE_NAME . ", you can safely ignore this email.";
    }

    /**
     * Send admin registration notification
     */
    public function sendAdminRegistrationNotification($userData) {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();
            
            $adminEmail = 'support@crofund.org';
            $this->mail->addAddress($adminEmail);
            $this->mail->addReplyTo('no-reply@crofund.org', SITE_NAME);

            $this->mail->Subject = 'New User Registration - ' . SITE_NAME;
            $this->mail->Body = $this->getAdminNotificationEmailTemplate($userData);
            $this->mail->AltBody = $this->getAdminNotificationAltText($userData);
            $this->mail->MessageID = $this->generateMessageId();

            return $this->sendWithRetry();
        } catch (Exception $e) {
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

    /**
     * Send 2FA verification code
     */
    public function sendTwoFactorCode($to, $code) {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();
            
            $this->mail->addAddress($to);
            $this->mail->addReplyTo('no-reply@crofund.org', SITE_NAME);

            $this->mail->Subject = 'Your Two-Factor Verification Code - ' . SITE_NAME;
            $this->mail->Body = $this->getTwoFactorEmailTemplate($code);
            $this->mail->AltBody = $this->getTwoFactorEmailPlainText($code);
            $this->mail->MessageID = $this->generateMessageId();

            return $this->sendWithRetry();
        } catch (Exception $e) {
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
        
        <p style="color: #6b7280; font-size: 13px;">
            If you didn\'t request this code, you can safely ignore this email.
        </p>
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
            $this->mail->clearAddresses();
            $this->mail->clearReplyTos();
            
            $this->mail->addAddress($to);
            $this->mail->addReplyTo('no-reply@crofund.org', SITE_NAME);

            $this->mail->Subject = "Verify Your Email - " . SITE_NAME;
            $this->mail->Body = $this->getVerificationCodeEmailTemplate($code, $name);
            $this->mail->AltBody = $this->getVerificationCodeEmailPlainText($code, $name);
            $this->mail->MessageID = $this->generateMessageId();

            return $this->sendWithRetry();
        } catch (Exception $e) {
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
        
        <p style="color: #6b7280; font-size: 13px;">
            If you didn\'t create an account with ' . SITE_NAME . ', you can safely ignore this email.
        </p>
    </div>
</body>
</html>';
    }

    private function getVerificationCodeEmailPlainText($code, $name = null) {
        $greeting = $name ? "Hi $name," : "Hi,";
        
        return "Verify Your Email Address\n\n" .
               htmlspecialchars($greeting) . "\n\n" .
               "Thank you for signing up for " . SITE_NAME . "! Please use the verification code below to verify your email address.\n\n" .
               "Verification Code: $code\n\n" .
               "This code will expire in 1 hour.\n\n" .
               "If you didn't create an account with " . SITE_NAME . ", you can safely ignore this email.";
    }
}
