<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Initialize Campaign object
$campaign = new Campaign();

// Get filter parameters from URL or default values
$category = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? ''; // No default sorting
$page = (int)($_GET['page'] ?? 1);
$perPage = 12;

// Require Database to get total count
require_once __DIR__ . '/../config/Database.php';

// Get campaigns with filters
try {
    $result = $campaign->getBrowseCampaigns($category, $sort, $page, $perPage);
    $campaigns = $result['campaigns'] ?? [];

    // Calculate total and total pages using Database directly
    $db = Database::getInstance();
    $countSql = "SELECT COUNT(*) as total FROM campaigns WHERE status = 'active'";
    $countParams = [];
    if ($category !== 'all') {
        $countSql .= " AND category = ?";
        $countParams[] = $category;
    }
    $countResult = $db->fetchOne($countSql, $countParams);
    $total = (int)($countResult['total'] ?? 0);
    $totalPages = (int)ceil($total / $perPage);
} catch (Exception $e) {
    error_log("Error loading campaigns: " . $e->getMessage());
    $campaigns = [];
    $total = 0;
    $totalPages = 1;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Browse Fundraisers - CroFunding</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    tailwind.config = {
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
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          }
        },
      },
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght=400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      display: -moz-box;
      -moz-line-clamp: 2;
      -moz-box-orient: vertical;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      display: -moz-box;
      -moz-line-clamp: 3;
      -moz-box-orient: vertical;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    [x-cloak] {
      display: none !important;
    }
    .campaign-card-content {
      min-height: 0;
    }
    .campaign-card-content * {
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    #campaigns-container > div {
      min-width: 0;
    }
    .hero-orb {
      position: absolute;
      border-radius: 9999px;
      filter: blur(60px);
      opacity: 0.5;
      pointer-events: none;
    }
  </style>
</head>
<body class="bg-slate-50 font-sans">

