<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PremiumAccountService.php';
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
<body class="min-h-screen bg-[#F5F8F5] text-[#1F2A22]">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <div class="flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-[#F5F8F5] lg:ml-64">
            <div class="w-full px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr),360px]">
                    <div class="space-y-6">
                        <section class="relative overflow-hidden rounded-3xl border border-[#D9E9DE] bg-[#EEF7F0] shadow-sm">
                            <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                              
                                <h1 class="mt-1 text-3xl font-bold tracking-tight text-[#1E2B24] sm:text-4xl">Upgrade to a premium fundraiser</h1>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-[#4C5D55] sm:text-base">
                                    Unlock premium fundraising tools, faster withdrawals, custom payment gateways, quick customer care, and a stronger profile for a cleaner supporter experience with Mobile Money checkout.
                                </p>
                            </div>
                        </section>

                        <?php if ($notice !== ''): ?>
                            <div class="rounded-2xl border border-[#CDE5D7] bg-[#EEF8F1] px-4 py-4 text-[#1E5E3C] shadow-sm">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($notice); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($error !== ''): ?>
                            <div class="rounded-2xl border border-[#F2C7C7] bg-[#FFF3F3] px-4 py-4 text-[#8A2C2C] shadow-sm">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="overflow-hidden rounded-3xl border border-[#DDEAE0] bg-white shadow-sm">
                            <div class="border-b border-[#E5EEE7] bg-[#F8FBF9] px-4 py-3 sm:px-5 sm:py-4">
                                <h2 class="text-base font-semibold text-[#1B2C25] sm:text-lg">Choose your plan</h2>
                                <p class="mt-1 text-xs text-[#586A62] sm:text-sm">Your upgrade is billed through Mobile Money and activated immediately after approval.</p>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div class="mb-4 rounded-2xl border border-[#DDEAE0] bg-[#F3F8F5] px-3 py-3 sm:px-4">
                                    <label for="premium-payment-number" class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.18em] text-[#4E6559]">Mobile Money number</label>
                                    <input id="premium-payment-number" type="tel" name="payment_phone" value="<?php echo htmlspecialchars((string) ($_POST['payment_phone'] ?? $defaultPaymentPhone)); ?>" placeholder="e.g. 0772 123 456" autocomplete="tel" class="block w-full rounded-xl border border-[#D1E1D8] bg-white px-3 py-2.5 text-sm text-[#1E2B24] shadow-sm transition focus:border-[#1F8A5D] focus:outline-none focus:ring-4 focus:ring-[#CFEAD9]">
                                    <p class="mt-1 text-[11px] text-[#607166]">Use your account number or enter a different one for this payment.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <?php foreach ($plans as $plan): ?>
                                        <?php $selected = (string) ($plan['code'] ?? ''); ?>
                                        <div class="flex min-h-full flex-col rounded-2xl border <?php echo $hasPremiumAccess ? 'border-[#BEE2C9] bg-[#EEF9F2]' : 'border-[#DDEAE0] bg-[#F8FBF9]'; ?> p-4 shadow-sm transition hover:border-[#B5DCC2] hover:shadow-md">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-base font-bold text-[#1C2C25]"><?php echo htmlspecialchars((string) $plan['title']); ?></p>
                                                    <p class="mt-1 text-[10px] uppercase tracking-[0.18em] text-[#5D6F67]"><?php echo htmlspecialchars((string) $plan['code']); ?></p>
                                                </div>
                                                <?php if ($hasPremiumAccess): ?>
                                                    <span class="rounded-full bg-[#DDF3E7] px-2 py-1 text-[10px] font-semibold text-[#1E6C46]">Active</span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="mt-4 flex items-end gap-2">
                                                <span class="text-2xl font-bold leading-none text-[#1B2C25]">UGX <?php echo number_format((float) $plan['price'], 0); ?></span>
                                                <span class="pb-0.5 text-[11px] text-[#677B72]">/<?php echo (int) $plan['duration_days']; ?> days</span>
                                            </div>

                                            <ul class="mt-4 space-y-2 text-xs text-[#4C5D55]">
                                                <?php $features = json_decode((string) ($plan['features'] ?? '[]'), true); ?>
                                                <?php if (is_array($features) && $features !== []): ?>
                                                    <?php foreach ($features as $feature): ?>
                                                        <li class="flex items-start gap-2">
                                                            <span class="mt-1.5 inline-block h-1.5 w-1.5 rounded-full bg-[#1F8A5D]"></span>
                                                            <span><?php echo htmlspecialchars((string) $feature); ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <li class="flex items-start gap-2">
                                                        <span class="mt-1.5 inline-block h-1.5 w-1.5 rounded-full bg-[#1F8A5D]"></span>
                                                        <span>Premium fundraising tools and supporter visibility.</span>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>

                                            <form method="POST" class="mt-4">
                                                <input type="hidden" name="premium_plan" value="<?php echo htmlspecialchars($selected); ?>">
                                                <input type="hidden" name="payment_phone" value="<?php echo htmlspecialchars((string) ($_POST['payment_phone'] ?? $defaultPaymentPhone)); ?>">
                                                <button type="submit" data-premium-submit-button <?php echo $hasPremiumAccess ? 'disabled' : ''; ?> class="premium-submit-button w-full rounded-xl bg-[#1F8A5D] px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-[#18724D] disabled:cursor-not-allowed disabled:bg-[#B9C9C1]">
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
                        <div class="rounded-2xl border border-[#DDEAE0] bg-white p-4 shadow-sm sm:p-5">
                            <h2 class="text-base font-semibold text-[#1B2C25] sm:text-lg">Payment details</h2>
                            <div class="mt-4 space-y-3 text-sm text-[#4C5D55]">
                                <div class="rounded-xl bg-[#F3F8F5] p-3">
                                    <p class="text-[10px] uppercase tracking-[0.18em] text-[#5B6D65]">Gateway</p>
                                    <p class="mt-1.5 text-sm font-semibold text-[#1B2C25]">Mobile Money</p>
                                </div>
                                <div class="rounded-xl bg-[#F3F8F5] p-3">
                                    <p class="text-[10px] uppercase tracking-[0.18em] text-[#5B6D65]">Phone</p>
                                    <p class="mt-1.5 text-sm font-semibold text-[#1B2C25]"><?php echo htmlspecialchars((string) ($_POST['payment_phone'] ?? $defaultPaymentPhone ?: ($currentUser['phone'] ?? 'Not provided'))); ?></p>
                                </div>
                                <div class="rounded-xl bg-[#F3F8F5] p-3">
                                    <p class="text-[10px] uppercase tracking-[0.18em] text-[#5B6D65]">Status</p>
                                    <p class="mt-1.5 text-sm font-semibold text-[#1B2C25]"><?php echo $hasPremiumAccess ? 'Premium active' : 'Not upgraded yet'; ?></p>
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
            document.querySelectorAll('[data-premium-submit-button]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const label = button.querySelector('.premium-submit-label');
                    const spinner = button.querySelector('.premium-submit-spinner');
                    if (!label || !spinner) return;

                    label.classList.add('hidden');
                    spinner.classList.remove('hidden');
                    spinner.classList.add('inline-flex');
                    button.disabled = true;
                    button.classList.add('opacity-90', 'cursor-not-allowed');
                });
            });
        });
    </script>
</body>
</html>
