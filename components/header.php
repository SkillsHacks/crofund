<?php
// No output first - separate logic and HTML
$isSubdirectory = strpos($_SERVER['SCRIPT_NAME'], '/campaigns/') !== false;
$isDashboardPage = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'dashboard.php';
$isDashboardShell = $isDashboardPage || !empty($useDashboardShell);
$basePath = dirname(__DIR__);

require_once $basePath . '/config/config.php';
require_once $basePath . '/config/User.php';

// Defensive user initialization - only if not already set
if (!isset($user)) {
    $user = new User();
}
if (!isset($currentUser)) {
    $currentUser = $user->getCurrentUser();
}

// Defensive avatar function
if (!function_exists('getUserAvatar')) {
    function getUserAvatar($avatar) {
        if (empty($avatar)) {
            return BASE_URL . '/assets/images/default-avatar.jpg';
        }
        
        if (filter_var($avatar, FILTER_VALIDATE_URL)) {
            return $avatar;
        }
        
        return BASE_URL . '/uploads/avatars/' . $avatar;
    }
}

$avatarUrl = getUserAvatar($currentUser['avatar'] ?? null);
?>
<?php if (empty($hidePremiumHeaderBanner)): ?>
    <div class="bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500">
        <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center gap-2 py-2 text-center text-xs font-semibold tracking-wide text-white sm:text-sm">
                <span>Upgrade to a premium account for faster donations and withdrawals.</span>
                <a href="<?php echo BASE_URL; ?>/account/premium.php" class="inline-flex items-center rounded-full border border-white/30 bg-white/10 px-2.5 py-1 font-semibold text-white transition hover:bg-white/20">
                    Upgrade now
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Favicon - still here but after PHP logic -->
<link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
<link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
<link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">

<style>
    body.sidebar-collapsed #sidebar .sidebar-label,
    body.sidebar-collapsed #sidebar .sidebar-section-title {
        opacity: 0;
        width: 0;
        max-width: 0;
        margin: 0;
        overflow: hidden;
        pointer-events: none;
        white-space: nowrap;
    }

    body.sidebar-collapsed #sidebar .sidebar-link {
        justify-content: center;
    }

    body.sidebar-collapsed #sidebar .sidebar-link > svg,
    body.sidebar-collapsed #sidebar .sidebar-link > span:first-child > svg,
    body.sidebar-collapsed #sidebar .sidebar-link > a > svg {
        margin-left: 0;
        margin-right: 0;
    }

    @media (min-width: 1024px) {
        body .app-main {
            transition: margin-left 0.3s ease-in-out;
        }

        body.sidebar-collapsed .app-main {
            margin-left: 5rem !important;
        }

        body #app-brand-link {
            transition: padding-left 0.3s ease-in-out;
            padding-left: 0;
        }

        body.sidebar-collapsed #app-brand-link {
            padding-left: 4.5rem;
        }
    }
</style>

