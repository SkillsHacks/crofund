<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../components/trust_risk_ui.php';

$user = new User();
if (!$user->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$currentUser = $user->getCurrentUser();
$trustRiskUi = getTrustRiskUiState($currentUser);
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

function renderNotificationsContent(array $currentUser, array $trustRiskUi): void {
?>
<div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr),360px]">
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        Notifications
                    </div>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Stay up to date</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                        Review important account, campaign, and payout alerts from one organized place.
                    </p>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-slate-900">Notification inbox</h2>
                    <p class="mt-1 text-sm text-slate-500">Important account, moderation, and operational notices appear here.</p>
                </div>
                <div class="px-5 py-5 sm:px-6 sm:py-6 space-y-4">
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
                                <div class="shrink-0">
                                    <span class="inline-flex rounded-full bg-white/70 px-3 py-1 text-xs font-semibold">Active notice</span>
                                </div>
                            </div>
                        </article>
                    <?php else: ?>
                        <div class="px-2 py-6">
                            <div class="mx-auto max-w-xl text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-100 text-emerald-600">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                    </svg>
                                </div>
                                <h3 class="mt-5 text-xl font-semibold text-slate-900">No notices right now</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    When CroFund sends you updates about verification, withdrawals, campaign progress, moderation, or account activity, they will appear here.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Account owner</p>
                    <p class="mt-3 text-xl font-semibold text-slate-900"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Notification email</p>
                    <p class="mt-3 text-xl font-semibold text-slate-900"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">What will appear here?</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Campaign activity</p>
                        <p class="mt-1 text-sm text-slate-500">Updates related to fundraising milestones, donor activity, and campaign changes.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Finance alerts</p>
                        <p class="mt-1 text-sm text-slate-500">Withdrawal reviews, payout status, and important transaction notices.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Security updates</p>
                        <p class="mt-1 text-sm text-slate-500">Verification reminders, account protection prompts, and login-related events.</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
<?php
}

if ($isAjaxRequest) {
    ob_start();
}

if (!$isAjaxRequest) {
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
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen pt-16">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
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
