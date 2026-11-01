<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Campaign.php';

$userCampaigns = new UserCampaigns();
$campaigns = $userCampaigns->getAllByUser(1);
$pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verified Fundraisers - CroFunding</title>
  <meta name="description" content="Explore verified fundraisers and find a cause to support on CroFunding.">
  <meta property="og:title" content="Verified Fundraisers - CroFunding">
  <meta property="og:description" content="Explore verified fundraisers and find a cause to support on CroFunding.">
  <meta property="og:image" content="<?php echo BASE_URL; ?>/assets/images/logo.svg">
  <meta property="og:url" content="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CroFunding">
  <meta name="twitter:card" content="summary">
  <meta name="twitter:title" content="Verified Fundraisers - CroFunding">
  <meta name="twitter:description" content="Explore verified fundraisers and find a cause to support on CroFunding.">
  <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] }
        }
      }
    };
  </script>
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
  <style>
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .progress-fill { transition: width 1s cubic-bezier(.4, 0, .2, 1); }
  </style>
</head>
<body class="bg-white text-slate-900 antialiased">
  <?php include __DIR__ . '/../../components/public-header.php'; ?>

  <main>
    <section class="bg-[#f7f6f3] px-4 pb-10 pt-24 sm:px-6 sm:pb-12 sm:pt-28 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="mx-auto max-w-3xl text-center">
          <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
            Support a cause that matters.
          </h1>
          <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600 sm:text-base">
            Browse fundraisers and find a story you want to support.
          </p>
        </div>

        <div class="mx-auto mt-7 grid max-w-xl grid-cols-2 gap-3 sm:mt-8 sm:gap-4">
          <div class="rounded-xl border border-[#e7e5e1] bg-white px-3 py-3 text-center sm:px-5 sm:py-3.5">
            <p class="text-xl font-extrabold tracking-tight text-emerald-700 sm:text-2xl"><?php echo number_format(count($campaigns)); ?></p>
            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">Fundraisers</p>
          </div>
          <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" class="flex items-center justify-center rounded-xl border border-[#e7e5e1] bg-white px-3 py-3 text-center text-xs font-semibold text-slate-700 transition hover:border-emerald-200 hover:text-emerald-700 sm:px-5 sm:py-3.5 sm:text-sm">
            Browse all campaigns
            <svg class="ml-2 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
          </a>
        </div>
      </div>
    </section>

    <section class="px-4 py-10 sm:px-6 sm:py-14 lg:px-8" aria-labelledby="campaigns-heading">
      <div class="mx-auto max-w-7xl">
        <div class="mb-6 sm:mb-8">
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Make a difference</p>
          <h2 id="campaigns-heading" class="mt-1.5 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Verified campaigns</h2>
        </div>

        <?php if (empty($campaigns)): ?>
          <div class="rounded-2xl border border-dashed border-slate-300 bg-[#f7f6f3] px-5 py-12 text-center sm:py-16">
            <h3 class="text-base font-semibold text-slate-900">No campaigns to show yet</h3>
            <p class="mt-1.5 text-sm text-slate-600">Browse all fundraisers to find a cause to support.</p>
            <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">
              Browse campaigns
            </a>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-2 gap-x-4 gap-y-6 sm:gap-x-5 sm:gap-y-7 md:grid-cols-4">
            <?php foreach ($campaigns as $index => $camp):
              $title = htmlspecialchars($camp['title'] ?? '', ENT_QUOTES, 'UTF-8');
              $category = htmlspecialchars(ucfirst($camp['category'] ?? ''), ENT_QUOTES, 'UTF-8');
              $current = (float) ($camp['current_amount'] ?? 0);
              $goal = (float) ($camp['goal_amount'] ?? 0);
              $donors = (int) ($camp['donor_count'] ?? 0);
              $id = (int) ($camp['id'] ?? 0);
              $percent = $goal > 0 ? max(0, min(($current / $goal) * 100, 100)) : 0;
              $imageUrl = !empty($camp['image_path'])
                ? BASE_URL . '/' . ltrim($camp['image_path'], '/')
                : BASE_URL . '/assets/images/default-campaign.jpg';
              $featured = $index === 0;
              $cardClass = $featured
                ? 'group col-span-2 min-w-0 md:row-span-2'
                : 'group min-w-0';
              $imageClass = $featured ? 'aspect-[1.55/1] md:aspect-[1.4/1]' : 'aspect-[1.55/1]';
              $titleClass = $featured ? 'text-base font-semibold sm:text-lg' : 'text-xs font-semibold sm:text-sm';
            ?>
              <article class="<?php echo $cardClass; ?>">
                <a href="<?php echo BASE_URL; ?>/campaigns/public-view.php?id=<?php echo $id; ?>" class="block">
                  <div class="relative <?php echo $imageClass; ?> overflow-hidden rounded-lg bg-[#f7f6f3]">
                    <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $title; ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="<?php echo $index < 3 ? 'eager' : 'lazy'; ?>" onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                    <span class="absolute left-2 top-2 inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-semibold text-emerald-800 shadow-sm sm:text-xs">
                      <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.025 12.025 0 002 12c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                      </svg>
                      Verified
                    </span>
                    <span class="absolute bottom-2 left-2 rounded bg-slate-950/75 px-2 py-1 text-[10px] font-semibold text-white sm:text-xs">
                      <?php echo number_format($donors); ?> donors
                    </span>
                  </div>
                  <div class="pt-2.5">
                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700"><?php echo $category; ?></p>
                    <h3 class="line-clamp-2 <?php echo $titleClass; ?> leading-snug tracking-tight text-slate-900 transition-colors group-hover:text-emerald-800">
                      <?php echo $title; ?>
                    </h3>
                    <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Fundraising progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) round($percent); ?>">
                      <div class="progress-fill h-full rounded-full bg-gradient-to-r from-lime-400 to-emerald-600" style="width: <?php echo round($percent); ?>%"></div>
                    </div>
                    <p class="mt-1.5 text-xs font-medium text-slate-700 sm:text-sm">
                      $<?php echo number_format($current, 0); ?> raised
                    </p>
                  </div>
                </a>
                <div class="mt-2 flex justify-end">
                  <button type="button" onclick="copyShareLink(<?php echo $id; ?>)" aria-label="Copy link to <?php echo $title; ?>" class="inline-flex min-h-8 items-center gap-1.5 rounded-lg px-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600/30">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-2M10 14L20 4m0 0h-6m6 0v6"/>
                    </svg>
                    Share
                  </button>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <div id="share-toast" class="pointer-events-none fixed bottom-4 right-4 z-50 translate-y-4 rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white opacity-0 shadow-lg transition duration-200" role="status" aria-live="polite">
    Link copied
  </div>

  <script>
    const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
    let shareToastTimeout;

    async function copyShareLink(campaignId) {
      const shareUrl = `${BASE_URL}/campaigns/public-view.php?id=${encodeURIComponent(campaignId)}`;
      await navigator.clipboard.writeText(shareUrl);

      const toast = document.getElementById('share-toast');
      toast.classList.remove('translate-y-4', 'opacity-0');
      toast.classList.add('translate-y-0', 'opacity-100');
      clearTimeout(shareToastTimeout);
      shareToastTimeout = setTimeout(() => {
        toast.classList.add('translate-y-4', 'opacity-0');
        toast.classList.remove('translate-y-0', 'opacity-100');
      }, 2000);
    }
  </script>

  <?php include __DIR__ . '/../../components/public-footer.php'; ?>
</body>
</html>
