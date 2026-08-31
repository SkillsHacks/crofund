<?php
// 1. Initialize session FIRST, before ANY output
session_start();

// 2. Require config files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Campaign.php';
require_once __DIR__ . '/config/User.php';
require_once __DIR__ . '/components/trust_risk_ui.php';

// 3. Check authentication BEFORE any HTML/component includes
$user = new User();
if (!$user->isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// 4. Get current user BEFORE including header.php
$currentUser = $user->getCurrentUser();
$trustRiskUi = getTrustRiskUiState($currentUser);

// 5. Defensive checks for user data
if (!$currentUser || !isset($currentUser['name'])) {
    // Fallback if user data is missing
    session_destroy();
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

define('DASHBOARD_ROUTES', [
    'home' => [
        'file' => null,
        'path' => '/dashboard.php'
    ],
    'create_campaign' => [
        'file' => __DIR__ . '/campaigns/create.php',
        'path' => '/campaigns/create.php'
    ],
    'my_campaigns' => [
        'file' => __DIR__ . '/campaigns/index.php',
        'path' => '/campaigns/'
    ],
    'donations' => [
        'file' => __DIR__ . '/withdrawals/logs.php',
        'path' => '/donations/'
    ],
    'profile' => [
        'file' => __DIR__ . '/account/settings.php',
        'path' => '/profile/'
    ],
    'settings' => [
        'file' => __DIR__ . '/account/settings.php',
        'path' => '/settings/'
    ],
    'verification' => [
        'file' => __DIR__ . '/account/verification.php',
        'path' => '/verification/'
    ],
    'premium' => [
        'file' => __DIR__ . '/account/premium.php',
        'path' => '/account/premium.php'
    ],
    'withdrawals' => [
        'file' => __DIR__ . '/withdrawals/initiate.php',
        'path' => '/withdrawals/'
    ],
    'withdrawal_logs' => [
        'file' => __DIR__ . '/withdrawals/logs.php',
        'path' => '/withdrawal-logs/'
    ],
]);

function dashboardRouteUrl(string $routeName): string {
    $routes = DASHBOARD_ROUTES;
    if (!isset($routes[$routeName])) {
        return BASE_URL . '/dashboard.php';
    }

    return BASE_URL . $routes[$routeName]['path'];
}

function resolveDashboardRoute(?string $routeName): array {
    $routeName = $routeName !== null ? trim($routeName) : 'home';
    if ($routeName === '') {
        $routeName = 'home';
    }

    $routes = DASHBOARD_ROUTES;
    if (!array_key_exists($routeName, $routes)) {
        return [
            'name' => '404',
            'file' => __DIR__ . '/404.php',
            'is_home' => false,
            'is_valid' => false,
        ];
    }

    return [
        'name' => $routeName,
        'file' => $routes[$routeName]['file'],
        'path' => $routes[$routeName]['path'],
        'is_home' => $routeName === 'home',
        'is_valid' => true,
    ];
}

$currentDashboardRoute = resolveDashboardRoute($_GET['view'] ?? 'home');
$isDashboardAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($isDashboardAjaxRequest && !$currentDashboardRoute['is_home']) {
    require $currentDashboardRoute['file'];
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

        $endDate = $campaign['end_date'] ?? null;
        $campaign['formatted_end_date'] = !empty($endDate)
            ? date('M d, Y', strtotime($endDate))
            : 'No end date';
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
    
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    
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

        <main class="app-main flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <div id="dashboard-default-view" class="p-4 sm:p-6 lg:p-8 space-y-6 sm:space-y-8 <?php echo $currentDashboardRoute['is_home'] ? '' : 'hidden'; ?>">
                <?php renderTrustRiskBanner($trustRiskUi); ?>
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
                                <?php $createCampaignBlocked = trustRiskActionDisabled($trustRiskUi, 'create_campaign'); ?>
                                <?php if ($createCampaignBlocked): ?>
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-100 px-4 py-4 text-slate-500 shadow-sm opacity-80">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold">Create campaign</span>
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">Blocked</span>
                                        </div>
                                        <p class="mt-2 text-xs"><?php echo htmlspecialchars((string) ($trustRiskUi['message']['short'] ?? 'Unavailable right now.')); ?></p>
                                    </div>
                                <?php else: ?>
                                <a href="<?php echo dashboardRouteUrl('create_campaign'); ?>" data-dashboard-route="create_campaign" class="group rounded-2xl bg-slate-900 px-4 py-4 text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold">Create campaign</span>
                                        <svg class="h-5 w-5 text-emerald-300 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-300">Launch a new fundraiser in a few steps.</p>
                                </a>
                                <?php endif; ?>
                                <?php if (!trustRiskActionDisabled($trustRiskUi, 'dashboard_access')): ?>
                                <a href="<?php echo dashboardRouteUrl('my_campaigns'); ?>" data-dashboard-route="my_campaigns" class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-4 text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold">Manage campaigns</span>
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Review active, ended, and draft campaigns.</p>
                                </a>
                                <?php endif; ?>
                                <?php $withdrawBlocked = trustRiskActionDisabled($trustRiskUi, 'withdraw_funds'); ?>
                                <?php if ($withdrawBlocked): ?>
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-100 px-4 py-4 text-slate-500 shadow-sm opacity-80">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-semibold">Withdraw funds</span>
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">Blocked</span>
                                        </div>
                                        <p class="mt-2 text-xs"><?php echo htmlspecialchars((string) ($trustRiskUi['message']['short'] ?? 'Unavailable right now.')); ?></p>
                                    </div>
                                <?php else: ?>
                                <a href="<?php echo dashboardRouteUrl('withdrawals'); ?>" data-dashboard-route="withdrawals" class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-4 text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-white">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold">Withdraw funds</span>
                                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Move available funds when you’re ready.</p>
                                </a>
                                <?php endif; ?>
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
                        <button type="button" onclick="showDashboardUpdateForm(<?php echo !empty($recentCampaigns[0]['id']) ? (int) $recentCampaigns[0]['id'] : 0; ?>)" <?php echo (empty($recentCampaigns[0]['id']) || trustRiskActionDisabled($trustRiskUi, 'edit_campaign')) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50">
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
                            <a href="<?php echo dashboardRouteUrl('my_campaigns'); ?>" data-dashboard-route="my_campaigns" class="hidden sm:inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:text-emerald-700">
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
                                <?php if (trustRiskActionDisabled($trustRiskUi, 'create_campaign')): ?>
                                    <div class="flex items-center justify-between rounded-2xl border border-dashed border-slate-300 bg-slate-100 px-4 py-3 text-slate-500 opacity-80">
                                        <div>
                                            <p class="text-sm font-semibold">Start a new campaign</p>
                                            <p class="text-xs"><?php echo htmlspecialchars((string) ($trustRiskUi['message']['short'] ?? 'Unavailable right now.')); ?></p>
                                        </div>
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Blocked</span>
                                    </div>
                                <?php else: ?>
                                <a href="<?php echo dashboardRouteUrl('create_campaign'); ?>" data-dashboard-route="create_campaign" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Start a new campaign</p>
                                        <p class="text-xs text-slate-500">Launch a fresh fundraiser.</p>
                                    </div>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">New</span>
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo dashboardRouteUrl('verification'); ?>" data-dashboard-route="verification" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Check verification</p>
                                        <p class="text-xs text-slate-500">Review your account verification status.</p>
                                    </div>
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                                <a href="<?php echo dashboardRouteUrl('withdrawal_logs'); ?>" data-dashboard-route="withdrawal_logs" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-emerald-200 hover:bg-emerald-50/60">
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

            <div id="dashboard-ajax-view" class="<?php echo $currentDashboardRoute['is_home'] ? 'hidden' : ''; ?> p-3 sm:p-6">
                <?php if (!$currentDashboardRoute['is_home']): ?>
                    <?php require $currentDashboardRoute['file']; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="update-modal" class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
        <div class="flex min-h-screen items-center justify-center">
            <div class="w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-200 bg-gradient-to-br from-emerald-50 via-white to-slate-50 px-5 py-5 sm:px-6 sm:py-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 shadow-sm">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Campaign update</p>
                                <h3 class="mt-1 text-xl font-bold text-slate-900 sm:text-2xl">Post an update to supporters</h3>
                                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">Share progress, milestones, or important news so supporters stay informed and engaged.</p>
                            </div>
                        </div>
                        <button type="button" onclick="hideDashboardUpdateForm()" class="rounded-2xl border border-slate-200 bg-white p-2 text-slate-400 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <form id="dashboard-update-form" class="space-y-6 px-5 py-5 sm:px-6 sm:py-6">
                    <input type="hidden" id="update-campaign-id" name="campaign_id">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)); ?>">

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                            <h4 class="text-base font-semibold text-slate-900">Update details</h4>
                            <p class="mt-1 text-sm text-slate-500">Write a short, clear message that explains what changed.</p>
                        </div>
                        <div class="space-y-5 px-5 py-5 sm:px-6 sm:py-6">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Update title</label>
                                <input type="text" name="update_title" required
                                       class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/20"
                                       placeholder="e.g., Milestone reached!">
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Update content</label>
                                <textarea name="update_content" rows="5" required
                                          class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition-all focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/20 resize-none"
                                          placeholder="Share the latest progress, donor impact, or any important next step..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                            <h4 class="text-base font-semibold text-slate-900">Optional image</h4>
                            <p class="mt-1 text-sm text-slate-500">Add a supporting image to make the update more engaging.</p>
                        </div>
                        <div class="px-5 py-5 sm:px-6 sm:py-6">
                            <input type="file" name="update_image" accept="image/*"
                                   class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition-all file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                            <p class="mt-3 text-sm text-slate-500">Max size: 5MB. Supported formats: JPG, PNG, GIF.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-2 sm:flex-row sm:justify-end">
                        <button type="button" onclick="hideDashboardUpdateForm()"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700">
                            Post update
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

    async function loadDashboardSection(routeName, pushState = true) {
        const ajaxView = document.getElementById('dashboard-ajax-view');
        setDashboardAjaxMode(true);
        ajaxView.innerHTML = '<div class="bg-white rounded-lg shadow-sm p-6 text-gray-600">Loading...</div>';

        try {
            const response = await fetch(`${BASE_URL}/dashboard.php?view=${encodeURIComponent(routeName)}&ajax=1`, {
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
                const routeUrlMap = <?php echo json_encode(array_map(static fn ($route) => BASE_URL . $route['path'], DASHBOARD_ROUTES)); ?>;
                window.history.pushState({ dashboardRoute: routeName }, '', routeUrlMap[routeName] || `${BASE_URL}/dashboard.php`);
            }
        } catch (error) {
            console.error('Failed to load dashboard section:', error);
            ajaxView.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">Unable to load this section right now. Please try again.</div>';
        }
    }

    function showDashboardHome(pushState = true) {
        setDashboardAjaxMode(false);
        if (pushState) {
            window.history.pushState({ dashboardRoute: null }, '', `${BASE_URL}/dashboard.php`);
        }
    }

    function initializeDashboardLinks(scope = document) {
        scope.querySelectorAll('[data-dashboard-route]').forEach((link) => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const routeName = this.getAttribute('data-dashboard-route');
                if (!routeName || routeName === 'home') {
                    showDashboardHome();
                    return;
                }
                loadDashboardSection(routeName);
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

        const initialRoute = <?php echo json_encode($currentDashboardRoute['name']); ?>;
        if (initialRoute && initialRoute !== 'home' && initialRoute !== '404') {
            setDashboardAjaxMode(true);
            const ajaxView = document.getElementById('dashboard-ajax-view');
            executeInjectedScripts(ajaxView);
            initializeDashboardLinks(ajaxView);
        } else if (initialRoute === '404') {
            setDashboardAjaxMode(true);
            const ajaxView = document.getElementById('dashboard-ajax-view');
            initializeDashboardLinks(ajaxView);
        }

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
        const routeName = params.get('view');
        if (routeName && routeName !== 'home' && routeName !== initialRoute) {
            loadDashboardSection(routeName, false);
        }
    });

    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.dashboardRoute) {
            loadDashboardSection(event.state.dashboardRoute, false);
        } else {
            const params = new URLSearchParams(window.location.search);
            const routeName = params.get('view');
            if (routeName && routeName !== 'home') {
                loadDashboardSection(routeName, false);
            } else {
                showDashboardHome(false);
            }
        }
    });

    const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</body>
</html>

