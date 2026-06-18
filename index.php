<?php
// Initialize session if needed
session_start();

// Include configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="CroFunding - A secure platform for fundraising. Help others or start your own fundraiser for medical, emergency, education, or community causes.">
    <meta name="keywords" content="fundraising, crowdfunding, donations, medical fundraising, emergency fund, community help">
    <meta name="author" content="CroFunding">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" content="CroFunding - Fundraising for Everyone">
    <meta property="og:description" content="Start your fundraiser today or help others in need. Secure, easy, and trusted platform for all types of causes.">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    
    <title>CroFunding - Fundraising for Everyone</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">

    <script>
        // Tailwind Configuration
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
                        }
                    }
                }
            }
        }
    </script>

    <!-- Add to the existing style section in the head -->
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Add smooth transition for expanding text */
        [x-cloak] { display: none; }
        .transition-max-height {
            transition: max-height 0.3s ease-out;
        }
        .gradient-overlay {
            background: linear-gradient(to right, rgba(16, 185, 129, 0.9), rgba(6, 95, 70, 0.9));
        }
        .progress-animation {
            transition: width 1.5s ease-in-out;
        }
        .trust-badge {
            transition: transform 0.2s;
        }
        .trust-badge:hover {
            transform: translateY(-2px);
        }

        /* New styles for alerts and popups */
        #verification-alert, #newsletter-popup {
            display: none;
        }
        #verification-alert.active, #newsletter-popup.active {
            display: block;
        }

        /* Update campaign card styles */
        .campaign-card {
            transition: transform 0.2s, box-shadow 0.2s, opacity 0.3s ease-in-out;
        }

        .campaign-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
        }

        .campaign-card.hidden {
            opacity: 0;
            transform: scale(0.95);
            pointer-events: none;
        }

        .campaign-progress-bar {
            transition: width 1s ease-in-out;
        }

        .campaign-actions {
            transition: opacity 0.2s;
        }

        .campaign-badge {
            animation: badge-pulse 2s infinite;
        }

        @keyframes badge-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Add to your existing styles */
        #newsletter-popup {
            transition: opacity 0.3s ease-in-out;
        }

        #newsletter-popup .bg-white {
            transition: transform 0.3s ease-in-out;
        }

        .scale-95 {
            transform: scale(0.95);
        }

        .scale-100 {
            transform: scale(1);
        }

        /* Add these styles to your existing style section */
        .scale-95 {
            transform: scale(0.95);
        }

        .scale-100 {
            transform: scale(1);
        }

        /* Search functionality styles */
        #campaign-search:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        #clear-search:hover {
            color: rgb(75 85 99);
        }

        /* Results count styling */
        #results-count {
            font-size: 0.875rem;
            color: rgb(107 114 128);
            margin-bottom: 1.5rem;
        }

        /* No results message styling */
        #no-results-message {
            grid-column: 1 / -1;
        }

        @media (max-width: 640px) {
            #verification-alert .bg-white,
            #newsletter-popup .bg-white {
                width: calc(100% - 2rem);
                margin: 1rem;
                max-height: calc(100vh - 2rem);
                overflow-y: auto;
            }
        }


        /* Add to your style section */
