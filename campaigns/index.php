<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/User.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Initialize objects
$campaign = new Campaign();
$user = new User();

// Get current user's campaigns with filters
$status = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$search = $_GET['search'] ?? '';

// Get campaigns with filters
$userCampaigns = $campaign->getUserCampaigns(
    $_SESSION['user_id'], 
    $status, 
    $sort,
    $search
);

// Get campaign statistics
$stats = $campaign->getUserCampaignStats($_SESSION['user_id']);
?>


<!-- Favicon -->
<link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
<link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
<link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Campaigns - CroFunding</title>
    
    <!-- Load Tailwind first -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Then Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Then other CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    
    <!-- Your custom styles -->
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
    
    <!-- Tailwind config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Include Header -->
    <?php include '../components/header.php'; ?>

    <div class="flex h-screen pt-16">
        <!-- Include Sidebar -->
        <?php include '../components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <div class="min-h-screen px-4 sm:px-6 lg:px-8 py-8">
                <!-- Page Header -->
                <div class="mb-8 space-y-6">
                    <!-- Header with Create Button -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">My Campaigns</h1>
                            <p class="text-base sm:text-lg text-gray-600">Manage your fundraising campaigns</p>
                        </div>
                        <a href="create.php" class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150 w-full sm:w-auto">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create Campaign
                        </a>
                    </div>

                    <!-- Campaign Stats -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <p class="text-sm font-medium text-gray-600">Total Campaigns</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <p class="text-sm font-medium text-gray-600">Active Campaigns</p>
                            <p class="mt-2 text-3xl font-bold text-emerald-600"><?php echo $stats['active']; ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <p class="text-sm font-medium text-gray-600">Total Raised</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">$<?php echo number_format($stats['total_raised'], 2); ?></p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <p class="text-sm font-medium text-gray-600">Success Rate</p>
                            <p class="mt-2 text-3xl font-bold text-emerald-600"><?php echo $stats['success_rate']; ?>%</p>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                        <form class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search campaigns..." 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                            <div>
                                <select name="status" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="ended" <?php echo $status === 'ended' ? 'selected' : ''; ?>>Ended</option>
                                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                            <div>
                                <select name="sort" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="most_funded" <?php echo $sort === 'most_funded' ? 'selected' : ''; ?>>Most Funded</option>
                                    <option value="ending_soon" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Campaigns Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    <?php if (empty($userCampaigns)): ?>
                        <!-- Empty State -->
                        <div class="col-span-full flex flex-col items-center justify-center text-center p-8 bg-white rounded-lg border-2 border-dashed border-gray-300">
                            <div class="w-16 h-16 mb-4 flex items-center justify-center rounded-full bg-emerald-100">
                                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-medium text-gray-900">No campaigns yet</h3>
                            <p class="mb-4 text-sm text-gray-500">Get started by creating your first fundraising campaign</p>
                            <a href="create.php" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Create Campaign
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userCampaigns as $campaign): ?>
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-150">
                                <div class="aspect-w-16 aspect-h-9">
                                    <img src="<?php echo $campaign['image_path'] 
                                        ? BASE_URL . '/' . $campaign['image_path']
                                        : BASE_URL . '/assets/images/default-campaign.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($campaign['title']); ?>"
                                         class="w-full h-full object-cover"
                                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                                </div>
                                <div class="p-4">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                        <?php echo htmlspecialchars($campaign['title']); ?>
                                    </h3>
                                    <div class="mb-4">
                                        <div class="relative pt-1">
                                            <div class="overflow-hidden h-2 mb-2 text-xs flex rounded bg-emerald-100">
                                                <?php 
                                                $percentage = ($campaign['current_amount'] / $campaign['goal_amount']) * 100;
                                                $percentage = min($percentage, 100);
                                                ?>
                                                <div style="width:<?php echo $percentage; ?>%" 
                                                     class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-emerald-500 transition-all duration-300">
                                                </div>
                                            </div>
                                            <div class="flex justify-between text-sm text-gray-600">
                                                <div class="flex flex-col">
                                                    <span class="font-medium">$<?php echo number_format($campaign['current_amount'], 2); ?></span>
                                                    <span class="text-xs text-gray-500">raised of $<?php echo number_format($campaign['goal_amount'], 2); ?></span>
                                                </div>
                                                <div class="flex flex-col items-end">
                                                    <span class="font-medium"><?php echo round($percentage); ?>%</span>
                                                    <span class="text-xs text-gray-500">funded</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-2 mt-4">
                                        <a href="view.php?id=<?php echo $campaign['id']; ?>" 
                                           class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-emerald-600 text-sm font-medium rounded-lg text-emerald-600 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150">
                                            View Details
                                        </a>
                                        <a href="edit.php?id=<?php echo $campaign['id']; ?>"
                                           class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-yellow-500 text-sm font-medium rounded-lg text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400 transition-colors duration-150">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6-6m2 2l-6 6m-2 2h2v2h2v-2h2v-2h-2v-2H9v2z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <button onclick="copyShareLink(<?php echo $campaign['id']; ?>, '<?php echo htmlspecialchars(addslashes($campaign['title'])); ?>')" 
                                                class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                            </svg>
                                            Share
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add this before closing </body> -->
    <div id="share-toast" class="fixed bottom-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg transform translate-y-full opacity-0 transition-all duration-300 z-50">
        Link copied to clipboard!
    </div>

    <!-- Add this before closing </html> -->
    <script>
    function copyShareLink(campaignId, campaignTitle) {
        // Generate share URL
        const shareUrl = `${BASE_URL}/campaigns/public-view.php?id=${campaignId}`;
        
        navigator.clipboard.writeText(shareUrl).then(() => {
            const toast = document.getElementById('share-toast');
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
            
            setTimeout(() => {
                toast.style.transform = 'translateY(100%)';
                toast.style.opacity = '0';
            }, 2000);
        });
    }

    const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    
</body>
</html>