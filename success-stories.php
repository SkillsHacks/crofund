<?php
require_once __DIR__ . '/config/config.php';
include __DIR__ . '/components/public-header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Success Stories – CroFunding</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
</head>
<body>
<main class="max-w-3xl mx-auto py-20 px-4">
    <h1 class="text-3xl font-bold mb-6 text-emerald-700">Success Stories</h1>
    <p class="mb-8 text-lg text-gray-700">
        Discover how CroFunding has empowered individuals and communities to achieve their dreams. These real stories show the impact of your support and the power of coming together.
    </p>
    <div class="grid gap-8 md:grid-cols-2">
        <div class="bg-white rounded-lg shadow p-6 flex flex-col">
            <img src="assets/images/success-jane.jpg" alt="Jane's Medical Fund" class="rounded mb-4 h-40 w-full object-cover">
            <h2 class="text-xl font-semibold text-emerald-700 mb-2">Jane's Medical Fund</h2>
            <p class="text-gray-700 mb-2">Raised <span class="font-bold">$10,000</span> for surgery thanks to community support.</p>
            <p class="text-gray-600 text-sm">"CroFunding gave me hope when I needed it most. The kindness of strangers changed my life." – Jane</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex flex-col">
            <img src="assets/images/success-school.jpg" alt="School Supplies Drive" class="rounded mb-4 h-40 w-full object-cover">
            <h2 class="text-xl font-semibold text-emerald-700 mb-2">School Supplies Drive</h2>
            <p class="text-gray-700 mb-2">Provided <span class="font-bold">200+ students</span> with essential materials.</p>
            <p class="text-gray-600 text-sm">"We couldn't have done it without CroFunding. Our students are ready for success!" – Mr. Adebayo</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex flex-col">
            <img src="assets/images/success-community.jpg" alt="Community Center Renovation" class="rounded mb-4 h-40 w-full object-cover">
            <h2 class="text-xl font-semibold text-emerald-700 mb-2">Community Center Renovation</h2>
            <p class="text-gray-700 mb-2">Revitalized a local space for everyone to enjoy.</p>
            <p class="text-gray-600 text-sm">"Now our community has a safe, beautiful place to gather. Thank you, CroFunding!" – Community Leader</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex flex-col">
            <img src="assets/images/success-ngo.jpg" alt="NGO Water Project" class="rounded mb-4 h-40 w-full object-cover">
            <h2 class="text-xl font-semibold text-emerald-700 mb-2">NGO Water Project</h2>
            <p class="text-gray-700 mb-2">Brought clean water to a rural village.</p>
            <p class="text-gray-600 text-sm">"With CroFunding, we made a lasting difference for hundreds of families." – Water4All Team</p>
        </div>
    </div>
    <div class="mt-12 text-center">
        <a href="register.php" class="inline-block px-8 py-3 bg-emerald-600 text-white font-semibold rounded-lg shadow hover:bg-emerald-700 transition">Start Your Success Story</a>
    </div>
</main>
<?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>