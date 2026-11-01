<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/TrustRiskGuard.php';

$user = new User();
if (!$user->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = normalizeRedirectPath($_SERVER['REQUEST_URI']);
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

TrustRiskGuard::enforceOrAbort((int) $_SESSION['user_id'], 'update_sensitive_profile');

$currentUser = $user->getCurrentUser();
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';
$successMessage = '';
$errorMessage = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                $errorMessage = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 8) {
                $errorMessage = 'New password must be at least 8 characters long.';
            } else {
                $user->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                $successMessage = 'Password changed successfully.';
            }
        }

        if (isset($_POST['save_paypal_email'])) {
            if (!hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
                $errorMessage = 'Your session expired. Refresh the page and try again.';
            } else {
                $user->savePaypalEmail((int) $_SESSION['user_id'], (string) ($_POST['paypal_email'] ?? ''));
                $currentUser = $user->getCurrentUser();
                $successMessage = 'Your PayPal payout email has been saved.';
            }
        }

        if (isset($_POST['remove_paypal_email'])) {
            if (!hash_equals($csrfToken, (string) ($_POST['csrf_token'] ?? ''))) {
                $errorMessage = 'Your session expired. Refresh the page and try again.';
            } else {
                $user->clearPaypalEmail((int) $_SESSION['user_id']);
                $currentUser = $user->getCurrentUser();
                $successMessage = 'Your PayPal payout email has been removed.';
            }
        }
    } catch (InvalidArgumentException $e) {
        $errorMessage = $e->getMessage();
    } catch (Exception $e) {
        error_log('Account settings error for user ' . $_SESSION['user_id'] . ': ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $errorMessage = 'Something went wrong. Please try again.';
    } catch (Error $e) {
        error_log('Account settings PHP error for user ' . $_SESSION['user_id'] . ': ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $errorMessage = 'Something went wrong. Please try again.';
    }
}

if ($isAjaxRequest) {
    ob_start();
}

function renderAccountSettingsContent(array $currentUser, string $csrfToken, string $successMessage, string $errorMessage): void {
?>
<div class="w-full px-3 py-4 pb-24 sm:px-5 sm:py-5 lg:px-6 lg:py-6 lg:pb-6">
    <div class="mx-auto w-full max-w-none space-y-4 sm:space-y-5">
        <section class="flex items-center rounded-2xl border border-[#e7e5e1] bg-white px-4 py-4 sm:px-5">
            <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Account settings</h1>
        </section>

        <?php if ($successMessage !== ''): ?>
            <div class="flex items-center gap-2 px-1 text-sm font-medium text-emerald-700" role="status">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="flex items-center gap-2 px-1 text-sm font-medium text-red-700" role="alert">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        <?php endif; ?>

        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
            <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                <h2 class="text-base font-semibold text-slate-900">Account details</h2>
                <p class="mt-0.5 text-xs text-slate-500">Contact support to update these details.</p>
            </div>
            <dl class="grid grid-cols-1 divide-y divide-[#e7e5e1] px-4 sm:grid-cols-2 sm:divide-y-0 sm:px-5">
                <div class="py-3 sm:border-b sm:border-[#e7e5e1] sm:py-4 sm:pr-5">
                    <dt class="text-xs font-medium text-slate-500">Full name</dt>
                    <dd class="mt-1 break-words text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></dd>
                </div>
                <div class="py-3 sm:border-b sm:border-[#e7e5e1] sm:py-4 sm:pl-5">
                    <dt class="text-xs font-medium text-slate-500">Email address</dt>
                    <dd class="mt-1 break-words text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></dd>
                </div>
                <div class="py-3 sm:py-4 sm:pr-5">
                    <dt class="text-xs font-medium text-slate-500">Phone number</dt>
                    <dd class="mt-1 break-words text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($currentUser['phone'] ?? 'Not provided'); ?></dd>
                </div>
            </dl>
        </section>

        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
            <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                <h2 class="text-base font-semibold text-slate-900">Account access</h2>
            </div>
            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 sm:p-5">
                <a href="<?php echo BASE_URL; ?>/account/verification.php"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#e7e5e1] bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-[#f7f6f4] focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Verification
                </a>
                <a href="<?php echo BASE_URL; ?>/account/premium.php"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Premium upgrade
                </a>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
            <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                <h2 class="text-base font-semibold text-slate-900">PayPal payout email</h2>
                <p class="mt-0.5 text-xs text-slate-500">Set the PayPal email address where you want to receive payouts.</p>
            </div>
            <div class="px-4 py-4 sm:px-5">
                <form method="POST" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="min-w-0 flex-1">
                        <label for="paypal_email" class="block text-xs font-semibold text-slate-700">PayPal email</label>
                        <input id="paypal_email" type="email" name="paypal_email" value="<?php echo htmlspecialchars((string) ($currentUser['paypal_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="name@example.com" required
                               class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15">
                    </div>
                    <?php if (!empty($currentUser['paypal_email'])): ?>
                        <button type="submit" name="remove_paypal_email" value="1" formnovalidate class="inline-flex min-h-11 items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                            Remove
                        </button>
                    <?php endif; ?>
                    <button type="submit" name="save_paypal_email" value="1" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">
                        Save email
                    </button>
                </form>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white">
            <div class="border-b border-[#e7e5e1] px-4 py-3 sm:px-5">
                <h2 class="text-base font-semibold text-slate-900">Change password</h2>
            </div>
            <div class="px-4 py-4 sm:px-5">
                <form method="POST">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <input id="current_password" type="password" name="current_password" placeholder="Current password" aria-label="Current password" autocomplete="current-password" required
                               class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15">
                        <input id="new_password" type="password" name="new_password" placeholder="New password" aria-label="New password" autocomplete="new-password" required
                               class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15">
                        <input id="confirm_password" type="password" name="confirm_password" placeholder="Confirm new password" aria-label="Confirm new password" autocomplete="new-password" required
                               class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 transition focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/15">
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" name="change_password" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">
                            Update password
                        </button>
                    </div>
                </form>
            </div>
        </section>
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
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>Account Settings - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen pt-2 sm:pt-3 lg:pt-4">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <?php renderAccountSettingsContent($currentUser, $csrfToken, $successMessage, $errorMessage); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderAccountSettingsContent($currentUser, $csrfToken, $successMessage, $errorMessage);
    echo ob_get_clean();
}
