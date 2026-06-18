<?php
session_start();
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Policy - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include 'components/public-header.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Cookie Policy</h1>
        
        <div class="prose max-w-none bg-white p-8 rounded-lg shadow-sm">
            <h2 class="text-xl font-semibold mb-4">What Are Cookies</h2>
            <p class="mb-4">Cookies are small text files stored on your device that help us improve your experience.</p>

            <h2 class="text-xl font-semibold mb-4">Types of Cookies We Use</h2>
            <ul class="list-disc pl-5 mb-4">
                <li><strong>Essential Cookies:</strong> Required for basic site functionality</li>
                <li><strong>Functional Cookies:</strong> Remember your preferences</li>
                <li><strong>Analytics Cookies:</strong> Help us understand site usage</li>
                <li><strong>Marketing Cookies:</strong> Track effectiveness of campaigns</li>
            </ul>

            <h2 class="text-xl font-semibold mb-4">Cookie Management</h2>
            <p class="mb-4">You can manage cookies through your browser settings. Note that disabling certain cookies may affect site functionality.</p>

            <h2 class="text-xl font-semibold mb-4">Third-Party Cookies</h2>
            <p class="mb-4">We use services from:</p>
            <ul class="list-disc pl-5 mb-4">
                <li>Google Analytics</li>
                <li>Payment processors</li>
                <li>Social media platforms</li>
            </ul>

            <p class="text-sm text-gray-600 mt-8">Last updated: <?php echo date('F d, Y'); ?></p>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
</body>
</html>