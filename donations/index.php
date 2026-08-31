<?php
session_start();

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

include '../components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen pt-16">
        <?php include '../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="space-y-6">
                    <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                        <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Track your received donations</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                                Review the total donations you have received, see which funds are on hold, and check what remains available for withdrawal.
                            </p>
                        </div>
                    </section>

                    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="relative overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-500 via-emerald-600 to-emerald-700 px-4 py-4 text-white shadow-md shadow-emerald-900/20">
                            <div class="absolute right-0 top-0 h-20 w-20 -translate-y-5 translate-x-5 rounded-full bg-white/10"></div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-50/85">Available balance</p>
                            <p class="mt-2 text-2xl font-bold tracking-tight">$<?php echo number_format($availableToWithdraw, 2); ?></p>
                        </article>

                        <article class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white px-4 py-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-700">Hold</p>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-amber-900">$<?php echo number_format((float) $summary['total_on_hold'], 2); ?></p>
                                </div>
                                <span class="rounded-xl bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700">Restricted</span>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Total donations</p>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">$<?php echo number_format((float) $summary['total_received'], 2); ?></p>
                                </div>
                                <span class="rounded-xl bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600"><?php echo (int) $summary['donation_count']; ?></span>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Pending withdrawals</p>
                                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">$<?php echo number_format($pendingWithdrawals, 2); ?></p>
                                </div>
                                <span class="rounded-xl bg-yellow-100 px-2.5 py-1 text-[11px] font-semibold text-yellow-700">Pending</span>
                            </div>
                        </article>
                    </section>

                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
                            <h2 class="text-base font-semibold text-slate-900">Donation activity</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Campaign</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Donor</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Payment</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Date</th>
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
                                        $statusLabel = $isOnHold ? 'On Hold' : ucfirst($status);
                                        ?>
                                        <tr>
                                            <td class="px-4 py-3.5 align-top">
                                                <div class="font-medium text-slate-900"><?php echo htmlspecialchars((string) ($donation['campaign_title'] ?? 'Campaign')); ?></div>
                                            </td>
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
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                                <?php if ($isOnHold && !empty($donation['hold_reason'])): ?>
                                                    <div class="mt-1.5 text-xs text-amber-700"><?php echo htmlspecialchars((string) $donation['hold_reason']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3.5 align-top text-slate-500"><?php echo htmlspecialchars((string) ($donation['created_at'] ?? '—')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (empty($donationRecords)): ?>
                            <div class="px-5 py-10 text-center text-sm text-slate-500">No donations have been received yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
