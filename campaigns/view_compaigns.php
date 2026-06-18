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

// Get campaigns with filters
try {
    $result = $campaign->getBrowseCampaigns($category, $sort, $page, 1000); // Large number to show all campaigns
    $campaigns = $result['campaigns'] ?? [];
} catch (Exception $e) {
    error_log("Error loading campaigns: " . $e->getMessage());
    $campaigns = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Browse Fundraisers - CroFunding</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
          animation: {
            gradient: 'gradient 8s linear infinite',
          },
          keyframes: {
            gradient: {
              '0%, 100%': {
                'background-size': '200% 200%',
                'background-position': 'left center',
              },
              '50%': {
                'background-size': '200% 200%',
                'background-position': 'right center',
              },
            },
          },
        },
      },
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    [x-cloak] {
      display: none !important;
    }
    .loading {
      opacity: 0.6;
      pointer-events: none;
    }
  </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../components/public-header.php'; ?>

  <main class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-500 p-8 mb-12 animate-gradient">
        <div class="relative z-10 text-center text-white">
          <h1 class="text-4xl md:text-5xl font-bold mb-4">Browse Fundraisers</h1>
          <p class="text-xl md:text-2xl text-emerald-50">Support amazing causes and make a difference today</p>
        </div>
        <div class="absolute inset-0 bg-black opacity-10"></div>
      </div>

      <!-- Filters -->
      <div class="mb-8 bg-white rounded-xl shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-4 justify-between items-center">
          <div class="flex flex-wrap gap-4 w-full sm:w-auto">
            <select id="category-filter" class="rounded-lg border-gray-300 bg-gray-50 hover:bg-gray-100 transition-colors px-4 py-2 focus:ring-emerald-500 focus:border-emerald-500" onchange="filterCampaigns()">
              <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>All Categories</option>
              <option value="medical" <?= $category === 'medical' ? 'selected' : '' ?>>Medical</option>
              <option value="education" <?= $category === 'education' ? 'selected' : '' ?>>Education</option>
              <option value="emergency" <?= $category === 'emergency' ? 'selected' : '' ?>>Emergency</option>
              <option value="community" <?= $category === 'community' ? 'selected' : '' ?>>Community</option>
            </select>
            <select id="sort-filter" class="rounded-lg border-gray-300 bg-gray-50 hover:bg-gray-100 transition-colors px-4 py-2 focus:ring-emerald-500 focus:border-emerald-500" onchange="filterCampaigns()">
              <option value="" <?= $sort === '' ? 'selected' : '' ?>>Sort by...</option>
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
              <option value="most-funded" <?= $sort === 'most-funded' ? 'selected' : '' ?>>Most Funded</option>
              <option value="ending-soon" <?= $sort === 'ending-soon' ? 'selected' : '' ?>>Ending Soon</option>
            </select>
            <?php if ($category !== 'all' || $sort !== ''): ?>
            <button onclick="clearFilters()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
              Clear Filters
            </button>
            <?php endif; ?>
          </div>
          <div class="flex flex-col sm:flex-row items-center gap-2">
            <div id="results-count" class="text-sm text-gray-600">
              <?= count($campaigns) ?> campaigns found
            </div>
            <?php if ($category !== 'all' || $sort !== ''): ?>
            <div class="flex flex-wrap gap-2">
              <?php if ($category !== 'all'): ?>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                Category: <?= ucfirst($category) ?>
                <button onclick="removeFilter('category')" class="ml-1 text-emerald-600 hover:text-emerald-800">×</button>
              </span>
              <?php endif; ?>
              <?php if ($sort !== ''): ?>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Sort: <?= ucwords(str_replace('-', ' ', $sort)) ?>
                <button onclick="removeFilter('sort')" class="ml-1 text-blue-600 hover:text-blue-800">×</button>
              </span>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Loading Spinner -->
      <div id="loading-spinner" class="hidden text-center py-8">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-emerald-600 transition ease-in-out duration-150">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Loading campaigns...
        </div>
      </div>

      <!-- Campaigns Grid -->
      <div id="campaigns-container" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <?php if (empty($campaigns)): ?>
          <div class="col-span-2 text-center py-12">
            <div class="text-gray-500">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No campaigns found</h3>
              <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or check back later.</p>
            </div>
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
          <div class="group bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden max-w-md mx-auto" x-data="{ expanded: false }">
            <div class="flex flex-row sm:flex-col">
              <!-- Image -->
              <div class="w-1/3 h-32 sm:w-full sm:h-48 overflow-hidden relative">
                <a href="public-view.php?id=<?= $id ?>" class="block w-full h-full cursor-pointer">
                  <img src="<?= $imageUrl ?>" alt="<?= $title ?>" class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105" onerror="this.src='<?= BASE_URL ?>/assets/images/default-campaign.jpg'" />
                </a>
                <span class="absolute top-3 right-3 px-2 py-1 text-xs font-semibold text-white bg-emerald-500 rounded-full shadow">
                  <?= $category ?>
                </span>
              </div>

              <!-- Content -->
              <div class="w-3/4 sm:w-full p-3 sm:p-6 flex flex-col justify-between">
                <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-1 group-hover:text-emerald-600 leading-snug line-clamp-2" title="<?= $title ?>">
                  <?= $title ?>
                </h3>

                <div class="relative mb-3 text-xs text-gray-600 flex-1">
                  <p :class="{ 'line-clamp-3': !expanded }"><?= $description ?></p>
                  <?php if (strlen($camp['description']) > 140): ?>
                  <button @click="expanded = !expanded" class="mt-1 text-emerald-600 hover:text-emerald-700 text-xs font-medium flex items-center">
                    <span x-text="expanded ? 'Show Less' : 'Read More'"></span>
                    <svg class="w-4 h-4 ml-1 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <?php endif; ?>
                </div>

                <!-- Updated Progress Section with Donors Inline -->
                <div class="mb-3">
                  <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full" style="width: <?= round($percent) ?>%"></div>
                  </div>
                  <div class="flex justify-between items-center mt-2 text-xs text-gray-700">
                    <span><strong>$<?= number_format($current, 2) ?></strong> <span class="text-gray-500">of $<?= number_format($goal, 2) ?></span></span>
                    <div class="flex items-center gap-2 text-emerald-600 font-semibold">
                      <span><?= round($percent) ?>%</span>
                      <span class="text-gray-400">|</span>
                      <span class="text-gray-600"><?= $donors ?> donors</span>
                    </div>
                  </div>
                </div>

                <div class="flex justify-between pt-2 border-t border-gray-100">
                  <div class="flex space-x-2">
                    <button onclick="copyShareLink(<?= $id ?>, '<?= addslashes($title) ?>')" class="px-3 py-1 text-xs font-medium text-gray-700 border border-gray-300 rounded hover:bg-gray-100">Share</button>
                    <a href="public-view.php?id=<?= $id ?>" class="px-4 py-1.5 text-xs font-medium text-white rounded bg-emerald-600 hover:bg-emerald-700 transition">Donate</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- Share Toast -->
  <div id="share-toast" class="fixed bottom-4 right-4 bg-gray-900 text-white px-6 py-3 rounded-xl shadow-lg transform translate-y-full opacity-0 transition-all duration-300 z-50">
    <div class="flex items-center">
      <svg class="w-5 h-5 text-emerald-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      Link copied to clipboard!
    </div>
  </div>

  <script>
    // AOS animations removed for smoother scrolling
    
    // Ensure DOM is loaded before running scripts
    document.addEventListener('DOMContentLoaded', function() {
      // All JavaScript functions are now available
    });

    function copyShareLink(campaignId, title) {
      const url = `${BASE_URL}/public-view.php?id=${campaignId}`;
      navigator.clipboard.writeText(url).then(() => {
        const toast = document.getElementById('share-toast');
        toast.classList.remove('translate-y-full', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
        setTimeout(() => {
          toast.classList.add('translate-y-full', 'opacity-0');
          toast.classList.remove('translate-y-0', 'opacity-100');
        }, 2000);
      });
    }

    function filterCampaigns() {
      const category = document.getElementById('category-filter').value;
      const sort = document.getElementById('sort-filter').value;
      
      // Show loading state
      const container = document.getElementById('campaigns-container');
      const spinner = document.getElementById('loading-spinner');
      const resultsCount = document.getElementById('results-count');
      
      // Check if elements exist before using them
      if (!container || !spinner || !resultsCount) {
        console.error('Required elements not found');
        return;
      }
      
      container.classList.add('loading');
      spinner.classList.remove('hidden');
      resultsCount.textContent = 'Loading...';
      
      // Build URL with parameters
      const url = new URL(window.location);
      url.searchParams.set('category', category);
      url.searchParams.set('sort', sort);
      url.searchParams.set('page', '1'); // Reset to first page when filtering
      
      // Update URL without reloading
      window.history.pushState({}, '', url);
      
      // Fetch filtered campaigns
      fetch(`${BASE_URL}/campaigns/api/filter_campaigns.php?category=${category}&sort=${sort}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            container.innerHTML = data.html;
            if (resultsCount) resultsCount.textContent = `${data.count} campaigns found`;
            
                         // AOS refresh removed for smoother performance
          } else {
            container.innerHTML = `
              <div class="col-span-2 text-center py-12">
                <div class="text-gray-500">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                  <h3 class="mt-2 text-sm font-medium text-gray-900">Error loading campaigns</h3>
                  <p class="mt-1 text-sm text-gray-500">Please try again later.</p>
                </div>
              </div>
            `;
            if (resultsCount) resultsCount.textContent = 'Error loading campaigns';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          container.innerHTML = `
            <div class="col-span-2 text-center py-12">
              <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Error loading campaigns</h3>
                <p class="mt-1 text-sm text-gray-500">Please try again later.</p>
              </div>
            </div>
          `;
          if (resultsCount) resultsCount.textContent = 'Error loading campaigns';
        })
        .finally(() => {
          if (container) container.classList.remove('loading');
          if (spinner) spinner.classList.add('hidden');
        });
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
