<?php
// 1. Initialize session FIRST, before ANY output
session_start();

// 2. Require config files
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Campaign.php';
require_once __DIR__ . '/config/User.php';
require_once __DIR__ . '/config/Withdrawal.php';
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
        'path' => '/campaigns/create/'
    ],
    'my_campaigns' => [
        'file' => __DIR__ . '/campaigns/index.php',
        'path' => '/campaigns/'
    ],
    'donations' => [
        'file' => __DIR__ . '/donations/index.php',
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
$dashboardFinance = [
    'withdrawn' => 0.0,
    'balance' => 0.0,
    'available' => 0.0,
    'on_hold' => 0.0,
];

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
    $withdrawal = new Withdrawal();
    $totalDonations = (float) $campaign->getUserTotalDonations($userId);
    $heldDonations = (float) $campaign->getUserHeldDonations($userId);
    $withdrawableDonations = (float) $campaign->getUserWithdrawableDonations($userId);
    $totalWithdrawn = (float) $withdrawal->getTotalApprovedWithdrawals($userId);
    $pendingWithdrawals = (float) $withdrawal->getPendingWithdrawals($userId);
    $dashboardFinance = [
        'withdrawn' => $totalWithdrawn,
        'balance' => max(0, $totalDonations - $totalWithdrawn),
        'available' => max(0, $withdrawableDonations - $totalWithdrawn - $pendingWithdrawals),
        'on_hold' => $heldDonations,
    ];
} catch (Exception $e) {
    error_log("Error fetching dashboard payout summary: " . $e->getMessage());
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
                            50: '#f5ffe7',
                            100: '#eaffcc',
                            200: '#d9f9a5',
                            300: '#cafa8c',
                            400: '#aee871',
                            500: '#8bcf57',
                            600: '#3e7843',
                            700: '#274a32',
                            800: '#1f3e29',
                            900: '#193623',
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
            background: #f6f1dc;
        }
        .dashboard-home {
            color: #1c1c1c;
        }
        .dashboard-stat-card,
        .dashboard-panel {
            border-color: #e7e5e1;
            border-radius: 18px;
            box-shadow: none;
        }
        .dashboard-mobile-link[aria-current="page"] {
            color: #274a32;
            background: #f5ffe7;
        }
        .dashboard-mobile-link:focus-visible {
            outline: 2px solid #8bcf57;
            outline-offset: 2px;
        }
        .dashboard-mobile-nav {
            padding-bottom: max(env(safe-area-inset-bottom), 0.5rem);
        }
        body.dashboard-page {
            background: #f3f2ef;
            color: #1c1c1c;
        }
        body.dashboard-page .bg-gradient-to-r.from-emerald-600 {
            display: none;
        }
        body.dashboard-page .dashboard-app-header {
            left: 0;
            right: 0;
            width: auto;
            height: 72px;
            border-color: #e7e5e1;
            background: #fff;
            box-shadow: 0 1px 3px rgb(28 28 28 / 4%);
        }
        body.dashboard-page .dashboard-app-header > div {
            height: 100%;
            padding-left: 24px;
            padding-right: 24px;
        }
        body.dashboard-page .dashboard-app-header > div > div {
            display: flex;
            height: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        body.dashboard-page .dashboard-app-header > div > div > div:first-child {
            flex: 0 1 auto;
            justify-content: flex-start;
            gap: 24px;
        }
        body.dashboard-page #app-brand-link {
            position: static;
            transform: none;
            gap: 10px;
            padding: 0;
            color: #1c1c1c;
        }
        body.dashboard-page #app-brand-link > span:first-child {
            display: flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }
        body.dashboard-page #app-brand-link > span:last-child {
            display: inline;
            color: #274a32;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        body.dashboard-page .dashboard-app-header > div > div > div:nth-child(2) {
            display: none;
        }
        body.dashboard-page .dashboard-app-header > div > div > div:last-child {
            flex: 0 0 auto;
            gap: 12px;
        }
        body.dashboard-page #user-menu-button {
            min-height: 42px;
            gap: 9px;
            padding: 4px 10px 4px 5px;
            border: 1px solid #e7e5e1;
            border-radius: 12px;
            background: #fff;
            color: #292524;
            font-size: 0.8125rem;
            font-weight: 600;
            transition: border-color 150ms ease, background-color 150ms ease;
        }
        body.dashboard-page #user-menu-button:hover {
            border-color: #d6d3d1;
            background: #fafaf9;
        }
        body.dashboard-page #user-menu-button:focus-visible {
            outline: 2px solid #7c3aed;
            outline-offset: 2px;
        }
        body.dashboard-page #user-menu-button img {
            width: 32px;
            height: 32px;
            border: 0;
            box-shadow: 0 0 0 1px #e7e5e1;
        }
        body.dashboard-page #user-menu-button > span {
            display: inline-block;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        body.dashboard-page #user-menu-button > svg {
            display: block;
            color: #78716c;
        }
        body.dashboard-page #user-menu {
            top: calc(100% + 8px);
            margin-top: 0;
            border: 1px solid #e7e5e1;
            border-radius: 12px;
            box-shadow: 0 12px 28px rgb(28 28 28 / 12%);
        }
        body.dashboard-page #user-menu a {
            padding: 10px 14px;
            font-size: 0.8125rem;
        }
        body.dashboard-page #sidebar {
            width: 280px !important;
            top: 72px;
            bottom: 0;
            border: 0;
            background: #f3f2ef;
            padding-top: 0;
        }
        body.dashboard-page #sidebar > .overflow-y-auto {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        body.dashboard-page #sidebar > .overflow-y-auto::-webkit-scrollbar {
            display: none;
        }
        body.dashboard-page #sidebar .flex-1.space-y-4 > .flex.items-center.gap-3,
        body.dashboard-page #sidebar .sidebar-section-title {
            display: none;
        }
        body.dashboard-page #sidebar .flex-1.space-y-4 {
            padding: 24px 18px;
        }
        body.dashboard-page #sidebar .flex-1.space-y-4 > .space-y-1 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0.125rem;
        }
        body.dashboard-page #sidebar .sidebar-link {
            min-height: 64px;
            gap: 14px;
            border-radius: 999px;
            padding: 6px 10px;
            color: #1c1c1c;
            font-size: 0.9rem;
            font-weight: 700;
        }
        body.dashboard-page #sidebar .sidebar-link > svg,
        body.dashboard-page #sidebar .sidebar-link > span:first-child > svg {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border-radius: 50%;
            background: #eceae6;
            padding: 15px;
            color: #292524;
        }
        body.dashboard-page #sidebar .sidebar-link.bg-emerald-50 {
            background: #ece7f8;
            color: #1c1c1c;
        }
        body.dashboard-page #sidebar .sidebar-link.bg-emerald-50 > svg {
            background: #ddd4f5;
            color: #4c1d95;
        }
        body.dashboard-page #sidebar-toggle {
            display: none;
        }
        body.dashboard-page .app-main {
            flex: 1 1 0%;
            min-width: 0;
            margin-left: 0 !important;
            width: auto;
            background: #f3f2ef;
        }
        body.dashboard-page.sidebar-collapsed .app-main {
            margin-left: 0 !important;
        }
        body.dashboard-page.sidebar-collapsed #sidebar {
            width: 280px !important;
        }
        body.dashboard-page.sidebar-collapsed #sidebar .sidebar-label {
            opacity: 1;
            width: auto;
            max-width: none;
            margin-left: 0.75rem;
            overflow: visible;
            pointer-events: auto;
        }
        .dashboard-payout-summary {
            background: #f6f1dc;
        }
        .dashboard-campaign-row + .dashboard-campaign-row {
            border-top: 14px solid #f3f2ef;
            padding-top: 14px;
        }
        @media (max-width: 1023px) {
            body.dashboard-page > .flex.min-h-screen {
                padding-left: 0;
                padding-top: 64px;
            }
            body.dashboard-page .dashboard-app-header {
                left: 0;
                height: 64px;
            }
            body.dashboard-page #sidebar {
                top: 0;
                padding-top: 64px;
            }
            body.dashboard-page .dashboard-app-header > div {
                padding-left: 16px;
                padding-right: 16px;
            }
            body.dashboard-page .dashboard-app-header > div > div > div:first-child {
                position: static;
                flex: 1 1 100%;
                justify-content: center;
            }
            body.dashboard-page #app-brand-link {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }
            body.dashboard-page #app-brand-link > span:first-child {
                width: 34px;
                height: 34px;
            }
            body.dashboard-page #app-brand-link > span:first-child svg {
                width: 34px;
                height: 34px;
            }
            body.dashboard-page #app-brand-link > span:last-child {
                color: #274a32;
                font-size: 0.9rem;
            }
            body.dashboard-page .dashboard-app-header > div > div > div:nth-child(2) {
                position: absolute;
                right: 16px;
                display: flex;
                width: auto;
                flex: 0 0 auto;
                justify-content: flex-end;
            }
            body.dashboard-page .dashboard-app-header > div > div > div:nth-child(2) img {
                width: 34px;
                height: 34px;
                border: 0;
                border-radius: 50%;
                box-shadow: 0 0 0 1px #e7e5e1;
            }
            body.dashboard-page .dashboard-app-header > div > div > div:last-child {
                display: none;
            }
            body.dashboard-page #sidebar-toggle,
            body.dashboard-page #sidebar,
            body.dashboard-page .sidebar-overlay {
                display: none !important;
            }
            body.dashboard-page .app-main {
                flex: 1 1 100%;
                margin-left: 0 !important;
                width: 100%;
            }
            body.dashboard-page .dashboard-home {
                padding: 14px;
                padding-bottom: 6rem;
            }
            body.dashboard-page .dashboard-home > :not([hidden]) ~ :not([hidden]) {
                margin-top: 1rem;
            }
            body.dashboard-page .dashboard-payout-summary {
                gap: 10px;
                padding: 16px;
                border-radius: 16px;
            }
            body.dashboard-page .dashboard-stat-card {
                padding: 12px;
                border-radius: 14px;
            }
            body.dashboard-page .dashboard-mobile-nav {
                min-height: 64px;
                padding-top: 6px;
                background: #fff;
                box-shadow: 0 -3px 14px rgb(28 28 28 / 5%);
            }
        }
        @media (min-width: 1024px) {
            body.dashboard-page > .flex.min-h-screen {
                padding-left: 280px;
                padding-top: 72px;
            }
            body.dashboard-page .dashboard-app-header > div > div > div:first-child {
                position: static;
            }
        }
        body.dashboard-page .dashboard-home .text-xs {
            font-size: 0.6875rem;
            line-height: 1rem;
        }
        body.dashboard-page .dashboard-home .text-sm {
            font-size: 0.8125rem;
            line-height: 1.25rem;
        }
        body.dashboard-page .dashboard-home .text-base {
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        body.dashboard-page .dashboard-home .text-lg {
            font-size: 1rem;
            line-height: 1.5rem;
        }
        body.dashboard-page .dashboard-home .text-2xl {
            font-size: 1.375rem;
            line-height: 1.875rem;
        }
        body.dashboard-page .dashboard-home .text-3xl {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        .dashboard-ajax-content {
            padding-bottom: 6rem;
        }
        @media (max-width: 1023px) {
            .dashboard-home {
                padding-bottom: 6rem;
            }
        }
        @media (min-width: 1024px) {
            .dashboard-home {
                max-width: 1440px;
                margin-inline: auto;
            }
            .dashboard-ajax-content {
                padding-bottom: 1.5rem;
            }
        }
    </style>
    
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
</head>
<body class="dashboard-page bg-gray-50">
    <div class="flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include 'components/sidebar.php'; ?>

        <main class="app-main flex-1 min-w-0 overflow-auto bg-[#f3f2ef] lg:ml-64">
            <div id="dashboard-default-view" class="dashboard-home p-4 sm:p-5 lg:p-6 space-y-6 sm:space-y-7 <?php echo $currentDashboardRoute['is_home'] ? '' : 'hidden'; ?>">
                <?php renderTrustRiskBanner($trustRiskUi); ?>
                <section class="dashboard-payout-summary flex flex-col gap-3 rounded-2xl px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                    <div class="shrink-0">
                        <h1 class="text-lg font-bold text-slate-900">Welcome, <?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></h1>
                        <p class="text-sm text-slate-600">Your fundraising at a glance</p>
                    </div>
                    <p class="max-w-2xl text-sm leading-6 text-slate-700">
                        Funds are available after the hold period. Withdrawals may require account checks.
                    </p>
                    <?php if (!trustRiskActionDisabled($trustRiskUi, 'withdraw_funds')): ?>
                        <a href="<?php echo dashboardRouteUrl('withdrawals'); ?>" data-dashboard-route="withdrawals" class="inline-flex shrink-0 items-center justify-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Withdraw funds</a>
                    <?php endif; ?>
                </section>

                <section class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" aria-label="Payout summary">
                    <article class="dashboard-stat-card rounded-2xl border bg-white p-4 sm:p-5">
                        <p class="text-sm text-slate-600">Withdrawn</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">$<?php echo number_format($dashboardFinance['withdrawn'], 2); ?></p>
                        <p class="mt-1 text-xs text-slate-500">Paid out</p>
                    </article>
                    <article class="dashboard-stat-card rounded-2xl border bg-white p-4 sm:p-5">
                        <p class="text-sm text-slate-600">Balance</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">$<?php echo number_format($dashboardFinance['balance'], 2); ?></p>
                        <p class="mt-1 text-xs text-slate-500">After withdrawals</p>
                    </article>
                    <article class="dashboard-stat-card rounded-2xl border bg-white p-4 sm:p-5">
                        <p class="text-sm text-slate-600">Available</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">$<?php echo number_format($dashboardFinance['available'], 2); ?></p>
                        <p class="mt-1 text-xs text-slate-500">Ready to withdraw</p>
                    </article>
                    <article class="dashboard-stat-card rounded-2xl border bg-white p-4 sm:p-5">
                        <p class="text-sm text-slate-600">On hold</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">$<?php echo number_format($dashboardFinance['on_hold'], 2); ?></p>
                        <p class="mt-1 text-xs text-slate-500">In hold period</p>
                    </article>
                </section>

                <section class="space-y-3" aria-labelledby="dashboard-campaigns-heading">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 id="dashboard-campaigns-heading" class="text-lg font-bold text-slate-900">Your campaigns</h2>
                        </div>
                        <?php if (trustRiskActionDisabled($trustRiskUi, 'create_campaign')): ?>
                            <span class="rounded-full bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-800">Campaign creation unavailable</span>
                        <?php else: ?>
                            <a href="<?php echo dashboardRouteUrl('create_campaign'); ?>" data-dashboard-route="create_campaign" class="inline-flex shrink-0 items-center justify-center rounded-full bg-[#4c1d95] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#3b1675]">Create campaign</a>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($recentCampaigns)): ?>
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center">
                            <h3 class="font-semibold text-slate-900">No campaigns yet</h3>
                            <p class="mt-1 text-sm text-slate-600">Start a campaign to raise funds.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recentCampaigns as $recentCampaign): ?>
                                <?php
                                $campaignId = (int) $recentCampaign['id'];
                                $campaignTitle = (string) ($recentCampaign['title'] ?? 'Campaign');
                                $campaignImage = !empty($recentCampaign['image_path'])
                                    ? BASE_URL . '/' . ltrim((string) $recentCampaign['image_path'], '/')
                                    : '';
                                $campaignRaised = (float) ($recentCampaign['current_amount'] ?? 0);
                                $campaignGoal = (float) ($recentCampaign['goal_amount'] ?? 0);
                                $campaignProgress = $campaignGoal > 0
                                    ? min(100, max(0, (float) $recentCampaign['progress']))
                                    : 0;
                                ?>
                                <article class="dashboard-campaign-row flex flex-col gap-4 rounded-2xl bg-transparent sm:flex-row sm:items-center">
                                    <a href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo $campaignId; ?>" class="block shrink-0 overflow-hidden rounded-xl bg-[#e7e5e1] sm:w-40" aria-label="View <?php echo htmlspecialchars($campaignTitle); ?>">
                                        <?php if ($campaignImage !== ''): ?>
                                            <img src="<?php echo htmlspecialchars($campaignImage); ?>" alt="" class="h-40 w-full object-cover sm:h-28" onerror="this.style.display='none'">
                                        <?php else: ?>
                                            <span class="flex h-40 w-full items-center justify-center text-slate-400 sm:h-28" aria-hidden="true">
                                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16l5-5 4 4 4-6 5 7M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <a href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo $campaignId; ?>" class="font-bold leading-6 text-slate-900 hover:text-emerald-700"><?php echo htmlspecialchars($campaignTitle); ?></a>
                                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600"><?php echo htmlspecialchars(ucfirst((string) ($recentCampaign['status'] ?? 'active'))); ?></span>
                                        </div>
                                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Campaign funding progress" aria-valuenow="<?php echo (int) round($campaignProgress); ?>" aria-valuemin="0" aria-valuemax="100">
                                            <div class="h-full rounded-full bg-emerald-500" style="width: <?php echo $campaignProgress; ?>%"></div>
                                        </div>
                                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-sm text-slate-700">
                                            <span>$<?php echo number_format($campaignRaised, 2); ?> raised of $<?php echo number_format($campaignGoal, 2); ?></span>
                                            <div class="flex items-center gap-3 text-xs font-semibold">
                                                <a href="<?php echo BASE_URL; ?>/campaigns/edit.php?id=<?php echo $campaignId; ?>" class="text-slate-600 hover:text-emerald-700">Edit</a>
                                                <button type="button" class="text-slate-600 hover:text-emerald-700" onclick="showDashboardUpdateForm(<?php echo $campaignId; ?>)" <?php echo trustRiskActionDisabled($trustRiskUi, 'edit_campaign') ? 'disabled' : ''; ?>>Post update</button>
                                                <button type="button" class="text-slate-600 hover:text-emerald-700" onclick="copyShareLink(<?php echo $campaignId; ?>, '')">Share</button>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <?php if (false): ?>
                <div hidden>
                <section class="dashboard-glow relative overflow-hidden rounded-3xl border border-[#e7e5e1]">
                    <div class="relative px-5 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
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
                    <div class="dashboard-stat-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
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

                    <div class="dashboard-stat-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
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

                    <div class="dashboard-stat-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
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

                    <div class="dashboard-stat-card rounded-2xl border border-slate-200 bg-slate-900 p-5 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <p class="text-sm font-medium text-slate-300">Next best action</p>
                        <p class="mt-3 text-xl font-semibold">Grow your momentum</p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Publish updates, share campaign links, and keep supporters informed to build trust and improve conversions.</p>
                        <button type="button" onclick="showDashboardUpdateForm(<?php echo !empty($recentCampaigns[0]['id']) ? (int) $recentCampaigns[0]['id'] : 0; ?>)" <?php echo (empty($recentCampaigns[0]['id']) || trustRiskActionDisabled($trustRiskUi, 'edit_campaign')) ? 'disabled' : ''; ?> class="mt-4 inline-flex items-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50">
                            Add update now
                        </button>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.8fr),minmax(320px,1fr)]">
                    <div class="dashboard-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                        <div class="dashboard-campaigns p-4 sm:p-6">
                            <?php include 'components/campaign-cards.php'; ?>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
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

                        <div class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
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
                <?php endif; ?>
            </div>

            <div id="dashboard-ajax-view" class="dashboard-ajax-content <?php echo $currentDashboardRoute['is_home'] ? 'hidden' : ''; ?> p-3 sm:p-6">
                <?php if (!$currentDashboardRoute['is_home']): ?>
                    <?php
                    if (in_array($currentDashboardRoute['name'], ['create_campaign', 'my_campaigns', 'donations'], true)) {
                        $dashboardOriginalAjaxParam = $_GET['ajax'] ?? null;
                        $_GET['ajax'] = '1';
                        try {
                            require $currentDashboardRoute['file'];
                        } finally {
                            if ($dashboardOriginalAjaxParam === null) {
                                unset($_GET['ajax']);
                            } else {
                                $_GET['ajax'] = $dashboardOriginalAjaxParam;
                            }
                        }
                    } else {
                        require $currentDashboardRoute['file'];
                    }
                    ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <nav class="dashboard-mobile-nav fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-[#e7e5e1] bg-[#f7f6f4] px-2 pt-2 pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Dashboard navigation">
        <a href="<?php echo dashboardRouteUrl('home'); ?>" data-dashboard-home="true" data-dashboard-mobile-route="home" class="dashboard-mobile-link flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition" aria-current="<?php echo $currentDashboardRoute['is_home'] ? 'page' : 'false'; ?>">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
            Home
        </a>
        <a href="<?php echo dashboardRouteUrl('my_campaigns'); ?>" data-dashboard-route="my_campaigns" data-dashboard-mobile-route="my_campaigns" class="dashboard-mobile-link flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition" aria-current="<?php echo in_array($currentDashboardRoute['name'], ['my_campaigns', 'create_campaign'], true) ? 'page' : 'false'; ?>">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm7 0v5h5M9 13h6m-6 4h6"/></svg>
            Campaigns
        </a>
        <a href="<?php echo dashboardRouteUrl('donations'); ?>" data-dashboard-route="donations" data-dashboard-mobile-route="donations" class="dashboard-mobile-link flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition" aria-current="<?php echo $currentDashboardRoute['name'] === 'donations' || $currentDashboardRoute['name'] === 'withdrawal_logs' ? 'page' : 'false'; ?>">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/></svg>
            Donations
        </a>
        <a href="<?php echo dashboardRouteUrl('profile'); ?>" data-dashboard-route="profile" data-dashboard-mobile-route="profile" class="dashboard-mobile-link flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition" aria-current="<?php echo in_array($currentDashboardRoute['name'], ['profile', 'settings'], true) ? 'page' : 'false'; ?>">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
            Account
        </a>
    </nav>

    <div id="update-modal" class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
        <div class="flex min-h-screen items-center justify-center">
            <div class="w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                <div class="border-b border-slate-200 bg-white px-5 py-5 sm:px-6 sm:py-6">
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

    function updateDashboardMobileNav(routeName) {
        const activeRoute = ['settings', 'create_campaign'].includes(routeName)
            ? (routeName === 'settings' ? 'profile' : 'my_campaigns')
            : routeName;

        document.querySelectorAll('[data-dashboard-mobile-route]').forEach((link) => {
            const linkRoute = link.getAttribute('data-dashboard-mobile-route');
            const isActive = linkRoute === activeRoute
                || (linkRoute === 'donations' && routeName === 'withdrawal_logs');

            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.setAttribute('aria-current', 'false');
            }
        });
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

    const dashboardRouteUrlMap = <?php echo json_encode(array_map(static fn ($route) => BASE_URL . $route['path'], DASHBOARD_ROUTES)); ?>;

    async function loadDashboardSection(routeName, pushState = true, routeParams = new URLSearchParams()) {
        const ajaxView = document.getElementById('dashboard-ajax-view');
        const requestParams = new URLSearchParams(routeParams);
        requestParams.set('view', routeName);
        requestParams.set('ajax', '1');
        setDashboardAjaxMode(true);
        ajaxView.innerHTML = '<div class="bg-white rounded-lg shadow-sm p-6 text-gray-600">Loading...</div>';

        try {
            const response = await fetch(`${BASE_URL}/dashboard.php?${requestParams.toString()}`, {
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
            updateDashboardMobileNav(routeName);

            if (pushState) {
                const historyParams = new URLSearchParams(routeParams);
                historyParams.delete('view');
                historyParams.delete('ajax');
                const query = historyParams.toString();
                const routeUrl = dashboardRouteUrlMap[routeName] || `${BASE_URL}/dashboard.php`;
                window.history.pushState({ dashboardRoute: routeName }, '', query ? `${routeUrl}?${query}` : routeUrl);
            }
        } catch (error) {
            console.error('Failed to load dashboard section:', error);
            ajaxView.innerHTML = '<div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">Unable to load this section right now. Please try again.</div>';
        }
    }

    function showDashboardHome(pushState = true) {
        setDashboardAjaxMode(false);
        updateDashboardMobileNav('home');
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

        scope.querySelectorAll('[data-dashboard-filter-form]').forEach((form) => {
            form.addEventListener('submit', function(event) {
                if (!document.body.classList.contains('dashboard-page')) {
                    return;
                }

                event.preventDefault();
                const routeName = form.getAttribute('data-dashboard-filter-form');
                if (!routeName) {
                    return;
                }

                const params = new URLSearchParams(new FormData(form));
                loadDashboardSection(routeName, true, params);
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
        updateDashboardMobileNav(initialRoute);
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
            const params = new URLSearchParams(window.location.search);
            params.delete('view');
            params.delete('ajax');
            loadDashboardSection(event.state.dashboardRoute, false, params);
        } else {
            const params = new URLSearchParams(window.location.search);
            const routePath = window.location.pathname.replace(/\/+$/, '') || '/';
            const pathRoute = Object.entries(dashboardRouteUrlMap).find(([, routeUrl]) => {
                const mappedPath = new URL(routeUrl, window.location.origin).pathname.replace(/\/+$/, '') || '/';
                return mappedPath === routePath;
            });
            const routeName = params.get('view') || pathRoute?.[0];
            if (routeName && routeName !== 'home') {
                params.delete('view');
                params.delete('ajax');
                loadDashboardSection(routeName, false, params);
            } else {
                showDashboardHome(false);
            }
        }
    });

    const BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
</body>
</html>
