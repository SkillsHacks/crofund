<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Learn how to create a CroFunding fundraiser, share it, and receive donations.">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>How It Works - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
<?php include __DIR__ . '/components/public-header.php'; ?>
<main>
    <section class="bg-[#f7f6f3] px-4 pb-10 pt-24 sm:px-6 sm:pb-14 sm:pt-28 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">A simple place to start</p>
                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">How fundraising works</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">Create your fundraiser, share your story, keep supporters updated, and set up how you receive funds.</p>
                <a href="<?php echo BASE_URL; ?>/register.php" class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2">Start a fundraiser</a>
            </div>
        </div>
    </section>

    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8" aria-labelledby="steps-heading">
        <div class="mx-auto max-w-6xl">
            <div class="mb-7 max-w-2xl sm:mb-9">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">The process</p>
                <h2 id="steps-heading" class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Four steps from story to support</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-800" aria-hidden="true">01</span>
                    <h3 class="mt-4 text-base font-bold text-slate-900">Create your fundraiser</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Add a photo or video, explain who you’re raising funds for, and describe how the money will be used. Choose a goal that works for you; you can update it later.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-800" aria-hidden="true">02</span>
                    <h3 class="mt-4 text-base font-bold text-slate-900">Share your story</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Send your fundraiser link to friends and family, share it on social media, and invite people you trust to help spread the word.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-800" aria-hidden="true">03</span>
                    <h3 class="mt-4 text-base font-bold text-slate-900">Keep supporters updated</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Post updates as your fundraiser progresses and thank the people who contribute.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-800" aria-hidden="true">04</span>
                    <h3 class="mt-4 text-base font-bold text-slate-900">Set up fund transfers</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Add your bank information to receive funds; you don’t need to reach your goal first. If you’re raising for someone else, you can add a beneficiary. A transaction fee applies to each donation; see our <a href="<?php echo BASE_URL; ?>/terms.php" class="font-semibold text-emerald-800 underline decoration-emerald-300 underline-offset-2 hover:text-emerald-900">Terms &amp; Conditions</a>.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="px-4 pb-12 sm:px-6 sm:pb-16 lg:px-8">
        <div class="mx-auto flex max-w-6xl flex-col gap-5 rounded-2xl bg-emerald-950 px-6 py-8 text-white sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:py-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">Need more information?</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight">We’re here to help you get started.</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-emerald-100">Browse common questions or contact the CroFunding team.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="<?php echo BASE_URL; ?>/faq.php" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">Common questions</a>
                <a href="<?php echo BASE_URL; ?>/contact.php" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">Contact us</a>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
