<?php
session_start();

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
<div class="w-full px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
            <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-white/50 to-transparent lg:block"></div>
            <div class="relative px-4 py-5 sm:px-5 sm:py-6 lg:px-6 lg:py-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Manage your campaigns</h1>
                        <p class="mt-2 max-w-2xl text-xs leading-5 text-slate-600 sm:text-sm">
                            Review campaign performance, filter your fundraising activity, and jump into edits or updates from one organized space.
                        </p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create campaign
                    </a>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total campaigns</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900"><?php echo (int) ($stats['total'] ?? 0); ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Active campaigns</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-emerald-700"><?php echo (int) ($stats['active'] ?? 0); ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total raised</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">$<?php echo number_format((float) ($stats['total_raised'] ?? 0), 2); ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Success rate</p>
                <p class="mt-3 text-3xl font-bold tracking-tight text-blue-700"><?php echo (float) ($stats['success_rate'] ?? 0); ?>%</p>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-lg font-semibold text-slate-900">Filters</h2>
                <p class="mt-1 text-sm text-slate-500">Narrow your campaign list by status, sorting, or search terms.</p>
            </div>
            <div class="px-5 py-5 sm:px-6 sm:py-6">
                <form class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search campaigns..."
                               class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="ended" <?php echo $status === 'ended' ? 'selected' : ''; ?>>Ended</option>
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Sort by</label>
                        <select name="sort" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="most_funded" <?php echo $sort === 'most_funded' ? 'selected' : ''; ?>>Most Funded</option>
                            <option value="ending_soon" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                        </select>
                    </div>
                </form>
            </div>
        </section>

        <?php if (empty($userCampaigns)): ?>
            <section class="rounded-3xl border-2 border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-100 text-emerald-600">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-xl font-semibold text-slate-900">No campaigns yet</h3>
                <p class="mt-2 text-sm text-slate-500">Get started by creating your first fundraising campaign.</p>
                <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="mt-6 inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700">
                    Create campaign
                </a>
            </section>
        <?php else: ?>
            <section class="grid grid-cols-1 gap-5 xl:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($userCampaigns as $campaignItem): ?>
                    <?php
                    $percentage = 0;
                    if ((float) $campaignItem['goal_amount'] > 0) {
                        $percentage = min(100, ((float) $campaignItem['current_amount'] / (float) $campaignItem['goal_amount']) * 100);
                    }
                    ?>
                    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="relative h-52 overflow-hidden bg-slate-100">
                            <img src="<?php echo $campaignItem['image_path'] ? BASE_URL . '/' . $campaignItem['image_path'] : BASE_URL . '/assets/images/default-campaign.jpg'; ?>"
                                 alt="<?php echo htmlspecialchars($campaignItem['title']); ?>"
                                 class="h-full w-full object-cover"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/70 to-transparent px-5 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="inline-flex rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700">
                                        <?php echo round($percentage); ?>% funded
                                    </span>
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <?php echo htmlspecialchars(ucfirst($campaignItem['status'] ?? 'active')); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <h3 class="line-clamp-2 text-xl font-semibold text-slate-900"><?php echo htmlspecialchars($campaignItem['title']); ?></h3>
                            <div class="mt-4">
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <span class="font-medium text-slate-700">$<?php echo number_format((float) $campaignItem['current_amount'], 2); ?></span>
                                    <span class="text-slate-500">Goal: $<?php echo number_format((float) $campaignItem['goal_amount'], 2); ?></span>
                                </div>
                                <div class="h-2.5 rounded-full bg-slate-100">
                                    <div class="h-2.5 rounded-full bg-emerald-500" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                            <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                                <a href="view.php?id=<?php echo $campaignItem['id']; ?>" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                    View details
                                </a>
                                <a href="edit.php?id=<?php echo $campaignItem['id']; ?>" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                                    Edit
                                </a>
                                <button onclick="copyShareLink(<?php echo $campaignItem['id']; ?>, '<?php echo htmlspecialchars(addslashes($campaignItem['title'])); ?>')" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:text-emerald-700">
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
    const shareUrl = `${BASE_URL}/campaigns/public-view.php?id=${campaignId}`;

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

const BASE_URL = '<?php echo BASE_URL; ?>';
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <?php renderCampaignsIndexContent($userCampaigns, $stats, $status, $sort, $search); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderCampaignsIndexContent($userCampaigns, $stats, $status, $sort, $search);
    echo ob_get_clean();
}
