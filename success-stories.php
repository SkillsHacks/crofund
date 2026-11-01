<?php
require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Read stories of people and communities brought together through CroFunding.">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>Success Stories - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
</head>
<body class="bg-white font-sans text-slate-900 antialiased">
<?php include __DIR__ . '/components/public-header.php'; ?>
<main>
    <section class="bg-[#f7f6f3] px-4 pb-10 pt-24 sm:px-6 sm:pb-14 sm:pt-28 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Community stories</p>
                <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">Small acts of support. Lasting impact.</h1>
                <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">Meet people and communities whose goals moved forward with help from others.</p>
            </div>
        </div>
    </section>

    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8" aria-labelledby="stories-heading">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 flex flex-col gap-2 sm:mb-8 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Real experiences</p>
                    <h2 id="stories-heading" class="mt-1.5 text-2xl font-bold tracking-tight text-slate-950">Stories from the community</h2>
                </div>
                <p class="max-w-md text-sm leading-6 text-slate-500">Every fundraiser starts with a need and grows when people choose to help.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2 md:gap-6">
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex h-28 items-end justify-between bg-rose-50 px-5 pb-4 sm:h-32 sm:px-6" aria-hidden="true">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-800">Health &amp; wellbeing</span>
                        <svg class="h-12 w-12 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                        </svg>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Medical support</p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-950">Jane's Medical Fund</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Raised <span class="font-bold text-slate-900">$10,000</span> for surgery thanks to community support.</p>
                        <blockquote class="mt-5 border-l-2 border-emerald-300 pl-4 text-sm leading-6 text-slate-600">
                            “CroFunding gave me hope when I needed it most. The kindness of strangers changed my life.”
                            <footer class="mt-2 text-xs font-semibold text-slate-500">— Jane</footer>
                        </blockquote>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex h-28 items-end justify-between bg-sky-50 px-5 pb-4 sm:h-32 sm:px-6" aria-hidden="true">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-800">Learning &amp; opportunity</span>
                        <svg class="h-12 w-12 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 7v14m0-14C10.7 5.7 8.9 5 7 5s-3.7.7-5 2v14c1.3-1.3 3.1-2 5-2s3.7.7 5 2m0-14c1.3-1.3 3.1-2 5-2s3.7.7 5 2v14c-1.3-1.3-3.1-2-5-2s-3.7.7-5 2"/>
                        </svg>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Education</p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-950">School Supplies Drive</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Provided <span class="font-bold text-slate-900">200+ students</span> with essential materials.</p>
                        <blockquote class="mt-5 border-l-2 border-emerald-300 pl-4 text-sm leading-6 text-slate-600">
                            “We couldn't have done it without CroFunding. Our students are ready for success!”
                            <footer class="mt-2 text-xs font-semibold text-slate-500">— Mr. Adebayo</footer>
                        </blockquote>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex h-28 items-end justify-between bg-amber-50 px-5 pb-4 sm:h-32 sm:px-6" aria-hidden="true">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-amber-800">Stronger together</span>
                        <svg class="h-12 w-12 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m16-13a4 4 0 11-8 0 4 4 0 018 0zm2 13v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Community</p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-950">Community Center Renovation</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Revitalized a local space for everyone to enjoy.</p>
                        <blockquote class="mt-5 border-l-2 border-emerald-300 pl-4 text-sm leading-6 text-slate-600">
                            “Now our community has a safe, beautiful place to gather. Thank you, CroFunding!”
                            <footer class="mt-2 text-xs font-semibold text-slate-500">— Community Leader</footer>
                        </blockquote>
                    </div>
                </article>

                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex h-28 items-end justify-between bg-cyan-50 px-5 pb-4 sm:h-32 sm:px-6" aria-hidden="true">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-800">Water &amp; essentials</span>
                        <svg class="h-12 w-12 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 22a8 8 0 008-8c0-4-8-12-8-12S4 10 4 14a8 8 0 008 8z"/>
                        </svg>
                    </div>
                    <div class="p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Clean water</p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-950">NGO Water Project</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Brought clean water to a rural village.</p>
                        <blockquote class="mt-5 border-l-2 border-emerald-300 pl-4 text-sm leading-6 text-slate-600">
                            “With CroFunding, we made a lasting difference for hundreds of families.”
                            <footer class="mt-2 text-xs font-semibold text-slate-500">— Water4All Team</footer>
                        </blockquote>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="px-4 pb-12 sm:px-6 sm:pb-16 lg:px-8">
        <div class="mx-auto flex max-w-6xl flex-col gap-5 rounded-2xl bg-emerald-950 px-6 py-8 text-white sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:py-10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">Your story starts here</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight">Ready to bring people together?</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-emerald-100">Create a fundraiser and share what matters to you.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/register.php" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-emerald-900 transition hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-emerald-950">Start a fundraiser</a>
        </div>
    </section>
</main>
<?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
