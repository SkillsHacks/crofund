<?php
session_start();

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
<div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr),360px]">
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Manage your account</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
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

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-slate-900">Protected account details</h2>
                    <p class="mt-1 text-sm text-slate-500">For security and verification reasons, these details cannot be changed from settings.</p>
                </div>
                <div class="space-y-6 px-5 py-5 sm:px-6 sm:py-6">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Account owner</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Full name</label>
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="text-base text-slate-900"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Email address</label>
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="text-base text-slate-900"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Phone number</label>
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="text-base text-slate-900"><?php echo htmlspecialchars($currentUser['phone'] ?? 'Not provided'); ?></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Username</label>
                            <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="text-base text-slate-900"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-slate-900">Change password</h2>
                    <p class="mt-1 text-sm text-slate-500">Use a strong password you don’t reuse elsewhere.</p>
                </div>
                <div class="px-5 py-5 sm:px-6 sm:py-6">
                    <form method="POST" class="space-y-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">Current password</label>
                                <input type="password" name="current_password" required
                                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700">New password</label>
                                <input type="password" name="new_password" required
                                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-slate-700">Confirm new password</label>
                                <input type="password" name="confirm_password" required
                                       class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base text-slate-900 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" name="change_password" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-500/20">
                                Update password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Profile checklist</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Use an active email</p>
                        <p class="mt-1 text-sm text-slate-500">Important account notices and verification messages are sent there.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Keep your phone current</p>
                        <p class="mt-1 text-sm text-slate-500">It helps with account recovery and contact verification workflows.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">Refresh passwords regularly</p>
                        <p class="mt-1 text-sm text-slate-500">Choose a password with a mix of letters, numbers, and symbols.</p>
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
    <div class="flex min-h-screen pt-16">
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
