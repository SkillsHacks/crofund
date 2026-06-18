<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Withdrawal.php';
require_once __DIR__ . '/../config/User.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $method = $_POST['withdrawal_method'] ?? '';
    $amount = floatval($_POST['amount']);

    // Validate amount
    if ($amount < MIN_WITHDRAWAL_AMOUNT) {
        $_SESSION['error_message'] = "Minimum withdrawal amount is $" . number_format(MIN_WITHDRAWAL_AMOUNT, 2);
        header("Location: initiate.php");
        exit();
    }

    if ($amount > MAX_WITHDRAWAL_AMOUNT) {
        $_SESSION['error_message'] = "Maximum withdrawal amount is $" . number_format(MAX_WITHDRAWAL_AMOUNT, 2);
        header("Location: initiate.php");
        exit();
    }

    // Initialize and sanitize withdrawal details
    $details = [];

    if ($method === 'bank') {
        $details['account_name']   = htmlspecialchars(trim($_POST['account_name'] ?? ''));
        $details['bank_name']      = htmlspecialchars(trim($_POST['bank_name'] ?? ''));
        $details['account_number'] = htmlspecialchars(trim($_POST['account_number'] ?? ''));
    } elseif ($method === 'mobile') {
        $details['full_name']       = htmlspecialchars(trim($_POST['full_name'] ?? ''));
        $details['country']         = htmlspecialchars(trim($_POST['country'] ?? ''));
        $details['mobile_provider'] = htmlspecialchars(trim($_POST['mobile_provider'] ?? ''));
        $details['phone_number']    = htmlspecialchars(trim($_POST['phone_number'] ?? ''));
    } elseif ($method === 'paypal') {
        $details['paypal_email'] = htmlspecialchars(trim($_POST['paypal_email'] ?? ''));
    }

    // Filter out empty fields
    $details = array_filter($details, fn($v) => !empty($v));

    // Create withdrawal entry
    $withdrawal = new Withdrawal();
    $result = $withdrawal->createWithdrawal($user_id, $method, $amount, $details);

    if ($result) {
        // Friendly method name for email
        switch ($method) {
            case 'bank':   $methodLabel = 'Bank Transfer'; break;
            case 'mobile': $methodLabel = 'Mobile Money'; break;
            case 'paypal': $methodLabel = 'PayPal'; break;
            default:       $methodLabel = ucfirst($method); break;
        }

        // Optional method-specific disclaimer
        $disclaimer = '';
        if ($method === 'paypal') {
            $disclaimer = '<div style="background-color:#fff3cd;padding:15px;border-left:4px solid #ffc107;margin:20px 0;">
                <strong>Note:</strong> PayPal withdrawals may take longer to process due to additional verification. Ensure your email is correct.
            </div>';
        } elseif ($method === 'bank') {
            $disclaimer = '<div style="background-color:#fff3cd;padding:15px;border-left:4px solid #ffc107;margin:20px 0;">
                <strong>Note:</strong> Ensure your bank details are correct. Transfers may take 1–3 business days depending on your bank.
            </div>';
        }

        // Fetch user email
        $userObj = new User();
        $user = $userObj->getUserById($user_id);

        if ($user && !empty($user['email'])) {
            $to = $user['email'];
            $subject = "Withdrawal Request Received";

            // Load email template and replace placeholders
            $templatePath = __DIR__ . '/withdrawal_initiated.html';
            $template = file_get_contents($templatePath);
            $message = str_replace(
                ['{name}', '{amount}', '{company_name}', '{current_year}', '{method}', '{disclaimer}'],
                [
                    htmlspecialchars($user['name'] ?? 'User'),
                    number_format($amount, 2),
                    'CroFunding',
                    date('Y'),
                    $methodLabel,
                    $disclaimer
                ],
                $template
            );

            // Send email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'mail.crofund.org';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'oppeminc@crofund.org';
                $mail->Password   = 'Ihateu123#@!';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('oppeminc@crofund.org', 'CroFunding');
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $message;

                $mail->send();
            } catch (Exception $e) {
                // Optional: Log or notify admin
            }
        }

        // Redirect to form with success message
        header("Location: initiate.php?success=1");
        exit();
    } else {
        echo "Failed to create withdrawal. Please try again.";
    }
}
?>
