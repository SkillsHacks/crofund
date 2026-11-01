<?php
session_start();
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Learn what information CroFunding collects, how it is used, and your choices.">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>Privacy Policy - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-slate-900 antialiased">
    <?php include __DIR__ . '/components/public-header.php'; ?>
    <main>
        <section class="bg-[#f7f6f3] px-4 pb-8 pt-24 sm:px-6 sm:pb-10 sm:pt-28 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Legal</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Privacy Policy</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">How information is collected, used, shared, and managed when you use CroFunding.</p>
                <nav class="mt-6 flex gap-2 overflow-x-auto pb-1 text-sm font-semibold" aria-label="Policy pages">
                    <a href="<?php echo BASE_URL; ?>/terms.php" class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-slate-700 transition hover:border-emerald-300 hover:text-emerald-800">Terms</a>
                    <a href="<?php echo BASE_URL; ?>/privacy.php" aria-current="page" class="shrink-0 rounded-full bg-emerald-700 px-4 py-2 text-white">Privacy</a>
                    <a href="<?php echo BASE_URL; ?>/cookies.php" class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-slate-700 transition hover:border-emerald-300 hover:text-emerald-800">Cookies</a>
                </nav>
            </div>
        </section>

        <section class="px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-10">
                <nav class="h-fit rounded-2xl border border-slate-200 bg-white p-4 lg:sticky lg:top-24" aria-label="On this page">
                    <p class="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">On this page</p>
                    <ol class="mt-3 space-y-1 text-sm">
                        <li><a href="#collection" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Data we collect</a></li>
                        <li><a href="#use" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">How we use your data</a></li>
                        <li><a href="#sharing" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Data sharing</a></li>
                        <li><a href="#rights" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Your rights</a></li>
                    </ol>
                </nav>

                <article class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white px-5 shadow-sm sm:px-8">
                    <section id="collection" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Data We Collect</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">We collect the following information:</p>
                        <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            <li>Name and contact information</li>
                            <li>Payment information</li>
                            <li>Device and browser information</li>
                            <li>Usage data and interactions</li>
                            <li>Communications with our platform</li>
                        </ul>
                    </section>
                    <section id="use" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">How We Use Your Data</h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            <li>Process donations and withdrawals</li>
                            <li>Verify identity and prevent fraud</li>
                            <li>Improve our services</li>
                            <li>Send important updates</li>
                            <li>Comply with legal obligations</li>
                        </ul>
                    </section>
                    <section id="sharing" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Data Sharing</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">We share data with:</p>
                        <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            <li>Payment processors</li>
                            <li>Identity verification services</li>
                            <li>Legal authorities when required</li>
                        </ul>
                    </section>
                    <section id="rights" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">Your Rights</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">You have the right to:</p>
                        <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            <li>Access your personal data</li>
                            <li>Request data correction</li>
                            <li>Request data deletion</li>
                            <li>Withdraw consent</li>
                        </ul>
                    </section>
                </article>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
