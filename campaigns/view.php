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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <div class="w-full px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.65fr),300px]">
                    <div class="space-y-6">
                        <section class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                            <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-white/50 to-transparent lg:block"></div>
                            <div class="relative px-4 py-5 sm:px-5 sm:py-6 lg:px-6 lg:py-7">
                                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="max-w-3xl">
                                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                            <?php echo htmlspecialchars($campaignDetails['title']); ?>
                                        </h1>
                                        <p class="mt-2 max-w-2xl text-xs leading-5 text-slate-600 sm:text-sm">
                                            Review campaign performance, share it with supporters, and manage updates from one place.
                                        </p>
                                        <div class="mt-5 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 font-medium text-slate-700">
                                                <?php echo htmlspecialchars($statusLabel); ?>
                                            </span>
                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 font-medium text-slate-700">
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) $categoryLabel))); ?>
                                            </span>
                                            <span>Created <?php echo htmlspecialchars($createdDate); ?></span>
                                            <span>By <?php echo htmlspecialchars($campaignDetails['creator_name'] ?? 'Anonymous'); ?></span>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <a href="<?php echo BASE_URL; ?>/campaigns/"
                                           class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:text-emerald-700">
                                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                            </svg>
                                            Back to Campaigns
                                        </a>
                                        <?php if ($isOwner): ?>
                                            <a href="<?php echo BASE_URL; ?>/campaigns/edit.php?id=<?php echo (int) $campaignDetails['id']; ?>"
                                               class="inline-flex items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-amber-100">
                                                Edit campaign
                                            </a>
                                            <button type="button" onclick="openDeleteModal()"
                                                    class="inline-flex items-center justify-center rounded-2xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-red-700">
                                                Delete campaign
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="relative h-64 overflow-hidden bg-slate-100 sm:h-80 lg:h-[28rem]">
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                     alt="<?php echo htmlspecialchars($campaignDetails['title']); ?>"
                                     class="h-full w-full object-cover"
                                     onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/75 via-slate-950/35 to-transparent px-6 py-5 sm:px-8">
                                    <div class="flex flex-wrap items-end justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-white/80">Campaign progress</p>
                                            <p class="mt-1 text-3xl font-bold tracking-tight text-white"><?php echo round($progress); ?>%</p>
                                        </div>
                                        <span class="inline-flex rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700">
                                            <?php echo $donorCount; ?> donor<?php echo $donorCount === 1 ? '' : 's'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-sm font-medium text-slate-500">Raised</p>
                                <p class="mt-3 text-3xl font-bold tracking-tight text-emerald-700">$<?php echo number_format($currentAmount, 2); ?></p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-sm font-medium text-slate-500">Goal</p>
                                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">$<?php echo number_format($goalAmount, 2); ?></p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-sm font-medium text-slate-500">Pending donations</p>
                                <p class="mt-3 text-3xl font-bold tracking-tight text-amber-600">$<?php echo number_format($pendingAmount, 2); ?></p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-sm font-medium text-slate-500">Supporters</p>
                                <p class="mt-3 text-3xl font-bold tracking-tight text-blue-700"><?php echo $donorCount; ?></p>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-slate-900">Funding progress</h2>
                                        <p class="text-sm text-slate-500">Track how close this campaign is to reaching its goal.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-5 px-5 py-5 sm:px-6 sm:py-6">
                                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-slate-700">$<?php echo number_format($currentAmount, 2); ?> raised</span>
                                    <span class="text-slate-500">Goal: $<?php echo number_format($goalAmount, 2); ?></span>
                                </div>
                                <div class="h-3 rounded-full bg-slate-100">
                                    <div class="h-3 rounded-full bg-emerald-500 transition-all duration-500" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <p class="text-sm text-slate-500"><?php echo round($progress); ?>% of the goal has been reached so far.</p>
                            </div>
                        </section>

                        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-slate-900">Campaign story</h2>
                                        <p class="text-sm text-slate-500">The details supporters see when deciding whether to contribute.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="px-5 py-5 sm:px-6 sm:py-6">
                                <div class="prose max-w-none whitespace-pre-line text-sm leading-7 text-slate-600 sm:text-base">
                                    <?php echo htmlspecialchars($campaignDetails['description']); ?>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6">
                        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">Share campaign</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Send this fundraiser to supporters and make it easy to spread the word.</p>

                            <div class="mt-5 space-y-3">
                                <button type="button" onclick="copyToClipboard()"
                                        class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800">
                                    Copy campaign link
                                </button>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($publicShareUrl); ?>" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                                    Share on Facebook
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($publicShareUrl); ?>&text=<?php echo urlencode('Check out this campaign: ' . $campaignDetails['title']); ?>" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                                    Share on Twitter
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($publicShareUrl); ?>" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                                    Share on LinkedIn
                                </a>
                                <a href="mailto:?subject=<?php echo urlencode($campaignDetails['title']); ?>&body=<?php echo urlencode('Check out this campaign: ' . $publicShareUrl); ?>"
                                   class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                                    Share by email
                                </a>
                            </div>
                        </section>

                        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">Quick details</h2>
                            <div class="mt-5 space-y-4">
                                <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-900">Category</p>
                                    <p class="mt-1 text-sm text-slate-500"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', (string) $categoryLabel))); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-900">Campaign status</p>
                                    <p class="mt-1 text-sm text-slate-500"><?php echo htmlspecialchars($statusLabel); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-900">Created on</p>
                                    <p class="mt-1 text-sm text-slate-500"><?php echo htmlspecialchars($createdDate); ?></p>
                                </div>
                            </div>
                        </section>

                        <?php if ($isOwner): ?>
                            <section class="rounded-3xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                                <h2 class="text-lg font-semibold text-emerald-900">Owner actions</h2>
                                <ul class="mt-4 space-y-3 text-sm text-emerald-900/80">
                                    <li class="flex gap-3">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                        <span>Share your campaign regularly to reach more supporters.</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                        <span>Review your story and image if you want to improve conversion.</span>
                                    </li>
                                    <li class="flex gap-3">
                                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                        <span>Keep your campaign up to date so donors stay informed.</span>
                                    </li>
                                </ul>
                            </section>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </main>
    </div>

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
