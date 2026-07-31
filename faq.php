<?php
require_once __DIR__ . '/config/config.php';
include __DIR__ . '/components/public-header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Common Questions – CroFunding</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
</head>
<body>
<main class="max-w-3xl mx-auto py-20 px-4">
    <h1 class="text-3xl font-bold mb-6 text-emerald-700">Common Questions</h1>
    <div class="space-y-8">
        <div>
            <h2 class="font-semibold text-lg text-gray-800">How do I start a campaign?</h2>
            <p class="text-gray-700">Sign up, click <span class="font-semibold text-emerald-700">"Start a Campaign"</span>, and follow the prompts to launch your fundraiser. You’ll be guided step-by-step through the process.</p>
        </div>
        <div>
            <h2 class="font-semibold text-lg text-gray-800">How do I withdraw funds?</h2>
            <p class="text-gray-700">Go to your dashboard and click <span class="font-semibold text-emerald-700">"Withdraw"</span> to request a payout. Funds are typically processed within 4–6 business days.</p>
        </div>
        <div>
            <h2 class="font-semibold text-lg text-gray-800">Is CroFunding secure?</h2>
            <p class="text-gray-700">Yes, we use industry-standard security to protect your data and donations. Our Trust &amp; Safety team works around the clock to keep your fundraising safe.</p>
        </div>
        <div>
            <h2 class="font-semibold text-lg text-gray-800">Do I need to reach my goal to get the money?</h2>
            <p class="text-gray-700">No, you can withdraw funds at any time, even if you haven’t reached your goal. There’s no penalty for not meeting your target.</p>
        </div>
        <div>
            <h2 class="font-semibold text-lg text-gray-800">Are there any fees?</h2>
            <p class="text-gray-700">A small transaction fee is deducted from each donation to cover payment processing. There are no platform fees for organizers.</p>
        </div>
        <div>
            <h2 class="font-semibold text-lg text-gray-800">How do I get more donations?</h2>
            <p class="text-gray-700">Share your fundraiser link with friends, family, and on social media. Post updates and thank your donors to keep them engaged. For more tips, visit our <a href="blog.php" class="text-emerald-600 underline">blog</a>.</p>
        </div>
        <div>
            <h2 class="font-semibold text-lg text-gray-800">Who can donate?</h2>
            <p class="text-gray-700">Anyone with a valid payment method can donate to your fundraiser, from anywhere in the world.</p>
        </div>
        <div>
            <h2 class="font-semibold text-lg text-gray-800">Need more help?</h2>
            <p class="text-gray-700">Contact our support team at <a href="mailto:support@crofund.org" class="text-emerald-600 underline">support@crofund.org</a> or visit our <a href="contact.php" class="text-emerald-600 underline">Contact Us</a> page.</p>
        </div>
    </div>
</main>
<?php include __DIR__ . '/components/public-footer.php'; ?>