@media (max-width: 639px) {
    .campaign-card .flex-row {
        flex-direction: row !important;
    }
    .campaign-card .w-32 {
        width: 8rem !important;
        min-width: 8rem !important;
        max-width: 8rem !important;
    }
    .campaign-card .h-48 {
        height: 12rem !important;
        min-height: 12rem !important;
        max-height: 12rem !important;
    }
}
@media (min-width: 640px) {
    .campaign-card .flex-row {
        flex-direction: column !important;
    }
    .campaign-card .w-32 {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
}
    </style>
</head>
<body class="antialiased">
    <!-- Include the public header -->
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <!-- Hero Section - GoFundMe Style -->
    <div class="bg-white py-16 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <!-- Main Heading -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                    #1 crowdfunding platform
                </h1>
                
                <!-- Subheading -->
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-gray-700 mb-8">
                    Successful fundraisers start here
                </h2>
                
                <!-- CTA Button -->
                <div class="mb-12">
                    <a href="/new/register" class="inline-flex items-center justify-center px-8 py-4 bg-emerald-600 text-white text-lg font-semibold rounded-lg hover:bg-emerald-700 transition-colors duration-200 shadow-lg hover:shadow-xl">
                        Start a CroFunding
                    </a>
                </div>
                
                <!-- Category Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 max-w-5xl mx-auto mb-16">
                    <a href="/new/register" class="text-center group cursor-pointer block">
                        <div class="w-16 h-16 mx-auto mb-3 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-50 transition-colors">
                            <svg class="w-8 h-8 text-gray-600 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 group-hover:text-emerald-600">Your cause</p>
                    </a>
                    
                    <a href="/new/register" class="text-center group cursor-pointer block">
                        <div class="w-16 h-16 mx-auto mb-3 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-50 transition-colors">
                            <svg class="w-8 h-8 text-gray-600 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 group-hover:text-emerald-600">Medical</p>
                    </a>
                    
                    <a href="/new/register" class="text-center group cursor-pointer block">
                        <div class="w-16 h-16 mx-auto mb-3 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-50 transition-colors">
                            <svg class="w-8 h-8 text-gray-600 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 group-hover:text-emerald-600">Emergency</p>
                    </a>
                    
                    <a href="/new/register" class="text-center group cursor-pointer block">
                        <div class="w-16 h-16 mx-auto mb-3 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-50 transition-colors">
                            <svg class="w-8 h-8 text-gray-600 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 group-hover:text-emerald-600">Business</p>
                    </a>
                    
                    <a href="/new/register" class="text-center group cursor-pointer block">
                        <div class="w-16 h-16 mx-auto mb-3 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-50 transition-colors">
                            <svg class="w-8 h-8 text-gray-600 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 group-hover:text-emerald-600">Animal</p>
                    </a>
                    
                    <a href="/new/register" class="text-center group cursor-pointer block">
                        <div class="w-16 h-16 mx-auto mb-3 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-50 transition-colors">
                            <svg class="w-8 h-8 text-gray-600 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-700 group-hover:text-emerald-600">Education</p>
                    </a>
                </div>
                
                <!-- Stats Section -->
                <div class="bg-gray-50 rounded-2xl p-8 mb-16">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">
                        More than $20000 is raised every week on CroFunding.*
                    </h3>
                    <p class="text-lg text-gray-600 mb-8 max-w-3xl mx-auto">
                        Get started in just a few minutes — with helpful new tools, it's easier than ever to pick the perfect title, write a compelling story, and share it with the world.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-emerald-600 mb-2">15% fee</div>
                            <div class="text-gray-600">transparent pricing</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-emerald-600 mb-2">1 donation</div>
                            <div class="text-gray-600">made every second</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-emerald-600 mb-2">1K+</div>
                            <div class="text-gray-600">fundraisers started daily</div>
                        </div>
                    </div>
                </div>
                
                <!-- Secondary CTA -->
                <div class="text-center">
                    <a href="/new/campaigns/view_compaigns.php" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 font-semibold text-lg">
                        Discover fundraisers
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

  <div id="trending" class="py-8 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Our Fundraisers</h2>
                <p class="mt-4 text-lg text-gray-600">Support these active fundraisers making a difference</p>
            </div>

            <!-- Search Bar -->
            <div class="max-w-2xl mx-auto mb-12">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           id="campaign-search" 
                           placeholder="Search campaigns by title, description, or category..." 
                           class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" 
                                id="clear-search" 
                                class="text-gray-400 hover:text-gray-600 hidden transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Campaigns grid - Now populated via AJAX search -->
            <div id="campaigns-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Campaigns will be loaded here via AJAX search -->
            </div>
        </div>
    </div>
    <!-- Trust Indicators Section -->
    <div class="bg-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Trust Indicators -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div class="flex items-center justify-center p-6 bg-gray-50 rounded-xl trust-badge">
                    <i class="fas fa-shield-alt text-3xl text-emerald-600 mr-4"></i>
                    <div>
                        <h3 class="font-semibold">Secure & Protected</h3>
                        <p class="text-sm text-gray-600">256-bit SSL encryption</p>
                    </div>
                </div>
                <div class="flex items-center justify-center p-6 bg-gray-50 rounded-xl trust-badge">
                    <i class="fas fa-lock text-3xl text-emerald-600 mr-4"></i>
                    <div>
                        <h3 class="font-semibold">Data Privacy</h3>
                        <p class="text-sm text-gray-600">GDPR Compliant</p>
                    </div>
                </div>
                <div class="flex items-center justify-center p-6 bg-gray-50 rounded-xl trust-badge">
                    <i class="fas fa-headset text-3xl text-emerald-600 mr-4"></i>
                    <div>
                        <h3 class="font-semibold">24/7 Support</h3>
                        <p class="text-sm text-gray-600">Always here to help</p>
                    </div>
                </div>
                <div class="flex items-center justify-center p-6 bg-gray-50 rounded-xl trust-badge">
                    <i class="fas fa-heart text-3xl text-emerald-600 mr-4"></i>
                    <div>
                        <h3 class="font-semibold">Trusted Platform</h3>
                        <p class="text-sm text-gray-600">By thousands worldwide</p>
                    </div>
                </div>
            </div>

            <!-- Data Protection Promise -->
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Your Trust is Our Priority</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    We employ industry-leading security measures to protect your data and ensure secure transactions. 
                    Your information is never shared without your consent.
                </p>
            </div>

            <!-- Security Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-fingerprint text-2xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Secure Authentication</h3>
                    <p class="text-gray-600">Multi-factor authentication and advanced fraud prevention systems.</p>
                </div>
                
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-server text-2xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Data Encryption</h3>
                    <p class="text-gray-600">End-to-end encryption for all sensitive information and transactions.</p>
                </div>
                
                <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
                        <i class="fas fa-certificate text-2xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Verified Platform</h3>
                    <p class="text-gray-600">Regular security audits and compliance with international standards.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="bg-gray-50 py-16 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-12 text-center">Fundraise for...</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 lg:gap-8">
                <?php
                $categories = ['Medical', 'Emergency', 'Memorial', 'Education', 'Nonprofit', 'Business', 'Animals', 'Community'];
                foreach ($categories as $category) :
                ?>
                <a href="category/<?php echo strtolower($category); ?>" class="group">
                    <div class="bg-white rounded-xl p-6 text-center transition-all hover:shadow-lg">
                        <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center group-hover:bg-emerald-600">
                            <svg class="w-8 h-8 text-emerald-600 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $category; ?></h3>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Trust & Safety -->
    <div class="bg-emerald-50 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.025 12.025 0 002 12c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Trusted Platform</h3>
                    <p class="text-gray-600">Secure and transparent fundraising platform trusted by thousands.</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Secure Payments</h3>
                    <p class="text-gray-600">Your donations are protected with industry-leading security.</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Fast & Easy</h3>
                    <p class="text-gray-600">Start your fundraising campaign in minutes.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="bg-emerald-600 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Ready to Make a Difference?</h2>
            <p class="text-lg text-emerald-200 mb-8">Join us today and start fundraising for the causes you care about.</p>
            <a href="/new/register" class="bg-white text-emerald-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-emerald-100 transition-colors">
                Start Fundraising Now
            </a>
        </div>
    </div>

    <!-- Trending Campaigns Section -->
  

    <!-- Trusted Stats Section -->
    <div class="bg-emerald-50 py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                    Trusted by thousands worldwide
                </h2>
            </div>
            <dl class="mt-10 text-center sm:max-w-3xl sm:mx-auto sm:grid sm:grid-cols-3 sm:gap-8">
                <?php
                // Get stats directly from database
                try {
                    $db = Database::getInstance();
                    
                    // Get total raised from completed donations
                    $totalRaised = $db->fetchOne(
                        "SELECT COALESCE(SUM(amount), 0) as total 
                         FROM donations 
                         WHERE status = 'completed'"
                    )['total'];

                    // Get total active campaigns
                    $totalCampaigns = $db->fetchOne(
                        "SELECT COUNT(*) as count 
                         FROM campaigns 
                         WHERE status = 'active'"
                    )['count'];

                    // Get total donors (count every donation, even same email)
                    $totalDonors = $db->fetchOne(
                        "SELECT COUNT(*) as count 
                         FROM donations 
                         WHERE status = 'completed'"
                    )['count'];

                    $stats = [
                        'total_raised' => floatval($totalRaised),
                        'total_campaigns' => intval($totalCampaigns),
                        'total_donors' => intval($totalDonors)
                    ];
                } catch (Exception $e) {
                    error_log("Error fetching platform stats: " . $e->getMessage());
                    $stats = [
                        'total_raised' => 0,
                        'total_campaigns' => 0,
                        'total_donors' => 0
                    ];
                }
                ?>
                <div class="flex flex-col">
                    <dt class="order-2 mt-2 text-lg leading-6 font-medium text-gray-500">
                        Total Raised
                    </dt>
                    <dd class="order-1 text-5xl font-extrabold text-emerald-600">
                        $<?php echo number_format($stats['total_raised']); ?>
                    </dd>
                </div>
                <div class="flex flex-col mt-10 sm:mt-0">
                    <dt class="order-2 mt-2 text-lg leading-6 font-medium text-gray-500">
                        Campaigns
                    </dt>
                    <dd class="order-1 text-5xl font-extrabold text-emerald-600">
                        <?php echo number_format($stats['total_campaigns']); ?>
                    </dd>
                </div>
                <div class="flex flex-col mt-10 sm:mt-0">
                    <dt class="order-2 mt-2 text-lg leading-6 font-medium text-gray-500">
                        Donors
                    </dt>
                    <dd class="order-1 text-5xl font-extrabold text-emerald-600">
                        <?php echo number_format($stats['total_donors']); ?>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- How to Start Section -->
    <div class="bg-white py-8 lg:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900">How to Start Fundraising</h2>
                <p class="mt-4 text-lg text-gray-600">Get your campaign up and running in just a few simple steps</p>
            </div>

            <div class="relative">
                <!-- Steps Container -->
                <div class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Step 1 -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 text-xl font-bold mb-4">1</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Create Account</h3>
                        <p class="text-gray-600">Sign up for free and verify your email address</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 text-xl font-bold mb-4">2</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Start Campaign</h3>
                        <p class="text-gray-600">Tell your story and set your fundraising goal</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 text-xl font-bold mb-4">3</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Share</h3>
                        <p class="text-gray-600">Share your campaign with friends and family</p>
                    </div>

                    <!-- Step 4 -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 text-xl font-bold mb-4">4</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Withdraw Funds</h3>
                        <p class="text-gray-600">Receive donations directly to your account</p>
                    </div>
                </div>

                <!-- Connection Line (visible on md and up) -->
                <div class="hidden md:block absolute top-20 left-0 w-full h-0.5 bg-emerald-100"></div>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="bg-gray-50 py-16 lg:py-24" id="faq">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900">Frequently Asked Questions</h2>
                <p class="mt-4 text-lg text-gray-600">Got questions? We've got answers.</p>
            </div>

            <div class="max-w-3xl mx-auto" x-data="{ selected: null }">
                <!-- FAQ Item 1 -->
                <div class="mb-4">
                    <button class="flex justify-between w-full px-4 py-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-all"
                            @click="selected !== 1 ? selected = 1 : selected = null">
                        <span class="text-lg font-medium text-gray-900">How long does it take to receive donations?</span>
                        <svg class="w-6 h-6 text-gray-500" :class="{'rotate-180': selected == 1}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="mt-2 px-4" x-show="selected == 1" x-collapse>
                        <p class="text-gray-600">Donations are typically processed within 2-5 business days after they are made. Once processed, you can withdraw funds to your connected bank account.</p>
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="mb-4">
                    <button class="flex justify-between w-full px-4 py-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-all"
                            @click="selected !== 2 ? selected = 2 : selected = null">
                        <span class="text-lg font-medium text-gray-900">What fees are involved?</span>
                        <svg class="w-6 h-6 text-gray-500" :class="{'rotate-180': selected == 2}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="mt-2 px-4" x-show="selected == 2" x-collapse>
                        <p class="text-gray-600">We charge a small platform fee of 2.9% + $0.30 per donation to cover payment processing and platform maintenance.</p>
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="mb-4">
                    <button class="flex justify-between w-full px-4 py-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-all"
                            @click="selected !== 3 ? selected = 3 : selected = null">
                        <span class="text-lg font-medium text-gray-900">Is my donation secure?</span>
                        <svg class="w-6 h-6 text-gray-500" :class="{'rotate-180': selected == 3}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="mt-2 px-4" x-show="selected == 3" x-collapse>
                        <p class="text-gray-600">Yes, all donations are processed through secure payment gateways with industry-standard encryption and security measures.</p>
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="mb-4">
                    <button class="flex justify-between w-full px-4 py-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-all"
                            @click="selected !== 4 ? selected = 4 : selected = null">
                        <span class="text-lg font-medium text-gray-900">What types of campaigns are allowed?</span>
                        <svg class="w-6 h-6 text-gray-500" :class="{'rotate-180': selected == 4}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="mt-2 px-4" x-show="selected == 4" x-collapse>
                        <p class="text-gray-600">We support various campaign types including medical, emergency, education, community projects, and more. All campaigns must comply with our terms of service and community guidelines.</p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-12">
                <p class="text-gray-600">Still have questions?</p>
                <a href="mailto:support@crofund.org" class="inline-flex items-center mt-2 text-emerald-600 hover:text-emerald-700">
                    Contact our support team
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
 
    <!-- Define BASE_URL for JavaScript -->
    <script>
        // Define BASE_URL for JavaScript
        window.BASE_URL = '<?php echo BASE_URL; ?>';
    </script>

    <!-- Browse Campaigns Section -->
    <div id="browse-campaigns" class="py-16 lg:py-24 bg-gray-50" x-data="{ 
    campaigns: [],
    loading: false,
    hasMore: false,
    page: 1,
    category: 'all',
    sort: 'newest',

    async loadCampaigns() {
        try {
            this.loading = true;
            const response = await fetch(`${window.BASE_URL}/api/browse_campaigns.php?category=${this.category}&sort=${this.sort}&page=${this.page}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (data.success) {
                if (this.page === 1) {
                    this.campaigns = data.campaigns;
                } else {
                    this.campaigns = [...this.campaigns, ...data.campaigns];
                }
                this.hasMore = data.hasMore;
            } else {
                console.error('Error:', data.error);
            }
        } catch (error) {
            console.error('Error loading campaigns:', error);
        } finally {
            this.loading = false;
        }
    }
}" x-init="loadCampaigns()">
      

    <!-- Verification Alert -->
    <div id="verification-alert" class="fixed inset-0 bg-black bg-opacity-50 z-50 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white rounded-lg max-w-lg mx-auto mt-20 p-6 relative transform transition-transform duration-300 scale-95 m-4">
            <button onclick="closeVerificationAlert()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Important Notice: Account Verification Required</h3>
                <p class="text-gray-600">
                    All unverified accounts will be deactivated after June 20th, 2025. Please verify your identity to maintain account access.
                </p>
            </div>

            <div class="space-y-4">
                <a href="/verify-identity" class="block w-full bg-yellow-600 text-white text-center py-3 rounded-lg font-semibold hover:bg-yellow-700 transition-colors">
                    Verify My Account Now
                </a>
                <button onclick="closeVerificationAlert()" class="block w-full bg-gray-100 text-gray-700 text-center py-3 rounded-lg font-semibold hover:bg-gray-200 transition-colors">
                    Remind Me Later
                </button>
            </div>
        </div>
    </div>

    <!-- Newsletter Popup -->
    <div id="newsletter-popup" class="fixed inset-0 bg-black bg-opacity-50 z-50 opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="bg-white rounded-lg max-w-lg mx-auto mt-20 p-6 relative transform transition-transform duration-300 scale-95">
            <button onclick="closeNewsletterPopup()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Stay Updated with CroFunding</h3>
                <p class="text-gray-600">
                    Get notified about new campaigns and success stories in your area.
                </p>
            </div>

            <form id="newsletter-form" class="space-y-4">
                <div>
                    <input type="email" 
                           placeholder="Enter your email" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <button type="submit" class="w-full bg-emerald-600 text-white py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-colors">
                    Subscribe Now
                </button>
            </form>
        </div>
    </div>

    <!-- Share Toast Notification -->
    <div id="share-toast" class="fixed bottom-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg opacity-0 pointer-events-none transition-all duration-300 z-50">
        Link copied to clipboard!
    </div>

    <!-- Scripts -->

    <!-- Show verification alert after 3 seconds -->
    <script>
        setTimeout(() => {
            document.getElementById('verification-alert').classList.remove('hidden');
        }, 3000);
    </script>

    <!-- Newsletter popup functionality -->
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Newsletter popup functionality
    const newsletterPopup = document.getElementById('newsletter-popup');
    const verificationAlert = document.getElementById('verification-alert');
    const hasSeenNewsletter = localStorage.getItem('newsletter-shown');
    const hasSeenVerification = localStorage.getItem('verification-shown');

    function showPopup(element) {
        element.classList.remove('opacity-0', 'pointer-events-none');
        element.querySelector('.bg-white').classList.remove('scale-95');
        element.querySelector('.bg-white').classList.add('scale-100');
    }

    function closePopup(element, storageKey) {
        element.classList.add('opacity-0', 'pointer-events-none');
        element.querySelector('.bg-white').classList.remove('scale-100');
        element.querySelector('.bg-white').classList.add('scale-95');
        localStorage.setItem(storageKey, 'true');
    }

    // Show verification alert first
    if (!hasSeenVerification) {
        setTimeout(() => showPopup(verificationAlert), 2000);
    }

    // Show newsletter popup after verification (if not seen)
    if (!hasSeenNewsletter) {
        setTimeout(() => showPopup(newsletterPopup), 7000);
    }

    // Close button handlers
    window.closeVerificationAlert = function() {
        closePopup(verificationAlert, 'verification-shown');
    };

    window.closeNewsletterPopup = function() {
        closePopup(newsletterPopup, 'newsletter-shown');
    };

    // Handle newsletter form submission
    document.getElementById('newsletter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('input[type="email"]').value;
        // Add your newsletter subscription logic here
        alert('Thank you for subscribing!');
        closeNewsletterPopup();
    });

    // Close popups when clicking outside
    [newsletterPopup, verificationAlert].forEach(popup => {
        popup.addEventListener('click', function(e) {
            if (e.target === this) {
                if (popup === newsletterPopup) closeNewsletterPopup();
                if (popup === verificationAlert) closeVerificationAlert();
            }
        });
    });

    // Handle mobile responsiveness
    function adjustPopupPosition() {
        const popups = [newsletterPopup, verificationAlert];
        popups.forEach(popup => {
            const dialog = popup.querySelector('.bg-white');
            if (window.innerWidth < 640) { // mobile breakpoint
                dialog.style.margin = '1rem';
                dialog.style.maxHeight = 'calc(100vh - 2rem)';
                dialog.style.overflowY = 'auto';
            } else {
                dialog.style.margin = '';
                dialog.style.maxHeight = '';
                dialog.style.overflowY = '';
            }
        });
    }

    // Adjust on load and resize
    adjustPopupPosition();
    window.addEventListener('resize', adjustPopupPosition);
});
</script>

    <script>
function copyShareLink(campaignId, campaignTitle) {
    const shareUrl = `${window.BASE_URL}/campaigns/public-view.php?id=${campaignId}`;
    navigator.clipboard.writeText(shareUrl).then(() => {
        const toast = document.getElementById('share-toast');
        toast.style.opacity = '1';
        toast.style.pointerEvents = 'auto';
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.pointerEvents = 'none';
        }, 2000);
    });
}
</script>
<script>
  function toggleDesc(id) {
    const el = document.getElementById(`desc-${id}`);
    el.classList.toggle('line-clamp-3');
  }
</script>

    <!-- Campaign Search Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('campaign-search');
            const clearSearchBtn = document.getElementById('clear-search');
            const campaignsContainer = document.getElementById('campaigns-container');
            
            if (!campaignsContainer) {
                return;
            }
            
            let currentSearchTerm = '';
            let searchTimeout;
            let isLoading = false;

            // AJAX search campaigns
            async function searchCampaigns(searchTerm = '', page = 1) {
                if (isLoading) return;
                
                try {
                    isLoading = true;
                    showLoadingState();
                    
                    const params = new URLSearchParams({
                        q: searchTerm,
                        page: page,
                        limit: 12
                    });
                    
                    const response = await fetch(`${window.BASE_URL}/api/search_campaigns.php?${params}`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    // Debug: log the API response
                    console.log('API Response:', data);
                    
                    if (data.success) {
                        displaySearchResults(data.campaigns, data.pagination, data.search);
                    } else {
                        throw new Error(data.error || 'Search failed');
                    }
                    
                } catch (error) {
                    console.error('Search error:', error);
                    showErrorMessage('Search failed. Please try again.');
                } finally {
                    isLoading = false;
                    hideLoadingState();
                }
            }

            // Display search results
            function displaySearchResults(campaigns, pagination, searchInfo) {
                // Clear existing content
                campaignsContainer.innerHTML = '';
                
                if (campaigns.length === 0) {
                    if (searchInfo.term) {
                        // No search results
                        campaignsContainer.innerHTML = `
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No campaigns found</h3>
                                    <p class="text-gray-600">No campaigns match "${searchInfo.term}". Try different keywords.</p>
                                </div>
                            </div>
                        `;
                    } else {
                        // No campaigns at all
                        campaignsContainer.innerHTML = `
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No campaigns available</h3>
                                    <p class="text-gray-600">There are currently no active campaigns.</p>
                                </div>
                            </div>
                        `;
                    }
                    return;
                }
                
                // Display campaigns
                campaigns.forEach(campaign => {
                    const campaignCard = createCampaignCard(campaign);
                    campaignsContainer.appendChild(campaignCard);
                });
            }

            // Create campaign card HTML
            function createCampaignCard(campaign) {
                // Debug: log campaign data to see what we're receiving
                console.log('Campaign data:', campaign);
                
                const div = document.createElement('div');
                div.className = 'campaign-card bg-white rounded-lg shadow hover:shadow-md transition-transform hover:-translate-y-1 flex flex-row sm:flex-col max-w-full';
                
                const imageUrl = campaign.image_path 
                    ? `${window.BASE_URL}/${campaign.image_path.replace(/^\//, '')}`
                    : `${window.BASE_URL}/assets/images/default-campaign.jpg`;
                
                // Ensure donor_count is properly handled
                const donorCount = campaign.donor_count || 0;
                
                div.innerHTML = `
                    <div class="w-1/3 sm:w-full h-28 sm:h-48 relative flex-shrink-0 overflow-hidden rounded-l-lg sm:rounded-t-lg">
                        <a href="campaigns/public-view.php?id=${campaign.id}">
                            <img src="${imageUrl}" 
                                 alt="${campaign.title}" 
                                 class="w-full h-full object-cover"
                                 onerror="this.src='${window.BASE_URL}/assets/images/default-campaign.jpg'">
                        </a>
                        <div class="absolute top-1 right-1 sm:top-2 sm:right-2">
                            <span class="campaign-category px-2 py-1 text-xs font-semibold text-white bg-emerald-500 rounded">
                                ${campaign.category}
                            </span>
                        </div>
                    </div>
                    
                    <div class="w-2/3 sm:w-full p-3 sm:p-5 flex flex-col justify-between text-sm space-y-2">
                        <h3 class="campaign-title text-base font-semibold text-gray-900 leading-snug line-clamp-2">
                            ${campaign.title}
                        </h3>
                        
                        <div class="relative">
                            <p class="campaign-description text-gray-600 text-xs sm:text-sm line-clamp-2">
                                ${campaign.description}
                            </p>
                        </div>
                        
                        <div class="space-y-1">
                            <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-1.5 bg-emerald-500" style="width: ${campaign.percentage}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>
                                    <strong>$${campaign.current_amount.toLocaleString()}</strong> of $${campaign.goal_amount.toLocaleString()}
                                </span>
                                <span class="font-semibold text-emerald-600">${campaign.percentage}%</span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center mt-1.5">
                            <div class="flex items-center text-gray-500 text-xs">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                </svg>
                                ${donorCount} donors
                            </div>
                            <div class="flex space-x-1">
                                <button onclick="copyShareLink(${campaign.id}, '${campaign.title.replace(/'/g, "\\'")}')" 
                                        class="px-2 py-1 text-xs border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                                    Share
                                </button>
                                <a href="campaigns/public-view.php?id=${campaign.id}" 
                                   class="px-3 py-1 text-xs rounded text-white bg-emerald-600 hover:bg-emerald-700">
                                    Donate
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                
                return div;
            }

            // Show/hide loading state
            function showLoadingState() {
                campaignsContainer.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-gray-600">Searching campaigns...</span>
                        </div>
                    </div>
                `;
            }

            function hideLoadingState() {
                // Loading state is replaced by results
            }

            function showErrorMessage(message) {
                campaignsContainer.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <div class="text-red-500">
                            <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Search Error</h3>
                            <p class="text-gray-600">${message}</p>
                        </div>
                    </div>
                `;
            }

            // Search input event with debouncing
            searchInput.addEventListener('input', function() {
                currentSearchTerm = this.value.trim();
                
                // Show/hide clear button
                if (currentSearchTerm.length > 0) {
                    clearSearchBtn.classList.remove('hidden');
                } else {
                    clearSearchBtn.classList.add('hidden');
                }
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // Set new timeout for search
                searchTimeout = setTimeout(() => {
                    searchCampaigns(currentSearchTerm, 1);
                }, 500);
            });

            // Clear search
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                currentSearchTerm = '';
                this.classList.add('hidden');
                searchCampaigns('', 1);
            });

            // Initialize with all campaigns
            searchCampaigns('', 1);
        });
    </script>

    <!-- Newsletter Subscription Section -->
<div class="bg-white py-16">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Subscribe to Our Newsletter</h2>
            <p class="mt-2 text-gray-600">Get the latest updates, campaigns, and success stories from CroFunding.</p>
        </div>
        <form id="newsletter-form-main" class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <input type="email" name="newsletter_email" required
                   class="w-full sm:w-auto flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                   placeholder="Enter your email address">
            <button type="submit"
                    class="w-full sm:w-auto bg-emerald-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition">
                Subscribe
            </button>
        </form>
        <div id="newsletter-success" class="hidden mt-4 text-green-600 text-center font-medium"></div>
        <div id="newsletter-error" class="hidden mt-4 text-red-600 text-center font-medium"></div>
    </div>
</div>

<script>
document.getElementById('newsletter-form-main').addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.newsletter_email.value;
    const successDiv = document.getElementById('newsletter-success');
    const errorDiv = document.getElementById('newsletter-error');
    successDiv.classList.add('hidden');
    errorDiv.classList.add('hidden');

    // Simple AJAX example (replace with your backend endpoint)
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
<a href="https://wa.me/message/WCXGXP72FE7YP1" target="_blank" rel="noopener noreferrer" 
   class="fixed bottom-6 right-6 bg-green-500 hover:bg-green-600 text-white p-4 rounded-full shadow-lg transition-all duration-200 hover:shadow-xl z-50"
   title="Join our WhatsApp group">
    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.67-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.076 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421-7.403h-.004c-1.025 0-2.034.377-2.794 1.118-.76.74-1.18 1.726-1.18 2.76 0 1.06.424 2.063 1.192 2.821.747.748 1.76 1.18 2.799 1.18h.001c1.026 0 2.035-.377 2.795-1.118.76-.74 1.181-1.726 1.181-2.761 0-1.059-.424-2.063-1.193-2.822-.748-.748-1.76-1.18-2.799-1.18M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0m0 23.391c-6.338 0-11.391-5.053-11.391-11.391S5.662.609 12 .609s11.391 5.053 11.391 11.391-5.053 11.391-11.391 11.391"/>
    </svg>
</a>

</body>
  <?php include __DIR__ . '/components/public-footer.php'; ?>
</html>