<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/Mail.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userObj = new User();
if (!$userObj->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = normalizeRedirectPath($_SERVER['REQUEST_URI']);
    $_SESSION['error'] = 'Please login to access this page.';
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

$userData = $userObj->getCurrentUser();
$mailer = new Mail();
$error = null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
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

function renderVerificationContent(array $userData, ?string $error, ?string $success): void {
?>
<div class="w-full px-3 py-4 pb-24 sm:px-5 sm:py-5 lg:px-6 lg:py-6 lg:pb-6">
    <div class="mx-auto w-full max-w-none space-y-4 sm:space-y-5">
        <section class="flex items-center rounded-2xl border border-[#e7e5e1] bg-white px-4 py-4 sm:px-5">
            <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Email verification</h1>
        </section>

        <?php if ($success): ?>
            <div class="flex items-center gap-2 px-1 text-sm font-medium text-emerald-700" role="status">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="flex items-center gap-2 px-1 text-sm font-medium text-red-700" role="alert">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
            <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                <h2 class="text-base font-semibold text-slate-900">Verification status</h2>
            </div>
            <div class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <p class="text-xs font-medium text-slate-500">Email address</p>
                    <p class="mt-1 break-all text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($userData['email']); ?></p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold <?php echo $userData['is_verified'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'; ?>">
                    <?php echo $userData['is_verified'] ? 'Verified' : 'Not verified'; ?>
                </span>
            </div>
        </section>

        <?php if ($userData['is_verified']): ?>
            <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                <div class="flex items-start gap-3 px-4 py-4 sm:px-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Your email is verified</h2>
                        <p class="mt-1 text-sm text-slate-500">Your account is ready to use.</p>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
                <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                    <h2 class="text-base font-semibold text-slate-900">Verify your email</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Enter the latest code sent to your inbox.</p>
                </div>
                <div class="px-4 py-4 sm:px-5">
                    <form method="POST" class="space-y-4">
                        <div class="max-w-md">
                            <label for="code" class="block text-xs font-semibold text-slate-700">6-digit code</label>
                            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code"
                                   class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-lg tracking-[0.35em] text-slate-900 transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/15"
                                   placeholder="000000">
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <button type="submit" name="verify_code" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[#4c1d95] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#3b1675] focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                                Verify email
                            </button>
                            <button type="submit" name="send_verification" formnovalidate class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#e7e5e1] bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-[#f7f6f4] focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2">
                                Resend code
                            </button>
                        </div>
                    </form>
                    <p class="mt-3 text-xs text-slate-500">Codes expire after one hour.</p>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php
}

if (!$isAjaxRequest) {
    $useDashboardShell = true;
    $hidePremiumHeaderBanner = true;
    $hideHeaderCreateButton = true;
    include __DIR__ . '/../components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body.account-verification-page {
            background: #f3f2ef;
            color: #1c1c1c;
        }

        body.account-verification-page .dashboard-app-header {
            height: 72px;
            border-color: #e7e5e1;
            background: #fff;
            box-shadow: 0 1px 3px rgb(28 28 28 / 4%);
        }

        body.account-verification-page .dashboard-app-header > div {
            height: 100%;
            padding: 0 24px;
        }

        body.account-verification-page .dashboard-app-header > div > div {
            height: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        body.account-verification-page .dashboard-app-header > div > div > div:first-child {
            flex: 0 1 auto;
            justify-content: flex-start;
        }

        body.account-verification-page #app-brand-link {
            gap: 10px;
            padding: 0;
        }

        body.account-verification-page #app-brand-link > span:first-child {
            display: flex;
            width: 38px;
            height: 38px;
            align-items: center;
            justify-content: center;
            background: transparent;
            box-shadow: none;
        }

        body.account-verification-page #app-brand-link > span:last-child {
            display: inline;
            color: #274a32;
            font-size: 1rem;
            font-weight: 700;
        }

        body.account-verification-page .dashboard-app-header > div > div > div:nth-child(2) {
            display: none;
        }

        body.account-verification-page .dashboard-app-header > div > div > div:last-child {
            flex: 0 0 auto;
            gap: 12px;
        }

        body.account-verification-page #user-menu-button {
            min-height: 42px;
            gap: 9px;
            padding: 4px 10px 4px 5px;
            border: 1px solid #e7e5e1;
            border-radius: 12px;
            background: #fff;
            color: #292524;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        body.account-verification-page #user-menu-button img {
            width: 32px;
            height: 32px;
            border: 0;
            box-shadow: 0 0 0 1px #e7e5e1;
        }

        @media (max-width: 1023px) {
            body.account-verification-page .dashboard-app-header {
                height: 64px;
            }

            body.account-verification-page .dashboard-app-header > div {
                padding: 0 16px;
            }

            body.account-verification-page .dashboard-app-header > div > div > div:first-child {
                position: static;
                flex: 1 1 100%;
                justify-content: center;
            }

            body.account-verification-page #app-brand-link {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
            }

            body.account-verification-page #app-brand-link > span:first-child,
            body.account-verification-page #app-brand-link > span:first-child svg {
                width: 34px;
                height: 34px;
            }

            body.account-verification-page .dashboard-app-header > div > div > div:nth-child(2) {
                position: absolute;
                right: 16px;
                display: flex;
                width: auto;
                flex: 0 0 auto;
                justify-content: flex-end;
            }

            body.account-verification-page .dashboard-app-header > div > div > div:nth-child(2) img {
                width: 34px;
                height: 34px;
                border: 0;
                border-radius: 50%;
                box-shadow: 0 0 0 1px #e7e5e1;
            }

            body.account-verification-page .dashboard-app-header > div > div > div:last-child {
                display: none;
            }
        }
    </style>
</head>
<body class="account-verification-page min-h-screen">
    <div class="flex min-h-screen pt-16 lg:pt-[72px]">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <?php renderVerificationContent($userData, $error, $success); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderVerificationContent($userData, $error, $success);
    echo ob_get_clean();
}
