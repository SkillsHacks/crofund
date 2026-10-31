<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/User.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$campaign = new Campaign();
$user = new User();

$status = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$search = $_GET['search'] ?? '';

$userCampaigns = $campaign->getUserCampaigns(
    $_SESSION['user_id'],
    $status,
    $sort,
    $search
);

$stats = $campaign->getUserCampaignStats($_SESSION['user_id']);
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($isAjaxRequest) {
    ob_start();
}

function renderCampaignsIndexContent(array $userCampaigns, array $stats, string $status, string $sort, string $search): void {
?>
<div class="w-full px-3 py-4 pb-24 sm:px-5 sm:py-5 lg:px-6 lg:py-6 lg:pb-6">
    <div class="mx-auto w-full max-w-[1440px] space-y-4">
        <section class="rounded-2xl border border-[#e7e5e1] bg-white">
            <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Your campaigns</h1>
                </div>
                <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-[#4c1d95] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#3b1675] focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create campaign
                    </a>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Campaign summary">
            <div class="rounded-2xl border border-[#e7e5e1] bg-white p-3 sm:p-4">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">Total campaigns</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900"><?php echo (int) ($stats['total'] ?? 0); ?></p>
            </div>
            <div class="rounded-2xl border border-[#e7e5e1] bg-white p-3 sm:p-4">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">Active</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-700"><?php echo (int) ($stats['active'] ?? 0); ?></p>
            </div>
            <div class="rounded-2xl border border-[#e7e5e1] bg-white p-3 sm:p-4">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">Total raised</p>
                <p class="mt-2 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">$<?php echo number_format((float) ($stats['total_raised'] ?? 0), 2); ?></p>
            </div>
            <div class="rounded-2xl border border-[#e7e5e1] bg-white p-3 sm:p-4">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">Success rate</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900"><?php echo (float) ($stats['success_rate'] ?? 0); ?>%</p>
            </div>
        </section>

        <section class="rounded-2xl border border-[#e7e5e1] bg-white">
            <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
                <h2 class="text-base font-semibold text-slate-900">Find a campaign</h2>
            </div>
            <div class="px-4 py-4 sm:px-5">
                <form action="<?php echo BASE_URL; ?>/campaigns/index.php" data-dashboard-filter-form="my_campaigns" class="grid grid-cols-2 gap-3 xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)_minmax(0,1fr)_auto]">
                    <div class="col-span-2 xl:col-span-1">
                        <label for="campaign-search" class="block text-xs font-semibold text-slate-700">Search</label>
                        <input id="campaign-search" type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Campaign name"
                               class="mt-1.5 block min-h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/15">
                    </div>
                    <div>
                        <label for="campaign-status" class="block text-xs font-semibold text-slate-700">Status</label>
                        <select id="campaign-status" name="status" class="mt-1.5 block min-h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/15">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="ended" <?php echo $status === 'ended' ? 'selected' : ''; ?>>Ended</option>
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                    <div>
                        <label for="campaign-sort" class="block text-xs font-semibold text-slate-700">Sort by</label>
                        <select id="campaign-sort" name="sort" class="mt-1.5 block min-h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/15">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="most_funded" <?php echo $sort === 'most_funded' ? 'selected' : ''; ?>>Most Funded</option>
                            <option value="ending_soon" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                        </select>
                    </div>
                    <button type="submit" class="col-span-2 inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 xl:col-span-1 xl:self-end">Apply</button>
                </form>
            </div>
        </section>

        <?php if (empty($userCampaigns)): ?>
            <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-8 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <h3 class="mt-3 text-base font-semibold text-slate-900">No campaigns yet</h3>
                <p class="mt-1 text-sm text-slate-500">Create your first campaign to get started.</p>
                <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="mt-4 inline-flex min-h-10 items-center justify-center rounded-xl bg-[#4c1d95] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#3b1675]">
                    Create campaign
                </a>
            </section>
        <?php else: ?>
            <section class="grid grid-cols-1 gap-4 xl:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($userCampaigns as $campaignItem): ?>
                    <?php
                    $percentage = 0;
                    if ((float) $campaignItem['goal_amount'] > 0) {
                        $percentage = min(100, ((float) $campaignItem['current_amount'] / (float) $campaignItem['goal_amount']) * 100);
                    }
                    ?>
                    <article class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                        <div class="relative h-44 overflow-hidden bg-slate-100 sm:h-48">
                            <img src="<?php echo $campaignItem['image_path'] ? BASE_URL . '/' . $campaignItem['image_path'] : BASE_URL . '/assets/images/default-campaign.jpg'; ?>"
                                 alt="<?php echo htmlspecialchars($campaignItem['title']); ?>"
                                 class="h-full w-full object-cover"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-3 bg-slate-950/70 px-4 py-3">
                                    <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-800">
                                        <?php echo round($percentage); ?>% funded
                                    </span>
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">
                                        <?php echo htmlspecialchars(ucfirst($campaignItem['status'] ?? 'active')); ?>
                                    </span>
                            </div>
                        </div>
                        <div class="p-4 sm:p-5">
                            <h3 class="line-clamp-2 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($campaignItem['title']); ?></h3>
                            <div class="mt-3">
                                <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-slate-700">$<?php echo number_format((float) $campaignItem['current_amount'], 2); ?></span>
                                    <span class="text-slate-500">Goal: $<?php echo number_format((float) $campaignItem['goal_amount'], 2); ?></span>
                                </div>
                                <div class="h-2.5 rounded-full bg-slate-100">
                                    <div class="h-2.5 rounded-full bg-emerald-500" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-3 gap-2">
                                <a href="view.php?id=<?php echo $campaignItem['id']; ?>" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-emerald-50 px-2 py-2 text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100 sm:text-sm">
                                    View
                                </a>
                                <a href="edit.php?id=<?php echo $campaignItem['id']; ?>" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:text-sm">
                                    Edit
                                </a>
                                <button type="button" onclick="copyShareLink(<?php echo $campaignItem['id']; ?>, '<?php echo htmlspecialchars(addslashes($campaignItem['title'])); ?>')" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:text-sm">
                                    Share
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</div>

<div id="share-toast" class="fixed bottom-4 right-4 z-50 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white shadow-lg transform translate-y-full opacity-0 transition-all duration-300">
    Link copied to clipboard!
</div>

<script>
function copyShareLink(campaignId, campaignTitle) {
    const shareUrl = `${window.CROFUND_BASE_URL}/campaigns/public-view.php?id=${campaignId}`;

    navigator.clipboard.writeText(shareUrl).then(() => {
        const toast = document.getElementById('share-toast');
        if (!toast) return;
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';

        setTimeout(() => {
            toast.style.transform = 'translateY(100%)';
            toast.style.opacity = '0';
        }, 2000);
    });
}

window.CROFUND_BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<?php
}

if (!$isAjaxRequest) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Campaigns - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        body.campaigns-index-page {
            background: #f3f2ef;
            color: #1c1c1c;
        }

        body.campaigns-index-page > .bg-gradient-to-r.from-emerald-600 {
            display: none;
        }

        body.campaigns-index-page .dashboard-app-header {
            height: 72px;
            border-color: #e7e5e1;
            background: #fff;
            box-shadow: 0 1px 3px rgb(28 28 28 / 4%);
        }

        body.campaigns-index-page .dashboard-app-header > div {
            height: 100%;
            padding: 0 24px;
        }

        body.campaigns-index-page .dashboard-app-header > div > div {
            display: flex;
            height: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        body.campaigns-index-page .dashboard-app-header > div > div > div:first-child {
            flex: 0 1 auto;
            justify-content: flex-start;
            gap: 24px;
        }

        body.campaigns-index-page #app-brand-link {
            position: static;
            gap: 10px;
            padding: 0;
            color: #1c1c1c;
            transform: none;
        }

        body.campaigns-index-page #app-brand-link > span:first-child {
            display: flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        body.campaigns-index-page #app-brand-link > span:last-child {
            display: inline;
            color: #047857;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        body.campaigns-index-page .dashboard-app-header > div > div > div:nth-child(2) {
            display: none;
        }

        body.campaigns-index-page .dashboard-app-header > div > div > div:last-child {
            flex: 0 0 auto;
            gap: 12px;
        }

        body.campaigns-index-page #user-menu-button {
            min-height: 42px;
            gap: 9px;
            padding: 4px 10px 4px 5px;
            border: 1px solid #e7e5e1;
            border-radius: 12px;
            background: #fff;
            color: #292524;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        body.campaigns-index-page #user-menu-button img {
            width: 32px;
            height: 32px;
            border: 0;
            box-shadow: 0 0 0 1px #e7e5e1;
        }

        body.campaigns-index-page #sidebar {
            top: 72px;
            bottom: 0;
            width: 280px;
            border: 0;
            background: #f3f2ef;
            padding-top: 0;
        }

        body.campaigns-index-page #sidebar .flex-1.space-y-4 > .flex.items-center.gap-3,
        body.campaigns-index-page #sidebar .sidebar-section-title {
            display: none;
        }

        body.campaigns-index-page #sidebar .flex-1.space-y-4 {
            padding: 24px 18px;
        }

        body.campaigns-index-page #sidebar .flex-1.space-y-4 > .space-y-1 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0.125rem;
        }

        body.campaigns-index-page #sidebar .sidebar-link {
            min-height: 64px;
            gap: 14px;
            border-radius: 999px;
            padding: 6px 10px;
            color: #1c1c1c;
            font-size: 0.9rem;
            font-weight: 700;
        }

        body.campaigns-index-page #sidebar .sidebar-link > svg,
        body.campaigns-index-page #sidebar .sidebar-link > span:first-child > svg {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border-radius: 50%;
            background: #eceae6;
            padding: 15px;
            color: #292524;
        }

        body.campaigns-index-page #sidebar .sidebar-link.bg-emerald-50 {
            background: #ece7f8;
            color: #1c1c1c;
        }

        body.campaigns-index-page #sidebar .sidebar-link.bg-emerald-50 > svg {
            background: #ddd4f5;
            color: #4c1d95;
        }

        body.campaigns-index-page #sidebar-toggle,
        body.campaigns-index-page .sidebar-overlay {
            display: none !important;
        }

        body.campaigns-index-page .campaigns-index-shell {
            padding: 72px 0 0 280px;
        }

        body.campaigns-index-page .app-main {
            margin-left: 0 !important;
            width: auto;
            background: #f3f2ef;
        }

        @media (max-width: 1023px) {
            body.campaigns-index-page .dashboard-app-header {
                height: 64px;
            }

            body.campaigns-index-page .dashboard-app-header > div {
                padding-right: 16px;
                padding-left: 16px;
            }

            body.campaigns-index-page .dashboard-app-header > div > div > div:first-child {
                position: static;
                flex: 1 1 100%;
                justify-content: center;
            }

            body.campaigns-index-page #app-brand-link {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }

            body.campaigns-index-page #app-brand-link > span:first-child,
            body.campaigns-index-page #app-brand-link > span:first-child svg {
                width: 34px;
                height: 34px;
            }

            body.campaigns-index-page .dashboard-app-header > div > div > div:nth-child(2) {
                position: absolute;
                right: 16px;
                display: flex;
                width: auto;
                flex: 0 0 auto;
                justify-content: flex-end;
            }

            body.campaigns-index-page .dashboard-app-header > div > div > div:nth-child(2) img {
                width: 34px;
                height: 34px;
                border: 0;
                border-radius: 50%;
                box-shadow: 0 0 0 1px #e7e5e1;
            }

            body.campaigns-index-page .dashboard-app-header > div > div > div:last-child {
                display: none;
            }

            body.campaigns-index-page #sidebar {
                display: none;
            }

            body.campaigns-index-page .campaigns-index-shell {
                display: block;
                padding: 64px 0 0;
            }

            body.campaigns-index-page .app-main {
                display: block;
                width: 100%;
            }

            body.campaigns-index-page .dashboard-mobile-nav {
                min-height: 64px;
                padding-top: 6px;
                background: #fff;
                box-shadow: 0 -3px 14px rgb(28 28 28 / 5%);
            }
        }
    </style>
