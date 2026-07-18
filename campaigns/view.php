<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/Campaign.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Check if campaign ID is provided
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/campaigns/browse.php");
    exit();
}

// Initialize Campaign object and get campaign details
$campaign = new Campaign();
$campaignDetails = $campaign->getCampaignById($_GET['id']);

// If campaign doesn't exist, redirect to browse page
if (!$campaignDetails) {
    $_SESSION['error'] = "Campaign not found";
    header("Location: " . BASE_URL . "/campaigns/browse.php");
    exit();
}

// Check if current user is the campaign owner
$isOwner = $campaignDetails['user_id'] == $_SESSION['user_id'];

// Handle campaign deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!$isOwner) {
        $_SESSION['error'] = "You don't have permission to delete this campaign";
        header("Location: " . BASE_URL . "/campaigns/browse.php");
        exit();
    }
    
    if ($campaign->deleteCampaign($campaignDetails['id'])) {
        $_SESSION['success'] = "Campaign deleted successfully";
        header("Location: " . BASE_URL . "/campaigns/browse.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to delete campaign";
    }
}

include __DIR__ . '/../components/header.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <title><?php echo htmlspecialchars($campaignDetails['title']); ?> - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-overlay {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
        }
        .campaign-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .share-btn {
            transition: all 0.3s ease;
        }
        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .action-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="flex h-screen pt-16">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <main class="flex-1 min-w-0 overflow-auto bg-gradient-to-br from-gray-50 to-gray-100 lg:ml-64">
            <div class="max-w-5xl mx-auto p-3 sm:p-4 md:p-6">
                <!-- Campaign Details Card -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                    <!-- Campaign Image Section -->
                    <div class="relative h-48 sm:h-64 md:h-96 overflow-hidden group">
                        <?php 
                        $imageUrl = $campaignDetails['image_path'] 
                            ? BASE_URL . '/' . $campaignDetails['image_path']
                            : BASE_URL . '/assets/images/default-campaign.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                            alt="<?php echo htmlspecialchars($campaignDetails['title']); ?>"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                        
                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4">
                            <span class="px-4 py-2 campaign-badge text-white text-sm font-semibold rounded-full">
                                <?php echo ucfirst($campaignDetails['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Campaign Content -->
                    <div class="p-4 sm:p-6 md:p-8">
                        <!-- Header with Title and Actions -->
                        <div class="flex flex-col sm:flex-row md:items-start md:justify-between gap-3 sm:gap-4 md:gap-6 mb-6 md:mb-8">
                            <div class="flex-1 min-w-0">
                                <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-2 sm:mb-3 break-words">
                                    <?php echo htmlspecialchars($campaignDetails['title']); ?>
                                </h1>
                                <p class="text-gray-600 text-sm sm:text-base md:text-lg">
                                    Created by <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($campaignDetails['creator_name'] ?? 'Anonymous'); ?></span>
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-2 sm:gap-3 md:flex-col md:w-auto">
                                <!-- Share Button -->
                                <button onclick="toggleShareMenu()" 
                                        class="action-btn flex items-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-all duration-200 font-medium">
                                    <i class="fas fa-share-alt"></i>
                                    <span class="hidden sm:inline">Share</span>
                                </button>

                                <?php if ($isOwner): ?>
                                <!-- Edit Button -->
                                <a href="<?php echo BASE_URL; ?>/campaigns/edit.php?id=<?php echo $campaignDetails['id']; ?>" 
                                   class="action-btn flex items-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-all duration-200 font-medium text-center">
                                    <i class="fas fa-edit"></i>
                                    <span class="hidden sm:inline">Edit</span>
                                </a>

                                <!-- Delete Button -->
                                <button onclick="openDeleteModal()" 
                                        class="action-btn flex items-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 sm:py-3 text-sm sm:text-base bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all duration-200 font-medium">
                                    <i class="fas fa-trash-alt"></i>
                                    <span class="hidden sm:inline">Delete</span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Share Menu -->
                        <div id="shareMenu" class="hidden mb-6 md:mb-8 p-4 sm:p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">Share this campaign</h3>
                            <div class="flex flex-wrap gap-2 sm:gap-3 lg:gap-4">
                                <!-- Facebook Share -->
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(BASE_URL . '/campaigns/public-view.php?id=' . $campaignDetails['id']); ?>" 
                                   target="_blank"
                                   class="share-btn flex items-center gap-1 sm:gap-2 px-3 sm:px-5 py-2 sm:py-3 text-xs sm:text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                                    <i class="fab fa-facebook-f"></i>
                                    <span class="hidden sm:inline">Facebook</span>
                                </a>

                                <!-- Twitter Share -->
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(BASE_URL . '/campaigns/public-view.php?id=' . $campaignDetails['id']); ?>&text=<?php echo urlencode('Check out this amazing campaign: ' . $campaignDetails['title']); ?>" 
                                   target="_blank"
                                   class="share-btn flex items-center gap-1 sm:gap-2 px-3 sm:px-5 py-2 sm:py-3 text-xs sm:text-sm bg-sky-500 text-white rounded-lg hover:bg-sky-600 font-medium">
                                    <i class="fab fa-twitter"></i>
                                    <span class="hidden sm:inline">Twitter</span>
                                </a>

                                <!-- LinkedIn Share -->
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(BASE_URL . '/campaigns/public-view.php?id=' . $campaignDetails['id']); ?>" 
                                   target="_blank"
                                   class="share-btn flex items-center gap-1 sm:gap-2 px-3 sm:px-5 py-2 sm:py-3 text-xs sm:text-sm bg-blue-700 text-white rounded-lg hover:bg-blue-800 font-medium">
                                    <i class="fab fa-linkedin-in"></i>
                                    <span class="hidden sm:inline">LinkedIn</span>
                                </a>

                                <!-- Copy Link -->
                                <button onclick="copyToClipboard()" 
                                        class="share-btn flex items-center gap-1 sm:gap-2 px-3 sm:px-5 py-2 sm:py-3 text-xs sm:text-sm bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium">
                                    <i class="fas fa-link"></i>
                                    <span class="hidden sm:inline">Copy Link</span>
                                </button>

                                <!-- Email Share -->
                                <a href="mailto:?subject=<?php echo urlencode($campaignDetails['title']); ?>&body=<?php echo urlencode('Check out this campaign: ' . BASE_URL . '/campaigns/public-view.php?id=' . $campaignDetails['id']); ?>" 
                                   class="share-btn flex items-center gap-1 sm:gap-2 px-3 sm:px-5 py-2 sm:py-3 text-xs sm:text-sm bg-red-500 text-white rounded-lg hover:bg-red-600 font-medium">
                                    <i class="fas fa-envelope"></i>
                                    <span class="hidden sm:inline">Email</span>
                                </a>
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div class="mb-8 p-4 sm:p-6 gradient-overlay rounded-xl">
                            <div class="grid grid-cols-3 gap-3 sm:gap-6 mb-4 sm:mb-6">
                                <div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-medium mb-1 sm:mb-2">Raised</p>
                                    <p class="text-xl sm:text-3xl font-bold text-emerald-600">
                                        $<?php echo number_format($campaignDetails['current_amount'], 2); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-medium mb-1 sm:mb-2">Goal</p>
                                    <p class="text-xl sm:text-3xl font-bold text-gray-900">
                                        $<?php echo number_format($campaignDetails['goal_amount'], 2); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-medium mb-1 sm:mb-2">Progress</p>
                                    <p class="text-xl sm:text-3xl font-bold text-blue-600">
                                        <?php 
                                        $percentage = ($campaignDetails['current_amount'] / $campaignDetails['goal_amount']) * 100;
                                        $percentage = min($percentage, 100);
                                        echo round($percentage);
                                        ?>%
                                    </p>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="relative h-3 bg-gray-300 rounded-full overflow-hidden">
                                <div class="h-3 bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-full transition-all duration-500" 
                                    style="width: <?php echo $percentage; ?>%">
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mt-3">
                                <?php echo round($percentage); ?>% of goal reached
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="mb-6 md:mb-8">
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3 sm:mb-4">Campaign Details</h2>
                            <div class="prose prose-sm sm:prose-base md:prose-lg max-w-none text-gray-700 leading-relaxed text-sm sm:text-base">
                                <?php echo nl2br(htmlspecialchars($campaignDetails['description'])); ?>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl overflow-hidden">
            <div class="p-8">
                <div class="flex items-center justify-center w-14 h-14 mx-auto bg-red-100 rounded-full mb-6">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 text-center mb-3">Delete Campaign?</h3>
                <p class="text-gray-600 text-center mb-8">
                    Are you sure you want to delete this campaign? This action cannot be undone and all associated data will be permanently removed.
                </p>
                <div class="space-y-3">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" 
                                class="w-full px-4 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors duration-200">
                            <i class="fas fa-trash-alt mr-2"></i>Yes, Delete Campaign
                        </button>
                    </form>
                    <button onclick="closeDeleteModal()" 
                            class="w-full px-4 py-3 bg-gray-200 text-gray-900 font-bold rounded-lg hover:bg-gray-300 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Validation and Utility Scripts -->
    <script>
        function validateAmount() {
            const amount = document.getElementById('donation-amount').value;
            if (!amount || amount <= 0) {
                alert('Please enter a valid donation amount');
                return false;
            }
            return true;
        }

        function setAmount(amount) {
            document.getElementById('donation-amount').value = amount;
        }

        function toggleShareMenu() {
            const menu = document.getElementById('shareMenu');
            menu.classList.toggle('hidden');
        }

        function copyToClipboard() {
            const url = '<?php echo BASE_URL . '/campaigns/public-view.php?id=' . $campaignDetails['id']; ?>';
            navigator.clipboard.writeText(url).then(() => {
                alert('Campaign link copied to clipboard!');
            }).catch(err => {
                alert('Failed to copy link');
            });
        }

        function openDeleteModal() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('deleteModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close share menu when clicking outside
        document.addEventListener('click', function(e) {
            const shareMenu = document.getElementById('shareMenu');
            const shareBtn = e.target.closest('button[onclick*="toggleShareMenu"]');
            if (!shareBtn && !shareMenu?.contains(e.target)) {
                shareMenu?.classList.add('hidden');
            }
        });
    </script>
</body>
</html>