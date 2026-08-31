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
        $reference = 'premium_' . $request['transaction_id'];
        $msisdn = $normalizeRelworxMsisdn((string) ($currentUser['phone'] ?? ''));

        if ($msisdn === '' || $msisdn === '+') {
            throw new RuntimeException('A valid phone number is required before initiating a Mobile Money payment request.');
        }

        $mobileMoneyPayload = [
            'account_no' => RELWORX_ACCOUNT_NO,
            'reference' => $reference,
            'msisdn' => $msisdn,
            'currency' => 'UGX',
            'amount' => (float) $request['amount'],
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
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen pt-16">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr),360px]">
                    <div class="space-y-6">
                        <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                            <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                                <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Upgrade to a premium fundraiser</h1>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                                    Unlock premium fundraising tools, a stronger profile, and a cleaner supporter experience with Mobile Money checkout.
                                </p>
                            </div>
                        </section>

                        <?php if ($notice !== ''): ?>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800 shadow-sm">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($notice); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($error !== ''): ?>
                            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-red-700 shadow-sm">
                                <p class="text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                                <h2 class="text-lg font-semibold text-slate-900">Choose your plan</h2>
                                <p class="mt-1 text-sm text-slate-500">Your upgrade is billed through Mobile Money and activated immediately after approval.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 p-5 sm:p-6 lg:grid-cols-3">
                                <?php foreach ($plans as $plan): ?>
                                    <?php $selected = (string) ($plan['code'] ?? ''); ?>
                                    <div class="rounded-2xl border <?php echo $hasPremiumAccess ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50'; ?> p-5 shadow-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-lg font-bold text-slate-900"><?php echo htmlspecialchars((string) $plan['title']); ?></p>
                                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500"><?php echo htmlspecialchars((string) $plan['code']); ?></p>
                                            </div>
                                            <?php if ($hasPremiumAccess): ?>
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">Active</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mt-5 flex items-end gap-2">
                                            <span class="text-3xl font-bold text-slate-900">UGX <?php echo number_format((float) $plan['price'], 0); ?></span>
                                            <span class="mb-1 text-sm text-slate-500">/<?php echo (int) $plan['duration_days']; ?> days</span>
                                        </div>

                                        <ul class="mt-4 space-y-2 text-sm text-slate-600">
                                            <?php $features = json_decode((string) ($plan['features'] ?? '[]'), true); ?>
                                            <?php if (is_array($features) && $features !== []): ?>
                                                <?php foreach ($features as $feature): ?>
                                                    <li class="flex items-start gap-2">
                                                        <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                        <span><?php echo htmlspecialchars((string) $feature); ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <li class="flex items-start gap-2">
                                                    <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                    <span>Premium fundraising tools and supporter visibility.</span>
                                                </li>
                                            <?php endif; ?>
                                        </ul>

                                        <form method="POST" class="mt-6">
                                            <input type="hidden" name="premium_plan" value="<?php echo htmlspecialchars($selected); ?>">
                                            <button type="submit" data-premium-submit-button <?php echo $hasPremiumAccess ? 'disabled' : ''; ?> class="premium-submit-button w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                                                <span class="premium-submit-label"><?php echo $hasPremiumAccess ? 'Already active' : 'Upgrade to premium'; ?></span>
                                                <span class="premium-submit-spinner hidden items-center justify-center gap-2">
                                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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

                    <aside class="space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-900">Payment details</h2>
                            <div class="mt-5 space-y-4 text-sm text-slate-600">
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Gateway</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900">Mobile Money</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Phone</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900"><?php echo htmlspecialchars((string) ($currentUser['phone'] ?? 'Not provided')); ?></p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Status</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900"><?php echo $hasPremiumAccess ? 'Premium active' : 'Not upgraded yet'; ?></p>
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
