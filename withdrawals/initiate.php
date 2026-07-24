<?php
session_start();
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/Withdrawal.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/TrustRiskGuard.php';
require_once __DIR__ . '/../config/IdVerificationService.php';

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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

$campaign = new Campaign();
$withdrawal = new Withdrawal();
$idVerificationService = new IdVerificationService();

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
$latestVerificationAttempt = $idVerificationService->getLatestAttempt((int) $_SESSION['user_id']);
$hasPendingVerificationReview = $idVerificationService->hasPendingReview((int) $_SESSION['user_id']);
$latestVerificationWorkflowLabel = $idVerificationService->getWorkflowStatusLabel($latestVerificationAttempt);

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

    if ($hasPendingVerificationReview) {
        $_SESSION['error_message'] = 'Your most recent ID submission is still pending review. Please wait for the review outcome before uploading another image.';
        header('Location: ' . BASE_URL . '/withdrawals/initiate.php');
        exit();
    }

    try {
        $verificationUpload = $idVerificationService->storeUploadedNationalId($_FILES['national_id'] ?? [], (int) $_SESSION['user_id']);
        $campaign->markVerificationAttempted($_SESSION['user_id']);

        $ocrStatus = (string) ($verificationUpload['ocr']['status'] ?? 'unavailable');
        $ocrSummary = $ocrStatus === 'extracted'
            ? ' OCR extracted candidate identity text from the processed copy.'
            : ' OCR is not yet available or did not extract readable text.';

        $decision = $verificationUpload['decision'] ?? ['status' => 'manual_review', 'message' => 'Manual review is required.', 'score' => 0];
        $decisionSummary = ' Decision status: ' . ucfirst(str_replace('_', ' ', (string) ($decision['status'] ?? 'manual_review'))) . ' (score ' . (int) ($decision['score'] ?? 0) . '). ' . (string) ($decision['message'] ?? '');

        $_SESSION['success_message'] = sprintf(
            'Your National ID image was uploaded successfully, passed initial quality checks (%dx%d), and a verification-ready processed copy was created. Stored file: %s.%s%s',
            (int) ($verificationUpload['quality']['width'] ?? 0),
            (int) ($verificationUpload['quality']['height'] ?? 0),
            basename((string) ($verificationUpload['stored_path'] ?? '')),
            $ocrSummary,
            $decisionSummary
        );
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }

    header('Location: ' . BASE_URL . '/withdrawals/initiate.php');
    exit();
}

if ($isAjaxRequest) {
    ob_start();
}

function renderWithdrawalInitiateContent(): void {
    global $hasVerifiedId, $errorMessage, $verificationAttempts, $remainingAttempts, $hasExceededMaxAttempts, $pendingWithdrawalsList, $totalDonations, $pendingWithdrawals, $availableBalance, $totalWithdrawn, $userInfo, $latestVerificationAttempt, $hasPendingVerificationReview, $latestVerificationWorkflowLabel;
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

                        <?php if ($latestVerificationAttempt): ?>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                <p class="font-semibold text-slate-900">Latest verification attempt</p>
                                <p class="mt-1"><?php echo htmlspecialchars($latestVerificationWorkflowLabel); ?></p>
                                <?php if (!empty($latestVerificationAttempt['failure_reason'])): ?>
                                    <p class="mt-1"><strong>Reason:</strong> <?php echo htmlspecialchars((string) $latestVerificationAttempt['failure_reason']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($hasPendingVerificationReview): ?>
                            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-800">
                                Your latest ID submission is currently pending review. New uploads are temporarily disabled until that review is completed.
                            </div>
                        <?php endif; ?>

                        <form id="idVerificationForm" method="POST" action="<?php echo BASE_URL; ?>/withdrawals/initiate.php" enctype="multipart/form-data" class="space-y-4 <?php echo $hasPendingVerificationReview ? 'opacity-60 pointer-events-none' : ''; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="verify_national_id" value="1">

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <input type="file" id="nationalIdFile" name="national_id" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="handleIdUpload(event)" <?php echo $hasPendingVerificationReview ? 'disabled' : ''; ?>>
                                <button type="button" onclick="document.getElementById('nationalIdFile').click()" <?php echo $hasPendingVerificationReview ? 'disabled' : ''; ?> class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700 disabled:cursor-not-allowed disabled:opacity-70">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <span id="uploadButtonText">Choose image</span>
                                </button>
                                <span class="text-sm text-slate-500">JPG, PNG, or WEBP up to 10MB. Use a bright, sharp photo with all edges visible.</span>
                            </div>

                            <div id="fileSelectedIndicator" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                Image selected successfully.
                            </div>



                            <div class="flex justify-start">
                                <button type="submit" <?php echo $hasPendingVerificationReview ? 'disabled' : ''; ?> class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-70">
                                    Upload for verification
                                </button>
                            </div>
                        </form>
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
function handleIdUpload(event) {
    const file = event.target.files[0];
    const indicator = document.getElementById('fileSelectedIndicator');
    const buttonText = document.getElementById('uploadButtonText');

    if (!file) {
        indicator.classList.add('hidden');
        buttonText.textContent = 'Choose image';
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Please select a JPG, PNG, or WEBP image.');
        event.target.value = '';
        indicator.classList.add('hidden');
        buttonText.textContent = 'Choose image';
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        alert('File size must be less than 10MB');
        event.target.value = '';
        indicator.classList.add('hidden');
        buttonText.textContent = 'Choose image';
        return;
    }

    indicator.textContent = 'Selected: ' + file.name;
    indicator.classList.remove('hidden');
    buttonText.textContent = 'Change image';
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
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen pt-16">
        <?php include '../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
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
