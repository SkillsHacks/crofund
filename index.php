<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    
    <meta name="description" content="Start a fundraiser or support campaigns for medical, emergency, education, and community needs with CroFunding.">
    <meta name="keywords" content="fundraising, crowdfunding, donations, medical fundraising, emergency fund, community help">
    <meta name="author" content="CroFunding">
    
    <meta property="og:title" content="CroFunding - Fundraising for Everyone">
    <meta property="og:description" content="Start your fundraiser today or support people raising funds for the causes that matter.">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    
    <title>CroFunding - Fundraising for what matters</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        slate: {
                            850: '#1e293b',
                        }
                    },
                    boxShadow: {
                        'soft': '0 1px 3px 0 rgb(0 0 0 / 0.05), 0 1px 2px -1px rgb(0 0 0 / 0.05)',
                        'medium': '0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.05)',
                        'large': '0 10px 15px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.05)',
                    }
                }
            }
        }
    </script>

    <style>
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        [x-cloak] { display: none; }
        .progress-bar {
            transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="bg-white text-slate-900 antialiased">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <main>
        <section class="bg-[#f7f6f3] px-4 pb-12 pt-24 sm:px-6 sm:pb-16 sm:pt-28 lg:px-8 lg:pb-20">
            <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-[1.05fr,0.95fr] lg:gap-16">
                <div class="mx-auto max-w-2xl text-center lg:mx-0 lg:text-left">
                    <span class="inline-flex items-center rounded-full border border-emerald-100 bg-white px-3.5 py-1.5 text-xs font-semibold text-emerald-800">
                        Fundraising for what matters
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                        Bring your community together.
                    </h1>
                    <p class="mx-auto mt-4 max-w-xl text-base leading-7 text-slate-600 sm:text-lg lg:mx-0">
                        Tell your story, set a goal, and invite people to support the things that matter most.
                    </p>
                    <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                        <a href="<?php echo BASE_URL; ?>/register.php" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-emerald-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                            Start a fundraiser
                            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                        <a href="#campaigns" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-300/50">
                            Explore fundraisers
                        </a>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-xl">
                    <div class="absolute -left-3 top-8 h-24 w-24 rounded-full bg-emerald-100 sm:-left-6 sm:h-32 sm:w-32" aria-hidden="true"></div>
                    <div class="absolute -right-2 bottom-6 h-28 w-28 rounded-full bg-[#e8e1f3] sm:-right-5 sm:h-36 sm:w-36" aria-hidden="true"></div>
                    <div class="relative rounded-[1.75rem] border border-[#e7e5e1] bg-white p-5 shadow-sm sm:p-7">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">A simple place to begin</p>
                                <h2 class="mt-2 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Your story can start here.</h2>
                            </div>
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/>
                                </svg>
                            </span>
                        </div>
                        <div class="mt-6 space-y-3">
                            <div class="flex items-center gap-3 rounded-xl bg-[#f7f6f3] px-4 py-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-sm font-bold text-emerald-700">1</span>
                                <span class="text-sm font-semibold text-slate-700">Share what matters</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-xl bg-[#f7f6f3] px-4 py-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-sm font-bold text-emerald-700">2</span>
                                <span class="text-sm font-semibold text-slate-700">Set a goal</span>
                            </div>
                            <div class="flex items-center gap-3 rounded-xl bg-[#f7f6f3] px-4 py-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-sm font-bold text-emerald-700">3</span>
                                <span class="text-sm font-semibold text-slate-700">Invite support</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- Stats Section -->
    <section class="bg-[#f7f6f3] px-4 py-10 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 text-center">
                <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Fundraising in motion</h2>
            </div>
            <div class="grid grid-cols-3 gap-3 sm:gap-5">
                <?php
                try {
                    $db = Database::getInstance();
                    $totalRaised = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status = 'completed'")['total'];
                    $totalCampaigns = $db->fetchOne("SELECT COUNT(*) as count FROM campaigns WHERE status = 'active'")['count'];
                    $totalCompletedDonations = $db->fetchOne("SELECT COUNT(*) as count FROM donations WHERE status = 'completed'")['count'];
                } catch (Exception $e) {
                    $totalRaised = 0;
                    $totalCampaigns = 0;
                    $totalCompletedDonations = 0;
                }
                ?>
                <div class="rounded-2xl border border-[#e7e5e1] bg-white px-2 py-4 text-center sm:px-5 sm:py-6">
                    <div class="text-xl font-extrabold tracking-tight text-emerald-700 sm:text-4xl">$<?php echo number_format($totalRaised); ?></div>
                    <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">Total raised</div>
                </div>
                <div class="rounded-2xl border border-[#e7e5e1] bg-white px-2 py-4 text-center sm:px-5 sm:py-6">
                    <div class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"><?php echo number_format($totalCampaigns); ?></div>
                    <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">Active campaigns</div>
                </div>
                <div class="rounded-2xl border border-[#e7e5e1] bg-white px-2 py-4 text-center sm:px-5 sm:py-6">
                    <div class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"><?php echo number_format($totalCompletedDonations); ?></div>
                    <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">Completed donations</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Campaigns Section -->
    <section id="campaigns" class="scroll-mt-20 bg-white px-4 py-14 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8 text-center sm:mb-10">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Find a cause</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Fundraisers to support</h2>
                <p class="mt-2 text-sm text-slate-600 sm:text-base">Browse current campaigns or search by name and category.</p>
            </div>

            <div class="mx-auto mb-8 max-w-2xl sm:mb-10">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="campaign-search" placeholder="Search fundraisers..."
                           class="block min-h-12 w-full rounded-xl border border-slate-200 bg-[#f7f6f3] py-3 pl-11 pr-12 text-sm text-slate-700 placeholder-slate-400 transition focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-600/10 sm:text-base">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                        <button type="button" id="clear-search" class="hidden text-slate-400 transition-colors hover:text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div id="campaigns-container" class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-4 md:gap-x-5 md:gap-y-6"></div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-[#f7f6f3] px-4 py-14 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
        <div class="mx-auto max-w-6xl">
            <div class="mb-8 text-center sm:mb-10">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Getting started</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">A few steps to get going</h2>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3 md:gap-5">
                <div class="rounded-2xl border border-[#e7e5e1] bg-white p-5 sm:p-6">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-700">1</div>
                    <h3 class="text-base font-semibold text-slate-900">Tell your story</h3>
                    <p class="mt-1.5 text-sm leading-6 text-slate-600">Explain what you’re raising funds for.</p>
                </div>
                <div class="rounded-2xl border border-[#e7e5e1] bg-white p-5 sm:p-6">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-700">2</div>
                    <h3 class="text-base font-semibold text-slate-900">Set a goal</h3>
                    <p class="mt-1.5 text-sm leading-6 text-slate-600">Choose a target for your fundraiser.</p>
                </div>
                <div class="rounded-2xl border border-[#e7e5e1] bg-white p-5 sm:p-6">
                    <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-sm font-bold text-emerald-700">3</div>
                    <h3 class="text-base font-semibold text-slate-900">Share and update</h3>
                    <p class="mt-1.5 text-sm leading-6 text-slate-600">Invite supporters and keep them posted.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="bg-white px-4 py-14 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 text-center sm:mb-10">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Many reasons to come together</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">What are you fundraising for?</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                <?php
                $categories = [
                    'Medical', 'Emergency', 'Education', 'Community',
                    'Business', 'Nonprofit', 'Memorial', 'Animals'
                ];
                foreach ($categories as $category): ?>
                    <a href="category/<?php echo strtolower($category); ?>" 
                       class="group rounded-2xl border border-[#e7e5e1] bg-[#f7f6f3] p-4 transition hover:border-emerald-200 hover:bg-emerald-50 sm:p-6">
                        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-white text-emerald-700 transition group-hover:bg-emerald-100 sm:mb-4 sm:h-12 sm:w-12">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-center text-sm font-semibold text-slate-900 sm:text-base"><?php echo $category; ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    </main>

    <script>
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>

    <!-- Search Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('campaign-search');
            const clearSearchBtn = document.getElementById('clear-search');
            const campaignsContainer = document.getElementById('campaigns-container');
            
            let currentSearchTerm = '';
            let searchTimeout;
            let isLoading = false;

            async function searchCampaigns(searchTerm = '', page = 1) {
                if (isLoading) return;
                
                try {
                    isLoading = true;
                    showLoadingState();
                    
                    const params = new URLSearchParams({ q: searchTerm, page: page, limit: 9 });
                    const response = await fetch(`${window.BASE_URL}/api/search_campaigns.php?${params}`);
                    
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    
                    const data = await response.json();
                    if (data.success) displaySearchResults(data.campaigns, data.pagination, data.search);
                    else throw new Error(data.error || 'Search failed');
                } catch (error) {
                    console.error('Search error:', error);
                    showErrorMessage('Search failed. Please try again.');
                } finally {
                    isLoading = false;
                    hideLoadingState();
                }
            }

            function displaySearchResults(campaigns, pagination, searchInfo) {
                campaignsContainer.innerHTML = '';
                
                if (campaigns.length === 0) {
                    campaignsContainer.innerHTML = `
                        <div class="col-span-full rounded-[1.75rem] border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
                            <svg class="mx-auto mb-4 h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-slate-900 mb-2">No campaigns found</h3>
                            <p class="text-slate-600">${searchInfo.term ? 'No campaigns match "' + searchInfo.term + '".' : 'There are currently no active campaigns.'}</p>
                        </div>
                    `;
                    return;
                }
                
                campaigns.forEach((campaign, index) => {
                    campaignsContainer.appendChild(createCampaignCard(campaign, index === 0));
                });
            }

            function createCampaignCard(campaign, featured) {
                const div = document.createElement('div');
                div.className = featured
                    ? 'group col-span-2 min-w-0 md:row-span-2'
                    : 'group min-w-0';
                
                const imageUrl = campaign.image_path 
                    ? `${window.BASE_URL}/${campaign.image_path.replace(/^\//, '')}`
                    : `${window.BASE_URL}/assets/images/default-campaign.jpg`;
                const donorCount = new Intl.NumberFormat('en', {
                    notation: 'compact',
                    maximumFractionDigits: 1
                }).format(Number(campaign.donor_count) || 0);
                const percentage = Math.max(0, Math.min(Number(campaign.percentage) || 0, 100));
                const imageClass = featured
                    ? 'aspect-[1.55/1] md:aspect-[1.4/1]'
                    : 'aspect-[1.55/1]';
                const titleClass = featured
                    ? 'text-base font-semibold sm:text-lg'
                    : 'text-xs font-semibold sm:text-sm';

                div.innerHTML = `
                    <a href="${window.BASE_URL}/campaigns/public-view.php?id=${encodeURIComponent(campaign.id)}" class="block h-full min-w-0">
                        <div class="relative ${imageClass} overflow-hidden rounded-lg bg-[#f7f6f3]">
                            <img src="${imageUrl}" alt="${campaign.title}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='${window.BASE_URL}/assets/images/default-campaign.jpg'">
                            <span class="absolute bottom-2 left-2 rounded bg-slate-950/75 px-2 py-1 text-[10px] font-semibold text-white sm:text-xs">
                                ${donorCount} donations
                            </span>
                        </div>
                        <div class="${featured ? 'pt-2.5' : 'pt-2'}">
                            <h3 class="line-clamp-2 ${titleClass} leading-snug tracking-tight text-slate-900 transition-colors group-hover:text-emerald-800">
                                ${campaign.title}
                            </h3>
                            <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Fundraising progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${percentage}">
                                <div class="h-full rounded-full bg-gradient-to-r from-lime-400 to-emerald-600" style="width: ${percentage}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs font-medium text-slate-700 sm:text-sm">
                                $${Number(campaign.current_amount).toLocaleString()} raised
                            </p>
                        </div>
                    </a>
                `;
                return div;
            }

            function showLoadingState() {
                campaignsContainer.innerHTML = `
                    <div class="col-span-full py-16 text-center">
                        <div class="inline-flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 shadow-sm">
                            <svg class="h-5 w-5 animate-spin text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading campaigns...
                        </div>
                    </div>
                `;
            }

            function hideLoadingState() {}

            function showErrorMessage(message) {
                campaignsContainer.innerHTML = `
                    <div class="col-span-full rounded-[1.75rem] border border-red-200 bg-red-50 px-6 py-16 text-center shadow-sm">
                        <svg class="mx-auto mb-4 h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Search Error</h3>
                        <p class="text-slate-600">${message}</p>
                    </div>
                `;
            }

            searchInput.addEventListener('input', function() {
                currentSearchTerm = this.value.trim();
                clearSearchBtn.classList.toggle('hidden', currentSearchTerm.length === 0);
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => searchCampaigns(currentSearchTerm, 1), 300);
            });

            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                currentSearchTerm = '';
                this.classList.add('hidden');
                searchCampaigns('', 1);
            });

            searchCampaigns('', 1);
        });

    </script>

    <!-- WhatsApp Button -->
    <a href="https://wa.me/447878371924" target="_blank" rel="noopener noreferrer" 
       class="fixed bottom-6 right-6 bg-green-500 hover:bg-green-600 text-white p-4 rounded-xl shadow-large transition-all duration-200 hover:shadow-xl z-50">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004c-1.025 0-2.034.377-2.794 1.118-.76.74-1.18 1.726-1.18 2.76 0 1.06.424 2.063 1.192 2.821.747.748 1.76 1.18 2.799 1.18h.001c1.026 0 2.035-.377 2.795-1.118.76-.74 1.181-1.726 1.181-2.761 0-1.059-.424-2.063-1.193-2.822-.748-.748-1.76-1.18-2.799-1.18M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0m0 23.391c-6.338 0-11.391-5.053-11.391-11.391S5.662.609 12 .609s11.391 5.053 11.391 11.391-5.053 11.391-11.391 11.391"/>
        </svg>
    </a>
    <?php include __DIR__ . '/components/public-footer.php'; ?>
</body>
</html>
