<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Campaign.php';

// Use the new UserCampaigns class to fetch campaigns for user ID 1
$userCampaigns = new UserCampaigns();
$campaigns = $userCampaigns->getAllByUser(1);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Browse Verified Fundraisers - CroFunding</title>
  
  <!-- Open Graph Meta Tags for Social Media Sharing -->
  <meta property="og:title" content="Browse Verified Fundraisers - CroFunding">
  <meta property="og:description" content="Discover verified and trusted fundraising campaigns. Support real causes with confidence on our secure platform.">
  <meta property="og:image" content="<?php echo BASE_URL; ?>/assets/images/logo.svg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CroFunding">
  
  <!-- Twitter Card Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Browse Verified Fundraisers - CroFunding">
  <meta name="twitter:description" content="Discover verified and trusted fundraising campaigns. Support real causes with confidence on our secure platform.">
  <meta name="twitter:image" content="<?php echo BASE_URL; ?>/assets/images/logo.svg">
  
  <!-- Additional Meta Tags -->
  <meta name="description" content="Discover verified and trusted fundraising campaigns. Support real causes with confidence on our secure platform.">
  <meta name="keywords" content="verified fundraisers, crowdfunding, donations, trusted campaigns, secure fundraising">
  
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
    .hero-orb {
      position: absolute;
      border-radius: 9999px;
      filter: blur(60px);
      opacity: 0.45;
      pointer-events: none;
    }
  </style>
</head>
<body class="bg-slate-50 font-sans text-slate-900">

<?php include '../../components/public-header.php'; ?>

  <main class="relative overflow-hidden pt-20 pb-14 md:pt-28 md:pb-20">
    <div class="hero-orb -top-16 left-[-4rem] h-56 w-56 bg-emerald-200"></div>
    <div class="hero-orb top-20 right-[-3rem] h-64 w-64 bg-teal-200"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
      <section class="relative overflow-hidden rounded-[2rem] border border-white/70 bg-gradient-to-br from-slate-950 via-emerald-950 to-teal-900 px-6 py-8 text-white shadow-2xl sm:px-8 md:px-10 md:py-10 lg:px-12">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(250,204,21,0.18),_transparent_22%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.22),_transparent_34%)]"></div>
        <div class="relative grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
          <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.28em] text-emerald-100">
              <svg class="h-4 w-4 text-yellow-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="#facc15"/>
                <path d="M9 12l2 2l4-4" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
              </svg>
              Verified fundraisers
            </div>
            <h1 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
              Support trusted campaigns with confidence.
            </h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-emerald-50 sm:text-base">
              Every fundraiser listed here has passed an additional review step. Discover causes that have been vetted for trust, transparency, and real community impact.
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100/80">Verified campaigns</p>
              <p class="mt-2 text-2xl font-bold text-white"><?= number_format(count($campaigns)) ?></p>
              <p class="mt-1 text-sm text-emerald-50/80">Reviewed and listed</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100/80">Trust first</p>
              <p class="mt-2 text-2xl font-bold text-white">100%</p>
              <p class="mt-1 text-sm text-emerald-50/80">Manual verification focus</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur-sm">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100/80">Giving</p>
              <p class="mt-2 text-2xl font-bold text-white">Secure</p>
              <p class="mt-1 text-sm text-emerald-50/80">Donate with confidence</p>
            </div>
          </div>
        </div>
      </section>

      <section class="-mt-6 relative z-10 mx-auto max-w-4xl rounded-[1.75rem] border border-slate-200/70 bg-white/95 p-6 text-center shadow-xl shadow-slate-200/60 backdrop-blur sm:p-8">
        <p class="text-lg font-semibold tracking-tight text-slate-900 sm:text-xl">Your support truly matters.</p>
        <p class="mt-3 text-sm leading-7 text-slate-600 sm:text-base">
          Every verified campaign here represents a real story, a real person, and a real need. These fundraisers are reviewed so your generosity can go further with more confidence and more clarity.
        </p>
      </section>

      <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2 xl:gap-8">
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
        <div class="group overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-200/70" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>" x-data="{ expanded: false }">
          <div class="flex flex-col md:flex-row">
            <div class="relative h-60 overflow-hidden bg-slate-100 md:h-auto md:w-[44%]">
              <img src="<?= $imageUrl ?>" alt="<?= $title ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='<?= BASE_URL ?>/assets/images/default-campaign.jpg'" />
              <div class="absolute inset-x-0 top-0 flex items-start justify-between p-4">
                <span class="inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-800 shadow-sm backdrop-blur-sm">
                  <svg class="h-3.5 w-3.5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="#facc15"/>
                    <path d="M9 12l2 2l4-4" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                  </svg>
                  Verified
                </span>
                <span class="inline-flex items-center rounded-full bg-slate-950/75 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur-sm">
                  <?= round($percent) ?>% funded
                </span>
              </div>
              <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-slate-950/70 to-transparent"></div>
            </div>

            <div class="flex flex-1 flex-col p-5 sm:p-6">
              <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700"><?= $category ?></span>
                <span class="inline-flex items-center gap-1">
                  <svg class="h-3.5 w-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927C9.469 1.675 10.531 1.675 10.951 2.927l1.07 3.207a1 1 0 00.95.69h3.374c1.317 0 1.86 1.687.795 2.454l-2.73 1.967a1 1 0 00-.364 1.118l1.07 3.207c.42 1.252-.566 2.19-1.63 1.423l-2.73-1.967a1 1 0 00-1.172 0l-2.73 1.967c-1.064.767-2.05-.171-1.63-1.423l1.07-3.207a1 1 0 00-.364-1.118L2.68 9.278c-1.065-.767-.522-2.454.795-2.454h3.374a1 1 0 00.95-.69l1.07-3.207z"/>
                  </svg>
                  Trusted listing
                </span>
              </div>

              <h3 class="mt-4 text-xl font-semibold tracking-tight text-slate-900 transition-colors group-hover:text-emerald-700 line-clamp-2" title="<?= $title ?>">
                <?= $title ?>
              </h3>

              <div class="mt-4 flex-1 text-sm leading-7 text-slate-600">
                <p :class="{ 'line-clamp-3': !expanded }"><?= $description ?></p>
                <?php if (strlen($camp['description']) > 140): ?>
                <button @click="expanded = !expanded" class="mt-2 inline-flex items-center text-xs font-semibold text-emerald-600 transition hover:text-emerald-700">
                  <span x-text="expanded ? 'Show less' : 'Read more'"></span>
                  <svg class="ml-1 h-4 w-4 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <?php endif; ?>
              </div>

              <div class="mt-6 rounded-2xl bg-slate-50 p-4">
                <div class="mb-3 flex items-center justify-between text-sm">
                  <span class="text-slate-600">Raised</span>
                  <span class="font-semibold text-emerald-700"><?= round($percent) ?>%</span>
                </div>
                <div class="mb-3 h-2.5 overflow-hidden rounded-full bg-slate-200">
                  <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500" style="width: <?= round($percent) ?>%"></div>
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

              <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                <div class="flex items-center text-sm text-slate-600">
                  <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                  </svg>
                  <?= $donors ?> donors
                </div>
                <div class="flex items-center gap-2">
                  <button onclick="copyShareLink(<?= $id ?>, '<?= addslashes($title) ?>')" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Share</button>
                  <a href="../public-view.php?id=<?= $id ?>" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">Donate</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
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

    function filterCampaigns(category) {
      alert("Filtering by: " + category);
    }

    function sortCampaigns(method) {
      alert("Sorting by: " + method);
    }
  </script>
</body>
</html>

<?php include '../../components/public-footer.php'; ?>