<!-- Navigation -->
<nav class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-30<?php echo $isDashboardShell ? ' dashboard-app-header' : ''; ?>">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex-1 flex items-center justify-start">
                <a href="<?php echo BASE_URL; ?>/dashboard.php" id="app-brand-link" class="flex items-center gap-3 flex-shrink-0 pl-12 pr-4 lg:pl-0">
                    <span class="<?php echo $isDashboardShell ? 'dashboard-brand-mark' : 'flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-lg shadow-emerald-900/20'; ?>">
                        <?php if ($isDashboardShell): ?>
                            <svg class="h-9 w-9" viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="CroFunding logo">
                                <path d="M64 84C59.6 84 56 80.4 56 76V65.2C49.5 71.7 42.7 74.7 35.4 74.7C31.7 74.7 28.7 71.7 28.7 68C28.7 64.3 31.7 61.3 35.4 61.3C42.5 61.3 49 57.1 55.5 48.7C48.6 48.7 42.9 46.5 38.1 42C35.4 39.4 35.2 35.1 37.8 32.4C40.3 29.7 44.6 29.5 47.3 32.1C49.8 34.5 53.3 35.6 57.8 35.6C58.6 29.4 63.8 24.7 70.2 24.7C73.9 24.7 77.6 26.3 80.6 29.2C83.7 32.3 85.3 35.9 85.3 39.7C85.3 50.6 76.5 59.4 65.6 59.4H64V76C64 80.4 68.4 84 64 84Z" fill="#047857"/>
                                <path d="M67.1 49.3H65.6C70.1 49.3 73.8 45.6 73.8 41.1C73.8 39.3 73.1 37.6 71.8 36.3C70.8 35.3 69.4 34.7 68 34.7C65.1 34.7 62.7 37.1 62.7 40V44C62.7 46.9 64.9 49.3 67.1 49.3Z" fill="#10B981"/>
                            </svg>
                        <?php else: ?>
                            <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="CroFunding logo" class="h-6 w-6">
                        <?php endif; ?>
                    </span>
                    <span class="hidden text-xl font-bold text-emerald-600 sm:inline md:text-2xl">CroFunding</span>
                </a>
            </div>

            <div class="flex-1 flex items-center justify-center md:hidden">
                <div class="relative">
                    <button type="button"
                            id="mobile-user-menu-button"
                            class="flex h-10 w-10 items-center justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"
                            aria-label="Open account menu"
                            aria-controls="mobile-user-menu"
                            aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>"
                             alt=""
                             class="w-8 h-8 rounded-full object-cover border-2 border-emerald-600"
                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-avatar.png'">
                    </button>
                    <div class="hidden absolute right-0 top-full mt-2 w-48 rounded-xl border border-gray-200 bg-white py-1 shadow-lg z-50 md:hidden"
                         id="mobile-user-menu"
                         role="menu"
                         aria-label="Account menu">
                        <a href="<?php echo BASE_URL; ?>/account/settings.php" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Your Profile</a>
                        <a href="<?php echo BASE_URL; ?>/campaigns/index.php" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Your Campaigns</a>
                        <a href="<?php echo BASE_URL; ?>/account/settings.php" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">Settings</a>
                        <div class="my-1 border-t border-gray-100"></div>
                        <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50" role="menuitem">Sign out</a>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex items-center justify-end space-x-2 md:space-x-4">
                <?php if (!$isDashboardShell && empty($hideHeaderCreateButton)): ?>
                <a href="<?php echo BASE_URL; ?>/campaigns/create.php"
                   class="hidden md:inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create
                </a>
                <?php endif; ?>



                <div class="relative hidden md:block">
                    <button type="button" class="flex items-center space-x-2 text-gray-700 hover:text-emerald-600 focus:outline-none" id="user-menu-button" aria-controls="user-menu" aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
                             alt=""
                             class="w-8 h-8 rounded-full object-cover border-2 border-emerald-600"
                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-avatar.png'">
                        <span class="hidden md:inline-block"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50" id="user-menu">
                        <div class="py-1" role="menu">
                            <a href="<?php echo BASE_URL; ?>/account/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                            <a href="<?php echo BASE_URL; ?>/campaigns/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Campaigns</a>
                            <a href="<?php echo BASE_URL; ?>/account/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Settings</a>
                            <div class="border-t border-gray-100"></div>
                            <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" role="menuitem">Sign out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="hidden md:hidden bg-white border-t border-gray-200" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="<?php echo BASE_URL; ?>/account/settings.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-emerald-600 hover:bg-gray-50">Your Profile</a>
            <a href="<?php echo BASE_URL; ?>/campaigns/index.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-emerald-600 hover:bg-gray-50">Your Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-emerald-600 hover:bg-gray-50">Create Campaign</a>
            <a href="<?php echo BASE_URL; ?>/account/settings.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-emerald-600 hover:bg-gray-50">Settings</a>
            <div class="border-t border-gray-200 my-2"></div>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-gray-50">Sign out</a>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        const mobileUserMenuButton = document.getElementById('mobile-user-menu-button');
        const mobileUserMenu = document.getElementById('mobile-user-menu');
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        userMenuButton?.addEventListener('click', (e) => {
            e.stopPropagation();
            const isExpanded = userMenuButton.getAttribute('aria-expanded') === 'true';
            userMenuButton.setAttribute('aria-expanded', String(!isExpanded));
            userMenu.classList.toggle('hidden', isExpanded);
        });

        mobileUserMenuButton?.addEventListener('click', (e) => {
            e.stopPropagation();
            const isExpanded = mobileUserMenuButton.getAttribute('aria-expanded') === 'true';
            mobileUserMenuButton.setAttribute('aria-expanded', String(!isExpanded));
            mobileUserMenu.classList.toggle('hidden', isExpanded);
        });

        mobileMenuButton?.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', (event) => {
            if (userMenu && !userMenuButton?.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
                userMenuButton?.setAttribute('aria-expanded', 'false');
            }
            if (mobileUserMenu && !mobileUserMenuButton?.contains(event.target) && !mobileUserMenu.contains(event.target)) {
                mobileUserMenu.classList.add('hidden');
                mobileUserMenuButton?.setAttribute('aria-expanded', 'false');
            }
            if (mobileMenu && !mobileMenuButton?.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && userMenu && !userMenu.classList.contains('hidden')) {
                userMenu.classList.add('hidden');
                userMenuButton?.setAttribute('aria-expanded', 'false');
                userMenuButton?.focus();
            }
            if (event.key === 'Escape' && mobileUserMenu && !mobileUserMenu.classList.contains('hidden')) {
                mobileUserMenu.classList.add('hidden');
                mobileUserMenuButton?.setAttribute('aria-expanded', 'false');
                mobileUserMenuButton?.focus();
            }
        });
    });
</script>
