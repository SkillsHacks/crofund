<?php
session_start();
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/Withdrawal.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/TrustRiskGuard.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PremiumAccountService.php';

$user = new User();
if (!$user->isLoggedIn()) {
    ob_end_clean();
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$currentUser = $user->getCurrentUser();
if (!$currentUser || !isset($currentUser['name'])) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

TrustRiskGuard::enforceOrAbort((int) $_SESSION['user_id'], 'withdraw_funds');

$db = Database::getInstance()->getConnection();
$premiumService = new PremiumAccountService($db);
$hasPremiumAccess = $premiumService->hasPremiumAccess((int) $_SESSION['user_id']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$sessionErrorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

if ($sessionErrorMessage !== '' && $errorMessage === '') {
    $errorMessage = $sessionErrorMessage;
}

$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

$campaign = new Campaign();
$withdrawal = new Withdrawal();

$totalDonations = $campaign->getUserTotalDonations($_SESSION['user_id']);
$heldDonations = $campaign->getUserHeldDonations($_SESSION['user_id']);
$withdrawableDonations = $campaign->getUserWithdrawableDonations($_SESSION['user_id']);
$totalWithdrawn = $withdrawal->getTotalApprovedWithdrawals($_SESSION['user_id']);
$pendingWithdrawals = $withdrawal->getPendingWithdrawals($_SESSION['user_id']);
$userInfo = $campaign->getUserInfo($_SESSION['user_id']);
$availableBalance = $withdrawableDonations - $totalWithdrawn - $pendingWithdrawals;
$pendingWithdrawalsList = $withdrawal->getPendingWithdrawalsList($_SESSION['user_id']);

if ($hasPremiumAccess && $availableBalance < MIN_WITHDRAWAL_AMOUNT) {
    $errorMessage = 'Minimum withdrawal amount is $' . number_format(MIN_WITHDRAWAL_AMOUNT, 2);
}

if ($hasPremiumAccess) {
    $attemptCount = $withdrawal->getWithdrawalAttempts($_SESSION['user_id'], 24);
    if ($attemptCount >= MAX_WITHDRAWAL_ATTEMPTS) {
        $errorMessage = 'Maximum withdrawal attempts reached. Please try again later.';
    }
}

if ($isAjaxRequest) {
    ob_start();
}

function renderWithdrawalInitiateContent(): void {
    global $errorMessage, $pendingWithdrawalsList, $totalDonations, $heldDonations, $withdrawableDonations, $pendingWithdrawals, $availableBalance, $totalWithdrawn, $userInfo, $hasPremiumAccess;
?>
<div class="w-full px-4 py-4 pb-24 sm:px-6 sm:py-5 lg:px-8 lg:py-6 lg:pb-6">
    <div class="space-y-4 sm:space-y-5">
        <section class="flex flex-col gap-3 rounded-2xl border border-[#e7e5e1] bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Initiate withdrawal</h1>
                <p class="mt-1 text-xs text-slate-500 sm:text-sm">Review your balance and payout details.</p>
                <?php if (!$hasPremiumAccess): ?>
                    <p class="mt-2 text-xs leading-5 text-amber-800 sm:text-sm" role="status">
                        <span class="font-semibold">Premium membership required.</span>
                        You need an active premium membership to initiate withdrawals.
                    </p>
                <?php endif; ?>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <?php if (!$hasPremiumAccess): ?>
                    <a href="<?php echo BASE_URL; ?>/account/premium.php"
                       class="inline-flex min-h-10 items-center justify-center rounded-full bg-[#4c1d95] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#3b1675] focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                        Upgrade to premium
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/withdrawal-logs/"
                   class="inline-flex min-h-10 items-center justify-center rounded-full border border-[#e7e5e1] bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-[#f7f6f4] focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                    Withdrawal history
                </a>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr),300px]">
            <div class="space-y-6">
            <?php if ($errorMessage): ?>
                <p class="text-xs font-medium text-red-600"><?php echo $errorMessage; ?></p>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <p class="text-xs font-medium text-emerald-700">Withdrawal initiated successfully!</p>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="text-xs font-medium text-emerald-700"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Donations</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">$<?php echo number_format($totalDonations, 2); ?></p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 shadow-sm">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-amber-700">On hold</p>
                    <p class="mt-2 text-xl font-bold text-amber-800">$<?php echo number_format($heldDonations, 2); ?></p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Pending</p>
                    <p class="mt-2 text-xl font-bold text-yellow-700">$<?php echo number_format($pendingWithdrawals, 2); ?></p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Available</p>
                    <p class="mt-2 text-xl font-bold text-emerald-700">$<?php echo number_format($availableBalance, 2); ?></p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Completed</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">$<?php echo number_format($totalWithdrawn, 2); ?></p>
                </div>
            </div>

            <?php if ($hasPremiumAccess && !empty($pendingWithdrawalsList)): ?>
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 class="text-lg font-semibold text-slate-900">Pending withdrawals</h2>
                        <p class="mt-1 text-sm text-slate-500">Requests currently waiting to be processed.</p>
                    </div>
                    <div class="space-y-3 px-5 py-5 sm:px-6 sm:py-6">
                        <?php foreach ($pendingWithdrawalsList as $pending): ?>
                            <?php if ((float) $pending['amount'] > 0): ?>
                                <div class="flex flex-col gap-4 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">
                                            $<?php echo number_format($pending['amount'], 2); ?>
                                            <span class="ml-2 text-yellow-700">
                                                (<?php
                                                    $method = strtolower($pending['method']);
                                                    if ($method === 'bank') echo 'Bank Transfer';
                                                    elseif ($method === 'mobile') echo 'Mobile Money';
                                                    elseif ($method === 'paypal') echo 'PayPal';
                                                    else echo ucfirst($method);
                                                ?>)
                                            </span>
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">Requested on <?php echo date('M d, Y \a\t g:i A', strtotime($pending['created_at'])); ?></p>
                                    </div>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/withdrawals/delete_withdrawal.php" onsubmit="return confirmDelete()" class="w-full sm:w-auto">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="withdrawal_id" value="<?php echo $pending['id']; ?>">
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 sm:w-auto">
                                            Cancel withdrawal
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($hasPremiumAccess): ?>
                    <div class="<?php echo $errorMessage ? 'opacity-50 pointer-events-none' : ''; ?> overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                            <h2 class="text-lg font-semibold text-slate-900">Choose withdrawal method</h2>
                            <p class="mt-1 text-sm text-slate-500">Provide the payment details needed to complete your payout.</p>
                        </div>
                        <div class="px-5 py-5 sm:px-6 sm:py-6">
                            <form action="<?php echo BASE_URL; ?>/withdrawals/process_withdrawal.php" method="POST" class="space-y-6" onsubmit="return validateWithdrawalForm(event)">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="withdrawal_request_id" value="<?php echo bin2hex(random_bytes(16)); ?>">

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <label class="flex cursor-pointer items-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-violet-300 hover:bg-violet-50">
                                        <input type="radio" name="withdrawal_method" value="bank" class="form-radio text-violet-700" required onclick="showFields('bank')">
                                        <span class="ml-3">Bank transfer</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-violet-300 hover:bg-violet-50">
                                        <input type="radio" name="withdrawal_method" value="mobile" class="form-radio text-violet-700" onclick="showFields('mobile')">
                                        <span class="ml-3">Mobile money</span>
                                    </label>
                                    <label class="flex cursor-pointer items-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-violet-300 hover:bg-violet-50">
                                        <input type="radio" name="withdrawal_method" value="paypal" class="form-radio text-violet-700" onclick="showFields('paypal')">
                                        <span class="ml-3">PayPal</span>
                                    </label>
                                </div>

                                <div id="bankFields" class="hidden space-y-4">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <input type="text" name="account_name" placeholder="Account Name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                        <input type="text" name="bank_name" placeholder="Bank Name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                    </div>
                                    <input type="text" name="account_number" placeholder="Account Number" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                </div>

                                <div id="mobileFields" class="hidden space-y-4">
                                    <input type="text" name="full_name" placeholder="Full Name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <select name="country" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                            <option value="">Select Country</option>
                                            <option value="Uganda">Uganda</option>
                                            <option value="Kenya">Kenya</option>
                                            <option value="Tanzania">Tanzania</option>
                                            <option value="Rwanda">Rwanda</option>
                                        </select>
                                        <select name="mobile_provider" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                            <option value="">Select Mobile Money Provider</option>
                                            <option value="MTN">MTN</option>
                                            <option value="Airtel">Airtel</option>
                                            <option value="Vodafone">Vodafone</option>
                                            <option value="Safaricom">Safaricom</option>
                                        </select>
                                    </div>
                                    <input type="text" name="phone_number" placeholder="Phone Number" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                </div>

                                <div id="paypalFields" class="hidden space-y-4">
                                    <input type="email" name="paypal_email" placeholder="PayPal Email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                </div>

                                <div>
                                    <label for="amount" class="block text-sm font-semibold text-slate-700">Withdrawal amount</label>
                                    <input type="number" id="amount" name="amount" min="<?php echo number_format(MIN_WITHDRAWAL_AMOUNT, 2, '.', ''); ?>" max="<?php echo min($availableBalance, MAX_WITHDRAWAL_AMOUNT); ?>" step="0.01" required placeholder="Enter amount"
                                           class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-violet-500/10">
                                    <p class="mt-2 text-sm text-slate-500">Minimum withdrawal: $<?php echo number_format(MIN_WITHDRAWAL_AMOUNT, 2); ?> • Available balance: $<?php echo number_format($availableBalance, 2); ?> • Maximum withdrawal: $<?php echo number_format(MAX_WITHDRAWAL_AMOUNT, 2); ?></p>
                                </div>

                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[#4c1d95] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#3b1675] focus:outline-none focus:ring-4 focus:ring-violet-500/20">
                                    Initiate withdrawal
                                </button>
                            </form>
                        </div>
                    </div>
            <?php endif; ?>
        </div>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Withdrawal checklist</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Check your available balance</p>
                        <p class="mt-1 text-sm text-slate-500">Pending payouts reduce the amount you can request right now.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Use accurate payout details</p>
                        <p class="mt-1 text-sm text-slate-500">Incorrect payment information may delay or fail the withdrawal process.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-emerald-900">Quick rules</h2>
                <ul class="mt-4 space-y-3 text-sm text-emerald-900/80">
                    <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-violet-600"></span><span>Minimum request is $<?php echo number_format(MIN_WITHDRAWAL_AMOUNT, 2); ?>.</span></li>
                    <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-violet-600"></span><span>Maximum request is $<?php echo number_format(MAX_WITHDRAWAL_AMOUNT, 2); ?>.</span></li>
                    <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-violet-600"></span><span>A 15% processing fee is deducted from each request.</span></li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<script>
function showFields(method) {
    document.getElementById('bankFields').classList.add('hidden');
    document.getElementById('mobileFields').classList.add('hidden');
    document.getElementById('paypalFields').classList.add('hidden');
    document.getElementById(`${method}Fields`).classList.remove('hidden');
}

function validateWithdrawalForm(event) {
    const amount = parseFloat(document.getElementById('amount').value);
    if (isNaN(amount) || amount <= 0) {
        alert('Please enter a valid withdrawal amount');
        event.preventDefault();
        return false;
    }

    const maxAmount = <?php echo min($availableBalance, MAX_WITHDRAWAL_AMOUNT); ?>;
    const minAmount = <?php echo MIN_WITHDRAWAL_AMOUNT; ?>;

    if (amount < minAmount) {
        alert(`Withdrawal amount must be at least $${minAmount.toFixed(2)}`);
        event.preventDefault();
        return false;
    }

    if (amount > maxAmount) {
        alert(`Withdrawal amount cannot exceed $${maxAmount.toFixed(2)}`);
        event.preventDefault();
        return false;
    }

    return true;
}

function confirmDelete() {
    return confirm('Are you sure you want to cancel this withdrawal?');
}

const withdrawalForm = document.querySelector('form[action*="process_withdrawal.php"]');
if (withdrawalForm) {
    withdrawalForm.addEventListener('submit', function () {
        const submitButton = withdrawalForm.querySelector('button[type="submit"]');
        if (!submitButton || submitButton.disabled) {
            return;
        }

        submitButton.disabled = true;
        submitButton.classList.add('opacity-70', 'cursor-not-allowed');
        submitButton.textContent = 'Processing...';
    });
}
</script>
<?php
}

if (!$isAjaxRequest) {
    $hideHeaderCreateButton = true;
    $hidePremiumHeaderBanner = true;
    $useDashboardShell = true;
    include '../components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>Initiate Withdrawal - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 1023px) {
            body.withdrawal-initiate-page > .flex.min-h-screen {
                padding-left: 0;
                padding-top: 64px;
            }

            body.withdrawal-initiate-page .dashboard-app-header {
                left: 0;
                height: 64px;
            }

            body.withdrawal-initiate-page .dashboard-app-header > div {
                padding-left: 16px;
                padding-right: 16px;
            }

            body.withdrawal-initiate-page .dashboard-app-header > div > div > div:first-child {
                position: static;
                flex: 1 1 100%;
                justify-content: center;
            }

            body.withdrawal-initiate-page #app-brand-link {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                gap: 10px;
                padding: 0;
            }

            body.withdrawal-initiate-page #app-brand-link > span:first-child {
                display: flex;
                width: 34px;
                height: 34px;
                align-items: center;
                justify-content: center;
                border-radius: 0;
                background: transparent;
                box-shadow: none;
            }

            body.withdrawal-initiate-page #app-brand-link > span:first-child svg {
                width: 34px;
                height: 34px;
            }

            body.withdrawal-initiate-page #app-brand-link > span:last-child {
                display: inline;
                color: #274a32;
                font-size: 0.9rem;
            }

            body.withdrawal-initiate-page .dashboard-app-header > div > div > div:nth-child(2) {
                position: absolute;
                right: 16px;
                display: flex;
                width: auto;
                flex: 0 0 auto;
                justify-content: flex-end;
            }

            body.withdrawal-initiate-page .dashboard-app-header > div > div > div:nth-child(2) img {
                width: 34px;
                height: 34px;
                border: 0;
                border-radius: 50%;
                box-shadow: 0 0 0 1px #e7e5e1;
            }

            body.withdrawal-initiate-page .dashboard-app-header > div > div > div:last-child {
                display: none;
            }

            body.withdrawal-initiate-page #sidebar-toggle,
            body.withdrawal-initiate-page #sidebar,
            body.withdrawal-initiate-page .sidebar-overlay {
                display: none !important;
            }

            body.withdrawal-initiate-page .app-main {
                flex: 1 1 100%;
                width: 100%;
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="withdrawal-initiate-page min-h-screen bg-[#f3f2ef]">
    <div class="flex min-h-screen pt-4">
        <?php include '../components/sidebar.php'; ?>
        <main class="app-main min-w-0 flex-1 bg-[#f3f2ef] lg:ml-64">
            <?php renderWithdrawalInitiateContent(); ?>
        </main>
    </div>
    <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-[#e7e5e1] bg-white px-2 pt-2 pb-[env(safe-area-inset-bottom)] shadow-[0_-3px_14px_rgb(28_28_28_/_5%)] lg:hidden" aria-label="Dashboard navigation">
        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition" aria-label="Home">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
            Home
        </a>
        <a href="<?php echo BASE_URL; ?>/campaigns/index.php" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition" aria-label="Campaigns">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm7 0v5h5M9 13h6m-6 4h6"/></svg>
            Campaigns
        </a>
        <a href="<?php echo BASE_URL; ?>/donations/" class="flex flex-col items-center gap-1 rounded-xl bg-violet-50 px-2 py-2 text-[11px] font-semibold text-violet-800 transition" aria-current="page" aria-label="Donations">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/></svg>
            Donations
        </a>
        <a href="<?php echo BASE_URL; ?>/account/settings.php" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[11px] font-semibold text-slate-500 transition" aria-label="Account">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
            Account
        </a>
    </nav>
</body>
</html>
<?php
} else {
    renderWithdrawalInitiateContent();
    echo ob_get_clean();
}
