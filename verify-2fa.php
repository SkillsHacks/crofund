<?php
session_start();
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_code'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$message = $_GET['message'] ?? '';
$userEmail = $_SESSION['2fa_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputCode = trim($_POST['code'] ?? '');

    if (!isset($_SESSION['2fa_code_time']) || (time() - $_SESSION['2fa_code_time'] > 300)) {
        $error = 'Verification code expired. Please login again.';
        session_unset();
        session_destroy();
    } else {
        if (!isset($_SESSION['2fa_attempts'])) {
            $_SESSION['2fa_attempts'] = 0;
        }

        if ($_SESSION['2fa_attempts'] >= 3) {
            $error = 'Too many failed attempts. Please login again.';
            session_unset();
            session_destroy();
            header('Location: ' . BASE_URL . '/login.php?message=' . urlencode($error));
            exit;
        } elseif ($inputCode == ($_SESSION['2fa_code'] ?? '')) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $_SESSION['2fa_user_id'];
            $_SESSION['user_name'] = $_SESSION['2fa_user_name'];
            $_SESSION['last_activity'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

            $redirect = normalizeRedirectPath($_SESSION['2fa_redirect'] ?? '/dashboard.php');
            crofund_debug_log('verify-2fa before redirect', [
                'session_2fa_redirect' => $_SESSION['2fa_redirect'] ?? null,
                'normalized_redirect' => $redirect,
                'base_url' => BASE_URL,
            ]);

            unset($_SESSION['2fa_code'], $_SESSION['2fa_user_id'], $_SESSION['2fa_email'], $_SESSION['2fa_user_name'], $_SESSION['2fa_code_time'], $_SESSION['2fa_attempts'], $_SESSION['2fa_redirect']);

            header('Location: ' . BASE_URL . $redirect);
            exit;
        } else {
            $_SESSION['2fa_attempts']++;
            $remainingAttempts = max(0, 3 - $_SESSION['2fa_attempts']);
            $error = 'Invalid verification code.' . ($remainingAttempts > 0 ? ' ' . $remainingAttempts . ' attempt' . ($remainingAttempts === 1 ? '' : 's') . ' remaining.' : '');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">

    <title>Two-Factor Verification - CroFunding</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .verify-glow {
            background:
                radial-gradient(circle at top left, rgba(16, 185, 129, 0.24), transparent 38%),
                radial-gradient(circle at bottom right, rgba(5, 150, 105, 0.16), transparent 34%),
                linear-gradient(135deg, #f8fffc 0%, #f0fdf4 48%, #ecfdf5 100%);
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <main class="min-h-screen px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid min-h-[calc(100vh-11rem)] max-w-7xl items-center gap-8 lg:grid-cols-[minmax(0,1.05fr),minmax(420px,520px)] lg:gap-12">
            <section class="verify-glow relative overflow-hidden rounded-3xl border border-emerald-100 shadow-sm">
                <div class="relative px-6 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                    <h1 class="mt-5 max-w-xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                        One more step to secure your sign-in
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                        We sent a 6-digit verification code to your email. Enter it below to complete your login and continue to your dashboard.
                    </p>

                    <div class="mt-8 rounded-2xl border border-white/70 bg-white/80 p-5 shadow-sm backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Verification destination</p>
                        <p class="mt-3 text-lg font-semibold text-slate-900 break-all"><?php echo htmlspecialchars($userEmail ?: 'your email'); ?></p>
                        <p class="mt-2 text-sm text-slate-500">For security, the code expires after 5 minutes and only a few attempts are allowed.</p>
                    </div>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur">
                            <p class="text-sm font-semibold text-slate-900">Short expiry window</p>
                            <p class="mt-2 text-sm text-slate-500">Your verification code stays valid for 5 minutes.</p>
                        </div>
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur">
                            <p class="text-sm font-semibold text-slate-900">Limited attempts</p>
                            <p class="mt-2 text-sm text-slate-500">Too many failed attempts will return you to login for safety.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                <div class="px-6 py-7 sm:px-8 sm:py-8">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Verify your code</h2>
                        <p class="mt-2 text-sm text-slate-500 sm:text-base">
                            Enter the 6-digit code sent to your email address.
                        </p>
                    </div>

                    <?php if ($message): ?>
                        <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="text-sm font-medium text-yellow-800"><?php echo htmlspecialchars($message); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="verify-2fa-form" method="POST" class="space-y-6">
                        <div>
                            <label for="code" class="block text-sm font-semibold text-slate-700">Verification code</label>
                            <input id="code" name="code" type="text" maxlength="6" required autocomplete="one-time-code"
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-2xl tracking-[0.45em] text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                                   placeholder="000000">
                        </div>

                        <button id="verify-2fa-submit" type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0">
                            <svg id="verify-2fa-submit-icon" class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <svg id="verify-2fa-submit-spinner" class="mr-2 hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span id="verify-2fa-submit-text">Verify and continue</span>
                        </button>
                    </form>

                    <div class="mt-8 border-t border-slate-200 pt-6 text-center">
                        <p class="text-sm text-slate-500">
                            Didn’t get the code?
                            <a href="login.php" class="font-semibold text-emerald-600 transition hover:text-emerald-700 underline">
                                Login again
                            </a>
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/components/public-footer.php'; ?>

    <script>
        document.getElementById('verify-2fa-form').addEventListener('submit', function() {
            const submitButton = document.getElementById('verify-2fa-submit');
            const submitText = document.getElementById('verify-2fa-submit-text');
            const submitIcon = document.getElementById('verify-2fa-submit-icon');
            const submitSpinner = document.getElementById('verify-2fa-submit-spinner');

            submitButton.disabled = true;
            submitText.textContent = 'Verifying...';
            submitIcon.classList.add('hidden');
            submitSpinner.classList.remove('hidden');
        });
    </script>
</body>
</html>
