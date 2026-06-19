<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/CompaigsInGaza.php';

$campaignId = 10;
$campaignsInGaza = new CampaignsInGaza();
$camp = $campaignsInGaza->getById($campaignId);


// Pre-process values
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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($title); ?> – Support Gaza Fundraisers - CroFunding</title>
  
  <!-- Open Graph Meta Tags for Social Media Sharing -->
  <meta property="og:title" content="<?php echo htmlspecialchars($title); ?> – Support Gaza Fundraisers">
  <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($description), 0, 160)); ?>">
  <meta property="og:image" content="<?php echo $imageUrl; ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CroFunding">
  
  <!-- Twitter Card Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($title); ?> – Support Gaza Fundraisers">
  <meta name="twitter:description" content="<?php echo htmlspecialchars(substr(strip_tags($description), 0, 160)); ?>">
  <meta name="twitter:image" content="<?php echo $imageUrl; ?>">
  
  <!-- Additional Meta Tags -->
  <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($description), 0, 160)); ?>">
  <meta name="keywords" content="Gaza, fundraising, <?php echo htmlspecialchars($category); ?>, crowdfunding, donations, humanitarian aid">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet" />
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
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
  </style>
</head>
<body class="bg-gray-50 font-sans">

<?php include '../../../components/public-header.php'; ?>

  <main class="pt-20 pb-12 md:pt-28 md:pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Header -->
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-500 p-4 md:p-8 mb-8 md:mb-12 animate-gradient">
        <div class="relative z-10 text-center text-white">
          <h1 class="text-2xl md:text-4xl lg:text-5xl font-bold mb-3 md:mb-4">Support Gaza Fundraisers</h1>
          <p class="text-base md:text-xl lg:text-2xl text-emerald-50">Support the people of Gaza through this verified and trusted fundraising campaign.</p>
        </div>
        <div class="absolute inset-0 bg-black opacity-10"></div>
      </div>

      <!-- Single Campaign Card -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
        <div class="group bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden max-w-full w-full" data-aos="fade-up" x-data="{ expanded: false }">
          <div class="flex flex-row sm:flex-col">
            <!-- Image -->
            <div class="w-1/3 h-36 md:h-40 sm:w-full sm:h-48 overflow-hidden relative">
              <img src="<?= $imageUrl ?>" alt="<?= $title ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" onerror="this.src='<?= BASE_URL ?>/assets/images/default-campaign.jpg'" />
           
            </div>

            <!-- Content -->
            <div class="w-2/3 sm:w-full p-3 md:p-6 flex flex-col justify-between">
              <h3 class="text-sm md:text-lg font-semibold text-gray-900 mb-1 group-hover:text-emerald-600 leading-snug line-clamp-2" title="<?= $title ?>">
                <?= $title ?>
              </h3>

              <div class="relative mb-3 text-xs md:text-sm text-gray-600 flex-1">
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

              <div class="mb-3">
                <div class="w-full h-1.5 md:h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full" style="width: <?= round($percent) ?>%"></div>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mt-2 text-[10px] md:text-xs text-gray-700 gap-1">
                  <span><strong>$<?= number_format($current, 2) ?></strong> <span class="text-gray-500">of $<?= number_format($goal, 2) ?></span></span>
                  <div class="flex items-center gap-1 md:gap-2 text-emerald-600 font-semibold">
                    <span><?= round($percent) ?>%</span>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600"><?= $donors ?> donors</span>
                  </div>
                </div>
              </div>

              <div class="flex justify-between pt-2 border-t border-gray-100">
                <div class="flex space-x-2">
                  <button onclick="copyShareLink(<?= $id ?>, '<?= addslashes($title) ?>')" class="px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-300 rounded hover:bg-gray-100">Share</button>
                  <a href="../public-view.php?id=<?= $id ?>" class="px-4 py-1.5 text-xs font-medium text-white rounded bg-emerald-600 hover:bg-emerald-700 transition">Donate</a>
                </div>
              </div>
            </div>
          </div>
        </div>
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
    AOS.init({ duration: 800, once: true });

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
  </script>
</body>
</html>

<?php include '../../../components/public-footer.php'; ?>
