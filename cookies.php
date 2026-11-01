<?php
session_start();
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Learn how cookies are used on CroFunding and how to manage them.">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>Cookie Policy - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-slate-900 antialiased">
    <?php include __DIR__ . '/components/public-header.php'; ?>
    <main>
        <section class="bg-[#f7f6f3] px-4 pb-8 pt-24 sm:px-6 sm:pb-10 sm:pt-28 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Legal</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Cookie Policy</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">Learn about the cookies used on CroFunding and the controls available in your browser.</p>
                <nav class="mt-6 flex gap-2 overflow-x-auto pb-1 text-sm font-semibold" aria-label="Policy pages">
                    <a href="<?php echo BASE_URL; ?>/terms.php" class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-slate-700 transition hover:border-emerald-300 hover:text-emerald-800">Terms</a>
                    <a href="<?php echo BASE_URL; ?>/privacy.php" class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-slate-700 transition hover:border-emerald-300 hover:text-emerald-800">Privacy</a>
                    <a href="<?php echo BASE_URL; ?>/cookies.php" aria-current="page" class="shrink-0 rounded-full bg-emerald-700 px-4 py-2 text-white">Cookies</a>
                </nav>
            </div>
        </section>

        <section class="px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-10">
                <nav class="h-fit rounded-2xl border border-slate-200 bg-white p-4 lg:sticky lg:top-24" aria-label="On this page">
                    <p class="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">On this page</p>
                    <ol class="mt-3 space-y-1 text-sm">
                        <li><a href="#definition" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">What are cookies</a></li>
                        <li><a href="#types" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Types of cookies</a></li>
                        <li><a href="#management" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Cookie management</a></li>
                        <li><a href="#third-party" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Third-party cookies</a></li>
                    </ol>
                </nav>

                <article class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white px-5 shadow-sm sm:px-8">
                    <section id="definition" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">What Are Cookies</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Cookies are small text files stored on your device that help us improve your experience.</p>
                    </section>
                    <section id="types" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Types of Cookies We Use</h2>
                        <ul class="mt-3 space-y-3 text-sm leading-7 text-slate-600">
                            <li><strong class="text-slate-900">Essential Cookies:</strong> Required for basic site functionality.</li>
                            <li><strong class="text-slate-900">Functional Cookies:</strong> Remember your preferences.</li>
                            <li><strong class="text-slate-900">Analytics Cookies:</strong> Help us understand site usage.</li>
                            <li><strong class="text-slate-900">Marketing Cookies:</strong> Track effectiveness of campaigns.</li>
                        </ul>
                    </section>
                    <section id="management" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Cookie Management</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">You can manage cookies through your browser settings. Note that disabling certain cookies may affect site functionality.</p>
                    </section>
                    <section id="third-party" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Third-Party Cookies</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">We use services from:</p>
                        <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            <li>Google Analytics</li>
                            <li>Payment processors</li>
                            <li>Social media platforms</li>
                        </ul>
                    </section>
                </article>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
