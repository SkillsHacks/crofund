<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Campaign.php';

// Get parameters from URL
$campaignId = $_GET['campaign_id'] ?? null;
$amount = $_GET['amount'] ?? null;
$donor = $_GET['donor'] ?? null;
$gateway = $_GET['gateway'] ?? null;
$cancelled = $_GET['cancelled'] ?? null;

// Get campaign details if campaign_id is provided
$campaign = null;
if ($campaignId) {
    $campaign = new Campaign();
    $campaignDetails = $campaign->getCampaignById($campaignId);
}

// Set page title
$pageTitle = $cancelled ? 'Payment Cancelled' : 'Thank You for Your Donation';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - CroFunding</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Inter font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png" type="image/x-icon">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .font-sans {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 font-sans">

<?php include __DIR__ . '/components/public-header.php'; ?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-16">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="p-6 sm:p-10 text-center">
            <?php if ($cancelled): ?>
                <!-- Payment Cancelled -->
                <div class="mb-6 sm:mb-8">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6 shadow-md">
                        <i class="fas fa-times-circle text-3xl sm:text-4xl text-yellow-600"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 mb-3 sm:mb-4">Payment Cancelled</h1>
                    <p class="text-sm sm:text-base lg:text-lg text-slate-600 mb-6 sm:mb-8">
                        Your payment was cancelled and no charges were made. You can try again anytime.
                    </p>
                </div>
                
                <?php if ($campaignId && $campaignDetails): ?>
                    <div class="bg-slate-50 rounded-2xl p-5 sm:p-6 mb-6 sm:mb-8 text-left border border-slate-200">
                        <a href="<?php echo BASE_URL; ?>/" 
                           class="inline-flex items-center justify-center w-full sm:w-auto px-5 sm:px-6 py-3 bg-emerald-600 text-white text-sm sm:text-base font-semibold rounded-2xl hover:bg-emerald-700 transition-all duration-200 shadow-md hover:shadow-lg active:scale-[0.98]">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Campaigns
                        </a>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Successful Donation -->
                <div class="mb-6 sm:mb-8">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6 shadow-xl">
                        <i class="fas fa-check text-3xl sm:text-4xl text-white"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 mb-3 sm:mb-4">Thank You!</h1>
                    <p class="text-sm sm:text-base lg:text-lg text-slate-600 mb-6 sm:mb-8">
                        Your generous donation has been processed successfully.
                    </p>
                </div>
                
                <?php if ($amount): ?>
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white px-6 sm:px-10 py-4 sm:py-6 rounded-2xl font-bold text-xl sm:text-2xl lg:text-3xl mb-6 sm:mb-8 inline-block shadow-lg">
                        $<?php echo number_format($amount, 2); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($gateway): ?>
                    <div class="mb-6 sm:mb-8">
                        <span class="inline-flex items-center px-4 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-semibold 
                            <?php echo $gateway === 'paypal' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                            <i class="fas fa-<?php echo $gateway === 'paypal' ? 'paypal' : 'credit-card'; ?> mr-2"></i>
                            <?php echo ucfirst($gateway); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if ($campaignId && $campaignDetails): ?>
                    <div class="bg-slate-50 rounded-2xl p-5 sm:p-6 mb-6 sm:mb-8 text-left border border-slate-200">
                        <h5 class="text-base sm:text-lg font-semibold text-slate-900 mb-3">Campaign Details</h5>
                        <p class="font-semibold text-slate-900 text-sm sm:text-base mb-2"><?php echo htmlspecialchars($campaignDetails['title']); ?></p>
                        <p class="text-slate-600 text-xs sm:text-sm mb-4"><?php echo htmlspecialchars(substr($campaignDetails['description'], 0, 150)) . '...'; ?></p>
                        <a href="<?php echo BASE_URL; ?>/campaigns/public-view.php?id=<?php echo $campaignId; ?>" 
                           class="inline-flex items-center justify-center w-full sm:w-auto px-5 sm:px-6 py-3 bg-emerald-600 text-white text-sm sm:text-base font-semibold rounded-2xl hover:bg-emerald-700 transition-all duration-200 shadow-md hover:shadow-lg active:scale-[0.98]">
                            <i class="fas fa-eye mr-2"></i>View Campaign
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center items-center">
                <a href="<?php echo BASE_URL; ?>/" 
                   class="inline-flex items-center justify-center w-full sm:w-auto px-5 sm:px-6 py-3 bg-slate-700 text-white text-sm sm:text-base font-semibold rounded-2xl hover:bg-slate-800 transition-all duration-200 shadow-md hover:shadow-lg active:scale-[0.98]">
                    <i class="fas fa-home mr-2"></i>Go Home
                </a>
            </div>
        </div>
    </div>
</main>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<?php include __DIR__ . '/components/public-footer.php'; ?>

</body>
</html>
