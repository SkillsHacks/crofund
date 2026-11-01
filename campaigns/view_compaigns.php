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
      <section class="rounded-[1.75rem] border border-slate-200 bg-white px-6 py-7 shadow-sm sm:px-8 sm:py-9">
        <div class="max-w-3xl">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Find a cause</p>
          <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
            Fundraisers worth supporting.
          </h1>
          <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
            Browse active campaigns and find a cause that matters to you.
          </p>
        </div>
      </section>

      <section class="mt-5 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
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

      <div id="campaigns-container" class="mt-8 grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-4 md:gap-x-5 md:gap-y-6">
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
            $title = htmlspecialchars($camp['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $current = (float)($camp['current_amount'] ?? 0);
            $goal = (float)($camp['goal_amount'] ?? 0);
            $donors = (int)($camp['donor_count'] ?? 0);
            $id = (int)($camp['id'] ?? 0);
            $percent = $goal > 0 ? min(($current / $goal) * 100, 100) : 0;
            $imageUrl = !empty($camp['image_path']) ? BASE_URL . '/' . ltrim($camp['image_path'], '/') : BASE_URL . '/assets/images/default-campaign.jpg';
            $donorLabel = $donors > 0 ? number_format($donors) : '0';
          ?>
          <div class="group min-w-0 <?= $index === 0 ? 'col-span-2 md:row-span-2' : '' ?>">
            <a href="public-view.php?id=<?= $id ?>" class="block h-full min-w-0">
              <div class="relative <?= $index === 0 ? 'aspect-[1.55/1] md:aspect-[1.4/1]' : 'aspect-[1.55/1]' ?> overflow-hidden rounded-lg bg-slate-100">
                <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $title ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='<?= BASE_URL ?>/assets/images/default-campaign.jpg'" />
                <span class="absolute bottom-2 left-2 rounded bg-slate-950/75 px-2 py-1 text-[10px] font-semibold text-white sm:text-xs">
                  <?= $donorLabel ?> donations
                </span>
              </div>
              <div class="<?= $index === 0 ? 'pt-2.5' : 'pt-2' ?>">
                <h3 class="line-clamp-2 <?= $index === 0 ? 'text-base font-semibold sm:text-lg' : 'text-xs font-semibold sm:text-sm' ?> leading-snug tracking-tight text-slate-900 transition-colors group-hover:text-emerald-800">
                  <?= $title ?>
                </h3>
                <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Fundraising progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= round($percent) ?>">
                  <div class="h-full rounded-full bg-gradient-to-r from-lime-400 to-emerald-600" style="width: <?= round($percent) ?>%"></div>
                </div>
              </div>
            </a>
            <div class="mt-1.5 flex items-center justify-between gap-2">
              <p class="text-xs font-medium text-slate-700 sm:text-sm">$<?= number_format($current) ?> raised</p>
              <button type="button" onclick="copyShareLink(<?= $id ?>)" class="text-[10px] font-semibold text-slate-500 transition hover:text-emerald-700 sm:text-xs">Share</button>
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

    function copyShareLink(campaignId) {
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
