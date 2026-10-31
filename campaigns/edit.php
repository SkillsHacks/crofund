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
$useDashboardShell = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>Edit Campaign - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="campaign-edit-page min-h-screen bg-[#f3f2ef]">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="campaign-edit-shell flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>

        <main class="app-main min-w-0 flex-1 overflow-auto bg-[#f3f2ef]">
            <div class="w-full px-3 py-4 pb-24 sm:px-5 sm:py-5 lg:px-6 lg:py-6 lg:pb-6">
                <div class="mx-auto w-full max-w-[1440px] space-y-4 sm:space-y-5">
                    <section class="flex flex-col gap-3 rounded-2xl border border-[#e7e5e1] bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div>
                            <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Edit campaign</h1>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo (int) ($campaignData['id'] ?? $campaignId); ?>"
                               class="inline-flex min-h-10 flex-1 items-center justify-center rounded-full border border-[#e7e5e1] bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-[#f7f6f4] sm:flex-none sm:px-4 sm:text-sm">
                                View campaign
                            </a>
                            <a href="<?php echo BASE_URL; ?>/campaigns/"
                               class="inline-flex min-h-10 flex-1 items-center justify-center rounded-full border border-[#e7e5e1] bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-[#f7f6f4] sm:flex-none sm:px-4 sm:text-sm">
                                My campaigns
                            </a>
                        </div>
                    </section>

                        <?php if ($error): ?>
                            <div class="flex items-start gap-2 px-1 text-sm text-red-700" role="alert">
                                <div class="flex items-start gap-3">
                                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="font-semibold">Unable to open this campaign</p>
                                        <p class="mt-0.5 text-xs"><?php echo htmlspecialchars($error); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($success): ?>
                            <div class="flex items-center gap-2 px-1 text-sm text-emerald-700" role="status">
                                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <div>
                                    <p class="font-semibold">Campaign updated</p>
                                    <p class="text-xs"><?php echo htmlspecialchars($success); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($campaignData) && empty($error)): ?>
                            <form method="post" enctype="multipart/form-data" class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                                <div class="space-y-4 p-4 sm:space-y-5 sm:p-5">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5">
                                        <div>
                                            <label for="title" class="block text-xs font-semibold text-slate-700 sm:text-sm">Campaign title</label>
                                            <input type="text" id="title" name="title" required
                                                   value="<?php echo htmlspecialchars($campaignData['title']); ?>"
                                                   class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/15">
                                        </div>

                                        <div>
                                            <label for="category" class="block text-xs font-semibold text-slate-700 sm:text-sm">Category</label>
                                            <select id="category" name="category" required
                                                    class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/15">
                                                <?php foreach ($categoryOptions as $value => $label): ?>
                                                    <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($campaignData['category'] ?? '') === $value ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="goal" class="block text-xs font-semibold text-slate-700 sm:text-sm">Funding goal (USD)</label>
                                            <div class="relative mt-2">
                                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">$</span>
                                                <input type="number" id="goal" name="goal" min="100" required
                                                       value="<?php echo htmlspecialchars((string) $campaignData['goal_amount']); ?>"
                                                       class="block min-h-11 w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-8 pr-3.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/15">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                            <label for="description" class="block text-xs font-semibold text-slate-700 sm:text-sm">Campaign story</label>
                                            <textarea id="description" name="description" rows="8" required
                                                      class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/15"><?php echo htmlspecialchars($campaignData['description']); ?></textarea>
                                    </div>

                                    <div>
                                        <label for="campaign_image" class="block text-xs font-semibold text-slate-700 sm:text-sm">Campaign image</label>
                                        <div class="mt-1.5 flex flex-col gap-3 sm:flex-row sm:items-center">
                                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                                         alt="Campaign Image"
                                                         class="h-28 w-full rounded-lg object-cover sm:h-20 sm:w-32"
                                                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                                                    <div class="min-w-0 flex-1">
                                                        <input type="file" name="campaign_image" accept="image/*"
                                                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-2 file:font-semibold file:text-[#4c1d95] hover:file:bg-violet-100">
                                                        <p class="mt-1 text-xs text-slate-500">Leave blank to keep current image.</p>
                                                    </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end border-t border-[#e7e5e1] p-3 sm:px-5 sm:py-4">
                                    <button type="submit"
                                            class="inline-flex min-h-11 w-full items-center justify-center rounded-full bg-[#4c1d95] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#3b1675] focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 sm:w-auto">
                                        Update campaign
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
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
    <style>
        body.campaign-edit-page {
            background: #f3f2ef;
            color: #1c1c1c;
        }

        body.campaign-edit-page > .bg-gradient-to-r.from-emerald-600 {
            display: none;
        }

        body.campaign-edit-page .dashboard-app-header {
            height: 72px;
            border-color: #e7e5e1;
            background: #fff;
            box-shadow: 0 1px 3px rgb(28 28 28 / 4%);
        }

        body.campaign-edit-page .dashboard-app-header > div {
            height: 100%;
            padding-right: 24px;
            padding-left: 24px;
        }

        body.campaign-edit-page .dashboard-app-header > div > div {
            display: flex;
            height: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        body.campaign-edit-page .dashboard-app-header > div > div > div:first-child {
            flex: 0 1 auto;
            justify-content: flex-start;
            gap: 24px;
        }

        body.campaign-edit-page #app-brand-link {
            position: static;
            gap: 10px;
            padding: 0;
            color: #1c1c1c;
            transform: none;
        }

        body.campaign-edit-page #app-brand-link > span:first-child {
            display: flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        body.campaign-edit-page #app-brand-link > span:last-child {
            display: inline;
            color: #047857;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        body.campaign-edit-page .dashboard-app-header > div > div > div:nth-child(2) {
            display: none;
        }

        body.campaign-edit-page .dashboard-app-header > div > div > div:last-child {
            flex: 0 0 auto;
            gap: 12px;
        }

        body.campaign-edit-page #user-menu-button {
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

        body.campaign-edit-page #user-menu-button img {
            width: 32px;
            height: 32px;
            border: 0;
            box-shadow: 0 0 0 1px #e7e5e1;
        }

        body.campaign-edit-page #sidebar {
            top: 72px;
            bottom: 0;
            width: 280px;
            border: 0;
            background: #f3f2ef;
            padding-top: 0;
        }

        body.campaign-edit-page #sidebar .flex-1.space-y-4 > .flex.items-center.gap-3,
        body.campaign-edit-page #sidebar .sidebar-section-title {
            display: none;
        }

        body.campaign-edit-page #sidebar .flex-1.space-y-4 {
            padding: 24px 18px;
        }

        body.campaign-edit-page #sidebar .sidebar-link {
            min-height: 64px;
            gap: 14px;
            border-radius: 999px;
            padding: 6px 10px;
            color: #1c1c1c;
            font-size: 0.9rem;
            font-weight: 700;
        }

        body.campaign-edit-page #sidebar .sidebar-link > svg,
        body.campaign-edit-page #sidebar .sidebar-link > span:first-child > svg {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            border-radius: 50%;
            background: #eceae6;
            padding: 15px;
            color: #292524;
        }

        body.campaign-edit-page #sidebar .sidebar-link.bg-emerald-50 {
            background: #ece7f8;
            color: #1c1c1c;
        }

        body.campaign-edit-page #sidebar .sidebar-link.bg-emerald-50 > svg {
            background: #ddd4f5;
            color: #4c1d95;
        }

        body.campaign-edit-page #sidebar-toggle,
        body.campaign-edit-page .sidebar-overlay {
            display: none !important;
        }

        body.campaign-edit-page .app-main {
            margin-left: 0 !important;
            width: auto;
            background: #f3f2ef;
        }

        body.campaign-edit-page .campaign-edit-shell {
            padding-top: 72px;
            padding-left: 280px;
        }

        @media (max-width: 1023px) {
            body.campaign-edit-page .dashboard-app-header {
                height: 64px;
            }

            body.campaign-edit-page .dashboard-app-header > div {
                padding-right: 16px;
                padding-left: 16px;
            }

            body.campaign-edit-page .dashboard-app-header > div > div > div:first-child {
                position: static;
                flex: 1 1 100%;
                justify-content: center;
            }

            body.campaign-edit-page #app-brand-link {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }

            body.campaign-edit-page #app-brand-link > span:first-child,
            body.campaign-edit-page #app-brand-link > span:first-child svg {
                width: 34px;
                height: 34px;
            }

            body.campaign-edit-page .dashboard-app-header > div > div > div:nth-child(2) {
                position: absolute;
                right: 16px;
                display: flex;
                width: auto;
                flex: 0 0 auto;
                justify-content: flex-end;
            }

            body.campaign-edit-page .dashboard-app-header > div > div > div:nth-child(2) img {
                width: 34px;
                height: 34px;
                border: 0;
                border-radius: 50%;
                box-shadow: 0 0 0 1px #e7e5e1;
            }

            body.campaign-edit-page .dashboard-app-header > div > div > div:last-child {
                display: none;
            }

            body.campaign-edit-page #sidebar {
                display: none;
            }

            body.campaign-edit-page .campaign-edit-shell {
                display: block;
                padding: 64px 0 0;
            }

            body.campaign-edit-page .app-main {
                display: block;
                width: 100%;
            }

            body.campaign-edit-page .dashboard-mobile-nav {
                min-height: 64px;
                padding-top: 6px;
                background: #fff;
                box-shadow: 0 -3px 14px rgb(28 28 28 / 5%);
            }
        }

    </style>
</body>
</html>
