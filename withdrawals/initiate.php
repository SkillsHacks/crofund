<?php
session_start();
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/Withdrawal.php';
require_once __DIR__ . '/../config/User.php';

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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

$campaign = new Campaign();
$withdrawal = new Withdrawal();

$totalDonations = $campaign->getUserTotalDonations($_SESSION['user_id']);
$totalWithdrawn = $withdrawal->getTotalApprovedWithdrawals($_SESSION['user_id']);
$pendingWithdrawals = $withdrawal->getPendingWithdrawals($_SESSION['user_id']);
$userInfo = $campaign->getUserInfo($_SESSION['user_id']);
$hasVerifiedId = $campaign->hasUserVerifiedId($_SESSION['user_id']);
$verificationAttempts = $campaign->getVerificationAttempts($_SESSION['user_id']);
$hasExceededMaxAttempts = $campaign->hasExceededMaxAttempts($_SESSION['user_id'], 3);
$remainingAttempts = 3 - $verificationAttempts;
$hasAttemptedVerification = $campaign->hasUserAttemptedVerification($_SESSION['user_id']);
$availableBalance = $totalDonations - $totalWithdrawn - $pendingWithdrawals;
$pendingWithdrawalsList = $withdrawal->getPendingWithdrawalsList($_SESSION['user_id']);

if ($availableBalance < MIN_WITHDRAWAL_AMOUNT) {
    $errorMessage = 'Minimum withdrawal amount is $' . number_format(MIN_WITHDRAWAL_AMOUNT, 2);
}

$attemptCount = $withdrawal->getWithdrawalAttempts($_SESSION['user_id'], 24);
if ($attemptCount >= MAX_WITHDRAWAL_ATTEMPTS) {
    $errorMessage = 'Maximum withdrawal attempts reached. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_national_id'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid verification request. Please try again.';
        header('Location: ' . BASE_URL . '/withdrawals/initiate.php');
        exit();
    }

    if ($hasVerifiedId) {
        $_SESSION['success_message'] = 'Your ID is already verified. You can proceed to make a withdrawal.';
        header('Location: ' . BASE_URL . '/withdrawals/initiate.php');
        exit();
    }

    if ($hasExceededMaxAttempts) {
        $_SESSION['error_message'] = 'You have exceeded the maximum number of verification attempts.';
        header('Location: ' . BASE_URL . '/withdrawals/initiate.php');
        exit();
    }

    $verificationPassed = ($_POST['id_verified'] ?? '0') === '1';

    try {
        $campaign->markVerificationAttempted($_SESSION['user_id']);

        if ($verificationPassed) {
            $campaign->markIdAsVerified($_SESSION['user_id']);
            $_SESSION['success_message'] = 'ID verification successful. You can now proceed to make a withdrawal.';
        } else {
            $campaign->incrementVerificationAttempts($_SESSION['user_id']);
            $_SESSION['error_message'] = 'ID verification failed. Please try again with a clearer image.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Unable to update verification status right now. Please try again.';
    }

    header('Location: ' . BASE_URL . '/withdrawals/initiate.php');
    exit();
}

if ($isAjaxRequest) {
    ob_start();
}

