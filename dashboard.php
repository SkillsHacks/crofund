<?php
// 1. Initialize session FIRST, before ANY output
session_start();

// 2. Require config files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Campaign.php';
require_once __DIR__ . '/config/User.php';

// 3. Check authentication BEFORE any HTML/component includes
$user = new User();
if (!$user->isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// 4. Get current user BEFORE including header.php
$currentUser = $user->getCurrentUser();

// 5. Defensive checks for user data
if (!$currentUser || !isset($currentUser['name'])) {
    // Fallback if user data is missing
    session_destroy();
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// 6. Now fetch data AFTER auth checks
$campaign = new Campaign();
$userId = $_SESSION['user_id'];

try {
    $stats = $campaign->getDashboardStats($userId);
    $userStats = $campaign->getUserStats($userId);
} catch (Exception $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    $userStats = [
        'total_raised' => 0,
        'total_campaigns' => 0,
        'total_donors' => 0,
        'recent_donations' => []
    ];
}

try {
    $db = Database::getInstance();
    $recentCampaigns = $db->fetchAll("SELECT 
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
        LIMIT 5", [$userId]);

    foreach ($recentCampaigns as &$campaign) {
        $campaign['progress'] = $campaign['goal_amount'] > 0
            ? round(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 2)
            : 0;
        $campaign['formatted_end_date'] = date('M d, Y', strtotime($campaign['end_date']));
        $campaign['name'] = "You";
    }
    unset($campaign);
} catch (Exception $e) {
    $recentCampaigns = [];
    error_log("Error fetching campaigns: " . $e->getMessage());
}

// 7. Now we can include components AFTER all logic is done
include 'components/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    
    <title>Dashboard - CroFunding</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
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
        .dashboard-glow {
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.18), transparent 38%),
                        radial-gradient(circle at bottom left, rgba(5, 150, 105, 0.14), transparent 32%),
                        linear-gradient(135deg, #f8fffc 0%, #f0fdf4 45%, #ecfdf5 100%);
        }
    </style>
    
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen pt-16">
        <?php include 'components/sidebar.php'; ?>

        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <div id="dashboard-default-view" class="p-4 sm:p-6 lg:p-8 space-y-6 sm:space-y-8">
                <section class="dashboard-glow relative overflow-hidden rounded-3xl border border-emerald-100 shadow-sm">
                    <div class="absolute inset-y-0 right-0 hidden lg:block w-1/3 bg-gradient-to-l from-white/40 to-transparent"></div>
                    <div class="relative px-5 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
                                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                    Dashboard overview
                                </div>
                                <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                                    Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?>
                                </h1>
                                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600 sm:text-base">
                                    Track campaign performance, monitor supporter activity, and jump back into the parts of CroFund that matter most.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-[420px]">
                                <a href="<?php echo BASE_URL; ?>/campaigns/create.php" data-dashboard-ajax="create-campaign" class="group rounded-2xl bg-slate-900 px-4 py-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold">Create campaign</span>
                                        <svg class="h-5 w-5 text-emerald-300 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-300">Launch a new fundraiser in a few steps.</p>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/campaigns/index.php" data-dashboard-ajax="section" class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-4 text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold">Manage campaigns</span>
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Review active, ended, and draft campaigns.</p>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/withdrawals/initiate.php" data-dashboard-ajax="section" class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-4 text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold">Withdraw funds</span>
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Move available funds when you’re ready.</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total raised</p>
                                <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">$<?php echo number_format($userStats['total_raised'] ?? 0, 2); ?></p>
                                <p class="mt-2 text-xs text-slate-500">Across all campaigns and supporter contributions.</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Campaigns created</p>
                                <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><?php echo number_format($userStats['total_campaigns'] ?? 0); ?></p>
                                <p class="mt-2 text-xs text-slate-500">Your active, completed, and archived fundraising efforts.</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total donors</p>
                                <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><?php echo number_format($userStats['total_donors'] ?? 0); ?></p>
                                <p class="mt-2 text-xs text-slate-500">People who have supported your fundraising work.</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-900 p-5 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <p class="text-sm font-medium text-slate-300">Next best action</p>
                        <p class="mt-3 text-xl font-semibold">Grow your momentum</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Publish updates, share campaign links, and keep supporters informed to build trust and improve conversions.</p>
                        <button type="button" onclick="showDashboardUpdateForm(<?php echo !empty($recentCampaigns[0]['id']) ? (int) $recentCampaigns[0]['id'] : 0; ?>)" <?php echo empty($recentCampaigns[0]['id']) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50">
                            Add update now
                        </button>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.8fr),minmax(320px,1fr)]">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Recent campaigns</h2>
                                <p class="mt-1 text-sm text-slate-500">A quick look at the latest campaigns you’ve created.</p>
                            </div>
                            <a href="<?php echo BASE_URL; ?>/campaigns/index.php" data-dashboard-ajax="section" class="hidden sm:inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:text-emerald-700">
                                View all
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                        <div class="p-4 sm:p-6">
                            <?php include 'components/campaign-cards.php'; ?>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">Quick actions</h2>
                            <p class="mt-1 text-sm text-slate-500">Common tasks you can jump into right away.</p>
                            <div class="mt-5 space-y-3">
                                <a href="<?php echo BASE_URL; ?>/campaigns/create.php" data-dashboard-ajax="create-campaign" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Start a new campaign</p>
                                        <p class="text-xs text-slate-500">Launch a fresh fundraiser.</p>
                                    </div>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">New</span>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/account/verification.php" data-dashboard-ajax="section" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Check verification</p>
                                        <p class="text-xs text-slate-500">Review your account verification status.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/withdrawals/logs.php" data-dashboard-ajax="section" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Review withdrawal logs</p>
                                        <p class="text-xs text-slate-500">Track payout requests and status changes.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">Performance snapshot</h2>
                            <div class="mt-5 space-y-4">
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-sm">
                                        <span class="font-medium text-slate-600">Funding reach</span>
                                        <span class="text-slate-500"><?php echo max(1, (int) ($userStats['total_campaigns'] ?? 0)); ?> campaign<?php echo (($userStats['total_campaigns'] ?? 0) == 1 ? '' : 's'); ?></span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-slate-100">
                                        <div class="h-2.5 rounded-full bg-emerald-500" style="width: <?php echo min(100, max(8, (float) (($userStats['total_campaigns'] ?? 0) * 12))); ?>%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-sm">
                                        <span class="font-medium text-slate-600">Supporter base</span>
                                        <span class="text-slate-500"><?php echo number_format($userStats['total_donors'] ?? 0); ?> donors</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-slate-100">
                                        <div class="h-2.5 rounded-full bg-violet-500" style="width: <?php echo min(100, max(6, (float) (($userStats['total_donors'] ?? 0) * 4))); ?>%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-sm">
                                        <span class="font-medium text-slate-600">Fundraising momentum</span>
                                        <span class="text-slate-500">$<?php echo number_format($userStats['total_raised'] ?? 0, 0); ?></span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-slate-100">
                                        <div class="h-2.5 rounded-full bg-blue-500" style="width: <?php echo min(100, max(10, (float) (($userStats['total_raised'] ?? 0) / 50))); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div id="dashboard-ajax-view" class="hidden p-3 sm:p-6"></div>
        </main>
    </div>

    <div id="update-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-3 sm:p-4">
            <div class="bg-white rounded-lg max-w-md w-full max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between p-3 sm:p-4 border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">Add Campaign Update</h3>
                    <button onclick="hideDashboardUpdateForm()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="dashboard-update-form" class="p-3 sm:p-4 space-y-3 sm:space-y-4">
                    <input type="hidden" id="update-campaign-id" name="campaign_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)); ?>">
                    
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Update Title</label>
                        <input type="text" name="update_title" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                               placeholder="e.g., Milestone Reached!">
                    </div>
                    
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Update Content</label>
                        <textarea name="update_content" rows="3 sm:rows-4" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                  placeholder="Share the latest news, progress, or thank you message..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">Image (Optional)</label>
                        <input type="file" name="update_image" accept="image/*" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <p class="text-[10px] sm:text-xs text-gray-500 mt-1">Max size: 5MB. Formats: JPG, PNG, GIF</p>
                    </div>
                    
                    <div class="flex gap-2 sm:gap-3 pt-2">
                        <button type="submit" 
                                class="flex-1 px-3 sm:px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition-colors text-sm">
                            Post Update
                        </button>
                        <button type="button" onclick="hideDashboardUpdateForm()" 
                                class="flex-1 px-3 sm:px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="share-toast" class="fixed bottom-3 right-3 sm:bottom-4 sm:right-4 bg-gray-800 text-white px-4 py-2 sm:px-6 sm:py-3 rounded-lg transform translate-y-full opacity-0 transition-all duration-300 z-50 text-sm sm:text-base">
        Link copied to clipboard!
    </div>

    <script>
    function copyShareLink(campaignId, campaignTitle) {
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

    function setDashboardAjaxMode(isAjaxMode) {
        const defaultView = document.getElementById('dashboard-default-view');
        const ajaxView = document.getElementById('dashboard-ajax-view');

        defaultView.classList.toggle('hidden', isAjaxMode);
        ajaxView.classList.toggle('hidden', !isAjaxMode);
    }

    function executeInjectedScripts(container) {
        const scripts = Array.from(container.querySelectorAll('script'));
        scripts.forEach((oldScript) => {
            const newScript = document.createElement('script');

            Array.from(oldScript.attributes).forEach((attr) => {
                newScript.setAttribute(attr.name, attr.value);
            });

            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }

            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    async function loadDashboardSection(path, pushState = true) {
        const ajaxView = document.getElementById('dashboard-ajax-view');
        setDashboardAjaxMode(true);
        ajaxView.innerHTML = '<div class="bg-white rounded-lg shadow-sm p-6 text-gray-600">Loading...</div>';

        try {
            const separator = path.includes('?') ? '&' : '?';
            const response = await fetch(`${path}${separator}ajax=1`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const html = await response.text();
            ajaxView.innerHTML = html;
            executeInjectedScripts(ajaxView);
            initializeDashboardLinks(ajaxView);

            if (pushState) {
                window.history.pushState({ dashboardAjaxPath: path }, '', `${BASE_URL}/dashboard.php?view=${encodeURIComponent(path)}`);
            }
        } catch (error) {
            console.error('Failed to load dashboard section:', error);
            ajaxView.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">Unable to load this section right now. Please try again.</div>';
        }
    }

    function showDashboardHome(pushState = true) {
        setDashboardAjaxMode(false);
        if (pushState) {
            window.history.pushState({ dashboardAjaxPath: null }, '', `${BASE_URL}/dashboard.php`);
        }
    }

    function initializeDashboardLinks(scope = document) {
        scope.querySelectorAll('[data-dashboard-ajax]').forEach((link) => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                loadDashboardSection(this.href);
            });
        });

        scope.querySelectorAll('[data-dashboard-home="true"]').forEach((link) => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                showDashboardHome();
            });
        });
    }

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

    document.addEventListener('DOMContentLoaded', function() {
        initializeDashboardLinks();

        const updateForm = document.getElementById('dashboard-update-form');
        if (updateForm) {
            updateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch(`${BASE_URL}/campaigns/add-update.php`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Update posted successfully!');
                        hideDashboardUpdateForm();
                        location.reload();
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

        const params = new URLSearchParams(window.location.search);
        const viewPath = params.get('view');
        if (viewPath) {
            loadDashboardSection(viewPath, false);
        }
    });

    window.addEventListener('popstate', function(event) {
        const dashboardPath = event.state?.dashboardAjaxPath;
        if (dashboardPath) {
            loadDashboardSection(dashboardPath, false);
        } else {
            showDashboardHome(false);
        }
    });

    const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</body>
</html>

