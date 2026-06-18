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
    <title>Terms and Conditions - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include 'components/public-header.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Terms and Conditions</h1>
        
        <div class="prose max-w-none bg-white p-8 rounded-lg shadow-sm">
            <h2 class="text-xl font-semibold mb-4">1. Acceptance of Terms</h2>
            <p class="mb-4">By accessing and using CroFunding, you accept and agree to be bound by the terms and conditions outlined here.</p>

            <h2 class="text-xl font-semibold mb-4">2. Campaign Creation and Management</h2>
            <ul class="list-disc pl-5 mb-4">
                <li>Users must provide accurate and truthful information</li>
                <li>Campaign creators are responsible for fulfilling their stated goals</li>
                <li>CroFunding reserves the right to remove campaigns that violate our policies</li>
            </ul>

            <h2 class="text-xl font-semibold mb-4">3. Donations</h2>
            <ul class="list-disc pl-5 mb-4">
                <li>All donations are final and non-refundable</li>
                <li>A platform fee of 2.9% + $0.30 applies to each donation</li>
                <li>Donors must be at least 18 years old</li>
            </ul>

            <h2 class="text-xl font-semibold mb-4">4. Prohibited Activities</h2>
            <p class="mb-4">Users may not:</p>
            <ul class="list-disc pl-5 mb-4">
                <li>Create fraudulent campaigns</li>
                <li>Harass other users</li>
                <li>Violate any applicable laws</li>
                <li>Share false or misleading information</li>
            </ul>

            <h2 class="text-xl font-semibold mb-4">5. Account Termination</h2>
            <p class="mb-4">CroFunding reserves the right to terminate or suspend accounts for violations of these terms.</p>

            <h2 class="text-xl font-semibold mb-4">6. Limitation of Liability</h2>
            <p class="mb-4">CroFunding is not liable for any damages arising from the use of our platform.</p>

            <p class="text-sm text-gray-600 mt-8">Last updated: <?php echo date('F d, Y'); ?></p>
        </div>
    </div>

   <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>