<?php
?>
<nav class="bg-white shadow fixed w-full z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo -->
           <div class="flex-shrink-0 flex items-center space-x-2" id="header-logo">
                <img src="<?php echo BASE_URL; ?>/assets/images/Logo.png" alt="Logo" class="h-6 w-6">
                <a href="<?php echo BASE_URL; ?>" class="text-2xl font-bold text-emerald-600">
                    CroFunding
                </a>
            </div>

            <!-- Search Icon and Field -->
            <div class="flex items-center flex-1 justify-center" id="header-search-section">
                <!-- Search Icon Button -->
                <button onclick="toggleHeaderSearch()" 
                        class="p-2 text-gray-600 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                        title="Search Campaigns"
                        id="search-icon-btn">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                
                <!-- Mobile Header Search Field (Hidden by default) -->
                <div class="hidden w-full max-w-sm mx-2 md:max-w-md lg:max-w-lg" id="header-search-container">
                    <div class="relative w-full">
                        <input type="text" 
                               id="header-search-input" 
                               placeholder="Search campaigns..." 
                               class="w-full px-4 py-2 pl-10 pr-12 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <button onclick="closeHeaderSearch()" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex md:items-center md:space-x-8" id="header-nav">
                <a href="<?php echo BASE_URL; ?>" class="text-gray-600 hover:text-gray-900">
                    Home
                </a>
                <a href="<?php echo BASE_URL; ?>/campaigns/v/" class="text-gray-600 hover:text-gray-900">
                    Verified Campaigns
                </a>
                <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" class="text-gray-600 hover:text-gray-900">
                    Browse Campaigns
                </a>
                <a href="<?php echo BASE_URL; ?>/login.php" class="text-gray-600 hover:text-gray-900">
                    Sign In
                </a>
                <a href="<?php echo BASE_URL; ?>/register.php" 
                   class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors">
                    Sign Up
                </a>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden" id="header-mobile-btn">
                <button type="button" 
                        class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                        aria-controls="mobile-menu" 
                        aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="md:hidden hidden fixed inset-0 bg-white z-40 mt-16" id="mobile-menu">
        <div class="flex flex-col h-full">
            <!-- Navigation links section -->
            <div class="flex-1 pt-4 pb-4 px-4 space-y-1">
                <a href="<?php echo BASE_URL; ?>" 
                   class="block px-3 py-3 text-lg font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg border-b border-gray-200">
                    Home
                </a>
                <a href="<?php echo BASE_URL; ?>/campaigns/v/" 
                   class="block px-3 py-3 text-lg font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg border-b border-gray-200">
                    Verified Campaigns
                </a>
                <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" 
                   class="block px-3 py-3 text-lg font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg border-b border-gray-200">
                    Browse Campaigns
                </a>
                <a href="<?php echo BASE_URL; ?>/campaigns/v/gaza/" 
                   class="block px-3 py-3 text-lg font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg border-b border-gray-200">
                    Gaza World
                </a>
            </div>
            
            <!-- Mobile authentication buttons at bottom -->
            <div class="border-t border-gray-200 px-4 py-6 space-y-3">
                <a href="<?php echo BASE_URL; ?>/register.php" 
                   class="block w-full text-center px-4 py-3 text-lg font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                   Sign Up
                </a>
                <a href="<?php echo BASE_URL; ?>/login.php" 
                   class="block w-full text-center px-4 py-3 text-lg font-medium text-emerald-600 border-2 border-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors">
                    Sign In
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
document.querySelector('.mobile-menu-button').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});

// Function to toggle header search visibility
function toggleHeaderSearch() {
    const searchContainer = document.getElementById('header-search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    const searchInput = document.getElementById('header-search-input');
    const headerLogo = document.getElementById('header-logo');

    if (searchContainer.classList.contains('hidden')) {
        // Show search field
        searchContainer.classList.remove('hidden');
        searchIconBtn.classList.add('text-emerald-600');
        searchInput.focus();
        
        // Hide logo text and search icon on mobile
        const logoText = headerLogo.querySelector('a');
        if (logoText) {
            logoText.classList.add('hidden');
        }
        searchIconBtn.classList.add('hidden');
        
        // Add click outside listener
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 100);
    } else {
        closeHeaderSearch();
    }
}

// Function to close header search
function closeHeaderSearch() {
    const searchContainer = document.getElementById('header-search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    const searchInput = document.getElementById('header-search-input');
    const headerLogo = document.getElementById('header-logo');

    // Hide search field
    searchContainer.classList.add('hidden');
    searchIconBtn.classList.remove('text-emerald-600');
    searchInput.blur();
    
    // Show logo text and search icon again
    const logoText = headerLogo.querySelector('a');
    if (logoText) {
        logoText.classList.remove('hidden');
    }
    searchIconBtn.classList.remove('hidden');
    
    // Remove click outside listener
    document.removeEventListener('click', handleClickOutside);
}

// Handle clicks outside search container
function handleClickOutside(event) {
    const searchContainer = document.getElementById('header-search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');
    
    if (!searchContainer.contains(event.target) && !searchIconBtn.contains(event.target)) {
        closeHeaderSearch();
    }
}

// Function to perform search from header
function performHeaderSearch() {
    const searchInput = document.getElementById('header-search-input');
    const searchTerm = searchInput.value.trim();
    
    if (searchTerm) {
        // Scroll to search section and trigger search
        const searchSection = document.getElementById('trending');
        if (searchSection) {
            searchSection.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
            
            // Wait for scroll to complete, then trigger search
            setTimeout(() => {
                const mainSearchInput = document.getElementById('campaign-search');
                if (mainSearchInput) {
                    mainSearchInput.value = searchTerm;
                    mainSearchInput.dispatchEvent(new Event('input'));
                }
                
                // Close header search
                closeHeaderSearch();
            }, 500);
        }
    }
}

// Add event listeners for header search
document.addEventListener('DOMContentLoaded', function() {
    const headerSearchInput = document.getElementById('header-search-input');
    
    if (headerSearchInput) {
        // Search on Enter key
        headerSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performHeaderSearch();
            }
        });
        
        // Search on input change (optional - for real-time search)
        headerSearchInput.addEventListener('input', function() {
            // You can add real-time search here if desired
        });
    }
});

// Function to scroll to search section
function scrollToSearch() {
    const searchSection = document.getElementById('trending');
    if (searchSection) {
        searchSection.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
        
        // Focus on search input after scrolling
        setTimeout(() => {
            const searchInput = document.getElementById('campaign-search');
            if (searchInput) {
                searchInput.focus();
            }
        }, 500);
    }
}
</script>