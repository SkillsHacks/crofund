<?php
session_start();
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    
    <title>Privacy Policy - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include 'components/public-header.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Privacy Policy</h1>
        
        <div class="prose max-w-none bg-white p-8 rounded-lg shadow-sm">
            <h2 class="text-xl font-semibold mb-4">Data We Collect</h2>
            <p class="mb-4">We collect the following information:</p>
            <ul class="list-disc pl-5 mb-4">
                <li>Name and contact information</li>
                <li>Payment information</li>
                <li>Device and browser information</li>
                <li>Usage data and interactions</li>
                <li>Communications with our platform</li>
            </ul>

            <h2 class="text-xl font-semibold mb-4">How We Use Your Data</h2>
            <ul class="list-disc pl-5 mb-4">
                <li>Process donations and withdrawals</li>
                <li>Verify identity and prevent fraud</li>
                <li>Improve our services</li>
                <li>Send important updates</li>
                <li>Comply with legal obligations</li>
            </ul>

            <h2 class="text-xl font-semibold mb-4">Data Sharing</h2>
            <p class="mb-4">We share data with:</p>
            <ul class="list-disc pl-5 mb-4">
                <li>Payment processors</li>
                <li>Identity verification services</li>
                <li>Legal authorities when required</li>
            </ul>

            <h2 class="text-xl font-semibold mb-4">Your Rights</h2>
            <p class="mb-4">You have the right to:</p>
            <ul class="list-disc pl-5 mb-4">
                <li>Access your personal data</li>
                <li>Request data correction</li>
                <li>Request data deletion</li>
                <li>Withdraw consent</li>
            </ul>

            <p class="text-sm text-gray-600 mt-8">Last updated: <?php echo date('F d, Y'); ?></p>
        </div>
    </div>
    

      <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>