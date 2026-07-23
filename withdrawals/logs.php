<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/Withdrawal.php';

$user = new User();
if (!$user->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$withdrawal = new Withdrawal();
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';
$withdrawalHistory = $withdrawal->getUserWithdrawalHistory($_SESSION['user_id']);

if ($isAjaxRequest) {
    ob_start();
}

function renderWithdrawalLogsContent(array $withdrawalHistory): void {
?>
<div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
            <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    Withdrawal history
                </div>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Track every payout request</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    Review your withdrawal timeline, monitor statuses, and inspect the details attached to each request.
                </p>
            </div>
        </section>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-lg font-semibold text-slate-900">Withdrawal records</h2>
                <p class="mt-1 text-sm text-slate-500"><?php echo count($withdrawalHistory); ?> request<?php echo count($withdrawalHistory) === 1 ? '' : 's'; ?> found.</p>
            </div>

            <?php if (empty($withdrawalHistory)): ?>
                <div class="px-6 py-12 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">No withdrawals yet</h3>
                    <p class="mt-2 text-sm text-slate-500">Once you initiate a payout, the full request history will appear here.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-200">
                    <?php foreach ($withdrawalHistory as $item): ?>
                        <?php
                        $statusClasses = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'completed' => 'bg-emerald-100 text-emerald-800',
                            'rejected' => 'bg-red-100 text-red-800',
                        ];
                        $statusClass = $statusClasses[$item['status']] ?? 'bg-slate-100 text-slate-700';
                        $details = json_decode($item['details'], true) ?: [];
                        ?>
                        <div class="px-5 py-5 sm:px-6">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="text-lg font-semibold text-slate-900">$<?php echo number_format($item['amount'], 2); ?></h3>
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-500">
                                        <?php echo ucfirst($item['method']); ?> • <?php echo date('M d, Y \a\t g:i A', strtotime($item['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 lg:min-w-[320px]">
                                    <?php if (empty($details)): ?>
                                        <p>No additional details were stored for this request.</p>
                                    <?php else: ?>
                                        <div class="space-y-2">
                                            <?php foreach ($details as $key => $value): ?>
                                                <div class="flex items-start justify-between gap-3">
                                                    <span class="font-medium text-slate-700"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></span>
                                                    <span class="text-right text-slate-500"><?php echo htmlspecialchars((string) $value); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
}

if (!$isAjaxRequest) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal History - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include '../components/header.php'; ?>
    <div class="flex h-screen pt-16">
        <?php include '../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <?php renderWithdrawalLogsContent($withdrawalHistory); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderWithdrawalLogsContent($withdrawalHistory);
    echo ob_get_clean();
}