<?php include '../components/public-header.php'; ?>

  <main class="relative overflow-hidden pt-20 pb-14 md:pt-28 md:pb-24">
    <div class="hero-orb -top-16 left-[-4rem] h-56 w-56 bg-emerald-200"></div>
    <div class="hero-orb top-24 right-[-3rem] h-64 w-64 bg-sky-200"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
      <section class="relative overflow-hidden rounded-[2rem] border border-white/70 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 px-6 py-8 text-white shadow-2xl sm:px-8 md:px-10 md:py-10 lg:px-12">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.22),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.14),_transparent_30%)]"></div>
        <div class="relative grid gap-8 lg:grid-cols-[1.25fr_0.75fr] lg:items-end">
          <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.28em] text-emerald-100">
              Discover active fundraisers
            </span>
            <h1 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
              Browse campaigns making a real-world impact.
            </h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-200 sm:text-base">
              Explore verified causes, urgent needs, and community-led fundraisers. Filter by category, sort by momentum, and support the stories that matter most to you.
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-300">Campaigns</p>
              <p class="mt-2 text-2xl font-bold text-white"><?= number_format($total) ?></p>
              <p class="mt-1 text-sm text-slate-300">Active fundraising stories</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-300">Categories</p>
              <p class="mt-2 text-2xl font-bold text-white">4</p>
              <p class="mt-1 text-sm text-slate-300">Medical, education and more</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-300">Page</p>
              <p class="mt-2 text-2xl font-bold text-white"><?= number_format($page) ?></p>
              <p class="mt-1 text-sm text-slate-300">Of <?= number_format($totalPages) ?> available</p>
            </div>
          </div>
        </div>
      </section>

      <section class="-mt-6 relative z-10 rounded-[1.75rem] border border-slate-200/70 bg-white/95 p-5 shadow-xl shadow-slate-200/60 backdrop-blur sm:p-6 lg:p-7">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
          <div class="grid flex-1 gap-4 md:grid-cols-2 xl:max-w-2xl">
            <div>
              <label for="category-filter" class="mb-2 block text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Category</label>
              <select id="category-filter" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10" onchange="filterCampaigns()">
                <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>All Categories</option>
                <option value="medical" <?= $category === 'medical' ? 'selected' : '' ?>>Medical</option>
                <option value="education" <?= $category === 'education' ? 'selected' : '' ?>>Education</option>
                <option value="emergency" <?= $category === 'emergency' ? 'selected' : '' ?>>Emergency</option>
                <option value="community" <?= $category === 'community' ? 'selected' : '' ?>>Community</option>
              </select>
            </div>
            <div>
              <label for="sort-filter" class="mb-2 block text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Sort</label>
              <select id="sort-filter" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 shadow-sm transition focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10" onchange="filterCampaigns()">
                <option value="" <?= $sort === '' ? 'selected' : '' ?>>Sort by</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="most-funded" <?= $sort === 'most-funded' ? 'selected' : '' ?>>Most Funded</option>
                <option value="ending-soon" <?= $sort === 'ending-soon' ? 'selected' : '' ?>>Ending Soon</option>
              </select>
            </div>
          </div>

          <div class="flex flex-col gap-3 xl:items-end">
            <div class="flex flex-wrap gap-2 xl:justify-end">
              <?php if ($category !== 'all'): ?>
              <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700">
                Category: <?= ucfirst($category) ?>
                <button onclick="removeFilter('category')" class="text-emerald-500 transition hover:text-emerald-700">×</button>
              </span>
              <?php endif; ?>
              <?php if ($sort !== ''): ?>
              <span class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3.5 py-2 text-xs font-semibold text-sky-700">
                Sort: <?= ucwords(str_replace('-', ' ', $sort)) ?>
                <button onclick="removeFilter('sort')" class="text-sky-500 transition hover:text-sky-700">×</button>
              </span>
              <?php endif; ?>
              <?php if ($category === 'all' && $sort === ''): ?>
              <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-medium text-slate-500">
                Showing all active campaigns
              </span>
              <?php endif; ?>
            </div>

            <?php if ($category !== 'all' || $sort !== ''): ?>
            <button onclick="clearFilters()" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
              Clear filters
            </button>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <div id="loading-spinner" class="hidden py-10">
        <div class="mx-auto inline-flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 shadow-sm">
          <svg class="h-5 w-5 animate-spin text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Loading campaigns...
        </div>
      </div>

      <div id="campaigns-container" class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-3">
        <?php if (empty($campaigns)): ?>
          <div class="col-span-full rounded-[1.75rem] border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm">
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100">
              <svg class="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-slate-900">No campaigns found</h3>
            <p class="mt-2 text-slate-600">Try changing the current filters or check back a little later.</p>
          </div>
        <?php else: ?>
          <?php foreach ($campaigns as $index => $camp): 
            $title = htmlspecialchars($camp['title'] ?? '');
            $description = htmlspecialchars($camp['description'] ?? '');
            $category = htmlspecialchars($camp['category'] ?? '');
            $current = (float)($camp['current_amount'] ?? 0);
            $goal = (float)($camp['goal_amount'] ?? 0);
            $donors = (int)($camp['donor_count'] ?? 0);
            $id = (int)($camp['id'] ?? 0);
            $percent = $goal > 0 ? min(($current / $goal) * 100, 100) : 0;
            $imageUrl = !empty($camp['image_path']) ? BASE_URL . '/' . ltrim($camp['image_path'], '/') : BASE_URL . '/assets/images/default-campaign.jpg';
          ?>
          <div class="group flex flex-col overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-200/70">
            <!-- Image -->
            <a href="public-view.php?id=<?= $id ?>" class="relative block h-60 overflow-hidden flex-shrink-0 bg-slate-100">
              <img src="<?= $imageUrl ?>" alt="<?= $title ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='<?= BASE_URL ?>/assets/images/default-campaign.jpg'" />
              <div class="absolute inset-x-0 top-0 flex items-start justify-between p-4">
                <span class="inline-flex items-center rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-sm backdrop-blur-sm">
                  <?= $category ?>
                </span>
                <span class="inline-flex items-center rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm">
                  <?= round($percent) ?>% funded
                </span>
              </div>
              <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-950/70 to-transparent"></div>
            </a>

            <!-- Content -->
            <div class="campaign-card-content flex flex-1 flex-col p-6">
              <h3 class="line-clamp-2 text-xl font-semibold tracking-tight text-slate-900 transition-colors group-hover:text-emerald-700 flex-shrink-0">
                <a href="public-view.php?id=<?= $id ?>">
                  <?= $title ?>
                </a>
              </h3>

              <div class="mt-3 flex items-center gap-2 text-xs font-medium text-slate-500">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1">Campaign</span>
                <span class="inline-flex items-center gap-1">
                  <svg class="h-3.5 w-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927C9.469 1.675 10.531 1.675 10.951 2.927l1.07 3.207a1 1 0 00.95.69h3.374c1.317 0 1.86 1.687.795 2.454l-2.73 1.967a1 1 0 00-.364 1.118l1.07 3.207c.42 1.252-.566 2.19-1.63 1.423l-2.73-1.967a1 1 0 00-1.172 0l-2.73 1.967c-1.064.767-2.05-.171-1.63-1.423l1.07-3.207a1 1 0 00-.364-1.118L2.68 9.278c-1.065-.767-.522-2.454.795-2.454h3.374a1 1 0 00.95-.69l1.07-3.207z"/>
                  </svg>
                  Open for support
                </span>
              </div>

              <div class="mt-4 min-h-[4.75rem] flex-grow text-sm leading-7 text-slate-600 line-clamp-3">
                <?= $description ?>
              </div>

              <div class="mt-6 rounded-2xl bg-slate-50 p-4">
                <div class="mb-3 flex items-center justify-between text-sm">
                  <span class="text-slate-600">Raised</span>
                  <span class="font-semibold text-emerald-700"><?= round($percent) ?>%</span>
                </div>
                <div class="mb-3 h-2.5 overflow-hidden rounded-full bg-slate-200">
                  <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600" style="width: <?= round($percent) ?>%"></div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                  <div>
                    <p class="text-slate-500">Current</p>
                    <p class="mt-1 font-semibold text-slate-900">$<?= number_format($current, 2) ?></p>
                  </div>
                  <div>
                    <p class="text-slate-500">Goal</p>
                    <p class="mt-1 font-semibold text-slate-900">$<?= number_format($goal, 2) ?></p>
                  </div>
                </div>
              </div>

              <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 flex-shrink-0">
                <div class="flex items-center text-sm text-slate-600">
                  <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                  </svg>
                  <?= $donors ?> donors
                </div>
                <div class="flex items-center gap-2">
                  <button onclick="copyShareLink(<?= $id ?>, '<?= addslashes($title) ?>')" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 whitespace-nowrap">
                    Share
                  </button>
                  <a href="public-view.php?id=<?= $id ?>" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 whitespace-nowrap">
                    Donate
                  </a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      
      <!-- Pagination Controls -->
      <?php if ($totalPages > 1): ?>
      <div id="pagination-container" class="mt-12 flex items-center justify-center">
        <nav class="flex flex-wrap items-center gap-2 rounded-[1.5rem] border border-slate-200 bg-white p-2 shadow-sm">
          <!-- Previous Button -->
          <button id="prev-page" onclick="goToPage(<?= $page - 1 ?>)" 
            class="px-3 py-2 sm:px-4 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:pointer-events-none"
            <?= $page <= 1 ? 'disabled' : '' ?>>
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="hidden sm:inline ml-1.5">Previous</span>
          </button>
          
          <!-- Page Numbers -->
          <div id="page-numbers" class="flex items-center gap-1">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <?php 
                // On mobile: show first, last, current, and 1 around current
                // On desktop: show first, last, current, and 2 around current
                $showOnMobile = (
                  $i === 1 || 
                  $i === $totalPages || 
                  $i === $page || 
                  $i === $page - 1 || 
                  $i === $page + 1
                );
                $showOnDesktop = (
                  $i === 1 || 
                  $i === $totalPages || 
                  ($i >= $page - 2 && $i <= $page + 2)
                );
                if ($showOnMobile || $showOnDesktop): 
              ?>
                <?php if ($i > 1 && !in_array($i - 1, [1, $page - 2, $page - 1, $page, $page + 1, $page + 2, $totalPages])): ?>
                  <span class="px-2 py-2 text-slate-400 sm:px-3">...</span>
                <?php endif; ?>
                <button onclick="goToPage(<?= $i ?>)" 
                  class="px-3 py-2 sm:px-4 rounded-xl text-sm font-semibold transition-colors <?= $i === $page ? 'bg-emerald-600 text-white' : 'text-slate-700 hover:bg-slate-50 border border-slate-200' ?> <?php if (!$showOnMobile): ?> hidden sm:inline-flex <?php endif; ?>">
                  <?= $i ?>
                </button>
              <?php endif; ?>
            <?php endfor; ?>
          </div>
          
          <!-- Next Button -->
          <button id="next-page" onclick="goToPage(<?= $page + 1 ?>)" 
            class="px-3 py-2 sm:px-4 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:pointer-events-none"
            <?= $page >= $totalPages ? 'disabled' : '' ?>>
            <span class="hidden sm:inline mr-1.5">Next</span>
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </nav>
      </div>
      <?php endif; ?>
    </div>
  </main>

  <!-- Share Toast -->
  <div id="share-toast" class="fixed bottom-6 right-6 z-50 rounded-2xl bg-slate-900 px-6 py-3 text-white opacity-0 pointer-events-none shadow-2xl transition-all duration-300">
    <div class="flex items-center">
      <svg class="mr-2 h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      Link copied to clipboard!
    </div>
  </div>

  <script>
    // Global state
    let currentPage = <?= $page ?>;
    let totalPages = <?= $totalPages ?>;
    
    // Ensure DOM is loaded before running scripts
    document.addEventListener('DOMContentLoaded', function() {
      // All JavaScript functions are now available
    });

    function copyShareLink(campaignId, title) {
      const url = `${BASE_URL}/campaigns/public-view.php?id=${campaignId}`;
      navigator.clipboard.writeText(url).then(() => {
        const toast = document.getElementById('share-toast');
        toast.style.opacity = '1';
        toast.style.pointerEvents = 'auto';
        setTimeout(() => {
          toast.style.opacity = '0';
          toast.style.pointerEvents = 'none';
        }, 2500);
      });
    }
    
    function goToPage(page) {
      if (page < 1 || page > totalPages) return;
      
      const category = document.getElementById('category-filter').value;
      const sort = document.getElementById('sort-filter').value;
      
      // Show loading state
      const container = document.getElementById('campaigns-container');
      const spinner = document.getElementById('loading-spinner');
      
      if (!container || !spinner) {
        console.error('Required elements not found');
        return;
      }
      
      container.classList.add('opacity-50');
      spinner.classList.remove('hidden');
      
      // Build URL with parameters
      const url = new URL(window.location);
      url.searchParams.set('category', category);
      url.searchParams.set('sort', sort);
      url.searchParams.set('page', page.toString());
      
      // Update URL without reloading
      window.history.pushState({}, '', url);
      
      // Fetch campaigns for this page
      fetch(`${BASE_URL}/campaigns/api/filter_campaigns.php?category=${encodeURIComponent(category)}&sort=${encodeURIComponent(sort)}&page=${page}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            container.innerHTML = data.html;
            currentPage = data.current_page;
            totalPages = data.total_pages;
            updatePaginationUI();
          } else {
            container.innerHTML = `
              <div class="col-span-full text-center py-16">
                <div class="w-20 h-20 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
                  <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Error loading campaigns</h3>
                <p class="text-slate-600">Please try again later.</p>
              </div>
            `;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          container.innerHTML = `
            <div class="col-span-full text-center py-16">
              <div class="w-20 h-20 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">Error loading campaigns</h3>
              <p class="text-slate-600">Please try again later.</p>
            </div>
          `;
        })
        .finally(() => {
          if (container) container.classList.remove('opacity-50');
          if (spinner) spinner.classList.add('hidden');
        });
    }

    function filterCampaigns() {
      goToPage(1);
    }
    
    function updatePaginationUI() {
      const paginationContainer = document.getElementById('pagination-container');
      if (!paginationContainer) return;
      
      // If only one page, hide pagination
      if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
      }
      
      // Build pagination HTML
      let html = `
        <nav class="flex items-center gap-2 flex-wrap">
          <!-- Previous Button -->
          <button onclick="goToPage(${currentPage - 1})" 
            class="px-3 py-2 sm:px-4 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:pointer-events-none"
            ${currentPage <= 1 ? 'disabled' : ''}>
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="hidden sm:inline ml-1.5">Previous</span>
          </button>
          
          <!-- Page Numbers -->
          <div class="flex items-center gap-1">
      `;
      
      for (let i = 1; i <= totalPages; i++) {
        // Show first, last, current, and 1 around current on mobile
        // Show first, last, current, and 2 around current on desktop
        const showOnMobile = (
          i === 1 || 
          i === totalPages || 
          i === currentPage || 
          i === currentPage - 1 || 
          i === currentPage + 1
        );
        const showOnDesktop = (
          i === 1 || 
          i === totalPages || 
          (i >= currentPage - 2 && i <= currentPage + 2)
        );
        
        if (showOnMobile || showOnDesktop) {
          // Add ellipsis if needed
          if (i > 1) {
            const prevPagesToShow = [1, currentPage - 2, currentPage - 1, currentPage, currentPage + 1, currentPage + 2, totalPages];
            if (!prevPagesToShow.includes(i - 1)) {
              html += `<span class="px-2 py-2 text-slate-400 sm:px-3">...</span>`;
            }
          }
          
          const hiddenOnMobileClass = !showOnMobile ? 'hidden sm:inline-flex' : '';
          
          html += `
            <button onclick="goToPage(${i})" 
              class="px-3 py-2 sm:px-4 rounded-xl text-sm font-semibold transition-colors ${i === currentPage ? 'bg-emerald-600 text-white' : 'text-slate-700 hover:bg-slate-50 border border-slate-200'} ${hiddenOnMobileClass}">
              ${i}
            </button>
          `;
        }
      }
      
      html += `
          </div>
          
          <!-- Next Button -->
          <button onclick="goToPage(${currentPage + 1})" 
            class="px-3 py-2 sm:px-4 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:pointer-events-none"
            ${currentPage >= totalPages ? 'disabled' : ''}>
            <span class="hidden sm:inline mr-1.5">Next</span>
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </nav>
      `;
      
      paginationContainer.innerHTML = html;
    }

    function clearFilters() {
      document.getElementById('category-filter').value = 'all';
      document.getElementById('sort-filter').value = '';
      filterCampaigns();
    }

    function removeFilter(type) {
      if (type === 'category') {
        document.getElementById('category-filter').value = 'all';
      } else if (type === 'sort') {
        document.getElementById('sort-filter').value = '';
      }
      filterCampaigns();
    }
  </script>
</body>
</html>

<?php include '../components/public-footer.php'; ?>
