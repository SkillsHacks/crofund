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
    
    <meta name="description" content="CroFunding - A secure platform for fundraising. Help others or start your own fundraiser for medical, emergency, education, or community causes.">
    <meta name="keywords" content="fundraising, crowdfunding, donations, medical fundraising, emergency fund, community help">
    <meta name="author" content="CroFunding">
    
    <meta property="og:title" content="CroFunding - Fundraising for Everyone">
    <meta property="og:description" content="Start your fundraiser today or help others in need. Secure, easy, and trusted platform for all types of causes.">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    
    <title>CroFunding - Trusted Fundraising Platform</title>
    
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
        .gradient-overlay {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.95), rgba(5, 150, 105, 0.98));
        }
        .progress-bar {
            transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hero-orb {
            position: absolute;
            border-radius: 9999px;
            filter: blur(60px);
            opacity: 0.45;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-white text-slate-900 antialiased">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-slate-50 pt-24 pb-20 lg:pt-32 lg:pb-28">
        <div class="hero-orb -top-16 left-[-4rem] h-64 w-64 bg-emerald-200"></div>
        <div class="hero-orb top-32 right-[-3rem] h-72 w-72 bg-sky-200"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="overflow-hidden rounded-[2rem] border border-white/70 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 px-6 py-10 text-white shadow-2xl sm:px-8 lg:px-12 lg:py-14">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.22),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.14),_transparent_30%)]"></div>
                <div class="relative grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                    <div class="max-w-3xl">
                        <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                            Raise support for the moments that matter most.
                        </h1>

                        <p class="mt-5 max-w-2xl text-base leading-8 text-slate-200 sm:text-lg">
                            CroFunding helps people launch trusted fundraisers for medical care, emergencies, education, and community needs with a cleaner, more secure giving experience.
                        </p>

                        <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <a href="<?php echo BASE_URL; ?>/register.php" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-emerald-900/20 transition hover:-translate-y-0.5 hover:bg-emerald-700">
                                Start a fundraiser
                                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                            <a href="#campaigns" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/10 px-8 py-4 text-base font-semibold text-white transition hover:bg-white/15">
                                Explore campaigns
                            </a>
                        </div>
                    </div>

                    <div class="grid min-w-0 grid-cols-1 gap-5 lg:grid-cols-1 xl:grid-cols-3">
                        <div class="min-w-0 rounded-2xl border border-white/10 bg-white/10 p-6 backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Secure giving</p>
                       
                            <p class="mt-2 text-sm leading-6 text-slate-300">Built for trust and transparency</p>
                        </div>
                        <div class="min-w-0 rounded-2xl border border-white/10 bg-white/10 p-6 backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Active causes</p>
                          
                            <p class="mt-2 text-sm leading-6 text-slate-300">Support urgent and community needs</p>
                        </div>
                        <div class="min-w-0 rounded-2xl border border-white/10 bg-white/10 p-6 backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">Fast setup</p>
                          
                            <p class="mt-2 text-sm leading-6 text-slate-300">Start and share quickly</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Indicators -->
    <section class="py-16 border-y border-slate-100 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.025 12.025 0 002 12c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Verified Platform</h3>
                    <p class="text-sm text-slate-500">Compliant & audited</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Bank-Level Security</h3>
                    <p class="text-sm text-slate-500">256-bit encryption</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">Transparent Fees</h3>
                    <p class="text-sm text-slate-500">No hidden costs</p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-1">24/7 Support</h3>
                    <p class="text-sm text-slate-500">Dedicated assistance</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-10 text-center">
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Built to earn trust at every step</h2>
                <p class="mt-3 text-slate-600">A quick look at the activity happening across the platform right now.</p>
            </div>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-3">
                <?php
                try {
                    $db = Database::getInstance();
                    $totalRaised = $db->fetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status = 'completed'")['total'];
                    $totalCampaigns = $db->fetchOne("SELECT COUNT(*) as count FROM campaigns WHERE status = 'active'")['count'];
                    $totalDonors = $db->fetchOne("SELECT COUNT(*) as count FROM donations WHERE status = 'completed'")['count'];
                } catch (Exception $e) {
                    $totalRaised = 0;
                    $totalCampaigns = 0;
                    $totalDonors = 0;
                }
                ?>
                <div class="bg-white rounded-2xl p-8 border border-slate-100 shadow-soft text-center">
                    <div class="text-4xl lg:text-5xl font-extrabold text-emerald-600 mb-2">$<?php echo number_format($totalRaised); ?></div>
                    <div class="text-sm font-medium text-slate-500 uppercase tracking-wide">Total Raised</div>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-slate-100 shadow-soft text-center">
                    <div class="text-4xl lg:text-5xl font-extrabold text-slate-800 mb-2"><?php echo number_format($totalCampaigns); ?></div>
                    <div class="text-sm font-medium text-slate-500 uppercase tracking-wide">Active Campaigns</div>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-slate-100 shadow-soft text-center">
                    <div class="text-4xl lg:text-5xl font-extrabold text-emerald-600 mb-2"><?php echo number_format($totalDonors); ?></div>
                    <div class="text-sm font-medium text-slate-500 uppercase tracking-wide">Total Donors</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Campaigns Section -->
    <section id="campaigns" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.24em] text-slate-600">Explore campaigns</span>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Discover active fundraisers</h2>
                <p class="mt-3 text-lg text-slate-600">Search by title, story, or category to find campaigns worth supporting.</p>
            </div>

            <div class="mx-auto mb-12 max-w-3xl rounded-[1.75rem] border border-slate-200/70 bg-white p-4 shadow-lg shadow-slate-200/60 sm:p-5">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="campaign-search" placeholder="Search campaigns by title, description, or category..."
                           class="block w-full rounded-2xl border border-slate-200 bg-slate-50 py-4 pl-12 pr-12 text-base text-slate-700 placeholder-slate-400 transition-all duration-200 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                        <button type="button" id="clear-search" class="hidden text-slate-400 transition-colors hover:text-slate-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div id="campaigns-container" class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3"></div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-slate-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-slate-900 mb-3">How It Works</h2>
                <p class="text-lg text-slate-600">Simple, secure, and straightforward process</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="bg-white rounded-2xl p-8 border border-slate-100 shadow-soft text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 text-xl font-bold mb-6">1</div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">Create Account</h3>
                    <p class="text-slate-600">Sign up and verify your identity in minutes</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-slate-100 shadow-soft text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 text-xl font-bold mb-6">2</div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">Launch Campaign</h3>
                    <p class="text-slate-600">Tell your story and set your fundraising goal</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-slate-100 shadow-soft text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 text-xl font-bold mb-6">3</div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">Share & Raise</h3>
                    <p class="text-slate-600">Share with your network and watch donations grow</p>
                </div>
                <div class="bg-white rounded-2xl p-8 border border-slate-100 shadow-soft text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 text-xl font-bold mb-6">4</div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-3">Withdraw Funds</h3>
                    <p class="text-slate-600">Receive funds directly to your verified account</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-slate-900 mb-12 text-center">Fundraise For</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php
                $categories = [
                    'Medical', 'Emergency', 'Education', 'Community',
                    'Business', 'Nonprofit', 'Memorial', 'Animals'
                ];
                foreach ($categories as $category): ?>
                    <a href="category/<?php echo strtolower($category); ?>" 
                       class="group bg-slate-50 rounded-2xl p-8 border border-slate-100 hover:border-emerald-200 hover:bg-emerald-50 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-4 bg-white rounded-xl flex items-center justify-center group-hover:bg-emerald-100 shadow-soft transition-all">
                            <svg class="w-7 h-7 text-slate-600 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 text-center"><?php echo $category; ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-slate-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-600 via-emerald-600 to-teal-600 px-6 py-10 text-center shadow-2xl sm:px-8 lg:px-12 lg:py-14">
                <h2 class="text-3xl font-bold tracking-tight text-white md:text-4xl">Ready to start your fundraiser?</h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-emerald-50">Join people raising support for medical, emergency, education, and community causes with a platform built for trust.</p>
                <div class="mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="<?php echo BASE_URL; ?>/register.php" class="inline-flex items-center justify-center rounded-2xl bg-white px-8 py-4 text-base font-semibold text-emerald-700 transition hover:bg-slate-50">
                        Start fundraising now
                    </a>
                    <a href="<?php echo BASE_URL; ?>/campaigns/v/" class="inline-flex items-center justify-center rounded-2xl border border-white/25 bg-white/10 px-8 py-4 text-base font-semibold text-white transition hover:bg-white/15">
                        View verified campaigns
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-16 bg-slate-50">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Stay Informed</h2>
                <p class="text-slate-600">Get updates on new campaigns and success stories</p>
            </div>
            <form id="newsletter-form-main" class="flex flex-col sm:flex-row gap-4">
                <input type="email" name="newsletter_email" required
                       class="flex-1 px-5 py-3 border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition"
                       placeholder="Enter your email address">
                <button type="submit"
                        class="px-8 py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition shadow-soft">
                    Subscribe
                </button>
            </form>
            <div id="newsletter-success" class="hidden mt-4 text-emerald-600 text-center font-medium"></div>
            <div id="newsletter-error" class="hidden mt-4 text-red-600 text-center font-medium"></div>
        </div>
    </section>

    <!-- Share Toast -->
    <div id="share-toast" class="fixed bottom-6 right-6 bg-slate-800 text-white px-6 py-3 rounded-xl opacity-0 pointer-events-none transition-all duration-300 z-50 shadow-large">
        Link copied to clipboard!
    </div>

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
                    
                    const params = new URLSearchParams({ q: searchTerm, page: page, limit: 12 });
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
                
                campaigns.forEach(campaign => campaignsContainer.appendChild(createCampaignCard(campaign)));
            }

            function createCampaignCard(campaign) {
                const div = document.createElement('div');
                div.className = 'group overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-200/70';
                
                const imageUrl = campaign.image_path 
                    ? `${window.BASE_URL}/${campaign.image_path.replace(/^\//, '')}`
                    : `${window.BASE_URL}/assets/images/default-campaign.jpg`;
                
                const donorCount = campaign.donor_count || 0;
                
                div.innerHTML = `
                    <a href="campaigns/public-view.php?id=${campaign.id}" class="relative block h-56 overflow-hidden bg-slate-100">
                        <img src="${imageUrl}" alt="${campaign.title}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='${window.BASE_URL}/assets/images/default-campaign.jpg'">
                        <div class="absolute inset-x-0 top-0 flex items-start justify-between p-4">
                            <span class="inline-flex items-center rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-sm backdrop-blur-sm">${campaign.category}</span>
                            <span class="inline-flex items-center rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm">${campaign.percentage}% funded</span>
                        </div>
                        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-950/70 to-transparent"></div>
                    </a>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold tracking-tight text-slate-900 mb-3 line-clamp-2">
                            <a href="campaigns/public-view.php?id=${campaign.id}" class="transition-colors hover:text-emerald-700">
                                ${campaign.title}
                            </a>
                        </h3>
                        <p class="text-sm leading-7 text-slate-600 line-clamp-3 mb-6">${campaign.description}</p>

                        <div class="rounded-2xl bg-slate-50 p-4 mb-5">
                            <div class="mb-3 flex items-center justify-between text-sm">
                                <span class="text-slate-600">Raised</span>
                                <span class="font-semibold text-emerald-700">${campaign.percentage}%</span>
                            </div>
                            <div class="mb-3 h-2.5 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full bg-emerald-500 progress-bar" style="width: ${campaign.percentage}%"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-slate-500">Current</p>
                                    <p class="mt-1 font-semibold text-slate-900">$${campaign.current_amount.toLocaleString()}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500">Goal</p>
                                    <p class="mt-1 font-semibold text-slate-900">$${campaign.goal_amount.toLocaleString()}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                            <div class="flex items-center text-slate-500 text-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                                ${donorCount} donors
                            </div>
                            <div class="flex gap-2">
                                <button onclick="copyShareLink(${campaign.id}, '${campaign.title.replace(/'/g, "\\'")}')"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Share
                                </button>
                                <a href="campaigns/public-view.php?id=${campaign.id}"
                                   class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                    Donate
                                </a>
                            </div>
                        </div>
                    </div>
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

        function copyShareLink(campaignId, campaignTitle) {
            const shareUrl = `${window.BASE_URL}/campaigns/public-view.php?id=${campaignId}`;
            navigator.clipboard.writeText(shareUrl).then(() => {
                const toast = document.getElementById('share-toast');
                toast.style.opacity = '1';
                toast.style.pointerEvents = 'auto';
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.pointerEvents = 'none';
                }, 2500);
            });
        }
    </script>

    <!-- Newsletter Script -->
    <script>
        document.getElementById('newsletter-form-main').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.newsletter_email.value;
            const successDiv = document.getElementById('newsletter-success');
            const errorDiv = document.getElementById('newsletter-error');
            successDiv.classList.add('hidden');
            errorDiv.classList.add('hidden');

            fetch('<?php echo BASE_URL; ?>/api/subscribe_newsletter.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    successDiv.textContent = "Thank you for subscribing!";
                    successDiv.classList.remove('hidden');
                    this.reset();
                } else {
                    errorDiv.textContent = data.error || "Subscription failed. Please try again.";
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(() => {
                errorDiv.textContent = "An error occurred. Please try again.";
                errorDiv.classList.remove('hidden');
            });
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

