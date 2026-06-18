<?php
session_start();


require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Campaign.php';


include 'components/header.php';


if (!$user->isLoggedIn()) {
    header("Location: login");
    exit();
}


$campaign = new Campaign();


try {
    $stats = $campaign->getDashboardStats($_SESSION['user_id']);
    $recentCampaigns = $campaign->getRecentCampaigns($_SESSION['user_id']);
} catch (Exception $e) {
    $error = $e->getMessage();
    $recentCampaigns = []; 
}




try {
    
    $userStats = $campaign->getUserStats($_SESSION['user_id']);
} catch (Exception $e) {
    error_log("Error fetching user stats: " . $e->getMessage());
    $userStats = [
        'total_raised' => 0,
        'total_campaigns' => 0,
        'total_donors' => 0,
        'recent_donations' => []
    ];
}



$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT 
            id, 
            title, 
            category, 
            current_amount, 
            goal_amount,
            end_date,
            image_path,
            status,
            (SELECT COUNT(*) FROM donations WHERE campaign_id = campaigns.id) as donors
        FROM campaigns
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5");
    $stmt->execute([$userId]);
    $recentCampaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add computed fields like progress and formatted date
    foreach ($recentCampaigns as &$campaign) {
        $campaign['progress'] = $campaign['goal_amount'] > 0
            ? round(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 2)
            : 0;
        $campaign['formatted_end_date'] = date('M d, Y', strtotime($campaign['end_date']));
        $campaign['name'] = "You"; // Optional: Since Campaign.php may have added this before
    }
    unset($campaign); // Best practice after foreach reference
} catch (PDOException $e) {
    $recentCampaigns = [];
    error_log("Error fetching campaigns: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CroFunding</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Tailwind Configuration -->
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
    
    <!-- Custom CSS for campaign updates -->
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
    
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
</head>
<body class="bg-gray-50">
    <!-- Include Header -->
    <?php include 'components/header.php'; ?>

    <!-- Page Container -->
    <div class="flex h-screen pt-16"> <!-- pt-16 to account for the fixed navbar -->
        <!-- Include Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <!-- Main Content Wrapper -->
            <div class="p-4 sm:p-6">
                <!-- Dashboard Header -->
                <div class="mb-6 sm:mb-8">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Dashboard Overview</h1>
                    <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>!</p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6 sm:mb-8">
                    <!-- User's Total Raised -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-emerald-100 rounded-full p-3">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5">
                                <p class="text-sm font-medium text-gray-500 truncate">Your Total Raised</p>
                                <p class="mt-1 text-xl font-semibold text-gray-900">
                                    $<?php echo number_format($userStats['total_raised'] ?? 0, 2); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- User's Active Campaigns -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-emerald-100 rounded-full p-3">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div class="ml-5">
                                <p class="text-sm font-medium text-gray-500 truncate">Your Campaigns</p>
                                <p class="mt-1 text-xl font-semibold text-gray-900">
                                    <?php echo number_format($userStats['total_campaigns'] ?? 0); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- User's Total Donors -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-emerald-100 rounded-full p-3">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="ml-5">
                                <p class="text-sm font-medium text-gray-500 truncate">Your Total Donors</p>
                                <p class="mt-1 text-xl font-semibold text-gray-900">
                                    <?php echo number_format($userStats['total_donors'] ?? 0); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Campaigns -->
         <div class="bg-white rounded-lg shadow mb-6 sm:mb-8">
          <div class="p-4 sm:p-6 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Recent Campaigns</h2>
          </div>
      
           <?php include 'components/campaign-cards.php'; ?>

        
        </div>
            </div>
        </main>
    </div>

    <!-- Campaign Update Modal -->
    <div id="update-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Add Campaign Update</h3>
                    <button onclick="hideDashboardUpdateForm()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="dashboard-update-form" class="p-4 space-y-4">
                    <input type="hidden" id="update-campaign-id" name="campaign_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Update Title</label>
                        <input type="text" name="update_title" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                               placeholder="e.g., Milestone Reached!">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Update Content</label>
                        <textarea name="update_content" rows="4" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                  placeholder="Share the latest news, progress, or thank you message..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Image (Optional)</label>
                        <input type="file" name="update_image" accept="image/*" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <p class="text-xs text-gray-500 mt-1">Max size: 5MB. Formats: JPG, PNG, GIF</p>
                    </div>
                    
                    <div class="flex gap-3 pt-2">
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition-colors">
                            Post Update
                        </button>
                        <button type="button" onclick="hideDashboardUpdateForm()" 
                                class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
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

    // Campaign Update Functions
    function showDashboardUpdateForm(campaignId) {
        document.getElementById('update-campaign-id').value = campaignId;
        document.getElementById('update-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function hideDashboardUpdateForm() {
        document.getElementById('update-modal').classList.add('hidden');
        document.body.style.overflow = 'auto';
        document.getElementById('dashboard-update-form').reset();
    }

    // Handle dashboard update form submission
    document.addEventListener('DOMContentLoaded', function() {
        const updateForm = document.getElementById('dashboard-update-form');
        if (updateForm) {
            updateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const campaignId = formData.get('campaign_id');
                
                fetch(`${BASE_URL}/campaigns/add-update.php`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Update posted successfully!');
                        hideDashboardUpdateForm();
                        // Refresh the updates section for this campaign
                        location.reload(); // Simple refresh for now
                    } else {
                        alert('Error: ' + (data.error || 'Failed to post update'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error posting update. Please try again.');
                });
            });
        }
    });

    const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</body>
</html>