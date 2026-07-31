<?php
require_once __DIR__ . '/config/config.php';
include __DIR__ . '/components/public-header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>How It Works – CroFunding</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
</head>
<body>
<main class="max-w-3xl mx-auto py-20 px-4"> <!-- Increased top padding for header overlap -->
    <h1 class="text-3xl font-bold mb-6 text-emerald-700">How Fundraising on CroFunding Works</h1>
    <p class="text-lg text-gray-700 mb-8">
        CroFunding is the trusted place to fundraise for what you care about. With no pressure to hit your fundraising goal, but helpful tools to help you reach it, you can confidently start fundraising. Learn step-by-step what to expect—from writing your story and sharing your fundraiser to receiving the money raised.
    </p>
    <div class="mb-10">
        <div class="w-full rounded-lg shadow-lg mb-6 aspect-video">
            <iframe class="w-full h-full rounded-lg" src="https://www.youtube.com/embed/fAGUimev5jQ" title="How it works video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
        </div>
        <p class="text-gray-600 text-center mb-8">Watch this short video to see how CroFunding empowers your fundraising journey.</p>
    </div>

    <a href="register.php" class="inline-block mb-8 px-6 py-3 bg-emerald-600 text-white font-semibold rounded-lg shadow hover:bg-emerald-700 transition">Start a CroFunding</a>

    <h2 class="text-2xl font-semibold mb-4 text-emerald-700">Here’s what to expect when fundraising on CroFunding:</h2>
    <ol class="list-decimal pl-6 space-y-8 text-gray-700">
        <li>
            <div class="font-bold text-lg mb-2">Follow the prompts to set up your fundraiser</div>
            <ul class="list-disc pl-6 space-y-2">
                <li>Click the ‘Start a CroFunding’ button and answer a few questions to get started</li>
                <li>Add a photo or video for your fundraiser (You can always change this later)</li>
                <li>In your fundraiser description, share the reason you are fundraising in 1-3 paragraphs</li>
                <li>Add details such as:
                    <ul class="list-disc pl-6">
                        <li>Who or what you’re fundraising for</li>
                        <li>How funds will be used</li>
                    </ul>
                </li>
                <li>We will suggest a goal amount for your fundraiser based on similar successful campaigns. You can always choose a different goal and edit it at any time.</li>
            </ul>
        </li>
        <li>
            <div class="font-bold text-lg mb-2">Share your fundraiser</div>
            <ul class="list-disc pl-6 space-y-2">
                <li>Share your fundraiser link with your closest friends and family through text messages and emails to start gaining momentum</li>
                <li>Post your fundraiser more broadly by using social media, sharing in-person with a printable poster, or writing a letter to people you know</li>
                <li>Continue to share your fundraiser each day to help reach your goals</li>
                <li>Add team members you know and trust to your fundraiser to help spread the word</li>
                <li>Want more tips? Check out our fundraiser awareness video.</li>
            </ul>
        </li>
        <li>
            <div class="font-bold text-lg mb-2">Post updates and thank donors</div>
            <ul class="list-disc pl-6 space-y-2">
                <li>Throughout your fundraising journey, you can post fundraiser updates to help increase donations and keep donors informed</li>
                <li>Easily thank donors within your fundraising dashboard</li>
                <li>Find more tips on our blog about how to ask for donations and how to write a donation thank-you letter.</li>
            </ul>
        </li>
        <li>
            <div class="font-bold text-lg mb-2">Set up bank transfers</div>
            <ul class="list-disc pl-6 space-y-2">
                <li>Add bank information to start receiving funds (you don’t need to hit your fundraising goal to receive your money)</li>
                <li>Add a beneficiary if you’re raising donations for someone else</li>
                <li><span class="italic">One small transaction fee is automatically deducted per donation. For more help and information about CroFunding fees, visit our Pricing page.</span></li>
            </ul>
        </li>
    </ol>

    <div class="my-10 p-6 bg-emerald-50 rounded-lg shadow">
        <h3 class="text-xl font-semibold text-emerald-700 mb-2">Fast and safe fundraising on CroFunding</h3>
        <p class="text-gray-700">
            CroFunding has become a trusted leader in online fundraising. Our Trust &amp; Safety team is dedicated to protecting organizers and donors so you can safely start a fundraiser or donate to a cause you care about. Learn more about our Giving Guarantee and find answers to common fundraising questions.
        </p>
    </div>
</main>
<?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>