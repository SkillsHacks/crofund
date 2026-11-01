<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/Withdrawal.php';
require_once __DIR__ . '/../config/User.php';

$user = new User();
if (!$user->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$currentUser = $user->getCurrentUser();
if (!$currentUser || !isset($currentUser['name'])) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$campaign = new Campaign();
$withdrawal = new Withdrawal();
$userId = (int) $_SESSION['user_id'];
$summary = $campaign->getUserDonationSummary($userId);
$donationRecords = $campaign->getUserDonationRecords($userId);
$totalWithdrawn = $withdrawal->getTotalApprovedWithdrawals($userId);
$pendingWithdrawals = $withdrawal->getPendingWithdrawals($userId);
$availableToWithdraw = max(0, (float) $summary['total_available'] - $totalWithdrawn - $pendingWithdrawals);
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($isAjaxRequest) {
    ob_start();
}

function renderDonationContent(array $summary, array $donationRecords, float $pendingWithdrawals, float $availableToWithdraw): void {
?>
<div class="w-full px-0.5 py-4 sm:px-5 sm:py-5 lg:px-6 lg:py-6">
    <div class="space-y-4 sm:space-y-5">
        <section class="flex flex-col gap-3 rounded-2xl border border-[#e7e5e1] bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Donations</h1>
                <p class="mt-1 text-xs text-slate-500 sm:text-sm">Received funds at a glance.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="<?php echo BASE_URL; ?>/withdrawals/initiate.php"
                   class="inline-flex min-h-10 items-center justify-center rounded-full bg-emerald-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Initiate withdrawal
                </a>
                <a href="<?php echo BASE_URL; ?>/withdrawal-logs/"
                   data-dashboard-route="withdrawal_logs"
                   class="inline-flex min-h-10 items-center justify-center rounded-full border border-[#e7e5e1] bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[#f7f6f4] focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Withdrawal history
                </a>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" aria-label="Donation summary">
            <article class="rounded-2xl border border-[#e7e5e1] bg-white p-3 sm:p-5">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">Available</p>
                <p class="mt-1 text-[clamp(0.875rem,5vw,1.25rem)] font-bold tracking-tight text-emerald-700 sm:mt-2 sm:text-2xl">$<?php echo number_format($availableToWithdraw, 2); ?></p>
                <p class="mt-1 hidden text-xs text-slate-500 sm:block">Ready to withdraw</p>
            </article>
            <article class="rounded-2xl border border-[#e7e5e1] bg-white p-3 sm:p-5">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">On hold</p>
                <p class="mt-1 text-[clamp(0.875rem,5vw,1.25rem)] font-bold tracking-tight text-amber-700 sm:mt-2 sm:text-2xl">$<?php echo number_format((float) ($summary['total_on_hold'] ?? 0), 2); ?></p>
                <p class="mt-1 hidden text-xs text-slate-500 sm:block">In the hold period</p>
            </article>
            <article class="rounded-2xl border border-[#e7e5e1] bg-white p-3 sm:p-5">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">Total received</p>
                <p class="mt-1 text-[clamp(0.875rem,5vw,1.25rem)] font-bold tracking-tight text-slate-900 sm:mt-2 sm:text-2xl">$<?php echo number_format((float) ($summary['total_received'] ?? 0), 2); ?></p>
                <p class="mt-1 hidden text-xs text-slate-500 sm:block">From supporters</p>
            </article>
            <article class="rounded-2xl border border-[#e7e5e1] bg-white p-3 sm:p-5">
                <p class="text-xs font-medium text-slate-500 sm:text-sm">Pending withdrawals</p>
                <p class="mt-1 text-[clamp(0.875rem,5vw,1.25rem)] font-bold tracking-tight text-slate-900 sm:mt-2 sm:text-2xl">$<?php echo number_format($pendingWithdrawals, 2); ?></p>
                <p class="mt-1 hidden text-xs text-slate-500 sm:block">Awaiting review</p>
            </article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
            <div class="flex items-center justify-between border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                <h2 class="text-base font-semibold text-slate-900">Donation activity</h2>
                <span class="text-xs text-slate-500"><?php echo count($donationRecords); ?> total</span>
            </div>

            <?php if (empty($donationRecords)): ?>
                <div class="px-5 py-10 text-center sm:px-6">
                    <h3 class="text-base font-semibold text-slate-900">No donations yet</h3>
                    <p class="mt-1 text-sm text-slate-500">Donations to your campaigns will show here.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-[#e7e5e1] xl:hidden">
                    <?php foreach ($donationRecords as $donation): ?>
                        <?php
                        $status = (string) ($donation['status'] ?? 'unknown');
                        $isOnHold = !empty($donation['is_on_hold']);
                        $donationTimestamp = strtotime((string) ($donation['created_at'] ?? ''));
                        $statusClass = $isOnHold
                            ? 'bg-amber-100 text-amber-800'
                            : match ($status) {
                                'completed' => 'bg-emerald-100 text-emerald-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'refunded' => 'bg-sky-100 text-sky-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        ?>
                        <article class="px-4 py-4 sm:px-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-slate-900"><?php echo htmlspecialchars((string) ($donation['campaign_title'] ?? 'Campaign')); ?></h3>
                                    <p class="mt-1 truncate text-xs text-slate-500"><?php echo htmlspecialchars((string) (($donation['donor_name'] ?? '') !== '' ? $donation['donor_name'] : 'Anonymous')); ?></p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold text-slate-900">$<?php echo number_format((float) ($donation['amount'] ?? 0), 2); ?></p>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold <?php echo $statusClass; ?>"><?php echo htmlspecialchars($isOnHold ? 'On hold' : ucfirst($status)); ?></span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                <?php echo htmlspecialchars((string) ucfirst((string) ($donation['payment_gateway'] ?? 'Unknown'))); ?>
                                <span aria-hidden="true">·</span>
                                <?php echo $donationTimestamp === false ? '—' : date('M j, Y', $donationTimestamp); ?>
                            </p>
                            <?php if ($isOnHold && !empty($donation['hold_reason'])): ?>
                                <p class="mt-1 text-xs text-amber-700"><?php echo htmlspecialchars((string) $donation['hold_reason']); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="hidden overflow-x-auto xl:block">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Campaign</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Donor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php foreach ($donationRecords as $donation): ?>
                                <?php
                                $status = (string) ($donation['status'] ?? 'unknown');
                                $isOnHold = !empty($donation['is_on_hold']);
                                $statusClass = $isOnHold
                                    ? 'bg-amber-100 text-amber-800'
                                    : match ($status) {
                                        'completed' => 'bg-emerald-100 text-emerald-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                        'refunded' => 'bg-sky-100 text-sky-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                $donationTimestamp = strtotime((string) ($donation['created_at'] ?? ''));
                                ?>
                                <tr>
                                    <td class="px-4 py-3.5 align-top font-medium text-slate-900"><?php echo htmlspecialchars((string) ($donation['campaign_title'] ?? 'Campaign')); ?></td>
                                    <td class="px-4 py-3.5 align-top">
                                        <div class="font-medium text-slate-900"><?php echo htmlspecialchars((string) (($donation['donor_name'] ?? '') !== '' ? $donation['donor_name'] : 'Anonymous')); ?></div>
                                        <div class="mt-0.5 text-xs text-slate-500"><?php echo htmlspecialchars((string) (($donation['donor_email'] ?? '') !== '' ? $donation['donor_email'] : 'No email provided')); ?></div>
                                    </td>
                                    <td class="px-4 py-3.5 align-top font-semibold text-slate-900">$<?php echo number_format((float) ($donation['amount'] ?? 0), 2); ?></td>
                                    <td class="px-4 py-3.5 align-top text-slate-600">
                                        <div><?php echo htmlspecialchars((string) ucfirst((string) ($donation['payment_gateway'] ?? 'Unknown'))); ?></div>
                                        <?php if (!empty($donation['transaction_id'])): ?>
                                            <div class="mt-0.5 text-xs text-slate-500"><?php echo htmlspecialchars((string) $donation['transaction_id']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3.5 align-top">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold <?php echo $statusClass; ?>"><?php echo htmlspecialchars($isOnHold ? 'On hold' : ucfirst($status)); ?></span>
                                        <?php if ($isOnHold && !empty($donation['hold_reason'])): ?>
                                            <div class="mt-1.5 text-xs text-amber-700"><?php echo htmlspecialchars((string) $donation['hold_reason']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3.5 align-top text-slate-500"><?php echo $donationTimestamp === false ? '—' : date('M j, Y', $donationTimestamp); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php
}

if ($isAjaxRequest) {
    renderDonationContent($summary, $donationRecords, $pendingWithdrawals, $availableToWithdraw);
    echo ob_get_clean();
} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#f3f2ef]">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main min-w-0 flex-1 overflow-auto bg-[#f3f2ef] lg:ml-64">
            <?php renderDonationContent($summary, $donationRecords, $pendingWithdrawals, $availableToWithdraw); ?>
        </main>
    </div>
</body>
</html>
<?php
}
