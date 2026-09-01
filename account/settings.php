<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';
require_once __DIR__ . '/../config/PremiumAccountService.php';
require_once __DIR__ . '/../config/TrustRiskGuard.php';

$user = new User();
if (!$user->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = normalizeRedirectPath($_SERVER['REQUEST_URI']);
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

TrustRiskGuard::enforceOrAbort((int) $_SESSION['user_id'], 'update_sensitive_profile');

$currentUser = $user->getCurrentUser();
$premiumService = new PremiumAccountService(Database::getInstance()->getConnection());
$hasPremiumAccess = $premiumService->hasPremiumAccess((int) ($_SESSION['user_id'] ?? 0));
$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';
$successMessage = '';
$errorMessage = '';

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
            if (!$hasPremiumAccess) {
                throw new RuntimeException('Only premium members can save a PayPal payout address.');
            }

            $user->savePaypalEmail((int) $_SESSION['user_id'], (string) ($_POST['paypal_email'] ?? ''));
            $currentUser = $user->getCurrentUser();
            $successMessage = 'Your PayPal payout email has been saved.';
        }
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

function renderAccountSettingsContent(array $currentUser, string $successMessage, string $errorMessage): void {
?>
<div class="w-full px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr),300px]">
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                <div class="relative px-4 py-5 sm:px-5 sm:py-6 lg:px-6 lg:py-7">
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Manage your account</h1>
                    <p class="mt-2 max-w-2xl text-xs leading-5 text-slate-600 sm:text-sm">
                        Review your locked account identity details and keep your login credentials secure from one place.
                    </p>
                </div>
            </section>

            <?php if ($successMessage !== ''): ?>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($successMessage); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-red-700 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($errorMessage); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
                    <h2 class="text-base font-semibold text-slate-900">Protected account details</h2>
                    <p class="mt-1 text-xs text-slate-500">For security and verification reasons, these details cannot be changed from settings.</p>
                </div>
                <div class="space-y-4 px-4 py-4 sm:px-5 sm:py-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Account owner</p>
                        <p class="mt-2 text-base font-semibold text-slate-900"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Full name</label>
                            <div class="mt-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <span class="text-sm text-slate-900"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Email address</label>
                            <div class="mt-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <span class="text-sm text-slate-900"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Phone number</label>
                            <div class="mt-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <span class="text-sm text-slate-900"><?php echo htmlspecialchars($currentUser['phone'] ?? 'Not provided'); ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Username</label>
                            <div class="mt-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <span class="text-sm text-slate-900"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php if ($hasPremiumAccess): ?>
                <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm">
                    <div class="border-b border-emerald-200 px-4 py-3 sm:px-5">
                        <h2 class="text-base font-semibold text-slate-900">Premium payout details</h2>
                        <p class="mt-1 text-xs text-slate-600">This is used for platform payout processing and is only available to active premium members.</p>
                    </div>
                    <div class="px-4 py-4 sm:px-5 sm:py-5">
                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">PayPal email</label>
                                <input type="email" name="paypal_email" value="<?php echo htmlspecialchars((string) ($currentUser['paypal_email'] ?? '')); ?>" placeholder="name@example.com" required
                                       class="mt-1.5 block w-full rounded-xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" name="save_paypal_email" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20">
                                    Save PayPal address
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3 sm:px-5">
                    <h2 class="text-base font-semibold text-slate-900">Change password</h2>
                    <p class="mt-1 text-xs text-slate-500">Use a strong password you don’t reuse elsewhere.</p>
                </div>
                <div class="px-4 py-4 sm:px-5 sm:py-5">
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Current password</label>
                                <input type="password" name="current_password" required
                                       class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">New password</label>
                                <input type="password" name="new_password" required
                                       class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Confirm new password</label>
                                <input type="password" name="confirm_password" required
                                       class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" name="change_password" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-500/20">
                                Update password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Profile checklist</h2>
                <div class="mt-4 space-y-3">
                    <div class="rounded-xl bg-slate-50 px-3 py-3">
                        <p class="text-sm font-semibold text-slate-900">Use an active email</p>
                        <p class="mt-1 text-xs text-slate-500">Important account notices and verification messages are sent there.</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-3">
                        <p class="text-sm font-semibold text-slate-900">Keep your phone current</p>
                        <p class="mt-1 text-xs text-slate-500">It helps with account recovery and contact verification workflows.</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 px-3 py-3">
                        <p class="text-sm font-semibold text-slate-900">Refresh passwords regularly</p>
                        <p class="mt-1 text-xs text-slate-500">Choose a password with a mix of letters, numbers, and symbols.</p>
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
            <?php renderAccountSettingsContent($currentUser, $successMessage, $errorMessage); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderAccountSettingsContent($currentUser, $successMessage, $errorMessage);
    echo ob_get_clean();
}
