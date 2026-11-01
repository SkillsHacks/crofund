<?php
?>
<style>
    button:not([data-close-mobile-menu]),
    input[type="button"],
    input[type="submit"],
    input[type="reset"],
    [role="button"],
    a[class*="justify-center"][class*="px-"] {
        border-radius: 9999px !important;
    }
</style>
<nav class="fixed inset-x-0 top-0 z-50 border-b border-white/60 bg-white/90 backdrop-blur-xl shadow-sm">
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
                <button type="button"
                        class="mobile-menu-button inline-flex h-10 items-center justify-center gap-2 rounded-xl px-2 text-sm font-medium text-slate-600 transition hover:text-emerald-600"
                        aria-controls="mobile-menu"
                        aria-expanded="false"
                        aria-label="Open main menu">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

</nav>

<div id="mobile-menu" class="fixed inset-0 z-[60] hidden md:hidden" aria-hidden="true">
    <button type="button" class="absolute inset-0 h-full w-full bg-slate-950/40" data-close-mobile-menu aria-label="Dismiss main menu backdrop"></button>
    <section class="absolute inset-y-0 right-0 flex w-[min(90%,28rem)] flex-col bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="mobile-menu-title">
        <div class="flex h-16 shrink-0 items-center justify-between px-6">
            <h2 id="mobile-menu-title" class="sr-only">Main menu</h2>
            <span class="text-sm font-semibold text-slate-500">Explore CroFunding</span>
            <button type="button" data-close-mobile-menu class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-700 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600" aria-label="Close main menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <nav class="min-h-0 flex-1 overflow-y-auto px-0 pb-4" aria-label="Mobile navigation">
            <a href="<?php echo BASE_URL; ?>/" class="group flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 transition hover:bg-slate-50">
                <span>
                    <span class="block text-lg font-bold tracking-tight text-slate-900">Homepage</span>
                    <span class="mt-1 block text-sm leading-5 text-slate-500">Return to the CroFunding homepage</span>
                </span>
                <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a href="<?php echo BASE_URL; ?>/login.php" class="group flex items-start justify-between gap-4 bg-slate-50 px-6 py-5 transition hover:bg-slate-100">
                <span>
                    <span class="block text-lg font-bold tracking-tight text-slate-900">Manage your activity</span>
                    <span class="mt-1 block text-sm leading-5 text-slate-500">Sign in or sign up to manage your donations, fundraisers and more</span>
                </span>
                <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>

            <div class="px-6">
                <details class="group border-b border-slate-100">
                    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 py-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">
                        <span><span class="block text-lg font-bold tracking-tight text-slate-900">Donate</span><span class="mt-1 block text-sm leading-5 text-slate-500">Discover fundraisers and causes to support</span></span>
                        <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="space-y-1 pb-4 pl-3">
                        <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Browse fundraisers</a>
                        <a href="<?php echo BASE_URL; ?>/campaigns/v/" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Verified campaigns</a>
                        <a href="<?php echo BASE_URL; ?>/campaigns/v/gaza/" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Gaza fundraiser</a>
                    </div>
                </details>

                <details class="group border-b border-slate-100">
                    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 py-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">
                        <span><span class="block text-lg font-bold tracking-tight text-slate-900">Fundraise</span><span class="mt-1 block text-sm leading-5 text-slate-500">Start fundraising, tips, and resources</span></span>
                        <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="space-y-1 pb-4 pl-3">
                        <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Start a fundraiser</a>
                        <a href="<?php echo BASE_URL; ?>/how-it-works.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">How it works</a>
                        <a href="<?php echo BASE_URL; ?>/success-stories.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Success stories</a>
                    </div>
                </details>

                <details class="group border-b border-slate-100">
                    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 py-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">
                        <span><span class="block text-lg font-bold tracking-tight text-slate-900">About</span><span class="mt-1 block text-sm leading-5 text-slate-500">How it works and more</span></span>
                        <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="space-y-1 pb-4 pl-3">
                        <a href="<?php echo BASE_URL; ?>/how-it-works.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">How it works</a>
                        <a href="<?php echo BASE_URL; ?>/terms.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Terms &amp; conditions</a>
                        <a href="<?php echo BASE_URL; ?>/privacy.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Privacy policy</a>
                        <a href="<?php echo BASE_URL; ?>/cookies.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Cookie policy</a>
                    </div>
                </details>

                <details class="group">
                    <summary class="flex cursor-pointer list-none items-start justify-between gap-4 py-5 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600">
                        <span><span class="block text-lg font-bold tracking-tight text-slate-900">Help Center</span><span class="mt-1 block text-sm leading-5 text-slate-500">Technical support and help</span></span>
                        <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </summary>
                    <div class="space-y-1 pb-4 pl-3">
                        <a href="<?php echo BASE_URL; ?>/faq.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Common questions</a>
                        <a href="<?php echo BASE_URL; ?>/contact.php" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-800">Contact support</a>
                    </div>
                </details>
            </div>
        </nav>

        <div class="mt-auto shrink-0 space-y-3 border-t border-slate-200 bg-white px-6 py-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
            <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="flex min-h-14 w-full items-center justify-center rounded-full bg-emerald-700 px-5 py-3 text-base font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">Start a fundraiser</a>
            <a href="<?php echo BASE_URL; ?>/login.php" class="flex min-h-14 w-full items-center justify-center rounded-full border-2 border-slate-200 bg-white px-5 py-3 text-base font-bold text-slate-900 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">Sign in</a>
        </div>
    </section>
</div>

<script>
(function () {
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (!mobileMenuButton || !mobileMenu) return;

    const closeButtons = mobileMenu.querySelectorAll('[data-close-mobile-menu]');
    const closeMenu = function () {
        if (mobileMenu.classList.contains('hidden')) return;
        mobileMenu.classList.add('hidden');
        mobileMenu.setAttribute('aria-hidden', 'true');
        mobileMenuButton.setAttribute('aria-expanded', 'false');
        mobileMenuButton.setAttribute('aria-label', 'Open main menu');
        document.body.classList.remove('overflow-hidden');
        mobileMenuButton.focus();
    };

    mobileMenuButton.addEventListener('click', function () {
        mobileMenu.classList.remove('hidden');
        mobileMenu.setAttribute('aria-hidden', 'false');
        mobileMenuButton.setAttribute('aria-expanded', 'true');
        mobileMenuButton.setAttribute('aria-label', 'Close main menu');
        document.body.classList.add('overflow-hidden');
        const closeButton = mobileMenu.querySelector('[role="dialog"] button[aria-label="Close main menu"]');
        if (closeButton) closeButton.focus();
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeMenu);
    });

    mobileMenu.querySelectorAll('nav a').forEach(function (link) {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', function (event) {
        if (mobileMenu.classList.contains('hidden')) return;

        if (event.key === 'Escape') {
            closeMenu();
            return;
        }

        if (event.key === 'Tab') {
            const dialog = mobileMenu.querySelector('[role="dialog"]');
            const focusableElements = dialog.querySelectorAll('button:not([disabled]), a[href], summary');
            const visibleElements = Array.from(focusableElements).filter(function (element) {
                return element.getClientRects().length > 0;
            });
            const firstElement = visibleElements[0];
            const lastElement = visibleElements[visibleElements.length - 1];

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
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
