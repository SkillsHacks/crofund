<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/Mail.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userObj = new User();
if (!$userObj->isLoggedIn()) {
    $_SESSION['error'] = 'Please login to access this page.';
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$userData = $userObj->getCurrentUser();
$mailer = new Mail();
$error = null;
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['verify_code'])) {
            $code = trim($_POST['code'] ?? '');
            if ($code !== '') {
                $userObj->verifyEmailCode($userData['email'], $code);
                $_SESSION['success'] = 'Email verified successfully!';
                header('Location: ' . BASE_URL . '/account/verification.php');
                exit();
            }
            $error = 'Please enter the verification code.';
        } elseif (isset($_POST['send_verification'])) {
            if (!$userData['is_verified']) {
                $verificationCode = $userObj->resendVerificationCode($userData['id']);
                $mailer->sendVerificationCode($userData['email'], $verificationCode, $userData['name']);
                $_SESSION['success'] = 'Verification email sent successfully!';
                header('Location: ' . BASE_URL . '/account/verification.php');
                exit();
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if ($isAjaxRequest) {
    ob_start();
}

function renderVerificationContent(array $userData, ?string $error): void {
?>
<div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.65fr),360px]">
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        Email verification
                    </div>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Secure your account access</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                        Verify your email address to unlock the full CroFund experience and receive important account notifications.
                    </p>
                </div>
            </section>

            <?php if ($userData['is_verified']): ?>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-emerald-600 shadow-sm">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-emerald-900">Email verified</h2>
                            <p class="mt-1 text-sm text-emerald-800">Your email address has been successfully verified and your account is fully active.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-yellow-600 shadow-sm">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-yellow-900">Verification required</h2>
                            <p class="mt-1 text-sm text-yellow-800">Your email address still needs to be verified to access all features.</p>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-red-700 shadow-sm">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <h2 class="text-lg font-semibold text-slate-900">Enter verification code</h2>
                        <p class="mt-1 text-sm text-slate-500">Use the latest code sent to <strong><?php echo htmlspecialchars($userData['email']); ?></strong>.</p>
                    </div>
                    <div class="px-5 py-5 sm:px-6 sm:py-6">
                        <form method="POST" class="space-y-6">
                            <div>
                                <label for="code" class="block text-sm font-semibold text-slate-700">Verification code</label>
                                <input id="code" name="code" type="text" maxlength="6" required autocomplete="one-time-code"
                                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-lg tracking-[0.35em] text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                                       placeholder="000000">
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button type="submit" name="verify_code" class="inline-flex flex-1 items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                                    Verify code
                                </button>
                                <button type="submit" name="send_verification" class="inline-flex flex-1 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                                    Resend code
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Why verify?</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Protect your account</p>
                        <p class="mt-1 text-sm text-slate-500">Verification helps confirm that your account is tied to a valid email address you control.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Receive important updates</p>
                        <p class="mt-1 text-sm text-slate-500">Campaign, payout, and security notices depend on a working verified inbox.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Unlock all features</p>
                        <p class="mt-1 text-sm text-slate-500">Some flows may remain limited until your verification status is complete.</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
<?php
}

if (!$isAjaxRequest) {
    include __DIR__ . '/../components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen pt-16">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <?php renderVerificationContent($userData, $error); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderVerificationContent($userData, $error);
    echo ob_get_clean();
}
