<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/trust_risk_ui.php';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$sidebarTrustRiskUi = isset($trustRiskUi) && is_array($trustRiskUi)
    ? $trustRiskUi
    : getTrustRiskUiState($currentUser ?? null);
?>
<button type="button"
        class="fixed top-3 left-4 z-50 inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-gray-700 shadow-lg transition-all duration-200 hover:bg-gray-50 hover:text-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 lg:h-10 lg:w-10 lg:px-0"
        aria-controls="sidebar"
        aria-expanded="false"
        id="sidebar-toggle">
    <svg class="h-5 w-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    <span class="text-sm font-medium leading-none lg:hidden">Menu</span>
</button>

<div class="fixed inset-0 z-30 hidden bg-gray-900/40 lg:hidden sidebar-overlay"></div>

<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-full -translate-x-full flex-col border-r border-gray-200 bg-white pt-16 transition-all duration-300 ease-in-out lg:w-64 lg:max-w-none lg:translate-x-0"
       data-collapsed="false">
    <div class="flex h-full flex-col overflow-y-auto">
        <div class="flex-1 space-y-4 p-3 lg:p-4">
            <div class="flex items-center gap-3 px-3 py-2 lg:px-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-lg shadow-emerald-900/20">
                    <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="CroFunding logo" class="h-7 w-7">
                </div>
                <div class="min-w-0">
                    <p class="sidebar-section-title text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600/80">Crofund</p>
                    <h2 class="sidebar-label truncate text-base font-bold text-gray-900">Dashboard</h2>
                </div>
            </div>

            <div class="space-y-1">
                <a href="<?php echo BASE_URL; ?>/dashboard/" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-700'; ?> group flex items-center rounded-lg px-3 py-3 transition-all duration-200 hover:bg-emerald-50 hover:text-emerald-600" title="Dashboard">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="sidebar-label ml-3 truncate text-sm font-medium">Dashboard</span>
                </a>
            </div>

            <div class="space-y-1">
                <h3 class="sidebar-section-title px-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Fundraising</h3>
                <?php renderSidebarNavItem([
                    'label' => 'Create Campaign',
                    'href' => BASE_URL . '/campaigns/create.php',
                    'action_key' => 'create_campaign',
                    'icon_html' => '<svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>',
                ], $current_dir === 'campaigns' && $current_page === 'create' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-700', $sidebarTrustRiskUi); ?>
                <?php renderSidebarNavItem([
                    'label' => 'My Campaigns',
                    'href' => BASE_URL . '/campaigns/index.php',
                    'action_key' => 'view_campaigns',
                    'icon_html' => '<svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
                ], $current_dir === 'campaigns' && $current_page === 'index' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-700', $sidebarTrustRiskUi); ?>
            </div>

            <div class="space-y-1">
                <h3 class="sidebar-section-title px-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Finance</h3>
                <?php renderSidebarNavItem([
                    'label' => 'Initiate Withdrawal',
                    'href' => BASE_URL . '/withdrawals/initiate.php',
                    'action_key' => 'withdraw_funds',
                    'icon_html' => '<svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>',
                ], $current_dir === 'withdrawals' && $current_page === 'initiate' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-700', $sidebarTrustRiskUi); ?>
                <?php renderSidebarNavItem([
                    'label' => 'Withdraw Logs',
                    'href' => BASE_URL . '/withdrawals/logs.php',
                    'action_key' => 'view_withdraw_logs',
                    'icon_html' => '<svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
                ], $current_dir === 'withdrawals' && $current_page === 'logs' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-700', $sidebarTrustRiskUi); ?>
            </div>

            <div class="space-y-1">
                <h3 class="sidebar-section-title px-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Account</h3>
                <?php renderSidebarNavItem([
                    'label' => 'Account Settings',
                    'href' => BASE_URL . '/account/settings.php',
                    'action_key' => 'update_sensitive_profile',
                    'icon_html' => '<svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
                ], $current_dir === 'account' && $current_page === 'settings' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-700', $sidebarTrustRiskUi); ?>
                <?php renderSidebarNavItem([
                    'label' => 'Verification',
                    'href' => BASE_URL . '/account/verification.php',
                    'action_key' => 'view_verification',
                    'icon_html' => '<svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                ], $current_dir === 'account' && $current_page === 'verification' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-700', $sidebarTrustRiskUi); ?>
                <?php renderSidebarNavItem([
                    'label' => 'Notifications',
                    'href' => BASE_URL . '/account/notifications.php',
                    'action_key' => 'view_notifications',
                    'icon_html' => '<svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
                ], $current_dir === 'account' && $current_page === 'notifications' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-700', $sidebarTrustRiskUi); ?>
            </div>
        </div>

        <div class="border-t border-gray-200 p-3 lg:p-4">
            <a href="<?php echo BASE_URL; ?>/logout.php" class="sidebar-link flex items-center rounded-lg px-3 py-3 text-red-600 transition-all duration-200 hover:bg-red-50 hover:text-red-700" title="Logout">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span class="sidebar-label ml-3 truncate text-sm font-medium">Logout</span>
            </a>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const toggleButton = document.getElementById('sidebar-toggle');
    const storageKey = 'crofund-sidebar-collapsed';

    if (!sidebar || !toggleButton) {
        return;
    }

    const sidebarLinks = sidebar.querySelectorAll('a[href]');
    const isDesktop = () => window.innerWidth >= 1024;
    const applyDesktopState = (collapsed) => {
        sidebar.dataset.collapsed = collapsed ? 'true' : 'false';
        sidebar.classList.toggle('lg:w-20', collapsed);
        sidebar.classList.toggle('lg:w-64', !collapsed);
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        toggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    const openMobileSidebar = () => {
        sidebar.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
        toggleButton.setAttribute('aria-expanded', 'true');
    };

    const closeMobileSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
        toggleButton.setAttribute('aria-expanded', 'false');
    };

    const syncResponsiveState = () => {
        if (isDesktop()) {
            sidebar.classList.remove('-translate-x-full');
            overlay?.classList.add('hidden');
            const collapsed = localStorage.getItem(storageKey) === 'true';
            applyDesktopState(collapsed);
        } else {
            closeMobileSidebar();
            sidebar.dataset.collapsed = 'false';
            document.body.classList.remove('sidebar-collapsed');
            sidebar.classList.remove('lg:w-20');
            sidebar.classList.add('lg:w-64');
        }
    };

    toggleButton.addEventListener('click', function(e) {
        e.stopPropagation();

        if (isDesktop()) {
            const nextCollapsed = sidebar.dataset.collapsed !== 'true';
            localStorage.setItem(storageKey, nextCollapsed ? 'true' : 'false');
            applyDesktopState(nextCollapsed);
            return;
        }

        if (sidebar.classList.contains('-translate-x-full')) {
            openMobileSidebar();
        } else {
            closeMobileSidebar();
        }
    });

    overlay?.addEventListener('click', closeMobileSidebar);

    sidebarLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (!isDesktop()) {
                closeMobileSidebar();
            }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !isDesktop()) {
            closeMobileSidebar();
        }
    });

    document.addEventListener('click', function(event) {
        if (!isDesktop() &&
            !sidebar.contains(event.target) &&
            !toggleButton.contains(event.target) &&
            !sidebar.classList.contains('-translate-x-full')) {
            closeMobileSidebar();
        }
    });

    window.addEventListener('resize', syncResponsiveState);
    syncResponsiveState();
});
</script>