</head>
<body class="campaigns-index-page min-h-screen bg-[#f3f2ef]">
    <?php $useDashboardShell = true; ?>
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="campaigns-index-shell flex min-h-screen">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main min-w-0 flex-1 overflow-auto bg-[#f3f2ef]">
            <?php renderCampaignsIndexContent($userCampaigns, $stats, $status, $sort, $search); ?>
        </main>
    </div>
    <nav class="dashboard-mobile-nav fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-[#e7e5e1] bg-white px-2 pt-2 pb-[env(safe-area-inset-bottom)] shadow-[0_-3px_14px_rgba(28,28,28,0.05)] lg:hidden" aria-label="Dashboard navigation">
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
            Home
        </a>
        <a href="<?php echo BASE_URL; ?>/campaigns/" aria-current="page" class="flex flex-col items-center gap-1 rounded-xl bg-[#ece7f8] px-2 py-2 text-[11px] font-semibold text-[#4c1d95] transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm7 0v5h5M9 13h6m-6 4h6"/></svg>
            Campaigns
        </a>
        <a href="<?php echo BASE_URL; ?>/donations/" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/></svg>
            Donations
        </a>
        <a href="<?php echo BASE_URL; ?>/profile/" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
            Account
        </a>
    </nav>
</body>
</html>
<?php
} else {
    renderCampaignsIndexContent($userCampaigns, $stats, $status, $sort, $search);
    echo ob_get_clean();
}
