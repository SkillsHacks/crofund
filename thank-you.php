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
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png" type="image/x-icon">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">

<?php include __DIR__ . '/components/public-header.php'; ?>

<main class="max-w-4xl mx-auto p-6 pt-24">
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-8 text-center">
            <?php if ($cancelled): ?>
                <!-- Payment Cancelled -->
                <div class="mb-6">
                    <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-times-circle text-4xl text-yellow-600"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Cancelled</h1>
                    <p class="text-lg text-gray-600 mb-6">
                        Your payment was cancelled and no charges were made. You can try again anytime.
                    </p>
                </div>
                
                <?php if ($campaignId && $campaignDetails): ?>
                    <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
                      
                        <a href="<?php echo BASE_URL; ?>/" 
                           class="inline-flex items-center px-6 py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Campaigns
                        </a>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Successful Donation -->
                <div class="mb-6">
                    <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-check-circle text-4xl text-emerald-600"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">Thank You!</h1>
                    <p class="text-lg text-gray-600 mb-6">
                        Your generous donation has been processed successfully.
                    </p>
                </div>
                
                <?php if ($amount): ?>
                    <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 text-white px-8 py-4 rounded-lg font-bold text-2xl mb-6 inline-block">
                        $<?php echo number_format($amount, 2); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($gateway): ?>
                    <div class="mb-6">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium 
                            <?php echo $gateway === 'paypal' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                            <i class="fas fa-<?php echo $gateway === 'paypal' ? 'paypal' : 'credit-card'; ?> mr-2"></i>
                            <?php echo ucfirst($gateway); ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if ($campaignId && $campaignDetails): ?>
                    <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
                        <h5 class="text-lg font-semibold text-gray-900 mb-3">Campaign Details</h5>
                        <p class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($campaignDetails['title']); ?></p>
                        <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(substr($campaignDetails['description'], 0, 150)) . '...'; ?></p>
                        <a href="<?php echo BASE_URL; ?>/campaigns/public-view.php?id=<?php echo $campaignId; ?>" 
                           class="inline-flex items-center px-6 py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                            <i class="fas fa-eye mr-2"></i>View Campaign
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
            
                <a href="<?php echo BASE_URL; ?>/" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
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
