<?php
session_start();
require_once __DIR__ . '/config/config.php';

function clearTwoFactorSessionPreservingCampaignDraft(): void {
    $campaignDraft = $_SESSION['campaign_creation_draft'] ?? null;
    session_unset();
    session_destroy();

    if (!is_array($campaignDraft)) {
        return;
    }

    if (!session_start()) {
        error_log('Unable to restart session while preserving campaign draft after 2FA failure.');
        return;
    }

    if (!session_regenerate_id(true)) {
        error_log('Unable to rotate session while preserving campaign draft after 2FA failure.');
    }

    $_SESSION['campaign_creation_draft'] = $campaignDraft;
    $_SESSION['redirect_after_login'] = '/campaigns/create.php?resume=1';
}

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
        clearTwoFactorSessionPreservingCampaignDraft();
        header('Location: ' . BASE_URL . '/login.php?message=' . urlencode($error));
        exit;
    } else {
        if (!isset($_SESSION['2fa_attempts'])) {
            $_SESSION['2fa_attempts'] = 0;
        }

        if ($_SESSION['2fa_attempts'] >= 3) {
            $error = 'Too many failed attempts. Please login again.';
            clearTwoFactorSessionPreservingCampaignDraft();
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
                            50: '#f5ffe7',
                            100: '#eaffcc',
                            200: '#d9f9a5',
                            300: '#cafa8c',
                            400: '#aee871',
                            500: '#8bcf57',
                            600: '#3e7843',
                            700: '#274a32',
                            800: '#1f3e29',
                            900: '#193623',
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
    </style>
</head>
<body class="min-h-screen bg-[#f3f2ef] text-slate-900">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <main class="min-h-[calc(100vh-5rem)] bg-[#f3f2ef] px-4 pb-10 pt-28 sm:px-6 sm:pt-32 lg:px-8">
        <div class="mx-auto grid w-full max-w-6xl items-center gap-6 lg:min-h-[calc(100vh-13rem)] lg:grid-cols-[minmax(0,1fr),minmax(400px,460px)] lg:gap-14">
            <section class="order-2 hidden rounded-3xl border border-emerald-100 bg-[#eaf4ed] p-8 lg:order-1 lg:block lg:p-10">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-800">Account security</p>
                <h2 class="mt-4 max-w-lg text-3xl font-bold leading-tight tracking-tight text-slate-900 xl:text-4xl">
                    One quick check, then you’re in.
                </h2>
                <p class="mt-4 max-w-lg text-base leading-7 text-slate-600">
                    Enter the code sent to your email to finish signing in.
                </p>
                <div class="mt-8 rounded-2xl border border-white/80 bg-white/80 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Code sent to</p>
                    <p class="mt-2 break-all text-base font-semibold text-slate-900"><?php echo htmlspecialchars($userEmail ?: 'your email'); ?></p>
                    <p class="mt-3 text-sm text-slate-500">It expires in 5 minutes. Three attempts are allowed.</p>
                </div>
            </section>

            <section class="order-1 rounded-2xl border border-[#e7e5e1] bg-white shadow-sm sm:rounded-3xl lg:order-2">
                <div class="px-5 py-6 sm:px-8 sm:py-8">
                    <div class="mb-6">
                        <p class="hidden text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 sm:block">Account security</p>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:mt-2 sm:text-3xl">Verify sign-in</h1>
                        <p class="mt-1.5 text-sm text-slate-500">Enter your 6-digit code.</p>
                        <p class="mt-3 break-all rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 sm:hidden">
                            Sent to <span class="font-medium text-slate-800"><?php echo htmlspecialchars($userEmail ?: 'your email'); ?></span>
                        </p>
                    </div>

                    <?php if ($message): ?>
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3" role="status">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="text-sm leading-5 text-amber-900"><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3" role="alert">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm leading-5 text-red-900"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="verify-2fa-form" method="POST" class="space-y-5">
                        <div>
                            <label for="code" class="block text-sm font-semibold text-slate-700">6-digit code</label>
                            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code"
                                   class="mt-1.5 min-h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-center text-2xl tracking-[0.45em] text-slate-900 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-600/10"
                                   placeholder="000000">
                        </div>

                        <button id="verify-2fa-submit" type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20 disabled:cursor-not-allowed disabled:opacity-70">
                            <svg id="verify-2fa-submit-icon" class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <svg id="verify-2fa-submit-spinner" class="mr-2 hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span id="verify-2fa-submit-text">Verify and continue</span>
                        </button>
                    </form>

                    <div class="mt-6 border-t border-[#e7e5e1] pt-5 text-center">
                        <p class="text-sm text-slate-500">
                            Code not working?
                            <a href="login.php" class="font-semibold text-emerald-600 transition hover:text-emerald-700 underline">
                                Sign in again
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
