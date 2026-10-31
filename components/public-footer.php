<?php
?>
<footer class="bg-white">
    <section class="bg-emerald-950 px-4 py-10 text-white sm:px-6 sm:py-14 lg:px-8" aria-labelledby="footer-community-title">
        <div class="mx-auto grid max-w-7xl items-center gap-8 sm:gap-10 md:grid-cols-[1.1fr,0.9fr]">
            <div class="max-w-xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-300">A little help goes a long way</p>
                <h2 id="footer-community-title" class="mt-3 text-2xl font-bold leading-tight tracking-tight sm:text-3xl">
                    Fundraising is about people coming together.
                </h2>
                <p class="mt-3 max-w-lg text-sm leading-6 text-emerald-100 sm:text-base">
                    Share your cause, invite your community, and keep supporters updated along the way.
                </p>
                <a href="<?php echo BASE_URL; ?>/register.php" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-white/30">
                    Start a fundraiser
                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>

            <div class="relative mx-auto flex h-48 w-full max-w-md items-center justify-center overflow-hidden rounded-3xl bg-emerald-900 sm:h-56" aria-hidden="true">
                <div class="absolute -left-8 -top-12 h-40 w-40 rounded-full bg-emerald-800"></div>
                <div class="absolute -bottom-16 -right-8 h-48 w-48 rounded-full bg-emerald-800"></div>
                <div class="relative flex items-end gap-3 sm:gap-5">
                    <div class="flex flex-col items-center">
                        <span class="h-10 w-10 rounded-full bg-emerald-200 sm:h-12 sm:w-12"></span>
                        <span class="mt-2 h-16 w-16 rounded-t-[2rem] bg-emerald-300 sm:h-20 sm:w-20"></span>
                    </div>
                    <div class="z-10 -mb-1 flex h-20 w-20 items-center justify-center rounded-full bg-white text-emerald-700 shadow-lg sm:h-24 sm:w-24">
                        <svg class="h-10 w-10 sm:h-12 sm:w-12" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/>
                        </svg>
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="h-10 w-10 rounded-full bg-lime-200 sm:h-12 sm:w-12"></span>
                        <span class="mt-2 h-16 w-16 rounded-t-[2rem] bg-lime-300 sm:h-20 sm:w-20"></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="border-t border-slate-200 px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid grid-cols-2 gap-x-6 gap-y-9 sm:grid-cols-4 sm:gap-8">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-900">Donate</h3>
                    <ul class="mt-4 space-y-3">
                        <li><a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Browse fundraisers</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/campaigns/v/" class="text-sm text-slate-600 transition hover:text-emerald-700">Verified campaigns</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/campaigns/browse.php?category=medical" class="text-sm text-slate-600 transition hover:text-emerald-700">Medical</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/campaigns/browse.php?category=emergency" class="text-sm text-slate-600 transition hover:text-emerald-700">Emergency</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-900">Fundraise</h3>
                    <ul class="mt-4 space-y-3">
                        <li><a href="<?php echo BASE_URL; ?>/register.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Start a fundraiser</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/how-it-works.php" class="text-sm text-slate-600 transition hover:text-emerald-700">How it works</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/success-stories.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Success stories</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-900">About</h3>
                    <ul class="mt-4 space-y-3">
                        <li><a href="<?php echo BASE_URL; ?>/faq.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Common questions</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/contact.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Contact us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/login.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Sign in</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-900">Resources</h3>
                    <ul class="mt-4 space-y-3">
                        <li><a href="<?php echo BASE_URL; ?>/terms.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Terms &amp; conditions</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/privacy.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Privacy policy</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/cookies.php" class="text-sm text-slate-600 transition hover:text-emerald-700">Cookie policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 flex flex-col gap-6 border-t border-slate-200 pt-6 sm:mt-12 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo BASE_URL; ?>" class="inline-flex items-center gap-2 self-start">
                    <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="" class="h-8 w-8">
                    <span class="text-base font-bold tracking-tight text-slate-900">CroFunding</span>
                </a>

                <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500">
                    <a href="<?php echo BASE_URL; ?>/terms.php" class="transition hover:text-emerald-700">Terms</a>
                    <a href="<?php echo BASE_URL; ?>/privacy.php" class="transition hover:text-emerald-700">Privacy</a>
                    <a href="<?php echo BASE_URL; ?>/cookies.php" class="transition hover:text-emerald-700">Cookies</a>
                    <span>&copy; <?php echo date('Y'); ?> CroFunding</span>
                </div>

                <nav class="flex flex-wrap gap-x-4 gap-y-2 text-xs font-medium text-slate-500" aria-label="Social media">
                    <a href="https://x.com/CroFunding1?t=T1lGpFRl_OiDVJ5wAnzONQ&amp;s=09" target="_blank" rel="noopener noreferrer" class="transition hover:text-emerald-700">X</a>
                    <a href="https://www.instagram.com/crofunding?igsh=eWF3OTR3cmNhaHI=" target="_blank" rel="noopener noreferrer" class="transition hover:text-emerald-700">Instagram</a>
                    <a href="https://www.facebook.com/profile.php?id=61577868655483" target="_blank" rel="noopener noreferrer" class="transition hover:text-emerald-700">Facebook</a>
                    <a href="https://www.tiktok.com/@crofunding?_t=ZM-8xc9rW6a3CI&amp;_r=1" target="_blank" rel="noopener noreferrer" class="transition hover:text-emerald-700">TikTok</a>
                </nav>
            </div>
        </div>
    </div>
</footer>
