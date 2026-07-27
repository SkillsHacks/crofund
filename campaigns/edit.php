<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/TrustRiskGuard.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

TrustRiskGuard::enforceOrAbort((int) $_SESSION['user_id'], 'edit_campaign');

$campaign = new Campaign();
$campaignId = $_GET['id'] ?? null;
$error = '';
$success = '';
$campaignData = null;

if (!$campaignId) {
    $error = 'No campaign selected.';
} else {
    $campaignData = $campaign->getCampaignById($campaignId);

    if (!$campaignData || (int) $campaignData['user_id'] !== (int) $_SESSION['user_id']) {
        $error = 'You do not have permission to edit this campaign.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    try {
        $updateData = [
            'title' => $_POST['title'] ?? '',
            'category' => $_POST['category'] ?? '',
            'goal' => $_POST['goal'] ?? '',
            'description' => $_POST['description'] ?? '',
        ];

        if (!empty($_FILES['campaign_image']['name'])) {
            $updateData['campaign_image'] = $_FILES['campaign_image'];
        }

        $campaign->update($campaignId, $updateData);
        $success = 'Campaign updated successfully!';
        $campaignData = $campaign->getCampaignById($campaignId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$imageUrl = !empty($campaignData['image_path'])
    ? BASE_URL . '/' . ltrim($campaignData['image_path'], '/')
    : BASE_URL . '/assets/images/default-campaign.jpg';
$categoryOptions = [
    'education' => 'Education',
    'medical' => 'Medical & Health',
    'emergency' => 'Emergency Relief',
    'community' => 'Community Project',
    'creative' => 'Creative Project',
    'nonprofit' => 'Nonprofit',
    'business' => 'Small Business',
    'technology' => 'Technology',
    'environment' => 'Environment',
    'others' => 'Others',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <title>Edit Campaign - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="flex min-h-screen pt-16">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.65fr),360px]">
                    <div class="space-y-6">
                        <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                            <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-white/50 to-transparent lg:block"></div>
                            <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                                <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                                    <div class="max-w-2xl">
                                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                            <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                            Campaign editing
                                        </div>
                                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Update your campaign</h1>
                                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                                            Refine your title, goal, story, and cover image so your campaign stays clear, accurate, and compelling for supporters.
                                        </p>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <a href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo (int) ($campaignData['id'] ?? $campaignId); ?>"
                                           class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:text-emerald-700">
                                            View campaign
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/campaigns/"
                                           class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:text-emerald-700">
                                            Back to Campaigns
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <?php if ($error): ?>
                            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-red-700 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold">We couldn’t update your campaign</p>
                                        <p class="mt-1 text-sm"><?php echo htmlspecialchars($error); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-700 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold">Campaign updated</p>
                                        <p class="mt-1 text-sm"><?php echo htmlspecialchars($success); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($campaignData) && empty($error)): ?>
                            <form method="post" enctype="multipart/form-data" class="space-y-6">
                                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h2 class="text-lg font-semibold text-slate-900">Basic information</h2>
                                                <p class="text-sm text-slate-500">Keep your title and category clear so people immediately understand your campaign.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-6 px-5 py-5 sm:px-6 sm:py-6">
                                        <div>
                                            <label for="title" class="block text-sm font-semibold text-slate-700">Campaign title</label>
                                            <input type="text" id="title" name="title" required
                                                   value="<?php echo htmlspecialchars($campaignData['title']); ?>"
                                                   class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                        </div>

                                        <div>
                                            <label for="category" class="block text-sm font-semibold text-slate-700">Category</label>
                                            <select id="category" name="category" required
                                                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                                <?php foreach ($categoryOptions as $value => $label): ?>
                                                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($campaignData['category'] ?? '') === $value ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h2 class="text-lg font-semibold text-slate-900">Funding details</h2>
                                                <p class="text-sm text-slate-500">Adjust your target while keeping expectations realistic.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-6 px-5 py-5 sm:px-6 sm:py-6 lg:grid-cols-2">
                                        <div>
                                            <label for="goal" class="block text-sm font-semibold text-slate-700">Funding goal (USD)</label>
                                            <div class="relative mt-2">
                                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">$</span>
                                                <input type="number" id="goal" name="goal" min="100" required
                                                       value="<?php echo htmlspecialchars((string) $campaignData['goal_amount']); ?>"
                                                       class="block w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-8 pr-4 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                            </div>
                                        </div>


                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h2 class="text-lg font-semibold text-slate-900">Story & media</h2>
                                                <p class="text-sm text-slate-500">Refresh your message and visuals to keep supporters engaged.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-6 px-5 py-5 sm:px-6 sm:py-6">
                                        <div>
                                            <label for="description" class="block text-sm font-semibold text-slate-700">Campaign story</label>
                                            <textarea id="description" name="description" rows="8" required
                                                      class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"><?php echo htmlspecialchars($campaignData['description']); ?></textarea>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700">Campaign image</label>
                                            <div class="mt-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                                                <div class="flex flex-col gap-5 md:flex-row md:items-center">
                                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                                         alt="Campaign Image"
                                                         class="h-32 w-full rounded-2xl object-cover shadow-sm md:w-52"
                                                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                                                    <div class="flex-1">
                                                        <input type="file" name="campaign_image" accept="image/*"
                                                               class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100">
                                                        <p class="mt-3 text-sm text-slate-500">Leave this blank if you want to keep the current cover image.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <p class="max-w-2xl text-sm leading-6 text-slate-500">
                                        Review your changes before saving. Updates affect what supporters see on your campaign page immediately.
                                    </p>
                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 sm:w-auto">
                                        Update campaign
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <aside class="space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">Editing tips</h2>
                            <div class="mt-5 space-y-4">
                                <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-900">1. Clarify your title</p>
                                    <p class="mt-1 text-sm text-slate-500">Use a title that explains who needs help and what the funds are for.</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-900">2. Keep the goal realistic</p>
                                    <p class="mt-1 text-sm text-slate-500">A target that feels achievable often builds more confidence with donors.</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-900">3. Update your story</p>
                                    <p class="mt-1 text-sm text-slate-500">Add context, progress, or urgency so supporters understand why the campaign matters now.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-emerald-900">Before you save</h2>
                            <ul class="mt-4 space-y-3 text-sm text-emerald-900/80">
                                <li class="flex gap-3">
                                    <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>Make sure your image still reflects the campaign clearly.</span>
                                </li>
                                <li class="flex gap-3">
                                    <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>Double-check that your story explains how funds will be used.</span>
                                </li>
                                <li class="flex gap-3">
                                    <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>Set an end date that matches your campaign timeline and urgency.</span>
                                </li>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
