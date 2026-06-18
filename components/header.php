<?php
// Determine if we're in a subdirectory
$isSubdirectory = strpos($_SERVER['SCRIPT_NAME'], '/campaigns/') !== false;
$configPath = $isSubdirectory ? '/../config' : '/config';
$basePath = dirname(__DIR__);

require_once $basePath . '/config/config.php';
require_once $basePath . '/config/User.php';

?>

<!-- Favicon -->
<link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
<link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
<link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">

<?php

if (!isset($currentUser)) {
    $user = new User();
    $currentUser = $user->getCurrentUser();
}

// Add function_exists check to prevent redeclaration
if (!function_exists('getUserAvatar')) {
    function getUserAvatar($avatar) {
        if (empty($avatar)) {
            return BASE_URL . '/assets/images/default-avatar.jpg';
        }
        
        // If it's already a full URL, return it
        if (filter_var($avatar, FILTER_VALIDATE_URL)) {
            return $avatar;
        }
        
        // Construct the path to the avatars directory
        return BASE_URL . '/uploads/avatars/' . $avatar;
    }
}

// Get the avatar URL
$avatarUrl = getUserAvatar($currentUser['avatar'] ?? null);
?>
<!-- Top Navigation Bar -->
<nav class="fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-30">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Left side - Logo -->
            <div class="flex-1 flex items-center justify-start">
                <!-- Mobile Menu Button (if needed) -->
               
                <!-- Logo -->
                <a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center flex-shrink-0 px-4">
                    <span class="text-xl md:text-2xl font-bold text-emerald-600">CroFunding</span>
                </a>
            </div>

            <!-- Center - Profile Picture (visible only on mobile) -->
            <div class="flex-1 flex items-center justify-center md:hidden">
                <div class="relative">
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
                         alt="<?php echo htmlspecialchars($currentUser['name']); ?>" 
                         class="w-8 h-8 rounded-full object-cover border-2 border-emerald-600"
                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-avatar.png'">
                </div>
            </div>

            <!-- Right Navigation -->
            <div class="flex-1 flex items-center justify-end space-x-2 md:space-x-4">
                <!-- Create Campaign Button (Hidden on mobile) -->
                <a href="<?php echo BASE_URL; ?>/campaigns/create.php" 
                   class="hidden md:inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create
                </a>

                <!-- User Menu (hidden on mobile) -->
                <div class="relative hidden md:block">
                    <button class="flex items-center space-x-2 text-gray-700 hover:text-emerald-600 focus:outline-none" id="user-menu-button">
                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
                             alt="<?php echo htmlspecialchars($currentUser['name']); ?>" 
                             class="w-8 h-8 rounded-full object-cover border-2 border-emerald-600"
                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-avatar.png'">
                        <span class="hidden md:inline-block"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                        <!-- Dropdown arrow -->
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu (Hidden by default) -->
                    <div class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50" id="user-menu">
                        <div class="py-1" role="menu">
                            <a href="<?php echo BASE_URL; ?>/account/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                            <a href="<?php echo BASE_URL; ?>/campaigns" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Campaigns</a>
                            <a href="<?php echo BASE_URL; ?>/account/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Settings</a>
                            <div class="border-t border-gray-100"></div>
                            <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" role="menuitem">Sign out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile menu (hidden by default) -->
    <div class="hidden md:hidden bg-white border-t border-gray-200" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="<?php echo BASE_URL; ?>/profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-emerald-600 hover:bg-gray-50">Your Profile</a>
            <a href="<?php echo BASE_URL; ?>/campaigns" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-emerald-600 hover:bg-gray-50">Your Campaigns</a>
            <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-emerald-600 hover:bg-gray-50">Create Campaign</a>
            <a href="<?php echo BASE_URL; ?>/settings.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-emerald-600 hover:bg-gray-50">Settings</a>
            <div class="border-t border-gray-200 my-2"></div>
            <a href="<?php echo BASE_URL; ?>/logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-gray-50">Sign out</a>
        </div>
    </div>
</nav>

<!-- JavaScript for user dropdown and mobile menu -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuButton = document.getElementById('user-menu-button');
        const userMenu = document.getElementById('user-menu');
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        // Toggle user dropdown
        userMenuButton?.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
        });

        // Toggle mobile menu
        mobileMenuButton?.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Close menus when clicking outside
        document.addEventListener('click', (event) => {
            if (userMenu && !userMenuButton?.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
            if (mobileMenu && !mobileMenuButton?.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
    });
</script>