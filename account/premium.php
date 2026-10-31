<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PremiumAccountService.php';
require_once __DIR__ . '/../config/RelworxPayoutService.php';
require_once __DIR__ . '/../config/User.php';

$user = new User();
error_log('[PREMIUM_DEBUG] entered premium page. session=' . session_id() . ' request_uri=' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

if (!$user->isLoggedIn()) {
    $requestedUri = normalizeRedirectPath($_SERVER['REQUEST_URI'] ?? '/');
    $_SESSION['redirect_after_login'] = $requestedUri;
    crofund_debug_log('premium guard', [
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
        'normalized' => $requestedUri,
        'session_redirect_after_login' => $_SESSION['redirect_after_login'] ?? null,
        'session' => $_SESSION,
    ]);
    error_log('[PREMIUM_DEBUG] user not logged in -> redirecting to login. requestedUri=' . $requestedUri);
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$currentUser = $user->getCurrentUser();
if (!$currentUser) {
    error_log('[PREMIUM_DEBUG] user is logged in but getCurrentUser() returned null. session=' . print_r($_SESSION, true));
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

error_log('[PREMIUM_DEBUG] current user loaded: id=' . ($currentUser['id'] ?? 'unknown') . ' email=' . ($currentUser['email'] ?? 'unknown'));

$db = Database::getInstance()->getConnection();
error_log('[PREMIUM_DEBUG] database connected for premium page');

try {
    $premiumService = new PremiumAccountService($db);
    $plans = $premiumService->getPlans();
    $hasPremiumAccess = $premiumService->hasPremiumAccess((int) $_SESSION['user_id']);
    error_log('[PREMIUM_DEBUG] premium plans loaded: ' . count($plans) . ' | hasPremiumAccess=' . ($hasPremiumAccess ? '1' : '0'));
} catch (Throwable $e) {
    error_log('[PREMIUM_DEBUG] premium service failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    throw $e;
}

$notice = '';
$error = '';
$defaultPaymentPhone = trim((string) ($currentUser['phone'] ?? ''));
$submittedPaymentPhone = trim((string) ($_POST['payment_phone'] ?? $defaultPaymentPhone));

$normalizeRelworxMsisdn = static function (?string $rawPhone): string {
    $digits = preg_replace('/\D+/', '', (string) $rawPhone);
    if ($digits === '') {
        return '';
    }

    if (strlen($digits) >= 12 && strpos($digits, '256') === 0) {
        return '+' . $digits;
    }

    if (strlen($digits) >= 10 && strpos($digits, '0') === 0) {
        return '+256' . substr($digits, 1);
    }

    return '+' . $digits;
};

$pollRelworxRequestStatus = static function (string $internalReference, string $accountNo): array {
    $maxAttempts = 18;
    $delaySeconds = 5;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $statusUrl = RELWORX_BASE_URL . '/check-request-status?internal_reference=' . rawurlencode($internalReference) . '&account_no=' . rawurlencode($accountNo);

        $statusCurl = curl_init($statusUrl);
        curl_setopt_array($statusCurl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . RELWORX_API_KEY,
            ],
        ]);

        $statusResponse = curl_exec($statusCurl);
        $statusHttpCode = curl_getinfo($statusCurl, CURLINFO_HTTP_CODE);
        $statusError = curl_error($statusCurl);
        curl_close($statusCurl);

        error_log('[RELWORX_DEBUG] poll attempt=' . $attempt . ' status_http_code=' . (string) $statusHttpCode . ' status_error=' . ($statusError ?: 'none') . ' raw_status=' . (is_string($statusResponse) ? $statusResponse : json_encode($statusResponse)));

        if ($statusResponse === false || $statusError !== '') {
            if ($attempt < $maxAttempts) {
                sleep($delaySeconds);
                continue;
            }

            throw new RuntimeException('Relworx status check failed: ' . ($statusError ?: 'Unknown network error.'));
        }

        $statusDecoded = json_decode($statusResponse, true);
        if (!is_array($statusDecoded)) {
            if ($attempt < $maxAttempts) {
                sleep($delaySeconds);
                continue;
            }

            throw new RuntimeException('Relworx returned an invalid status response.');
        }

        $requestStatus = strtolower((string) ($statusDecoded['request_status'] ?? $statusDecoded['status'] ?? 'pending'));
        $successFlag = !empty($statusDecoded['success']);

        if ($successFlag && in_array($requestStatus, ['success', 'completed'], true)) {
            return $statusDecoded;
        }

        if ($successFlag && in_array($requestStatus, ['failed', 'rejected', 'cancelled'], true)) {
            throw new RuntimeException('Relworx reported the payment as failed or rejected.');
        }

        if ($attempt < $maxAttempts) {
            sleep($delaySeconds);
            continue;
        }

        throw new RuntimeException('Relworx payment is still pending. Please check again later.');
    }

    throw new RuntimeException('Relworx payment status could not be confirmed.');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['premium_plan'])) {
    try {
        $planCode = trim((string) $_POST['premium_plan']);
        if ($planCode === '') {
            throw new InvalidArgumentException('Please choose a premium plan.');
        }

        if (empty(RELWORX_API_KEY) || empty(RELWORX_ACCOUNT_NO)) {
            throw new RuntimeException('Mobile Money credentials are not configured yet. Add relworx_api_key and relworx_account_no to the settings table before testing the payment flow.');
        }

        $request = $premiumService->createUpgradeRequest((int) $_SESSION['user_id'], $planCode);
        $expectedAmount = (float) $request['amount'];
        if ($expectedAmount <= 0) {
            throw new RuntimeException('The selected premium plan amount is invalid.');
        }

        $reference = 'premium_' . $request['transaction_id'];
        $paymentPhone = trim((string) ($_POST['payment_phone'] ?? $defaultPaymentPhone));
        $msisdn = $normalizeRelworxMsisdn($paymentPhone !== '' ? $paymentPhone : (string) ($currentUser['phone'] ?? ''));

        if ($msisdn === '' || $msisdn === '+') {
            throw new RuntimeException('A valid phone number is required before initiating a Mobile Money payment request.');
        }

        $digitsOnly = preg_replace('/\D+/', '', $msisdn);
        if ($digitsOnly === '' || strlen($digitsOnly) < 10 || strlen($digitsOnly) > 15) {
            throw new RuntimeException('The Mobile Money number is invalid or malformed. Please provide a valid Ugandan phone number.');
        }

        if (!preg_match('/^(?:\+?256|0)(?:7\d{8}|6\d{8}|5\d{8}|4\d{8}|3\d{8}|2\d{8})$/', $msisdn) && !preg_match('/^(?:\+?256|0)(?:7\d{8}|6\d{8}|5\d{8}|4\d{8}|3\d{8}|2\d{8})$/', $paymentPhone)) {
            throw new RuntimeException('Only valid Ugandan Mobile Money numbers are supported for premium payment requests.');
        }

        $mobileMoneyPayload = [
            'account_no' => RELWORX_ACCOUNT_NO,
            'reference' => $reference,
            'msisdn' => $msisdn,
            'currency' => 'UGX',
            'amount' => $expectedAmount,
            'description' => 'Premium upgrade for ' . ($currentUser['name'] ?? 'user') . ' (' . $request['plan_code'] . ')',
        ];

        error_log('[RELWORX_DEBUG] request payload: ' . json_encode($mobileMoneyPayload));
        error_log('[RELWORX_DEBUG] api key present: ' . (empty(RELWORX_API_KEY) ? 'no' : 'yes') . ' account_no present: ' . (empty(RELWORX_ACCOUNT_NO) ? 'no' : 'yes'));

        $curl = curl_init(RELWORX_BASE_URL . '/request-payment');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/vnd.relworx.v2',
                'Authorization: Bearer ' . RELWORX_API_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode($mobileMoneyPayload),
            CURLOPT_VERBOSE => false,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        error_log('[RELWORX_DEBUG] http_code=' . (string) $httpCode . ' curl_error=' . ($curlError ?: 'none') . ' raw_response=' . (is_string($response) ? $response : json_encode($response)));

        if ($response === false || $curlError !== '') {
            throw new RuntimeException('Mobile Money request failed: ' . ($curlError ?: 'Unknown network error.'));
        }

        $httpStatus = (int) $httpCode;
        if ($httpStatus >= 400) {
            $message = 'Mobile Money rejected the payment request. HTTP ' . $httpStatus . '. Response: ' . (string) $response;
            error_log('[RELWORX_DEBUG] ' . $message);
            throw new RuntimeException('Mobile Money rejected the payment request. Please check the phone number and account configuration.');
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['success'])) {
            $message = $decoded['message'] ?? 'The payment request could not be processed.';
            throw new RuntimeException($message);
        }

        $returnedAmount = 0.0;
        foreach (['amount', 'requested_amount', 'amount_paid', 'total_amount'] as $amountKey) {
            if (array_key_exists($amountKey, $decoded) && is_numeric($decoded[$amountKey])) {
                $returnedAmount = (float) $decoded[$amountKey];
                break;
            }
        }

        if ($returnedAmount > 0 && abs($returnedAmount - $expectedAmount) > 0.01) {
            throw new RuntimeException('The payment amount does not match the selected premium plan.');
        }

        $paymentReference = $decoded['internal_reference'] ?? $reference;
        $statusResult = $pollRelworxRequestStatus((string) $paymentReference, RELWORX_ACCOUNT_NO);
        $status = strtolower((string) ($statusResult['request_status'] ?? $statusResult['status'] ?? 'pending'));

        if (!in_array($status, ['success', 'completed'], true)) {
            throw new RuntimeException('Mobile Money payment is not yet confirmed. Please wait a few moments and try again.');
        }

        $premiumService->completeUpgrade((int) $_SESSION['user_id'], $request['transaction_id'], 'relworx', $paymentReference);

        $payoutResult = (new RelworxPayoutService($db))->dispatchForReference((string) $paymentReference, (float) ($statusResult['amount'] ?? $expectedAmount));
        if (!$payoutResult['success']) {
            error_log('[RELWORX_PAYOUT_LOG] ' . $payoutResult['message'] . ' for ref=' . $paymentReference);
        }

        $hasPremiumAccess = true;
        $notice = 'Your premium upgrade is active. The mobile money request was submitted successfully.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Upgrade - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#f3f2ef] text-slate-900">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-[#f3f2ef] lg:ml-64">
            <div class="w-full px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
                <section class="mb-4 flex flex-col gap-3 rounded-2xl border border-[#e7e5e1] bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Premium plans</h1>
                        <p class="mt-1 text-xs text-slate-500 sm:text-sm">Choose a plan to unlock premium fundraising features.</p>
                    </div>
                    <?php if ($hasPremiumAccess): ?>
                        <span class="inline-flex min-h-9 items-center justify-center self-start rounded-full bg-violet-50 px-4 py-2 text-sm font-semibold text-violet-800 sm:self-auto">Premium active</span>
                    <?php endif; ?>
                </section>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr),280px]">
                    <div class="space-y-6">
                        <?php if ($notice !== ''): ?>
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($notice); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($error !== ''): ?>
                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800" role="alert">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                            <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                                <h2 class="text-base font-semibold text-slate-900">Choose your plan</h2>
                                <p class="mt-1 text-xs text-slate-500 sm:text-sm">Pay with Mobile Money. Your plan activates after approval.</p>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div class="mb-4 rounded-xl border border-[#e7e5e1] bg-[#f7f6f4] px-3 py-3 sm:px-4">
                                    <label for="premium-payment-number" class="mb-1 block text-xs font-semibold text-slate-700">Mobile Money number</label>
                                    <input id="premium-payment-number" type="tel" name="payment_phone" value="<?php echo htmlspecialchars((string) ($_POST['payment_phone'] ?? $defaultPaymentPhone)); ?>" placeholder="e.g. 0772 123 456" autocomplete="tel" class="block min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/15">
                                    <p class="mt-1 text-xs text-slate-500">Use your account number or enter another number for this payment.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-3 <?php echo count($plans) === 1 ? 'md:grid-cols-1' : 'md:grid-cols-2 xl:grid-cols-3'; ?>">
                                    <?php if ($plans === []): ?>
                                        <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">Premium plans are not available right now. Please check back later.</p>
                                    <?php endif; ?>
                                    <?php foreach ($plans as $plan): ?>
                                        <?php $selected = (string) ($plan['code'] ?? ''); ?>
                                        <div class="flex min-h-full flex-col rounded-xl border border-[#e7e5e1] bg-white p-4 transition hover:border-violet-300 <?php echo count($plans) === 1 ? 'max-w-2xl' : ''; ?>">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-base font-bold text-slate-900"><?php echo htmlspecialchars((string) $plan['title']); ?></p>
                                                    <p class="mt-1 text-xs uppercase tracking-wide text-slate-500"><?php echo htmlspecialchars((string) $plan['code']); ?></p>
                                                </div>
                                                <?php if ($hasPremiumAccess): ?>
                                                    <span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-800">Active</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="mt-4 flex items-end gap-2">
                                                <span class="text-2xl font-bold leading-none text-slate-900">UGX <?php echo number_format((float) $plan['price'], 0); ?></span>
                                                <span class="pb-0.5 text-xs text-slate-500">/<?php echo (int) $plan['duration_days']; ?> days</span>
                                            </div>

                                            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                                                <?php $features = json_decode((string) ($plan['features'] ?? '[]'), true); ?>
                                                <?php if (is_array($features) && $features !== []): ?>
                                                    <?php foreach ($features as $feature): ?>
                                                        <li class="flex items-start gap-2">
                                                            <span class="mt-2 inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-violet-600"></span>
                                                            <span><?php echo htmlspecialchars((string) $feature); ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li class="flex items-start gap-2">
                                                        <span class="mt-2 inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-violet-600"></span>
                                                        <span>Premium fundraising tools and supporter visibility.</span>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>

                                            <form method="POST" data-premium-upgrade-form class="mt-4">
                                                <input type="hidden" name="premium_plan" value="<?php echo htmlspecialchars($selected); ?>">
                                                <input type="hidden" name="payment_phone" value="<?php echo htmlspecialchars((string) ($_POST['payment_phone'] ?? $defaultPaymentPhone)); ?>">
                                                <button type="submit" data-premium-submit-button <?php echo $hasPremiumAccess ? 'disabled' : ''; ?> class="premium-submit-button inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[#4c1d95] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#3b1675] disabled:cursor-not-allowed disabled:bg-slate-300">
                                                    <span class="premium-submit-label"><?php echo $hasPremiumAccess ? 'Already active' : 'Upgrade'; ?></span>
                                                    <span class="premium-submit-spinner hidden items-center justify-center gap-2">
                                                        <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle>
                                                            <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm0 0a8 8 0 018 8v4c-6.627 0-12-5.373-12-12h4z" fill="currentColor"></path>
                                                        </svg>
                                                        Processing...
                                                    </span>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="space-y-6">
                        <div class="rounded-2xl border border-[#e7e5e1] bg-white p-4 sm:p-5">
                            <h2 class="text-base font-semibold text-slate-900">Payment details</h2>
                            <div class="mt-4 space-y-3 text-sm text-slate-600">
                                <div class="rounded-xl bg-[#f7f6f4] p-3">
                                    <p class="text-xs font-medium text-slate-500">Gateway</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">Mobile Money</p>
                                </div>
                                <div class="rounded-xl bg-[#f7f6f4] p-3">
                                    <p class="text-xs font-medium text-slate-500">Phone</p>
                                    <p class="mt-1 break-words text-sm font-semibold text-slate-900"><?php echo htmlspecialchars((string) ($_POST['payment_phone'] ?? $defaultPaymentPhone ?: ($currentUser['phone'] ?? 'Not provided'))); ?></p>
                                </div>
                                <div class="rounded-xl bg-[#f7f6f4] p-3">
                                    <p class="text-xs font-medium text-slate-500">Status</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900"><?php echo $hasPremiumAccess ? 'Premium active' : 'Not upgraded'; ?></p>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentInput = document.getElementById('premium-payment-number');

            if (paymentInput) {
                paymentInput.addEventListener('input', function () {
                    document.querySelectorAll('form[data-premium-upgrade-form] input[name="payment_phone"]').forEach(function (hiddenInput) {
                        hiddenInput.value = paymentInput.value;
                    });
                });
            }

            document.querySelectorAll('form[data-premium-upgrade-form]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    const button = form.querySelector('[data-premium-submit-button]');
                    if (!button || button.disabled) return;

                    const hiddenPhone = form.querySelector('input[name="payment_phone"]');
                    if (hiddenPhone && paymentInput) {
                        hiddenPhone.value = paymentInput.value;
                    }

                    const label = button.querySelector('.premium-submit-label');
                    const spinner = button.querySelector('.premium-submit-spinner');
                    if (label && spinner) {
                        label.classList.add('hidden');
                        spinner.classList.remove('hidden');
                        spinner.classList.add('inline-flex');
                    }

                    button.disabled = true;
                    button.classList.add('opacity-90', 'cursor-not-allowed');
                });
            });
        });
    </script>
</body>
</html>
