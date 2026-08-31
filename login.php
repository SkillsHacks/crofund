<?php
session_start();
require_once __DIR__ . '/config/config.php';

$message = $_GET['message'] ?? '';
$redirect = normalizeRedirectPath($_SESSION['redirect_after_login'] ?? '/dashboard.php');
crofund_debug_log('login page', [
    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
    'session_redirect_after_login' => $_SESSION['redirect_after_login'] ?? null,
    'normalized_redirect' => $redirect,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">

    <title>Login - CroFunding</title>

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
        .login-glow {
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
            <section class="login-glow relative overflow-hidden rounded-3xl border border-emerald-100 shadow-sm">
                <div class="relative px-6 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                    <h1 class="mt-5 max-w-xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                        Continue building impact with CroFund
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                        Sign in to manage your campaigns, review withdrawals, publish updates, and stay close to the supporters backing your mission.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Campaigns</p>
                            <p class="mt-3 text-2xl font-bold text-slate-900">Launch</p>
                            <p class="mt-2 text-sm text-slate-500">Create and manage fundraisers from one dashboard.</p>
                        </div>
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Withdrawals</p>
                            <p class="mt-3 text-2xl font-bold text-slate-900">Track</p>
                            <p class="mt-2 text-sm text-slate-500">Monitor balances, requests, and payouts clearly.</p>
                        </div>
                        <div class="rounded-2xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Supporters</p>
                            <p class="mt-3 text-2xl font-bold text-slate-900">Grow</p>
                            <p class="mt-2 text-sm text-slate-500">Keep donors engaged with progress and updates.</p>
                        </div>
                    </div>

                    <div class="mt-8 rounded-2xl border border-emerald-200 bg-white/80 p-5 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Need an account first?</p>
                        <p class="mt-1 text-sm text-slate-500">
                            Create one in a few steps and start fundraising right away.
                        </p>
                        <a href="register.php" class="mt-4 inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700">
                            Create account
                        </a>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                <div class="px-6 py-7 sm:px-8 sm:py-8">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Sign in</h2>
                        <p class="mt-2 text-sm text-slate-500 sm:text-base">
                            Enter your account details to continue.
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

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-sm font-medium text-emerald-800"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                            </div>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 shadow-sm">
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-red-800">
                                    <div class="font-medium"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                                    <?php if (isset($_SESSION['unverified_email'])): ?>
                                        <a href="resend-verification.php?email=<?php echo urlencode($_SESSION['unverified_email']); ?>" class="mt-2 inline-block font-semibold text-emerald-700 underline hover:text-emerald-800">
                                            Resend verification email
                                        </a>
                                        <?php unset($_SESSION['unverified_email']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form id="login-form" action="<?php echo BASE_URL; ?>/auth/process-login.php" method="POST" class="space-y-6">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700">Email address</label>
                            <input id="email" name="email" type="email" required
                                   class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                                   placeholder="Enter your email">
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                                <a href="forgot-password.php" class="text-sm font-semibold text-emerald-600 transition hover:text-emerald-700">
                                    Forgot password?
                                </a>
                            </div>
                            <div class="relative">
                                <input id="password" name="password" type="password" required
                                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                                       placeholder="Enter your password">
                                <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-500 transition hover:text-slate-700">
                                    <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg id="eyeOffIcon" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908l3.29-3.29"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <label for="remember_me" class="inline-flex items-center text-sm text-slate-700">
                                <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="ml-3">Remember me</span>
                            </label>
                        </div>

                        <button id="login-submit" type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0">
                            <svg id="login-submit-icon" class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            <svg id="login-submit-spinner" class="mr-2 hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span id="login-submit-text">Sign in</span>
                        </button>
                    </form>

                    <div class="mt-8 border-t border-slate-200 pt-6 text-center">
                        <p class="text-sm text-slate-500">
                            Don’t have an account?
                            <a href="register.php" class="font-semibold text-emerald-600 transition hover:text-emerald-700">
                                Sign up now
                            </a>
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/components/public-footer.php'; ?>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        });

        document.getElementById('login-form').addEventListener('submit', function() {
            const submitButton = document.getElementById('login-submit');
            const submitText = document.getElementById('login-submit-text');
            const submitIcon = document.getElementById('login-submit-icon');
            const submitSpinner = document.getElementById('login-submit-spinner');

            submitButton.disabled = true;
            submitText.textContent = 'Signing in...';
            submitIcon.classList.add('hidden');
            submitSpinner.classList.remove('hidden');
        });
    </script>
</body>
</html>
