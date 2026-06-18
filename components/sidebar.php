<?php
require_once __DIR__ . '/../config/config.php';
// Get current page for active state handling
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!-- Toggle Button - Fixed position with high z-index -->
<button type="button" 
        class="fixed top-3 right-4 z-50 lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white shadow-lg border border-gray-200 text-gray-700 hover:text-emerald-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-all duration-200" 
        aria-controls="sidebar" 
        aria-expanded="false" 
        id="sidebar-toggle">
    <!-- Menu open icon -->
    <svg class="h-6 w-6 menu-open" 
         xmlns="http://www.w3.org/2000/svg" 
         fill="none" 
         viewBox="0 0 24 24" 
         stroke="currentColor" 
         aria-hidden="true">
        <path stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M4 6h16M4 12h16M4 18h16" />
    </svg>
    <!-- Menu close icon -->
    <svg class="h-6 w-6 menu-close hidden" 
         xmlns="http://www.w3.org/2000/svg" 
         fill="none" 
         viewBox="0 0 24 24" 
         stroke="currentColor" 
         aria-hidden="true">
        <path stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>

<!-- Overlay for mobile -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity lg:hidden sidebar-overlay hidden z-30"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out pt-12">
    <div class="h-full overflow-y-auto">
        <div class="p-4 space-y-4"> <!-- Reduced padding and spacing -->
            <!-- Main Navigation -->
            <div class="space-y-1"> <!-- Reduced spacing -->
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 group transition-all duration-200 <?php echo $current_page === 'dashboard' ? 'bg-emerald-50 text-emerald-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- Fundraising Section -->
            <div class="space-y-1"> <!-- Reduced spacing -->
                <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fundraising</h3>
                <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 group transition-all duration-200 <?php echo $current_dir === 'campaigns' && $current_page === 'create' ? 'bg-emerald-50 text-emerald-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>Create Campaign</span>
                </a>
                
            </div>

            <!-- Finance Section -->
            <div class="space-y-1"> <!-- Reduced spacing -->
                <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Finance</h3>
                <a href="<?php echo BASE_URL; ?>/withdrawals/initiate.php" class="flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 group transition-all duration-200 <?php echo $current_dir === 'withdrawals' && $current_page === 'initiate' ? 'bg-emerald-50 text-emerald-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                    </svg>
                    <span>Initiate Withdrawal</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/withdrawals/logs.php" class="flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 group transition-all duration-200 <?php echo $current_dir === 'withdrawals' && $current_page === 'logs' ? 'bg-emerald-50 text-emerald-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Withdraw Logs</span>
                </a>
            </div>

            <!-- Account Section -->
            <div class="space-y-1"> <!-- Reduced spacing -->
                <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Account</h3>
                <a href="<?php echo BASE_URL; ?>/account/settings.php" class="flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 group transition-all duration-200 <?php echo $current_dir === 'account' && $current_page === 'settings' ? 'bg-emerald-50 text-emerald-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Account Settings</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/account/verification.php" class="flex items-center px-4 py-3 rounded-lg text-gray-700 hover:text-emerald-600 hover:bg-emerald-50 group transition-all duration-200 <?php echo $current_dir === 'account' && $current_page === 'verification' ? 'bg-emerald-50 text-emerald-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Verification</span>
                </a>
            </div>

            <!-- Logout Button -->
            <div class="pt-4 mt-4 border-t border-gray-200"> <!-- Reduced padding and margin -->
                <a href="<?php echo BASE_URL; ?>/logout.php" class="flex items-center px-4 py-3 rounded-lg text-red-600 hover:text-red-700 hover:bg-red-50 group transition-all duration-200">
                    <svg class="w-5 h-5 mr-3 group-hover:text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        const toggleButton = document.getElementById('sidebar-toggle');
        const menuOpenIcon = toggleButton.querySelector('.menu-open');
        const menuCloseIcon = toggleButton.querySelector('.menu-close');

        function toggleSidebar() {
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            
            // Toggle sidebar
            sidebar.classList.toggle('-translate-x-full');
            
            // Toggle overlay
            if (overlay) {
                overlay.classList.toggle('hidden', isOpen);
            }
            
            // Toggle icons
            menuOpenIcon.classList.toggle('hidden', !isOpen);
            menuCloseIcon.classList.toggle('hidden', isOpen);
            
            // Update ARIA state
            toggleButton.setAttribute('aria-expanded', !isOpen);
        }

        // Handle toggle button clicks
        toggleButton.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSidebar();
        });

        // Handle overlay clicks
        if (overlay) {
            overlay.addEventListener('click', toggleSidebar);
        }

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        });

        // Handle resize events
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
                if (overlay) {
                    overlay.classList.add('hidden');
                }
                menuOpenIcon.classList.remove('hidden');
                menuCloseIcon.classList.add('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
            }
        });

        // Handle clicks outside
        document.addEventListener('click', (event) => {
            if (window.innerWidth < 1024 && 
                !sidebar.contains(event.target) && 
                !toggleButton.contains(event.target) && 
                !sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        });
    });
</script>