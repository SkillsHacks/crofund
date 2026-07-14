<?php
require_once __DIR__ . '/config/config.php';
include __DIR__ . '/components/public-header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/vendor/autoload.php';

// Handle form submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Send email using PHPMailer
        $to = 'desputes@crofund.org';
        $subject = "Contact Form Submission from $name";
        $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'mail.crofund.org';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'desputes@crofund.org';
            $mail->Password   = 'Ihateu123#@!';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('desputes@crofund.org', 'CroFunding');
            $mail->addAddress($to);
            $mail->addReplyTo($email, $name);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            $success = "Thank you for contacting us! We have received your message and will get back to you soon.";
        } catch (Exception $e) {
            $error = "Sorry, there was a problem sending your message. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Us – CroFunding</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
</head>
<body>
<main class="max-w-2xl mx-auto py-20 px-4">
    <h1 class="text-3xl font-bold mb-6 text-emerald-700">Contact Us</h1>
    <p class="mb-8 text-gray-700 text-lg">
        We’re here to help you succeed. Whether you have a question about your campaign, need support, or want to learn more about CroFunding, our team is ready to assist you.
    </p>
    <div class="bg-white rounded-lg shadow p-8 mb-10">
        <h2 class="text-xl font-semibold text-emerald-700 mb-2">Our Office</h2>
        <p class="text-gray-700 mb-2">
            CroFunding USA Headquarters<br>
            456 Hope Street, Suite 1200<br>
            San Francisco, CA 94105, United States
        </p>
        <p class="text-gray-700 mb-2">
            <span class="font-semibold">Phone:</span> <a href="tel:+14155552600" class="text-emerald-600 underline">+1 (415) 555-2600</a>
        </p>
        <p class="text-gray-700 mb-2">
            <span class="font-semibold">Email:</span> <a href="mailto:contact@crofund.org" class="text-emerald-600 underline">contact@crofund.org</a>
        </p>
        <div class="flex space-x-4 mt-4">
            <a href="https://facebook.com/your-page" target="_blank" title="Facebook">
                <img src="https://cdn-icons-png.flaticon.com/32/174/174848.png" alt="Facebook" width="32" height="32">
            </a>
            <a href="https://twitter.com/your-handle" target="_blank" title="Twitter">
                <img src="https://cdn-icons-png.flaticon.com/32/733/733579.png" alt="Twitter" width="32" height="32">
            </a>
            <a href="https://instagram.com/your-profile" target="_blank" title="Instagram">
                <img src="https://cdn-icons-png.flaticon.com/32/174/174855.png" alt="Instagram" width="32" height="32">
            </a>
            <a href="https://www.tiktok.com/@your-profile" target="_blank" title="TikTok">
                <img src="https://cdn-icons-png.flaticon.com/32/3046/3046122.png" alt="TikTok" width="32" height="32">
            </a>
        </div>
    </div>
    <div class="bg-emerald-50 rounded-lg shadow p-8 mb-10">
        <h2 class="text-xl font-semibold text-emerald-700 mb-2">About CroFunding</h2>
        <p class="text-gray-700 mb-2">
            CroFunding is a leading online fundraising platform dedicated to empowering individuals, families, and communities to achieve their goals. Since our founding, we’ve helped thousands of people raise funds for causes they care about—quickly, securely, and transparently.
        </p>
        <ul class="list-disc pl-6 text-gray-700 mb-2">
            <li>Trusted by thousands of fundraisers and donors</li>
            <li>Secure payment processing and data protection</li>
            <li>Dedicated support team</li>
            <li>Fast and easy withdrawals</li>
        </ul>
    </div>
    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-emerald-100 text-emerald-800 rounded"><?php echo $success; ?></div>
    <?php elseif ($error): ?>
        <div class="mb-6 p-4 bg-red-100 text-red-800 rounded"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-6 bg-white p-6 rounded shadow">
        <h2 class="text-xl font-semibold text-emerald-700 mb-4">Send Us a Message</h2>
        <div>
            <label class="block text-gray-700 mb-1" for="name">Name</label>
            <input class="w-full border rounded px-3 py-2" type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>
        <div>
            <label class="block text-gray-700 mb-1" for="email">Email</label>
            <input class="w-full border rounded px-3 py-2" type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div>
            <label class="block text-gray-700 mb-1" for="message">Message</label>
            <textarea class="w-full border rounded px-3 py-2" id="message" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
        </div>
        <button class="bg-emerald-600 text-white px-6 py-2 rounded hover:bg-emerald-700" type="submit">Send Message</button>
    </form>
    <p class="mt-6 text-gray-600">Prefer email? Reach us at <a href="mailto:support@crofund.org" class="text-emerald-600 underline">contact@crofund.org</a></p>
</main>
<?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>