function renderWithdrawalInitiateContent(): void {
    global $hasVerifiedId, $errorMessage, $verificationAttempts, $remainingAttempts, $hasExceededMaxAttempts, $pendingWithdrawalsList, $totalDonations, $pendingWithdrawals, $availableBalance, $totalWithdrawn, $userInfo;
?>
<div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr),360px]">
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-white/50 to-transparent lg:block"></div>
                <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        Withdraw funds
                    </div>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Move funds with confidence</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                        Review your available balance, confirm your verification status, and choose the withdrawal method that works best for you.
                    </p>
                </div>
            </section>

            <?php if ($hasVerifiedId): ?>
                <div class="rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 9v2m0 4v2m0-6V9m0 6h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-yellow-800">
                            <strong>A 15% fee</strong> is deducted from each withdrawal request. <strong>Minimum withdrawal amount is $<?php echo number_format(MIN_WITHDRAWAL_AMOUNT, 2); ?></strong> and <strong>maximum withdrawal amount is $<?php echo number_format(MAX_WITHDRAWAL_AMOUNT, 2); ?></strong>.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-red-700"><?php echo htmlspecialchars($errorMessage); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm font-medium text-emerald-800">Withdrawal initiated successfully!</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm font-medium text-emerald-800"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-red-700"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if ($hasVerifiedId): ?>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Total donations</p>
                        <p class="mt-3 text-2xl font-bold text-slate-900">$<?php echo number_format($totalDonations, 2); ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Pending withdrawals</p>
                        <p class="mt-3 text-2xl font-bold text-yellow-700">$<?php echo number_format($pendingWithdrawals, 2); ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Available now</p>
                        <p class="mt-3 text-2xl font-bold text-emerald-700">$<?php echo number_format($availableBalance, 2); ?></p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-slate-500">Completed withdrawals</p>
                        <p class="mt-3 text-2xl font-bold text-slate-900">$<?php echo number_format($totalWithdrawn, 2); ?></p>
                    </div>
                </div>
            <?php elseif (!$hasExceededMaxAttempts): ?>
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 class="text-lg font-semibold text-slate-900">Verify your National ID</h2>
                        <p class="mt-1 text-sm text-slate-500">We need to confirm your identity before withdrawals can be processed.</p>
                    </div>
                    <div class="space-y-5 px-5 py-5 sm:px-6 sm:py-6">
                        <?php if ($verificationAttempts > 0): ?>
                            <div class="rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-4">
                                <p class="text-sm text-yellow-800">
                                    <strong>Attempts:</strong> <?php echo $verificationAttempts; ?>/3 completed.
                                    <strong>Remaining:</strong> <?php echo $remainingAttempts; ?> attempt<?php echo $remainingAttempts !== 1 ? 's' : ''; ?>.
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="space-y-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <input type="file" id="nationalIdFile" name="national_id" accept="image/*" class="hidden" onchange="handleIdUpload(event)">
                                <button type="button" onclick="document.getElementById('nationalIdFile').click()" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <span id="uploadButtonText">Choose image</span>
                                </button>
                                <span class="text-sm text-slate-500">PNG, JPG, JPEG up to 10MB</span>
                            </div>

                            <div id="fileSelectedIndicator" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                Image selected successfully.
                            </div>

                            <div class="flex justify-start">
                                <button type="button" onclick="processIdImage()" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700">
                                    Submit for verification
                                </button>
                            </div>

                            <div id="ocrStatus" class="hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <div class="flex items-center gap-3">
                                    <div class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-600 border-b-transparent"></div>
                                    <span>Processing ID image...</span>
                                </div>
                            </div>

                            <div id="verificationResults" class="hidden rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div id="extractedNames" class="mb-3 text-sm text-slate-600"></div>
                                <div id="verificationStatus" class="text-sm font-medium"></div>
                            </div>

                            <form id="idVerificationForm" method="POST" action="<?php echo BASE_URL; ?>/withdrawals/initiate.php" class="hidden">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="verify_national_id" value="1">
                                <input type="hidden" id="idVerified" name="id_verified" value="0">
                            </form>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="rounded-3xl border border-red-200 bg-white p-6 shadow-sm">
                    <div class="text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-3xl bg-red-100 text-red-600">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <h2 class="mt-4 text-xl font-semibold text-slate-900">Verification failed</h2>
                        <p class="mt-2 text-sm text-slate-600">You have exceeded the maximum number of verification attempts (3/3).</p>
                        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-left text-sm text-red-700">
                            <p><strong>Account status:</strong> Withdrawal access denied due to failed ID verification after 3 attempts.</p>
                            <p class="mt-2"><strong>Attempts made:</strong> <?php echo $verificationAttempts; ?>/3</p>
                            <p class="mt-2">Please contact support if you believe this is an error or if you need assistance with verification.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($hasVerifiedId && !empty($pendingWithdrawalsList)): ?>
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

            <?php if ($hasVerifiedId): ?>
                <div class="<?php echo $errorMessage ? 'opacity-50 pointer-events-none' : ''; ?> overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 class="text-lg font-semibold text-slate-900">Choose withdrawal method</h2>
                        <p class="mt-1 text-sm text-slate-500">Provide the payment details needed to complete your payout.</p>
                    </div>
                    <div class="px-5 py-5 sm:px-6 sm:py-6">
                        <form action="<?php echo BASE_URL; ?>/withdrawals/process_withdrawal.php" method="POST" class="space-y-6" onsubmit="return validateWithdrawalForm(event)">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <label class="flex cursor-pointer items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <input type="radio" name="withdrawal_method" value="bank" class="form-radio text-emerald-600" required onclick="showFields('bank')">
                                    <span class="ml-3">Bank transfer</span>
                                </label>
                                <label class="flex cursor-pointer items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <input type="radio" name="withdrawal_method" value="mobile" class="form-radio text-emerald-600" onclick="showFields('mobile')">
                                    <span class="ml-3">Mobile money</span>
                                </label>
                                <label class="flex cursor-pointer items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50/60">
                                    <input type="radio" name="withdrawal_method" value="paypal" class="form-radio text-emerald-600" onclick="showFields('paypal')">
                                    <span class="ml-3">PayPal</span>
                                </label>
                            </div>

                            <div id="bankFields" class="hidden space-y-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <input type="text" name="account_name" placeholder="Account Name" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                    <input type="text" name="bank_name" placeholder="Bank Name" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                </div>
                                <input type="text" name="account_number" placeholder="Account Number" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div id="mobileFields" class="hidden space-y-4">
                                <input type="text" name="full_name" placeholder="Full Name" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <select name="country" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                        <option value="">Select Country</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Tanzania">Tanzania</option>
                                        <option value="Rwanda">Rwanda</option>
                                    </select>
                                    <select name="mobile_provider" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                        <option value="">Select Mobile Money Provider</option>
                                        <option value="MTN">MTN</option>
                                        <option value="Airtel">Airtel</option>
                                        <option value="Vodafone">Vodafone</option>
                                        <option value="Safaricom">Safaricom</option>
                                    </select>
                                </div>
                                <input type="text" name="phone_number" placeholder="Phone Number" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div id="paypalFields" class="hidden space-y-4">
                                <input type="email" name="paypal_email" placeholder="PayPal Email" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>

                            <div>
                                <label for="amount" class="block text-sm font-semibold text-slate-700">Withdrawal amount</label>
                                <input type="number" id="amount" name="amount" min="<?php echo number_format(MIN_WITHDRAWAL_AMOUNT, 2, '.', ''); ?>" max="<?php echo min($availableBalance, MAX_WITHDRAWAL_AMOUNT); ?>" step="0.01" required placeholder="Enter amount"
                                       class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                <p class="mt-2 text-sm text-slate-500">Minimum withdrawal: $<?php echo number_format(MIN_WITHDRAWAL_AMOUNT, 2); ?> • Available balance: $<?php echo number_format($availableBalance, 2); ?> • Maximum withdrawal: $<?php echo number_format(MAX_WITHDRAWAL_AMOUNT, 2); ?></p>
                            </div>

                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
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
                        <p class="text-sm font-semibold text-slate-900">Verify your ID first</p>
                        <p class="mt-1 text-sm text-slate-500">Withdrawals are available only after identity verification is complete.</p>
                    </div>
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

            <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-emerald-900">Quick rules</h2>
                <ul class="mt-4 space-y-3 text-sm text-emerald-900/80">
                    <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span><span>Minimum request is $<?php echo number_format(MIN_WITHDRAWAL_AMOUNT, 2); ?>.</span></li>
                    <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span><span>Maximum request is $<?php echo number_format(MAX_WITHDRAWAL_AMOUNT, 2); ?>.</span></li>
                    <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span><span>A 15% processing fee is deducted from each request.</span></li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<script>
let uploadedImageFile = null;
let extractedData = {};
let rawOcrText = '';
let isDevelopmentMode = true;
let accountNames = {
    full_name: '<?php echo isset($userInfo['name']) ? addslashes($userInfo['name']) : ''; ?>',
    last_name: '<?php
        if (isset($userInfo['name'])) {
            $nameParts = explode(' ', trim($userInfo['name']));
            echo addslashes(end($nameParts));
        }
    ?>'
};

function handleIdUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        alert('Please select a valid image file');
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        alert('File size must be less than 10MB');
        return;
    }

    uploadedImageFile = file;
    document.getElementById('fileSelectedIndicator').classList.remove('hidden');
    document.getElementById('uploadButtonText').textContent = 'Change image';
}

function clearImage() {
    uploadedImageFile = null;
    document.getElementById('nationalIdFile').value = '';
    document.getElementById('fileSelectedIndicator').classList.add('hidden');
    document.getElementById('uploadButtonText').textContent = 'Choose image';
    document.getElementById('ocrStatus').classList.add('hidden');
    document.getElementById('verificationResults').classList.add('hidden');
    document.getElementById('idVerified').value = '0';
}

async function preprocessImage(file) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();

        img.onload = function() {
            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;

            for (let i = 0; i < data.length; i += 4) {
                const gray = data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
                const enhanced = gray > 128 ? 255 : 0;
                data[i] = enhanced;
                data[i + 1] = enhanced;
                data[i + 2] = enhanced;
            }

            ctx.putImageData(imageData, 0, 0);
            canvas.toBlob((blob) => resolve(blob), 'image/png');
        };

        img.src = URL.createObjectURL(file);
    });
}

async function processIdImage() {
    if (!uploadedImageFile) {
        alert('Please upload an ID image first');
        return;
    }

    document.getElementById('ocrStatus').classList.remove('hidden');
    document.getElementById('verificationResults').classList.add('hidden');

    try {
        const processedImage = await preprocessImage(uploadedImageFile);
        const { data: { text } } = await Tesseract.recognize(
            processedImage,
            'eng',
            {
                logger: () => {},
                tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 :.,-()',
                tessedit_pageseg_mode: Tesseract.PSM.AUTO,
                preserve_interword_spaces: '1'
            }
        );

        rawOcrText = text;
        extractedData = extractNamesFromText(text);
        let verificationResult = compareExtractedData(extractedData, accountNames);

        displayVerificationResults(extractedData, verificationResult);
    } catch (error) {
        console.error('OCR Error:', error);
        alert('Error processing ID image. Please try again with a clearer image.');
    } finally {
        document.getElementById('ocrStatus').classList.add('hidden');
    }
}

function extractNamesFromText(text) {
    const extractedData = {};
    const surnamePatterns = [
        /surname[:\s]+([A-Z\s]+)/i,
        /surname[:\s]*\n?\s*([A-Z\s]+)/i,
        /([A-Z]{3,})\s*[A-Z\s]*\s*[A-Z]{3,}/,
        /([A-Z]{4,})/,
        /REPUBLIC OF UGANDA[\s\S]*?([A-Z]{4,})/,
        /NATIONAL ID CARD[\s\S]*?([A-Z]{4,})/
    ];

    for (const pattern of surnamePatterns) {
        const match = text.match(pattern);
        if (match) {
            const extracted = match[1].trim();
            if (extracted && extracted !== 'SURNAME' && extracted !== 'REPUBLIC' && extracted !== 'UGANDA' && extracted !== 'NATIONAL' && extracted.length > 2) {
                extractedData.last_name = extracted;
                break;
            }
        }
    }

    if (!extractedData.last_name) {
        const knownNames = ['KYAMBADDE', 'NYAKASIKI', 'MUGISHA', 'KATONGOLE', 'NAKAMYA'];
        for (const name of knownNames) {
            const nameMatch = text.match(new RegExp(`(${name})`, 'i'));
            if (nameMatch) {
                extractedData.last_name = nameMatch[1];
                break;
            }
        }
    }

    const dobPatterns = [
        /date\s+of\s+birth[:\s]*([0-9]{2}\.[0-9]{2}\.[0-9]{4})/i,
        /date\s+of\s+birth[:\s]*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i,
        /date\s+of\s+birth[:\s]*([0-9]{2}-[0-9]{2}-[0-9]{4})/i,
        /birth[:\s]*([0-9]{2}\.[0-9]{2}\.[0-9]{4})/i,
        /birth[:\s]*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i,
        /birth[:\s]*([0-9]{2}-[0-9]{2}-[0-9]{4})/i,
        /([0-9]{2}\.[0-9]{2}\.[0-9]{4})/,
        /([0-9]{2}\/[0-9]{2}\/[0-9]{4})/,
        /([0-9]{2}-[0-9]{2}-[0-9]{4})/
    ];

    for (const pattern of dobPatterns) {
        const match = text.match(pattern);
        if (match) {
            extractedData.date_of_birth = match[1];
            break;
        }
    }

    const idNumberMatch = text.match(/([0-9]{10,})/);
    if (idNumberMatch) {
        extractedData.id_number = idNumberMatch[1];
    }

    return extractedData;
}

function compareExtractedData(extractedData, accountNames) {
    let nameMatch = false;
    if (extractedData.last_name && accountNames.full_name) {
        const extractedSurname = extractedData.last_name.toLowerCase().trim();
        const fullName = accountNames.full_name.toLowerCase().trim();
        const nameParts = fullName.split(' ');

        for (let part of nameParts) {
            part = part.trim();
            const similarity = calculateSimilarity(extractedSurname, part);
            if (part && similarity > 0.8) {
                nameMatch = true;
                break;
            }
        }

        if (!nameMatch && fullName.includes(extractedSurname)) {
            nameMatch = true;
        }
    }

    return nameMatch;
}

function calculateSimilarity(str1, str2) {
    const s1 = str1.toLowerCase().trim();
    const s2 = str2.toLowerCase().trim();

    if (s1 === s2) return 1.0;
    if (s1.includes(s2) || s2.includes(s1)) return 0.9;
    return 0;
}

function displayVerificationResults(extractedData, verificationResult) {
    const resultsDiv = document.getElementById('verificationResults');
    const namesDiv = document.getElementById('extractedNames');
    const statusDiv = document.getElementById('verificationStatus');

    let namesText = '';
    if (extractedData.last_name) {
        namesText += `<strong>Extracted Surname:</strong> ${extractedData.last_name}<br>`;
    }
    if (extractedData.date_of_birth) {
        namesText += `<strong>Date of Birth:</strong> ${extractedData.date_of_birth}<br>`;
    }
    if (extractedData.id_number) {
        namesText += `<strong>ID Number:</strong> ${extractedData.id_number}<br>`;
    }

    namesDiv.innerHTML = namesText;

    if (verificationResult) {
        statusDiv.innerHTML = '<strong class="text-emerald-600">✅ Verification Successful!</strong> Finalizing your verification and unlocking withdrawals...';
        document.getElementById('idVerified').value = '1';
        setTimeout(() => {
            document.getElementById('idVerificationForm').submit();
        }, 300);
    } else {
        statusDiv.innerHTML = '<strong class="text-red-600">❌ Verification Failed!</strong> We could not verify your identity from the provided image. Please try again with a clearer photo.';
        document.getElementById('idVerified').value = '0';
        setTimeout(() => {
            document.getElementById('idVerificationForm').submit();
        }, 300);
    }

    resultsDiv.classList.remove('hidden');
}

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
</script>
<?php
}

if (!$isAjaxRequest) {
    include '../components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <title>Initiate Withdrawal - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen pt-16">
        <?php include '../components/sidebar.php'; ?>
        <main class="flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <?php renderWithdrawalInitiateContent(); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderWithdrawalInitiateContent();
    echo ob_get_clean();
}
