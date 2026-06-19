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
      /* Fallback for other browsers */
      display: -moz-box;
      -moz-line-clamp: 2;
      -moz-box-orient: vertical;
      overflow: hidden;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      /* Fallback for other browsers */
      display: -moz-box;
      -moz-line-clamp: 3;
      -moz-box-orient: vertical;
      overflow: hidden;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    [x-cloak] {
      display: none !important;
    }
    /* Ensure card content layout is consistent */
    .campaign-card-content {
      min-height: 0; /* Important for flexbox with overflow */
    }
    /* Prevent text overflow on all elements */
    .campaign-card-content * {
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    /* Ensure progress bar is always visible */
    .progress-container {
      width: 100%;
      overflow: hidden;
    }
    /* Ensure buttons stay visible and don't shrink */
    .campaign-card-content button,
    .campaign-card-content a {
      flex-shrink: 0;
    }
    /* Ensure grid columns are properly sized */
    #campaigns-container > div {
      min-width: 0; /* Fix for flex overflow */
    }
  </style>
</head>
<body class="bg-slate-50 font-sans">

<?php include '../components/public-header.php'; ?>

  <main class="pt-20 pb-12 md:pt-28 md:pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 mb-4">Browse Fundraisers</h1>
        <p class="text-base sm:text-lg md:text-xl text-slate-600 max-w-2xl mx-auto">Discover and support impactful campaigns making a difference in communities worldwide</p>
      </div>

      <!-- Filters -->
      <div class="mb-10 bg-white rounded-2xl shadow-soft border border-slate-100 p-6">
        <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
          <div class="flex flex-wrap gap-4 w-full sm:w-auto">
            <select id="category-filter" class="rounded-xl border-slate-200 bg-slate-50 hover:bg-slate-100 transition-colors px-4 py-3 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" onchange="filterCampaigns()">
              <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>All Categories</option>
              <option value="medical" <?= $category === 'medical' ? 'selected' : '' ?>>Medical</option>
              <option value="education" <?= $category === 'education' ? 'selected' : '' ?>>Education</option>
              <option value="emergency" <?= $category === 'emergency' ? 'selected' : '' ?>>Emergency</option>
              <option value="community" <?= $category === 'community' ? 'selected' : '' ?>>Community</option>
            </select>
            <select id="sort-filter" class="rounded-xl border-slate-200 bg-slate-50 hover:bg-slate-100 transition-colors px-4 py-3 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" onchange="filterCampaigns()">
              <option value="" <?= $sort === '' ? 'selected' : '' ?>>Sort by...</option>
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
              <option value="most-funded" <?= $sort === 'most-funded' ? 'selected' : '' ?>>Most Funded</option>
              <option value="ending-soon" <?= $sort === 'ending-soon' ? 'selected' : '' ?>>Ending Soon</option>
            </select>
            <?php if ($category !== 'all' || $sort !== ''): ?>
            <button onclick="clearFilters()" class="px-4 py-3 text-sm font-semibold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 transition-colors">
              Clear Filters
            </button>
            <?php endif; ?>
          </div>
          <div class="flex items-center gap-2">
            <?php if ($category !== 'all' || $sort !== ''): ?>
            <div class="flex flex-wrap gap-2">
              <?php if ($category !== 'all'): ?>
              <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                Category: <?= ucfirst($category) ?>
                <button onclick="removeFilter('category')" class="ml-1.5 text-emerald-600 hover:text-emerald-800">×</button>
              </span>
              <?php endif; ?>
              <?php if ($sort !== ''): ?>
              <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Sort: <?= ucwords(str_replace('-', ' ', $sort)) ?>
                <button onclick="removeFilter('sort')" class="ml-1.5 text-blue-600 hover:text-blue-800">×</button>
              </span>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Loading Spinner -->
      <div id="loading-spinner" class="hidden text-center py-16">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-emerald-600 transition ease-in-out duration-150">
          <svg class="animate-spin -ml-1 mr-3 h-6 w-6 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Loading campaigns...
        </div>
      </div>

      <!-- Campaigns Grid -->
      <div id="campaigns-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (empty($campaigns)): ?>
          <div class="col-span-full text-center py-16">
            <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">No campaigns found</h3>
            <p class="text-slate-600">Try adjusting your filters or check back later.</p>
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
          <div class="group bg-white rounded-2xl border border-slate-100 shadow-soft hover:shadow-medium transition-all duration-300 overflow-hidden flex flex-col">
            <!-- Image -->
            <a href="public-view.php?id=<?= $id ?>" class="block relative h-56 overflow-hidden flex-shrink-0">
              <img src="<?= $imageUrl ?>" alt="<?= $title ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='<?= BASE_URL ?>/assets/images/default-campaign.jpg'" />
              <span class="absolute top-4 left-4 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 rounded-xl shadow">
                <?= $category ?>
              </span>
            </a>

            <!-- Content -->
            <div class="p-6 flex flex-col flex-1 campaign-card-content">
              <h3 class="text-lg font-semibold text-slate-900 mb-3 line-clamp-2 group-hover:text-emerald-700 transition-colors flex-shrink-0">
                <a href="public-view.php?id=<?= $id ?>">
                  <?= $title ?>
                </a>
              </h3>

              <div class="text-slate-600 text-sm line-clamp-3 mb-6 flex-grow min-h-[4.5rem]">
                <?= $description ?>
              </div>

              <!-- Progress Section -->
              <div class="mb-4 flex-shrink-0">
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden mb-3">
                  <div class="h-full bg-emerald-600 rounded-full" style="width: <?= round($percent) ?>%"></div>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-slate-600"><strong class="text-slate-900">$<?= number_format($current, 2) ?></strong> of $<?= number_format($goal, 2) ?></span>
                  <span class="font-semibold text-emerald-700"><?= round($percent) ?>%</span>
                </div>
              </div>

              <div class="flex items-center justify-between pt-4 border-t border-slate-100 flex-shrink-0">
                <div class="flex items-center text-slate-600 text-sm">
                  <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                  </svg>
                  <?= $donors ?> donors
                </div>
                <div class="flex items-center gap-2">
                  <button onclick="copyShareLink(<?= $id ?>, '<?= addslashes($title) ?>')" class="px-3 py-1.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors whitespace-nowrap">
                    Share
                  </button>
                  <a href="public-view.php?id=<?= $id ?>" class="px-4 py-1.5 text-xs font-semibold text-white rounded-xl bg-emerald-600 hover:bg-emerald-700 transition-colors whitespace-nowrap">
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
        <nav class="flex items-center gap-2 flex-wrap">
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
  <div id="share-toast" class="fixed bottom-6 right-6 bg-slate-900 text-white px-6 py-3 rounded-xl opacity-0 pointer-events-none transition-all duration-300 z-50 shadow-large">
    <div class="flex items-center">
      <svg class="w-5 h-5 text-emerald-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
