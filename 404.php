<?php
http_response_code(404);
?>
<div class="w-full px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl rounded-3xl border border-red-200 bg-white p-8 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-red-600">404</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Page not found</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
                    The dashboard section you requested is not available. Please use one of the approved navigation links.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        Back to dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/campaigns/create/" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700">
                        Create campaign
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
