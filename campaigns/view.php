<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/Campaign.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/campaigns/');
    exit();
}

$campaign = new Campaign();
$campaignDetails = $campaign->getCampaignById($_GET['id']);

if (!$campaignDetails) {
    $_SESSION['error'] = 'Campaign not found';
    header('Location: ' . BASE_URL . '/campaigns/');
    exit();
}

$isOwner = (int) $campaignDetails['user_id'] === (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!$isOwner) {
        $_SESSION['error'] = "You don't have permission to delete this campaign";
        header('Location: ' . BASE_URL . '/campaigns/');
        exit();
    }

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid request token';
        header('Location: ' . BASE_URL . '/campaigns/view.php?id=' . (int) $campaignDetails['id']);
        exit();
    }

    if ($campaign->deleteCampaign($campaignDetails['id'])) {
        $_SESSION['success'] = 'Campaign deleted successfully';
        header('Location: ' . BASE_URL . '/campaigns/');
        exit();
    }

    $_SESSION['error'] = 'Failed to delete campaign';
}

$currentAmount = (float) ($campaignDetails['current_amount'] ?? 0);
$goalAmount = (float) ($campaignDetails['goal_amount'] ?? 0);
$pendingAmount = (float) ($campaignDetails['pending_amount'] ?? 0);
$donorCount = (int) ($campaignDetails['donor_count'] ?? 0);
$progress = $goalAmount > 0 ? min(100, ($currentAmount / $goalAmount) * 100) : 0;
$imageUrl = !empty($campaignDetails['image_path'])
    ? BASE_URL . '/' . ltrim($campaignDetails['image_path'], '/')
    : BASE_URL . '/assets/images/default-campaign.jpg';
$publicShareUrl = BASE_URL . '/campaigns/public-view.php?id=' . (int) $campaignDetails['id'];
$categoryLabel = $campaignDetails['category_name'] ?? $campaignDetails['category'] ?? 'Uncategorized';
$createdDate = !empty($campaignDetails['created_at']) ? date('M j, Y', strtotime($campaignDetails['created_at'])) : 'Recently created';
$statusLabel = ucfirst((string) ($campaignDetails['status'] ?? 'active'));
$useDashboardShell = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title><?php echo htmlspecialchars($campaignDetails['title']); ?> - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        body.campaign-view-page {
            background: #f3f2ef;
            color: #1c1c1c;
        }

        body.campaign-view-page > .bg-gradient-to-r.from-emerald-600 {
            display: none;
        }

        body.campaign-view-page .dashboard-app-header {
            height: 72px;
            border-color: #e7e5e1;
            background: #fff;
            box-shadow: 0 1px 3px rgb(28 28 28 / 4%);
        }

        body.campaign-view-page .dashboard-app-header > div {
            height: 100%;
            padding-right: 24px;
            padding-left: 24px;
        }

        body.campaign-view-page .dashboard-app-header > div > div {
            display: flex;
            height: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        body.campaign-view-page .dashboard-app-header > div > div > div:first-child {
            flex: 0 1 auto;
            justify-content: flex-start;
            gap: 24px;
        }

        body.campaign-view-page #app-brand-link {
            position: static;
            gap: 10px;
            padding: 0;
            color: #1c1c1c;
            transform: none;
        }

        body.campaign-view-page #app-brand-link > span:first-child {
            display: flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        body.campaign-view-page #app-brand-link > span:last-child {
            display: inline;
            color: #274a32;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        body.campaign-view-page .dashboard-app-header > div > div > div:nth-child(2) {
            display: none;
        }

        body.campaign-view-page .dashboard-app-header > div > div > div:last-child {
            flex: 0 0 auto;
            gap: 12px;
        }

        body.campaign-view-page #user-menu-button {
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

        body.campaign-view-page #user-menu-button img {
            width: 32px;
            height: 32px;
            border: 0;
            box-shadow: 0 0 0 1px #e7e5e1;
        }

        body.campaign-view-page #sidebar {
            top: 72px;
            bottom: 0;
            width: 280px;
            border: 0;
            background: #f3f2ef;
            padding-top: 0;
        }

        body.campaign-view-page #sidebar .flex-1.space-y-4 > .flex.items-center.gap-3,
        body.campaign-view-page #sidebar .sidebar-section-title {
            display: none;
        }

        body.campaign-view-page #sidebar .flex-1.space-y-4 {
            padding: 24px 18px;
        }

        body.campaign-view-page #sidebar .sidebar-link {
            min-height: 64px;
            gap: 14px;
            border-radius: 999px;
            padding: 6px 10px;
            color: #1c1c1c;
            font-size: 0.9rem;
            font-weight: 700;
        }

        body.campaign-view-page #sidebar .sidebar-link > svg,
        body.campaign-view-page #sidebar .sidebar-link > span:first-child > svg {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border-radius: 50%;
            background: #eceae6;
            padding: 15px;
            color: #292524;
        }

        body.campaign-view-page #sidebar .sidebar-link.bg-emerald-50 {
            background: #ece7f8;
            color: #1c1c1c;
        }

        body.campaign-view-page #sidebar .sidebar-link.bg-emerald-50 > svg {
            background: #ddd4f5;
            color: #4c1d95;
        }

        body.campaign-view-page #sidebar-toggle,
        body.campaign-view-page .sidebar-overlay {
            display: none !important;
        }

        body.campaign-view-page .app-main {
            margin-left: 0 !important;
            width: auto;
            background: #f3f2ef;
        }

        body.campaign-view-page .campaign-view-shell {
            padding-top: 72px;
            padding-left: 280px;
        }

        @media (max-width: 1023px) {
            body.campaign-view-page .dashboard-app-header {
                height: 64px;
            }

            body.campaign-view-page .dashboard-app-header > div {
                padding-right: 16px;
                padding-left: 16px;
            }

            body.campaign-view-page .dashboard-app-header > div > div > div:first-child {
                position: static;
                flex: 1 1 100%;
                justify-content: center;
            }

            body.campaign-view-page #app-brand-link {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }

            body.campaign-view-page #app-brand-link > span:first-child,
            body.campaign-view-page #app-brand-link > span:first-child svg {
                width: 34px;
                height: 34px;
            }

            body.campaign-view-page .dashboard-app-header > div > div > div:nth-child(2) {
                position: absolute;
                right: 16px;
                display: flex;
                width: auto;
                flex: 0 0 auto;
                justify-content: flex-end;
            }

            body.campaign-view-page .dashboard-app-header > div > div > div:nth-child(2) img {
                width: 34px;
                height: 34px;
                border: 0;
                border-radius: 50%;
                box-shadow: 0 0 0 1px #e7e5e1;
            }

            body.campaign-view-page .dashboard-app-header > div > div > div:last-child {
                display: none;
            }

            body.campaign-view-page #sidebar {
                display: none;
            }

            body.campaign-view-page .campaign-view-shell {
                display: block;
                padding: 64px 0 0;
            }

            body.campaign-view-page .app-main {
                display: block;
                width: 100%;
            }

            body.campaign-view-page .dashboard-mobile-nav {
                min-height: 64px;
                padding-top: 6px;
                background: #fff;
                box-shadow: 0 -3px 14px rgb(28 28 28 / 5%);
            }
        }
    </style>
