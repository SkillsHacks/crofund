<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../components/trust_risk_ui.php';

$user = new User();
if (!$user->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = normalizeRedirectPath($_SERVER['REQUEST_URI']);
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$currentUser = $user->getCurrentUser();
$trustRiskUi = getTrustRiskUiState($currentUser);
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

function renderNotificationsContent(array $currentUser, array $trustRiskUi): void {
?>
<div class="w-full px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
    <div class="space-y-4 sm:space-y-5">
        <section class="flex flex-col gap-3 rounded-2xl border border-[#e7e5e1] bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Notifications</h1>
                <p class="mt-1 text-xs text-slate-500 sm:text-sm">Account, campaign, and payout updates in one place.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/account/settings.php"
               class="inline-flex min-h-10 items-center justify-center rounded-full border border-[#e7e5e1] bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[#f7f6f4] focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                Account settings
            </a>
        </section>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr),280px]">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                    <h2 class="text-base font-semibold text-slate-900">Inbox</h2>
                </div>
                <div class="px-4 py-4 sm:px-5 sm:py-5">
                    <?php if (!empty($trustRiskUi['show_banner'])): ?>
                        <article class="rounded-2xl border px-4 py-4 <?php echo htmlspecialchars((string) $trustRiskUi['banner_class']); ?>">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?php echo htmlspecialchars((string) $trustRiskUi['badge_class']); ?>">
                                        <span><?php echo htmlspecialchars((string) $trustRiskUi['status']); ?></span>
                                    </div>
                                    <h3 class="mt-3 text-base font-semibold">Trust & safety notice</h3>
                                    <p class="mt-2 text-sm leading-6"><?php echo htmlspecialchars((string) ($trustRiskUi['message']['full'] ?? $trustRiskUi['message']['short'] ?? 'Your account currently has a trust and safety notice.')); ?></p>
                                </div>
                                <div class="shrink-0 flex flex-col gap-2 sm:items-end">
                                    <span class="inline-flex rounded-full bg-white/70 px-3 py-1 text-xs font-semibold">Active notice</span>
                                    <a href="mailto:support@crofund.org?subject=<?php echo rawurlencode('Request review for account status ' . (string) $trustRiskUi['status']); ?>" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Request review</a>
                                </div>
                            </div>
                        </article>
                    <?php else: ?>
                        <div class="px-2 py-6">
                            <div class="mx-auto max-w-xl text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-violet-50 text-violet-700">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-base font-semibold text-slate-900">You are all caught up</h3>
                                <p class="mt-1 text-sm text-slate-500">Important account, campaign, and payout notices will appear here.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>

        <aside class="space-y-6">
            <div class="rounded-2xl border border-[#e7e5e1] bg-white p-4 sm:p-5">
                <h2 class="text-base font-semibold text-slate-900">Notification email</h2>
                <p class="mt-2 break-all text-sm text-slate-600"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
                <a href="<?php echo BASE_URL; ?>/account/settings.php"
                   class="mt-4 inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-[#e7e5e1] bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[#f7f6f4] focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                    Manage account
                </a>
            </div>
            <?php if (empty($trustRiskUi['show_banner'])): ?>
                <div class="rounded-2xl border border-[#e7e5e1] bg-white p-4 sm:p-5">
                    <h2 class="text-base font-semibold text-slate-900">Stay informed</h2>
                    <p class="mt-2 text-sm leading-5 text-slate-500">Check back here for updates about your account, campaigns, and payouts.</p>
                </div>
            <?php endif; ?>
        </aside>
        </div>
    </div>
</div>
<?php
}

if ($isAjaxRequest) {
    ob_start();
}

if (!$isAjaxRequest) {
    $hideHeaderCreateButton = true;
    include __DIR__ . '/../components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <title>Notifications - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#f3f2ef]">
    <div class="flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-[#f3f2ef] lg:ml-64">
            <?php renderNotificationsContent($currentUser, $trustRiskUi); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderNotificationsContent($currentUser, $trustRiskUi);
    echo ob_get_clean();
}
