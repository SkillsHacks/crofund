<?php
?>
<nav class="fixed inset-x-0 top-0 z-50 border-b border-white/60 bg-white/90 backdrop-blur-xl shadow-sm">
    <div class="bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-500">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center gap-2 py-2 text-center text-xs font-semibold tracking-wide text-white sm:text-sm">
                <span>Upgrade to a premium account for faster donations and withdrawals.</span>
                <a href="<?php echo BASE_URL; ?>/account/premium.php" class="inline-flex items-center rounded-full border border-white/30 bg-white/10 px-2.5 py-1 font-semibold text-white transition hover:bg-white/20">
                    Upgrade now
                </a>
            </div>
        </div>
    </div>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3" id="header-logo">
                <a href="<?php echo BASE_URL; ?>" class="inline-flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100 shadow-sm">
                        <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="CroFunding logo" class="h-6 w-6">
                    </div>
                    <div class="min-w-0">
                        <span class="block truncate text-lg font-bold tracking-tight text-slate-900 sm:text-xl">CroFunding</span>
                        <span class="hidden text-xs font-medium text-slate-500 sm:block">Fundraising made simple</span>
                    </div>
                </a>
            </div>

            <div class="hidden flex-1 justify-center lg:flex" id="header-search-section">
                <div class="relative w-full max-w-xl">
                    <input type="text"
                           id="header-search-input-desktop"
                           placeholder="Search campaigns..."
                           class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 pl-11 pr-12 text-sm text-slate-700 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button onclick="performHeaderSearch('desktop')" type="button" class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-emerald-600 transition hover:text-emerald-700">
                        Search
                    </button>
                </div>
            </div>

            <div class="hidden items-center gap-2 md:flex lg:gap-3" id="header-nav">
                <a href="<?php echo BASE_URL; ?>" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                    Home
                </a>
                <a href="<?php echo BASE_URL; ?>/campaigns/v/" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                    Verified
                </a>
                <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                    Browse
                </a>
                <a href="<?php echo BASE_URL; ?>/login.php" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                    Sign in
                </a>
                <a href="<?php echo BASE_URL; ?>/register.php" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700">
                    Sign up
                </a>
            </div>

            <div class="flex items-center gap-2 md:hidden">
                <button onclick="toggleHeaderSearch()"
                        type="button"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-600"
                        title="Search campaigns"
                        aria-label="Search campaigns"
                        aria-controls="header-search-container"
                        aria-expanded="false"
                        id="search-icon-btn">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>Search</span>
                </button>
                <button type="button"
                        class="mobile-menu-button inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-600"
                        aria-controls="mobile-menu"
                        aria-expanded="false"
                        aria-label="Open main menu">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <span>Menu</span>
                </button>
            </div>
        </div>

        <div class="hidden pb-4 md:hidden" id="header-search-container">
            <div class="relative w-full">
                <input type="text"
                       id="header-search-input"
                       placeholder="Search campaigns..."
                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pl-11 pr-14 text-sm text-slate-700 placeholder-slate-400 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-3">
                    <button onclick="performHeaderSearch('mobile')" type="button" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">
                        Go
                    </button>
                    <button onclick="closeHeaderSearch()" type="button" class="inline-flex items-center justify-center rounded-lg p-1 text-slate-400 hover:text-slate-600" aria-label="Close search">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="hidden border-t border-slate-200 bg-white/95 md:hidden" id="mobile-menu">
        <div class="space-y-2 px-4 py-4">
            <a href="<?php echo BASE_URL; ?>" class="block rounded-2xl px-4 py-3 text-base font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                Home
            </a>
            <a href="<?php echo BASE_URL; ?>/campaigns/v/" class="block rounded-2xl px-4 py-3 text-base font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                Verified Campaigns
            </a>
            <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" class="block rounded-2xl px-4 py-3 text-base font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                Browse Campaigns
            </a>
            <a href="<?php echo BASE_URL; ?>/campaigns/v/gaza/" class="block rounded-2xl px-4 py-3 text-base font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                Gaza World
            </a>
        </div>
        <div class="border-t border-slate-200 px-4 py-4 space-y-3">
            <a href="<?php echo BASE_URL; ?>/register.php" class="block w-full rounded-2xl bg-emerald-600 px-4 py-3 text-center text-base font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                Sign Up
            </a>
            <a href="<?php echo BASE_URL; ?>/login.php" class="block w-full rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-base font-semibold text-emerald-700 transition hover:bg-emerald-100">
                Sign In
            </a>
        </div>
    </div>
</nav>

<script>
(function () {
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuButton?.addEventListener('click', function () {
        mobileMenu?.classList.toggle('hidden');
    });
})();

function getHeaderSearchInput(mode) {
    if (mode === 'desktop') {
        return document.getElementById('header-search-input-desktop');
    }
    return document.getElementById('header-search-input');
}

function toggleHeaderSearch() {
    const searchContainer = document.getElementById('header-search-container');
    const searchInput = document.getElementById('header-search-input');

    if (!searchContainer || !searchInput) return;

    searchContainer.classList.toggle('hidden');
    if (!searchContainer.classList.contains('hidden')) {
        searchInput.focus();
        setTimeout(() => document.addEventListener('click', handleClickOutside), 100);
    } else {
        document.removeEventListener('click', handleClickOutside);
    }
}

function closeHeaderSearch() {
    const searchContainer = document.getElementById('header-search-container');
    const searchInput = document.getElementById('header-search-input');

    if (!searchContainer || !searchInput) return;

    searchContainer.classList.add('hidden');
    searchInput.blur();
    document.removeEventListener('click', handleClickOutside);
}

function handleClickOutside(event) {
    const searchContainer = document.getElementById('header-search-container');
    const searchIconBtn = document.getElementById('search-icon-btn');

    if (searchContainer && searchIconBtn && !searchContainer.contains(event.target) && !searchIconBtn.contains(event.target)) {
        closeHeaderSearch();
    }
}

function performHeaderSearch(mode = 'mobile') {
    const searchInput = getHeaderSearchInput(mode);
    const searchTerm = searchInput?.value.trim();

    if (searchTerm) {
        const searchSection = document.getElementById('trending');
        if (searchSection) {
            searchSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(() => {
                const mainSearchInput = document.getElementById('campaign-search');
                if (mainSearchInput) {
                    mainSearchInput.value = searchTerm;
                    mainSearchInput.dispatchEvent(new Event('input'));
                }
                closeHeaderSearch();
            }, 500);
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const mobileSearchInput = document.getElementById('header-search-input');
    const desktopSearchInput = document.getElementById('header-search-input-desktop');

    [mobileSearchInput, desktopSearchInput].forEach((input, index) => {
        if (!input) return;
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performHeaderSearch(index === 1 ? 'desktop' : 'mobile');
            }
        });
    });
});

function scrollToSearch() {
    const searchSection = document.getElementById('trending');
    if (searchSection) {
        searchSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => {
            const searchInput = document.getElementById('campaign-search');
            if (searchInput) {
                searchInput.focus();
            }
        }, 500);
    }
}
</script>
