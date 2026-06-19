<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = 'mail.crofund.org';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'desputes@crofund.org';
        $this->mail->Password = '-9$Gwp_r@35M{c_}';
        $this->mail->SMTPSecure = 'ssl';
        $this->mail->Port = 465;
        
        // Reduce debug level
        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $this->mail->Debugoutput = 'error_log';
        
        // Set default sender
        $this->mail->setFrom('desputes@crofund.org', SITE_NAME);
        
        // Other settings
        $this->mail->Timeout = 30;
        $this->mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
    }

    public function sendVerificationEmail($to, $verificationLink) {
        try {
            // Clear all recipients first
            $this->mail->clearAddresses();
            
            // Add recipient
            $this->mail->addAddress($to);

            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Verify Your Email Address';
            $this->mail->Body    = $this->getVerificationEmailTemplate($verificationLink);
            $this->mail->AltBody = "Please click the following link to verify your email address:\n\n" . $verificationLink;

            return $this->mail->send();
        } catch (Exception $e) {
            throw new Exception("Email could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
        }
    }

    private function getVerificationEmailTemplate($verificationLink) {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Verify Your Email Address</h2>
            <p>Thank you for registering. Please click the button below to verify your email address:</p>
            <p style="margin: 25px 0;">
                <a href="' . $verificationLink . '" 
                   style="background-color: #10B981; 
                          color: white; 
                          padding: 12px 30px; 
                          text-decoration: none; 
                          border-radius: 5px; 
                          display: inline-block;">
                    Verify Email Address
                </a>
            </p>
            <p style="color: #666; font-size: 14px;">
                If the button doesn\'t work, copy and paste this link in your browser:<br>
                ' . $verificationLink . '
            </p>
        </div>';
    }

    public function sendWelcomeEmail($to, $data) {
        try {
            // Clear all recipients first
            $this->mail->clearAddresses();
            
            // Add recipient
            $this->mail->addAddress($to);

            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Welcome to ' . SITE_NAME . ' - Verify Your Email';
            $this->mail->Body = $this->getWelcomeEmailTemplate($data);
            $this->mail->AltBody = "Welcome to " . SITE_NAME . "!\n\n" .
                                  "Thank you for registering. Please verify your email address by clicking the following link:\n" .
                                  $data['verificationLink'];

            return $this->mail->send();
        } catch (Exception $e) {
            throw new Exception("Email could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
        }
    }

    private function getWelcomeEmailTemplate($data) {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">Welcome to ' . SITE_NAME . '!</h2>
            <p>Hello ' . htmlspecialchars($data['name']) . ',</p>
            <p>Thank you for creating an account. To complete your registration and verify your email address, please click the button below:</p>
            
            <div style="margin: 25px 0; text-align: center;">
                <a href="' . $data['verificationLink'] . '" 
                   style="background-color: #10B981; 
                          color: white; 
                          padding: 12px 30px; 
                          text-decoration: none; 
                          border-radius: 5px; 
                          display: inline-block;">
                    Verify Email Address
                </a>
            </div>
            
            <p style="color: #666;">If the button doesn\'t work, copy and paste this link in your browser:</p>
            <p style="color: #666; font-size: 14px;">' . $data['verificationLink'] . '</p>
            
            <hr style="border: none; border-top: 1px solid #eee; margin: 25px 0;">
            
            <p style="color: #666; font-size: 14px;">
                If you didn\'t create an account, you can safely ignore this email.
            </p>
        </div>';
    }

    public function sendAdminRegistrationNotification($userData) {
        try {
            $this->mail->clearAddresses();
            
            // Admin email address - you can change this!
            $adminEmail = 'support@crofund.org';
            $this->mail->addAddress($adminEmail);

            $this->mail->isHTML(true);
            $this->mail->Subject = 'New User Registration on ' . SITE_NAME;
            $this->mail->Body = $this->getAdminNotificationEmailTemplate($userData);
            $this->mail->AltBody = $this->getAdminNotificationAltText($userData);

            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Failed to send admin notification email: " . $e->getMessage());
            throw new Exception("Admin email notification could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
        }
    }

    private function getAdminNotificationEmailTemplate($userData) {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #333;">New User Registration!</h2>
            <p>A new user has registered on ' . SITE_NAME . ':</p>
            
            <div style="background-color: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <p style="margin: 8px 0;"><strong>Full Name:</strong> ' . htmlspecialchars($userData['name']) . '</p>
                <p style="margin: 8px 0;"><strong>Email:</strong> ' . htmlspecialchars($userData['email']) . '</p>
                <p style="margin: 8px 0;"><strong>Phone:</strong> ' . htmlspecialchars($userData['phone']) . '</p>
                <p style="margin: 8px 0;"><strong>Country:</strong> ' . htmlspecialchars($userData['country']) . '</p>
                <p style="margin: 8px 0;"><strong>City:</strong> ' . htmlspecialchars($userData['city']) . '</p>
                <p style="margin: 8px 0;"><strong>National ID (NIN):</strong> ' . htmlspecialchars($userData['nin']) . '</p>
                <p style="margin: 8px 0;"><strong>Newsletter Opt-in:</strong> ' . ($userData['newsletter'] ? 'Yes' : 'No') . '</p>
            </div>
            
            <p style="color: #666; font-size: 14px;">
                Please review this new registration and approve/verify the user as needed.
            </p>
        </div>';
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
}