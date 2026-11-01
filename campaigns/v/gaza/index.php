<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/CompaigsInGaza.php';

$campaignId = 10;
$campaignsInGaza = new CampaignsInGaza();
$camp = $campaignsInGaza->getById($campaignId);


// Pre-process values
$title = (string)($camp['title'] ?? '');
$description = (string)($camp['description'] ?? '');
$category = (string)($camp['category'] ?? '');
$current = max(0, (float)($camp['current_amount'] ?? 0));
$goal = (float)($camp['goal_amount'] ?? 0);
$donors = (int)($camp['donor_count'] ?? 0);
$id = (int)($camp['id'] ?? 0);
$percent = $goal > 0 ? max(0, min(($current / $goal) * 100, 100)) : 0;
$imageUrl = !empty($camp['image_path']) ? BASE_URL . '/' . ltrim($camp['image_path'], '/') : BASE_URL . '/assets/images/default-campaign.jpg';
$pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '');
$descriptionPreview = trim(preg_replace('/\s+/', ' ', strip_tags($description)));

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> – Support Gaza Fundraisers - CroFunding</title>
  
  <!-- Open Graph Meta Tags for Social Media Sharing -->
  <meta property="og:title" content="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> – Support Gaza Fundraisers">
  <meta property="og:description" content="<?php echo htmlspecialchars(substr($descriptionPreview, 0, 160), ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image" content="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CroFunding">
  
  <!-- Twitter Card Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> – Support Gaza Fundraisers">
  <meta name="twitter:description" content="<?php echo htmlspecialchars(substr($descriptionPreview, 0, 160), ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>">
  
  <!-- Additional Meta Tags -->
  <meta name="description" content="<?php echo htmlspecialchars(substr($descriptionPreview, 0, 160), ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="keywords" content="Gaza, fundraising, <?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>, crowdfunding, donations, humanitarian aid">
  
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
        },
      },
    };
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
  </style>
</head>
<body class="bg-white font-sans text-slate-900 antialiased">

<?php include '../../../components/public-header.php'; ?>

  <main class="bg-[#f7f6f3] pb-12 pt-24 sm:pb-16 sm:pt-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <header class="mx-auto max-w-3xl text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Gaza fundraiser</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
          Support a campaign in Gaza.
        </h1>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600 sm:text-base">
          Explore this fundraiser and help its organizer reach their goal.
        </p>
      </header>

      <section class="mx-auto mt-8 max-w-5xl overflow-hidden rounded-2xl border border-[#e7e5e1] bg-white shadow-sm sm:mt-10 lg:grid lg:grid-cols-[1.05fr_0.95fr]" aria-label="Featured Gaza fundraiser">
        <div class="relative min-h-56 bg-slate-100 sm:min-h-72 lg:min-h-[30rem]">
          <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" class="absolute inset-0 h-full w-full object-cover" loading="eager" onerror="this.src='<?= BASE_URL ?>/assets/images/default-campaign.jpg'" />
        </div>

        <div class="flex flex-col p-5 sm:p-7 lg:p-9" x-data="{ expanded: false }">
          <?php if ($category !== ''): ?>
          <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700"><?= htmlspecialchars(ucfirst($category), ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
          <h2 class="mt-2 text-xl font-bold leading-snug tracking-tight text-slate-950 sm:text-2xl">
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
          </h2>

          <div class="mt-4 text-sm leading-6 text-slate-600">
            <p :class="{ 'line-clamp-3': !expanded }"><?= nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) ?></p>
            <?php if (strlen($description) > 180): ?>
            <button type="button" @click="expanded = !expanded" class="mt-2 inline-flex items-center gap-1 font-semibold text-emerald-700 hover:text-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2">
              <span x-text="expanded ? 'Show less' : 'Read more'"></span>
              <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <?php endif; ?>
          </div>

          <div class="mt-6">
            <div class="flex items-end justify-between gap-3">
              <div>
                <p class="text-xs text-slate-500">Raised</p>
                <p class="mt-0.5 text-2xl font-extrabold tracking-tight text-slate-950">$<?= number_format($current, 2) ?></p>
              </div>
              <p class="pb-1 text-right text-xs text-slate-500">Goal <span class="font-semibold text-slate-800">$<?= number_format($goal, 2) ?></span></p>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Fundraising progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= round($percent) ?>">
              <div class="h-full rounded-full bg-emerald-600 transition-all" style="width: <?= round($percent) ?>%"></div>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs font-medium">
              <span class="text-emerald-700"><?= round($percent) ?>% funded</span>
              <span class="text-slate-500"><?= number_format($donors) ?> donors</span>
            </div>
          </div>

          <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row">
            <button type="button" onclick="copyShareLink(<?= $id ?>)" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2">
              Share fundraiser
            </button>
            <a href="../public-view.php?id=<?= $id ?>" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2">
              Donate
            </a>
          </div>
        </div>
      </section>
    </div>
  </main>

  <!-- Share Toast -->
  <div id="share-toast" role="status" aria-live="polite" class="fixed bottom-4 right-4 z-50 translate-y-full rounded-xl bg-slate-900 px-5 py-3 text-sm font-medium text-white opacity-0 shadow-lg transition-all duration-300">
    Link copied to clipboard!
  </div>

  <script>
    async function copyShareLink(campaignId) {
      const url = `${BASE_URL}/campaigns/public-view.php?id=${campaignId}`;
      const toast = document.getElementById('share-toast');
      try {
        await navigator.clipboard.writeText(url);
        toast.textContent = 'Link copied to clipboard!';
      } catch (error) {
        console.error('Unable to copy campaign link:', error);
        toast.textContent = 'Unable to copy link. Please try again.';
      }

      toast.classList.remove('translate-y-full', 'opacity-0');
      toast.classList.add('translate-y-0', 'opacity-100');
      setTimeout(() => {
        toast.classList.add('translate-y-full', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
      }, 2500);
    }
  </script>
</body>
</html>

<?php include '../../../components/public-footer.php'; ?>