</head>
<body class="campaign-view-page min-h-screen bg-[#f3f2ef]">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="campaign-view-shell flex min-h-screen">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <main class="app-main min-w-0 flex-1 overflow-auto bg-[#f3f2ef]">
            <div class="w-full px-3 py-4 pb-24 sm:px-5 sm:py-5 lg:px-6 lg:py-6 lg:pb-6">
                <div class="mx-auto w-full max-w-[1440px]">
                <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-[minmax(0,1.65fr),300px]">
                    <div class="space-y-4 sm:space-y-5">
                        <section class="rounded-2xl border border-[#e7e5e1] bg-white px-4 py-4 sm:px-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        <?php echo htmlspecialchars($campaignDetails['title']); ?>
                                    </h1>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Organized by <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($campaignDetails['creator_name'] ?? 'Anonymous'); ?></span>
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2 sm:justify-end">
                                        <a href="<?php echo BASE_URL; ?>/campaigns/"
                                           class="inline-flex min-h-9 items-center justify-center rounded-full border border-[#e7e5e1] bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-[#f7f6f4] sm:text-sm">
                                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                            </svg>
                                            Back
                                        </a>
                                        <?php if ($isOwner): ?>
                                            <a href="<?php echo BASE_URL; ?>/campaigns/edit.php?id=<?php echo (int) $campaignDetails['id']; ?>"
                                               class="inline-flex min-h-9 items-center justify-center rounded-full bg-[#4c1d95] px-4 py-1.5 text-xs font-semibold text-white transition hover:bg-[#3b1675] sm:text-sm">
                                                Edit
                                            </a>
                                            <button type="button" onclick="openDeleteModal()"
                                                    class="inline-flex min-h-9 items-center justify-center rounded-full border border-red-200 bg-white px-4 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50 sm:text-sm">
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                            <div class="relative h-64 overflow-hidden bg-slate-100 sm:h-80 lg:h-[28rem]">
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                     alt="<?php echo htmlspecialchars($campaignDetails['title']); ?>"
                                     class="h-full w-full object-cover"
                                     onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                            </div>
                        </section>

                        <section class="rounded-2xl border border-[#e7e5e1] bg-white p-4 sm:p-5">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-xs font-medium text-slate-500">Raised</p>
                                    <p class="mt-0.5 text-xl font-bold tracking-tight text-emerald-700">$<?php echo number_format($currentAmount, 2); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-medium text-slate-500">Goal</p>
                                    <p class="mt-0.5 text-lg font-bold tracking-tight text-slate-900">$<?php echo number_format($goalAmount, 2); ?></p>
                                </div>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-slate-100" role="progressbar" aria-label="Campaign funding progress" aria-valuenow="<?php echo (int) round($progress); ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-slate-500">
                                <span><?php echo round($progress); ?>% funded</span>
                                <div class="flex items-center gap-3">
                                    <span>Pending <strong class="font-semibold text-amber-700">$<?php echo number_format($pendingAmount, 2); ?></strong></span>
                                    <span><?php echo $donorCount; ?> supporter<?php echo $donorCount === 1 ? '' : 's'; ?></span>
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                            <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                                <h2 class="text-base font-bold text-slate-900">Campaign story</h2>
                            </div>
                            <div class="px-4 py-4 sm:px-5 sm:py-5">
                                <div class="prose max-w-none whitespace-pre-line text-sm leading-7 text-slate-600">
                                    <?php echo htmlspecialchars($campaignDetails['description']); ?>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-4 sm:space-y-5">
                        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                            <div class="p-4 sm:p-5">
                                <h2 class="text-base font-bold text-slate-900">Share campaign</h2>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                <button type="button" onclick="copyToClipboard()"
                                        class="col-span-2 inline-flex min-h-10 w-full items-center justify-center rounded-xl bg-[#4c1d95] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#3b1675]">
                                    Copy link
                                </button>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($publicShareUrl); ?>" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:text-sm">
                                    Share on Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($publicShareUrl); ?>&text=<?php echo urlencode('Check out this campaign: ' . $campaignDetails['title']); ?>" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:text-sm">
                                    Share on Twitter
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($publicShareUrl); ?>" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:text-sm">
                                    Share on LinkedIn
                                </a>
                                <a href="mailto:?subject=<?php echo urlencode($campaignDetails['title']); ?>&body=<?php echo urlencode('Check out this campaign: ' . $publicShareUrl); ?>"
                                   class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-2 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 sm:text-sm">
                                    Share by email
                                </a>
                            </div>
                            </div>
                            <div class="border-t border-[#e7e5e1] px-4 py-3 sm:px-5">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Details</h3>
                                <dl class="mt-2 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt class="text-xs text-slate-500">Category</dt>
                                        <dd class="mt-0.5 font-medium text-slate-800"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) $categoryLabel))); ?></dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-slate-500">Status</dt>
                                        <dd class="mt-0.5 font-medium text-slate-800"><?php echo htmlspecialchars($statusLabel); ?></dd>
                                    </div>
                                    <div class="col-span-2">
                                        <dt class="text-xs text-slate-500">Created</dt>
                                        <dd class="mt-0.5 font-medium text-slate-800"><?php echo htmlspecialchars($createdDate); ?></dd>
                                    </div>
                                </dl>
                            </div>
                        </section>
                    </aside>
                </div>
                </div>
            </div>
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

    <div id="share-toast" class="fixed bottom-4 right-4 z-50 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white shadow-lg transform translate-y-full opacity-0 transition-all duration-300">
        Link copied to clipboard!
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
            <div class="p-6 sm:p-8">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                    </svg>
                </div>
                <h3 class="mt-6 text-center text-2xl font-bold text-slate-900">Delete campaign?</h3>
                <p class="mt-3 text-center text-sm leading-6 text-slate-500">
                    This action cannot be undone. The campaign and its related data will be permanently removed.
                </p>
                <div class="mt-8 space-y-3">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            Yes, delete campaign
                        </button>
                    </form>
                    <button type="button" onclick="closeDeleteModal()"
                            class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const url = <?php echo json_encode($publicShareUrl); ?>;
            navigator.clipboard.writeText(url).then(() => {
                const toast = document.getElementById('share-toast');
                if (!toast) {
                    return;
                }

                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';

                setTimeout(() => {
                    toast.style.transform = 'translateY(100%)';
                    toast.style.opacity = '0';
                }, 2200);
            }).catch(() => {
                alert('Failed to copy campaign link');
            });
        }

        function openDeleteModal() {
            document.getElementById('deleteModal')?.classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal')?.classList.add('hidden');
        }

        document.getElementById('deleteModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
