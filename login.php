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
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-800">Fundraising made simple</p>
                <h2 class="mt-4 max-w-lg text-3xl font-bold leading-tight tracking-tight text-slate-900 xl:text-4xl">
                    Your mission, supported at every step.
                </h2>
                <p class="mt-4 max-w-lg text-base leading-7 text-slate-600">
                    Manage campaigns, connect with supporters, and keep track of your fundraising from one place.
                </p>
                <ul class="mt-8 space-y-4">
                    <li class="flex items-center gap-3 text-sm font-medium text-slate-700">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19V5m0 14h16M8 15l3-3 3 2 5-6"/></svg>
                        </span>
                        Create and manage your campaigns
                    </li>
                    <li class="flex items-center gap-3 text-sm font-medium text-slate-700">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-3.314 0-6 1.79-6 4s2.686 4 6 4 6-1.79 6-4-2.686-4-6-4zm0 0V5m0 11v3"/></svg>
                        </span>
                        Review donations and withdrawals
                    </li>
                    <li class="flex items-center gap-3 text-sm font-medium text-slate-700">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h10M7 16h6m-9 5h16a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg>
                        </span>
                        Share progress with your supporters
                    </li>
                </ul>
            </section>

            <section class="order-1 rounded-2xl border border-[#e7e5e1] bg-white shadow-sm sm:rounded-3xl lg:order-2">
                <div class="px-5 py-6 sm:px-8 sm:py-8">
                    <div class="mb-6">
                        <p class="hidden text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 sm:block">Your CroFunding account</p>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:mt-2 sm:text-3xl">Sign in</h1>
                        <p class="mt-1.5 hidden text-sm text-slate-500 sm:block">Continue to your dashboard.</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3" role="status">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="text-sm leading-5 text-amber-900"><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-3" role="status">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span class="text-sm leading-5 text-emerald-900"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3" role="alert">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm leading-5 text-red-900">
                                    <div><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                                    <?php if (isset($_SESSION['unverified_email'])): ?>
                                        <a href="resend-verification.php?email=<?php echo urlencode($_SESSION['unverified_email']); ?>" class="mt-2 inline-block font-semibold text-red-800 underline underline-offset-2 hover:text-red-900">
                                            Resend verification email
                                        </a>
                                        <?php unset($_SESSION['unverified_email']); ?>
                                    <?php endif; ?>
                            </div>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form id="login-form" action="<?php echo BASE_URL; ?>/auth/process-login.php" method="POST" class="space-y-5">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700">Email address</label>
                            <input id="email" name="email" type="email" required autocomplete="email"
                                   class="mt-1.5 min-h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-base text-slate-900 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-600/10"
                                   placeholder="Email address">
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                                <a href="forgot-password.php" class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800 focus:outline-none focus-visible:underline">
                                    Forgot password?
                                </a>
                            </div>
                            <div class="relative">
                                <input id="password" name="password" type="password" required autocomplete="current-password"
                                       class="min-h-12 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 pr-12 text-base text-slate-900 placeholder-slate-400 transition focus:border-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-600/10"
                                       placeholder="Password">
                                <button type="button" id="togglePassword" aria-label="Show password" aria-pressed="false" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-slate-500 transition hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-600">
                                    <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg id="eyeOffIcon" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908l3.29-3.29"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <label for="remember_me" class="inline-flex min-h-6 items-center gap-3 text-sm text-slate-600">
                            <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                            Remember me
                        </label>

                        <button id="login-submit" type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20 disabled:cursor-not-allowed disabled:opacity-70">
                            <svg id="login-submit-icon" class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            <svg id="login-submit-spinner" class="mr-2 hidden h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span id="login-submit-text">Sign in</span>
                        </button>
                    </form>

                    <p class="mt-6 border-t border-[#e7e5e1] pt-5 text-center text-sm text-slate-500">
                        <span class="sm:hidden">New here?</span>
                        <span class="hidden sm:inline">New to CroFunding?</span>
                        <a href="register.php" class="font-semibold text-emerald-700 transition hover:text-emerald-800">
                            <span class="sm:hidden">Sign up</span>
                            <span class="hidden sm:inline">Create an account</span>
                        </a>
                    </p>
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
            const isPasswordVisible = passwordInput.type === 'password';

            if (isPasswordVisible) {
                passwordInput.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
            this.setAttribute('aria-pressed', String(isPasswordVisible));
            this.setAttribute('aria-label', isPasswordVisible ? 'Hide password' : 'Show password');
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
