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
    <meta name="description" content="Read the terms and conditions for using CroFunding.">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>Terms &amp; Conditions - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-slate-900 antialiased">
    <?php include __DIR__ . '/components/public-header.php'; ?>
    <main>
        <section class="bg-[#f7f6f3] px-4 pb-8 pt-24 sm:px-6 sm:pb-10 sm:pt-28 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Legal</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Terms &amp; Conditions</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">The terms for using CroFunding, creating fundraisers, and making donations.</p>
                <nav class="mt-6 flex gap-2 overflow-x-auto pb-1 text-sm font-semibold" aria-label="Policy pages">
                    <a href="<?php echo BASE_URL; ?>/terms.php" aria-current="page" class="shrink-0 rounded-full bg-emerald-700 px-4 py-2 text-white">Terms</a>
                    <a href="<?php echo BASE_URL; ?>/privacy.php" class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-slate-700 transition hover:border-emerald-300 hover:text-emerald-800">Privacy</a>
                    <a href="<?php echo BASE_URL; ?>/cookies.php" class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-2 text-slate-700 transition hover:border-emerald-300 hover:text-emerald-800">Cookies</a>
                </nav>
            </div>
        </section>

        <section class="px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <div class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-10">
                <nav class="h-fit rounded-2xl border border-slate-200 bg-white p-4 lg:sticky lg:top-24" aria-label="On this page">
                    <p class="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">On this page</p>
                    <ol class="mt-3 space-y-1 text-sm">
                        <li><a href="#acceptance" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Acceptance of terms</a></li>
                        <li><a href="#campaigns" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Campaigns</a></li>
                        <li><a href="#donations" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Donations</a></li>
                        <li><a href="#prohibited" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Prohibited activities</a></li>
                        <li><a href="#termination" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Account termination</a></li>
                        <li><a href="#liability" class="block rounded-lg px-2 py-2 text-slate-600 hover:bg-emerald-50 hover:text-emerald-800">Limitation of liability</a></li>
                    </ol>
                </nav>

                <article class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white px-5 shadow-sm sm:px-8">
                    <section id="acceptance" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">1. Acceptance of Terms</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">By accessing and using CroFunding, you accept and agree to be bound by the terms and conditions outlined here.</p>
                    </section>
                    <section id="campaigns" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">2. Campaign Creation and Management</h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            <li>Users must provide accurate and truthful information.</li>
                            <li>Campaign creators are responsible for fulfilling their stated goals.</li>
                            <li>CroFunding reserves the right to remove campaigns that violate our policies.</li>
                        </ul>
                    </section>
                    <section id="donations" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">3. Donations</h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            <li>All donations are final and non-refundable.</li>
                            <li>A platform fee of 2.9% + $0.30 applies to each donation.</li>
                            <li>Donors must be at least 18 years old.</li>
                        </ul>
                    </section>
                    <section id="prohibited" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">4. Prohibited Activities</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">Users may not:</p>
                        <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            <li>Create fraudulent campaigns.</li>
                            <li>Harass other users.</li>
                            <li>Violate any applicable laws.</li>
                            <li>Share false or misleading information.</li>
                        </ul>
                    </section>
                    <section id="termination" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">5. Account Termination</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">CroFunding reserves the right to terminate or suspend accounts for violations of these terms.</p>
                    </section>
                    <section id="liability" class="scroll-mt-28 py-6 sm:py-8">
                        <h2 class="text-lg font-bold tracking-tight text-slate-900">6. Limitation of Liability</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">CroFunding is not liable for any damages arising from the use of our platform.</p>
                    </section>
                </article>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
