<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/Campaign.php';

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/campaigns/view_compaigns.php");
    exit();
}

$urlId = $_GET['id'];
if (strpos($urlId, '/') !== false) {
    $urlParts = explode('/', $urlId);
    $campaignId = intval($urlParts[0]);
} else {
    $campaignId = intval($urlId);
}

$campaign = new Campaign();
$details  = $campaign->getCampaignById($campaignId);

$page       = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage    = 10;
$offset     = ($page - 1) * $perPage;
$totalDonors = $campaign->getCampaignDonorCount($campaignId);
$totalPages  = ceil($totalDonors / $perPage);
$donors      = $campaign->getCampaignDonors($campaignId, $perPage, $offset);

if (!$details) {
    $_SESSION['error'] = "Campaign not found";
    header("Location: " . BASE_URL . "/campaigns/view_compaigns.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$percent  = ($details['goal_amount'] > 0)
    ? min(100, ($details['current_amount'] / $details['goal_amount']) * 100)
    : 0;

$daysLeft = null;
if (!empty($details['end_date'])) {
    $endDate  = new DateTime($details['end_date']);
    $now      = new DateTime();
    $daysLeft = $endDate > $now ? (int)$now->diff($endDate)->days : 0;
}

$creatorInitial = !empty($details['creator_name']) ? strtoupper(substr($details['creator_name'], 0, 1)) : 'O';
$creatorName    = !empty($details['creator_name']) ? htmlspecialchars($details['creator_name']) : 'Organizer';

$ogImageUrl = !empty($details['image_path'])
    ? BASE_URL . '/' . ltrim($details['image_path'], '/')
    : BASE_URL . '/assets/images/default-campaign.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($details['title']); ?> – CroFunding</title>

  <meta property="og:title"       content="<?php echo htmlspecialchars($details['title']); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($details['description']), 0, 160)); ?>">
  <meta property="og:image"       content="<?php echo $ogImageUrl; ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:url"         content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
  <meta property="og:type"        content="website">
  <meta property="og:site_name"   content="CroFunding">
  <meta name="twitter:card"       content="summary_large_image">
  <meta name="twitter:title"      content="<?php echo htmlspecialchars($details['title']); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars(substr(strip_tags($details['description']), 0, 160)); ?>">
  <meta name="twitter:image"      content="<?php echo $ogImageUrl; ?>">
  <meta name="description"        content="<?php echo htmlspecialchars(substr(strip_tags($details['description']), 0, 160)); ?>">
  <meta name="robots"             content="index, follow">

  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png" type="image/x-icon">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            emerald: { 50:'#ecfdf5',100:'#d1fae5',200:'#a7f3d0',300:'#6ee7b7',400:'#34d399',500:'#10b981',600:'#059669',700:'#047857',800:'#065f46',900:'#064e3b' }
          },
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          boxShadow: {
            'card'  : '0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.06)',
            'card-hover': '0 4px 6px rgba(0,0,0,.07), 0 12px 32px rgba(0,0,0,.1)',
            'cta'   : '0 4px 14px rgba(5,150,105,.45)',
          }
        }
      }
    };
  </script>

  <style>
    *{ box-sizing:border-box; }
    html{ scroll-behavior:smooth; }
    body{ font-family:'Inter',sans-serif; background:#f8fafc; color:#0f172a; }

    /* ── Hero ── */
    .hero-wrap{ position:relative; width:100%; background:#0f172a; overflow:hidden; max-height:480px; }
    .hero-wrap img{ width:100%; height:480px; object-fit:cover; opacity:.88; display:block; }
    .hero-gradient{ position:absolute; inset:0; background:linear-gradient(to bottom, rgba(15,23,42,.1) 0%, rgba(15,23,42,.55) 100%); }

    /* ── Cards ── */
    .card{ background:#fff; border-radius:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.06); border:1px solid #f1f5f9; }
    .card:hover{ box-shadow:0 4px 6px rgba(0,0,0,.07),0 12px 32px rgba(0,0,0,.1); }

    /* ── Progress bar ── */
    .progress-track{ height:10px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
    .progress-fill{ height:100%; border-radius:999px; background:linear-gradient(90deg,#059669,#10b981); transition:width 1.2s cubic-bezier(.4,0,.2,1); }

    /* ── Donor avatar ── */
    .donor-avatar{ width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.9rem; color:#fff; flex-shrink:0; }

    /* ── Social share ── */
    .share-btn{ display:flex; align-items:center; justify-content:center; gap:.4rem; border-radius:.75rem; padding:.55rem; font-size:.78rem; font-weight:600; transition:all .18s; border:1.5px solid; }

    /* ── Update timeline ── */
    .timeline-line{ position:absolute; left:18px; top:44px; bottom:0; width:2px; background:#e2e8f0; }
    .timeline-dot{ width:12px; height:12px; border-radius:50%; background:#059669; border:2px solid #fff; box-shadow:0 0 0 3px #d1fae5; flex-shrink:0; margin-top:4px; }

    /* ── Sticky sidebar ── */
    @media(min-width:1024px){ .sticky-sidebar{ position:sticky; top:88px; } }

    /* ── Donation modal ── */
    .modal-overlay{ display:none; position:fixed; inset:0; z-index:1000; background:rgba(15,23,42,.55); backdrop-filter:blur(8px); }
    .modal-overlay.active{ display:flex; align-items:center; justify-content:center; padding:1rem; overflow-y:auto; }
    .modal-box{ background:#fff; border-radius:1.5rem; box-shadow:0 25px 50px -12px rgba(0,0,0,.28); max-width:560px; width:100%; max-height:92vh; overflow-y:auto; animation:slideUp .35s cubic-bezier(.16,1,.3,1); }
    @keyframes slideUp{ from{ opacity:0; transform:translateY(24px) scale(.97); } to{ opacity:1; transform:translateY(0) scale(1); } }

    /* Form styles (used inside ajax-loaded form) */
    .form-section{ margin-bottom:1.4rem; }
    .form-section-title{ display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; }
    .input-group{ position:relative; }
    .input-icon{ position:absolute; left:.875rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.85rem; }
    .input-with-icon{ padding-left:2.4rem !important; }
    .donation-amount-btn.active{ border-color:#059669 !important; background:#059669 !important; color:#fff !important; }
    .gateway-option{ transition:all .18s; cursor:pointer; }

    /* ── Mobile bottom bar ── */
    .mobile-cta-bar{ display:none; }
    @media(max-width:1023px){
      .mobile-cta-bar{ display:flex; position:fixed; bottom:0; left:0; right:0; z-index:200; background:#fff; border-top:1px solid #e2e8f0; padding:.75rem 1rem; gap:.75rem; box-shadow:0 -4px 16px rgba(0,0,0,.08); }
      body{ padding-bottom:72px; }
    }

    /* ── Badge ── */
    .badge{ display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .75rem; border-radius:999px; font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }

    /* ── Verified check ── */
    .verified-badge{ background:#ecfdf5; color:#059669; }
    .status-active{ background:#ecfdf5; color:#047857; }
    .status-completed{ background:#eff6ff; color:#1d4ed8; }
    .status-other{ background:#f1f5f9; color:#475569; }
  </style>
</head>
<body>

<?php include __DIR__ . '/../components/public-header.php'; ?>

<!-- ═══════════════════  HERO  ═══════════════════ -->
<div class="hero-wrap">
  <img
    src="<?php echo !empty($details['image_path']) ? BASE_URL.'/'.$details['image_path'] : BASE_URL.'/assets/images/default-campaign.jpg'; ?>"
    alt="<?php echo htmlspecialchars($details['title']); ?>"
    onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
  <div class="hero-gradient"></div>
</div>

<!-- ═══════════════════  MAIN  ═══════════════════ -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">

  <!-- Breadcrumb -->
  <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6" aria-label="Breadcrumb">
    <a href="<?php echo BASE_URL; ?>" class="hover:text-emerald-600 transition-colors">Home</a>
    <i class="fas fa-chevron-right text-[9px]"></i>
    <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php" class="hover:text-emerald-600 transition-colors">Campaigns</a>
    <i class="fas fa-chevron-right text-[9px]"></i>
    <span class="text-slate-800 font-medium truncate max-w-[200px]"><?php echo htmlspecialchars($details['title']); ?></span>
  </nav>

  <!-- Campaign title block -->
  <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
    <div class="flex-1 min-w-0">
      <div class="flex flex-wrap items-center gap-2 mb-3">
        <span class="badge <?php
          if ($details['status']==='active') echo 'status-active';
          elseif ($details['status']==='completed') echo 'status-completed';
          else echo 'status-other';
        ?>">
          <span class="w-1.5 h-1.5 rounded-full bg-current inline-block"></span>
          <?php echo ucfirst($details['status']); ?>
        </span>
        <span class="badge" style="background:#f0fdf4;color:#166534;">
          <i class="fas fa-tag text-[9px]"></i>
          <?php echo htmlspecialchars(ucfirst($details['category'])); ?>
        </span>
        <span class="badge verified-badge">
          <i class="fas fa-shield-alt text-[9px]"></i>
          Verified Campaign
        </span>
      </div>
      <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-slate-900 leading-tight mb-2">
        <?php echo htmlspecialchars($details['title']); ?>
      </h1>
      <p class="text-sm text-slate-500">
        <i class="far fa-calendar-alt mr-1"></i>
        Started <?php echo date('F j, Y', strtotime($details['created_at'])); ?>
      </p>
    </div>
    <button onclick="copyShareLink()"
      class="flex items-center gap-2 px-4 py-2.5 border-2 border-slate-200 text-slate-700 rounded-xl hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all font-semibold text-sm shrink-0 self-start">
      <i class="fas fa-share-alt"></i> Share
    </button>
  </div>

  <!-- ── Two-column grid ── -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- ════ LEFT COLUMN ════ -->
    <div class="lg:col-span-2 space-y-8 order-2 lg:order-1">

      <!-- Progress stats card (mobile shows here, desktop in sidebar) -->
      <div class="card p-6 lg:hidden">
        <?php $raisedFmt = number_format($details['current_amount'], 0); $goalFmt = number_format($details['goal_amount'], 0); ?>
        <div class="mb-1 flex items-end justify-between">
          <span class="text-3xl font-extrabold text-slate-900">$<?php echo $raisedFmt; ?></span>
          <span class="text-sm text-slate-500 mb-1">of $<?php echo $goalFmt; ?> goal</span>
        </div>
        <div class="progress-track my-3">
          <div class="progress-fill" style="width:<?php echo round($percent); ?>%"></div>
        </div>
        <div class="flex items-center justify-between text-sm text-slate-600">
          <span><strong class="text-slate-900"><?php echo (int)$details['donor_count']; ?></strong> donors</span>
          <span><strong class="text-slate-900"><?php echo round($percent); ?>%</strong> funded</span>
          <?php if ($daysLeft !== null): ?>
            <span><strong class="text-slate-900"><?php echo $daysLeft; ?></strong> days left</span>
          <?php endif; ?>
        </div>
        <button onclick="openDonationModal()"
          class="mt-5 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-xl shadow-cta transition-all active:scale-[.98] text-base">
          <i class="fas fa-heart mr-2"></i> Donate Now
        </button>
      </div>

      <!-- About -->
      <div class="card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-9 h-9 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
            <i class="fas fa-info-circle text-emerald-700 text-sm"></i>
          </div>
          <h2 class="text-xl font-bold text-slate-900">About this campaign</h2>
        </div>

        <div x-data="{ expanded: false }">
          <div class="text-slate-700 leading-relaxed text-base prose max-w-none"
               :class="expanded ? '' : 'line-clamp-6'">
            <?php echo nl2br(htmlspecialchars($details['description'])); ?>
          </div>
          <button @click="expanded = !expanded"
            class="mt-4 flex items-center gap-2 text-emerald-700 hover:text-emerald-800 font-semibold text-sm transition-colors">
            <span x-text="expanded ? 'Show less' : 'Read full story'"></span>
            <svg class="w-4 h-4 transition-transform duration-300" :class="{'rotate-180': expanded}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Updates -->
      <div class="card p-6 md:p-8">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
              <i class="fas fa-bullhorn text-blue-700 text-sm"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-900">Campaign Updates</h2>
          </div>
          <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $details['user_id']): ?>
            <button onclick="showUpdateForm()"
              class="flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-colors">
              <i class="fas fa-plus text-xs"></i> Add Update
            </button>
          <?php endif; ?>
        </div>

        <?php
        $updates = $campaign->getCampaignUpdates($campaignId);
        if (!empty($updates)):
        ?>
        <div class="space-y-0">
          <?php foreach ($updates as $i => $update): ?>
          <div class="relative flex gap-4 pb-8 <?php echo $i === count($updates)-1 ? '' : ''; ?>">
            <?php if ($i < count($updates)-1): ?>
              <div class="timeline-line"></div>
            <?php endif; ?>
            <div class="timeline-dot mt-1 shrink-0 relative z-10"></div>
            <div class="flex-1 min-w-0 bg-slate-50 rounded-xl p-5 border border-slate-100 hover:border-emerald-200 transition-colors">
              <div class="flex items-start justify-between gap-3 mb-2">
                <h3 class="font-bold text-slate-900 text-base"><?php echo htmlspecialchars($update['title']); ?></h3>
                <span class="text-xs text-slate-500 shrink-0 bg-white border border-slate-200 px-2.5 py-1 rounded-full">
                  <?php echo date('M j, Y', strtotime($update['created_at'])); ?>
                </span>
              </div>
              <p class="text-sm text-slate-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($update['content'])); ?></p>
              <?php if (!empty($update['image_path'])): ?>
                <img src="<?php echo BASE_URL.'/'.$update['image_path']; ?>"
                     alt="Update image" class="mt-3 w-full max-w-sm rounded-xl border border-slate-200">
              <?php endif; ?>
              <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $details['user_id']): ?>
                <button onclick="editUpdate(<?php echo $update['id']; ?>)"
                  class="mt-3 text-xs text-slate-400 hover:text-emerald-600 transition-colors font-medium flex items-center gap-1">
                  <i class="fas fa-edit"></i> Edit
                </button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">
          <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-bullhorn text-slate-300 text-xl"></i>
          </div>
          <p class="font-semibold text-slate-700 mb-1">No updates yet</p>
          <p class="text-sm text-slate-500">Campaign organizers will post updates here</p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Add Update Form -->
      <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $details['user_id']): ?>
      <div id="update-form" class="hidden card p-6 md:p-8">
        <h4 class="text-lg font-bold mb-5 text-slate-900 flex items-center gap-2">
          <i class="fas fa-edit text-emerald-700"></i> Add Campaign Update
        </h4>
        <form id="campaign-update-form" class="space-y-4">
          <input type="hidden" name="campaign_id" value="<?php echo $details['id']; ?>">
          <input type="hidden" name="csrf_token"  value="<?php echo $_SESSION['csrf_token']; ?>">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Update Title</label>
            <input type="text" name="update_title" required
              class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-sm">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Update Content</label>
            <textarea name="update_content" rows="5" required
              class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-sm resize-none"
              placeholder="Share your latest progress or news..."></textarea>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Image <span class="font-normal text-slate-400">(Optional)</span></label>
            <input type="file" name="update_image" accept="image/*"
              class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl bg-slate-50 text-sm transition-all file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-semibold">
          </div>
          <div class="flex gap-3 pt-1">
            <button type="submit"
              class="flex-1 px-6 py-3 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-all font-semibold text-sm">
              <i class="fas fa-paper-plane mr-2"></i>Post Update
            </button>
            <button type="button" onclick="hideUpdateForm()"
              class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all font-semibold text-sm">
              Cancel
            </button>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- Recent Donors -->
      <div class="card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-9 h-9 bg-pink-100 rounded-xl flex items-center justify-center shrink-0">
            <i class="fas fa-heart text-pink-600 text-sm"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-slate-900">Recent Donors</h2>
            <?php if ($totalDonors > 0): ?>
              <p class="text-xs text-slate-500"><?php echo $totalDonors; ?> people have supported this campaign</p>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($donors)): ?>
        <div class="space-y-3">
          <?php
          $avatarGradients = [
            'from-emerald-400 to-teal-500',
            'from-blue-400 to-indigo-500',
            'from-violet-400 to-purple-500',
            'from-amber-400 to-orange-500',
            'from-rose-400 to-pink-500',
            'from-cyan-400 to-blue-500',
          ];
          foreach ($donors as $index => $donor):
            $isAnon = !isset($donor['donor_name']) || empty($donor['donor_name']) || (isset($donor['anonymous']) && $donor['anonymous'] == 1);
            $donorInitial = $isAnon ? 'A' : strtoupper(substr($donor['donor_name'], 0, 1));
            $donorName    = $isAnon ? 'Anonymous Donor' : htmlspecialchars($donor['donor_name']);
            $grad         = $avatarGradients[$index % count($avatarGradients)];

            $donatedAt = new DateTime($donor['donated_at']);
            $now       = new DateTime();
            $diff      = $now->diff($donatedAt);
            if ($diff->y > 0)      $timeAgo = $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
            elseif ($diff->m > 0)  $timeAgo = $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
            elseif ($diff->d > 0)  $timeAgo = $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
            elseif ($diff->h > 0)  $timeAgo = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
            elseif ($diff->i > 0)  $timeAgo = $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
            else $timeAgo = 'Just now';
          ?>
          <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors">
            <div class="donor-avatar bg-gradient-to-br <?php echo $grad; ?>">
              <?php echo $donorInitial; ?>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-slate-900 text-sm truncate"><?php echo $donorName; ?></p>
              <p class="text-xs text-slate-500"><?php echo $timeAgo; ?></p>
            </div>
            <div class="text-right shrink-0">
              <p class="font-bold text-emerald-700 text-base">$<?php echo number_format($donor['amount'], 2); ?></p>
              <?php if ($isAnon): ?>
                <p class="text-[10px] text-slate-400 mt-0.5">Anonymous</p>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-8 flex items-center justify-center gap-2 flex-wrap">
          <?php if ($page > 1): ?>
            <a href="?id=<?php echo $campaignId; ?>&page=<?php echo $page-1; ?>"
               class="px-4 py-2 text-sm font-semibold text-emerald-700 bg-white border-2 border-emerald-200 rounded-xl hover:bg-emerald-50 transition-all">
              <i class="fas fa-chevron-left mr-1"></i> Prev
            </a>
          <?php endif; ?>
          <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
            <a href="?id=<?php echo $campaignId; ?>&page=<?php echo $i; ?>"
               class="px-4 py-2 text-sm font-semibold rounded-xl transition-all border-2 <?php echo $i==$page ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-300 hover:bg-slate-50'; ?>">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
            <a href="?id=<?php echo $campaignId; ?>&page=<?php echo $page+1; ?>"
               class="px-4 py-2 text-sm font-semibold text-emerald-700 bg-white border-2 border-emerald-200 rounded-xl hover:bg-emerald-50 transition-all">
              Next <i class="fas fa-chevron-right ml-1"></i>
            </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">
          <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-heart text-slate-300 text-xl"></i>
          </div>
          <p class="font-semibold text-slate-700 mb-1">No donations yet</p>
          <p class="text-sm text-slate-500">Be the first to make a difference!</p>
          <button onclick="openDonationModal()"
            class="mt-4 px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold text-sm hover:bg-emerald-700 transition-all">
            Donate Now
          </button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Browse other campaigns -->
      <div class="rounded-2xl overflow-hidden"
           style="background:linear-gradient(135deg,#059669 0%,#0d9488 100%); padding:2rem;">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
            <i class="fas fa-compass text-white"></i>
          </div>
          <h3 class="text-lg font-bold text-white">Discover more campaigns</h3>
        </div>
        <p class="text-emerald-100 mb-5 text-sm leading-relaxed">
          Browse hundreds of verified fundraisers and find causes you care about.
        </p>
        <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-emerald-700 font-bold rounded-xl hover:bg-emerald-50 transition-all text-sm">
          <i class="fas fa-th-large"></i> Browse Campaigns
        </a>
      </div>

    </div><!-- /left column -->

    <!-- ════ RIGHT COLUMN (sticky sidebar) ════ -->
    <div class="lg:col-span-1 order-1 lg:order-2">
      <div class="sticky-sidebar space-y-5">

        <!-- Donation widget -->
        <div class="card p-6">

          <!-- Progress -->
          <?php $raisedFmt = number_format($details['current_amount'], 0); $goalFmt = number_format($details['goal_amount'], 0); ?>
          <div class="mb-1">
            <span class="text-3xl font-extrabold text-slate-900">$<?php echo $raisedFmt; ?></span>
            <span class="text-sm text-slate-500 ml-1">raised</span>
          </div>
          <p class="text-sm text-slate-500 mb-3">of <strong class="text-slate-700">$<?php echo $goalFmt; ?></strong> goal</p>
          <div class="progress-track mb-4">
            <div class="progress-fill" id="sidebarProgress" style="width:0%"
                 data-target="<?php echo round($percent); ?>"></div>
          </div>

          <!-- Stats row -->
          <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="text-center bg-slate-50 rounded-xl p-3 border border-slate-100">
              <p class="text-lg font-extrabold text-slate-900"><?php echo (int)$details['donor_count']; ?></p>
              <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Donors</p>
            </div>
            <div class="text-center bg-emerald-50 rounded-xl p-3 border border-emerald-100">
              <p class="text-lg font-extrabold text-emerald-700"><?php echo round($percent); ?>%</p>
              <p class="text-[10px] font-semibold text-emerald-600 uppercase tracking-wide">Funded</p>
            </div>
            <div class="text-center bg-slate-50 rounded-xl p-3 border border-slate-100">
              <?php if ($daysLeft !== null): ?>
                <p class="text-lg font-extrabold text-slate-900"><?php echo $daysLeft; ?></p>
                <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Days Left</p>
              <?php else: ?>
                <p class="text-lg font-extrabold text-slate-900"><i class="fas fa-infinity text-base"></i></p>
                <p class="text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Ongoing</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Donate CTA -->
          <button onclick="openDonationModal()"
            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 rounded-xl text-base transition-all active:scale-[.98] mb-3"
            style="box-shadow:0 4px 14px rgba(5,150,105,.4)">
            <i class="fas fa-heart mr-2"></i> Donate Now
          </button>

          <!-- Social share -->
          <div class="grid grid-cols-4 gap-2">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(BASE_URL.'/campaigns/public-view.php?id='.$details['id']); ?>"
               target="_blank" rel="noopener"
               class="share-btn border-blue-200 text-blue-600 hover:bg-blue-50 flex-col py-2.5">
              <i class="fab fa-facebook-f text-sm"></i>
              <span class="text-[9px] font-bold mt-0.5">Share</span>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(BASE_URL.'/campaigns/public-view.php?id='.$details['id']); ?>&text=<?php echo urlencode('Support: '.$details['title']); ?>"
               target="_blank" rel="noopener"
               class="share-btn border-sky-200 text-sky-500 hover:bg-sky-50 flex-col py-2.5">
              <i class="fab fa-x-twitter text-sm"></i>
              <span class="text-[9px] font-bold mt-0.5">Tweet</span>
            </a>
            <a href="https://wa.me/?text=<?php echo urlencode('Support this campaign: '.BASE_URL.'/campaigns/public-view.php?id='.$details['id']); ?>"
               target="_blank" rel="noopener"
               class="share-btn border-green-200 text-green-600 hover:bg-green-50 flex-col py-2.5">
              <i class="fab fa-whatsapp text-sm"></i>
              <span class="text-[9px] font-bold mt-0.5">WhatsApp</span>
            </a>
            <button onclick="copyShareLink()"
               class="share-btn border-slate-200 text-slate-600 hover:bg-slate-50 flex-col py-2.5 w-full">
              <i class="fas fa-link text-sm"></i>
              <span class="text-[9px] font-bold mt-0.5">Copy</span>
            </button>
          </div>
        </div>

        <!-- Creator card -->
        <div class="card p-5">
          <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Campaign Organizer</p>
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-bold text-lg shrink-0">
              <?php echo $creatorInitial; ?>
            </div>
            <div>
              <p class="font-bold text-slate-900 text-sm"><?php echo $creatorName; ?></p>
              <p class="text-xs text-slate-500">Campaign Organizer</p>
              <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full mt-1">
                <i class="fas fa-check-circle text-[9px]"></i> Verified
              </span>
            </div>
          </div>
        </div>

        <!-- Trust badges -->
        <div class="card p-5">
          <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Trust & Safety</p>
          <div class="space-y-2.5">
            <div class="flex items-center gap-2.5 text-xs text-slate-600">
              <div class="w-7 h-7 bg-emerald-50 rounded-lg flex items-center justify-center shrink-0">
                <i class="fas fa-lock text-emerald-600 text-[11px]"></i>
              </div>
              <span>All payments are encrypted &amp; secure</span>
            </div>
            <div class="flex items-center gap-2.5 text-xs text-slate-600">
              <div class="w-7 h-7 bg-blue-50 rounded-lg flex items-center justify-center shrink-0">
                <i class="fas fa-shield-alt text-blue-600 text-[11px]"></i>
              </div>
              <span>Campaign verified by CroFunding team</span>
            </div>
            <div class="flex items-center gap-2.5 text-xs text-slate-600">
              <div class="w-7 h-7 bg-purple-50 rounded-lg flex items-center justify-center shrink-0">
                <i class="fas fa-undo text-purple-600 text-[11px]"></i>
              </div>
              <span>Donation protected by our guarantee</span>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /right column -->

  </div><!-- /grid -->
</main>

<!-- ═══════════════  MOBILE BOTTOM BAR  ═══════════════ -->
<div class="mobile-cta-bar">
  <div class="flex-1">
    <p class="text-xs text-slate-500 leading-none">Raised so far</p>
    <p class="text-base font-extrabold text-slate-900">$<?php echo number_format($details['current_amount'],0); ?></p>
  </div>
  <button onclick="openDonationModal()"
    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl text-sm transition-all">
    <i class="fas fa-heart mr-1.5"></i> Donate Now
  </button>
  <button onclick="copyShareLink()"
    class="w-11 h-11 flex items-center justify-center border-2 border-slate-200 rounded-xl text-slate-600 hover:border-emerald-400 hover:text-emerald-700 transition-all shrink-0">
    <i class="fas fa-share-alt text-sm"></i>
  </button>
</div>

<!-- ═══════════════  DONATION MODAL  ═══════════════ -->
<div id="donationModal" class="modal-overlay">
  <div class="modal-box">
    <!-- Modal header -->
    <div class="sticky top-0 bg-white/95 backdrop-blur-sm border-b border-slate-100 px-6 py-4 flex items-center justify-between z-10 rounded-t-2xl">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow">
          <i class="fas fa-heart text-white text-sm"></i>
        </div>
        <div>
          <h2 class="text-lg font-bold text-slate-900 leading-tight">Make a Donation</h2>
          <p class="text-xs text-slate-400">Secure &amp; encrypted payment</p>
        </div>
      </div>
      <button onclick="closeDonationModal()"
        class="w-9 h-9 flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all rounded-full">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <!-- Modal body -->
    <div class="px-6 py-6">
      <div id="donationFormLoader" class="text-center py-14">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-emerald-200 border-t-emerald-600"></div>
        <p class="mt-4 text-sm text-slate-500">Loading secure payment form…</p>
      </div>
      <div id="donationFormContainer" class="hidden"></div>
    </div>
  </div>
</div>

<script>
const URL_BASE       = '<?php echo BASE_URL; ?>';
const campaignId     = <?php echo $details['id']; ?>;
const donationMinimum = 5;

// ── Animate progress bar on load ──
document.addEventListener('DOMContentLoaded', function () {
  const bar = document.getElementById('sidebarProgress');
  if (bar) {
    setTimeout(() => { bar.style.width = bar.dataset.target + '%'; }, 300);
  }
  checkForMessages();
});

// ── Donation Modal ──
let paypalConfig       = null;
let paypalButtonsLoaded   = false;
let paypalButtonsInstance = null;

function openDonationModal() {
  const modal         = document.getElementById('donationModal');
  const formContainer = document.getElementById('donationFormContainer');
  const formLoader    = document.getElementById('donationFormLoader');

  modal.classList.add('active');
  document.body.style.overflow = 'hidden';

  if (paypalButtonsInstance) {
    paypalButtonsInstance.close();
    paypalButtonsInstance = null;
    paypalButtonsLoaded   = false;
  }

  formContainer.classList.add('hidden');
  formLoader.classList.remove('hidden');
  formContainer.innerHTML = '';

  $.ajax({
    url: URL_BASE + '/campaigns/get-donation-form.php?id=' + campaignId,
    type: 'GET',
    dataType: 'json',
    success: function (response) {
      if (response.success) {
        paypalConfig = { clientId: response.paypal_client_id, mode: response.paypal_mode };
        formLoader.classList.add('hidden');
        formContainer.innerHTML = response.html;
        formContainer.classList.remove('hidden');
        initializeDonationForm();
      } else {
        showError('Failed to load donation form. Please try again.');
      }
    },
    error: function (xhr) {
      formLoader.classList.add('hidden');
      let msg = 'Failed to load donation form. Please try again.';
      try { const r = JSON.parse(xhr.responseText); if (r.error) msg = r.error; } catch(e) {}
      formContainer.innerHTML = '<div class="text-center py-8"><i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-3"></i><p class="font-semibold text-red-700 text-sm">' + msg + '</p></div>';
      formContainer.classList.remove('hidden');
    }
  });
}

function closeDonationModal() {
  document.getElementById('donationModal').classList.remove('active');
  document.body.style.overflow = '';
  paypalButtonsLoaded = false;
}

document.addEventListener('click', function (e) {
  const modal = document.getElementById('donationModal');
  if (e.target === modal) closeDonationModal();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeDonationModal();
});

// ── Form handlers ──
function initializeDonationForm() {
  $('.gateway-option').off('click').on('click', function () {
    $('.gateway-option').removeClass('border-emerald-500 bg-emerald-50').addClass('border-slate-200 bg-white');
    $('.gateway-option .w-3').removeClass('bg-emerald-500');
    $('input[name="payment_gateway"]').prop('checked', false);

    $(this).addClass('border-emerald-500 bg-emerald-50').removeClass('border-slate-200 bg-white');
    $(this).find('.w-3').addClass('bg-emerald-500');
    $(this).find('input[type="radio"]').prop('checked', true);

    const gw = $(this).data('gateway');
    if (gw === 'paypal') {
      $('#buttonDonation').addClass('hidden');
      $('#paypal-button-container').removeClass('hidden');
      loadPayPalButtons();
    } else {
      $('#buttonDonation').removeClass('hidden');
      $('#paypal-button-container').addClass('hidden');
    }
  });

  $('.donation-amount-btn').off('click').on('click', function () {
    $('.donation-amount-btn').removeClass('active');
    $(this).addClass('active');
    $('#onlyNumber').val($(this).data('amount'));
  });

  $('#onlyNumber').off('input').on('input', function () {
    $('.donation-amount-btn').removeClass('active');
  });

  $('#formDonation').off('submit').on('submit', function (e) {
    e.preventDefault();
    const amount = $('#onlyNumber').val();
    const name   = $('#cardholder-name').val();
    const email  = $('#cardholder-email').val();
    const gw     = $('input[name="payment_gateway"]:checked').val();

    if (!validateBasicFields(amount, name, email)) return false;

    if (gw === 'stripe') {
      $('#buttonDonation').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing…');
      $.ajax({
        url: URL_BASE + '/stripe/checkout-session.php',
        type: 'POST',
        data: {
          campaign_id: campaignId, amount, full_name: name, email,
          country: $('#country').val(), postal_code: $('#postal_code').val(),
          comment: $('#comment').val(), anonymous: $('input[name="anonymous"]').val() || '0',
          csrf_token: $('input[name="csrf_token"]').val()
        },
        dataType: 'json',
        success: function (r) {
          if (r.success && r.url) { window.location.href = r.url; }
          else {
            showError(r.error || 'Failed to create checkout session.');
            $('#buttonDonation').prop('disabled', false).html('<i class="fas fa-heart mr-2"></i> Complete Donation');
          }
        },
        error: function () {
          showError('An error occurred. Please try again.');
          $('#buttonDonation').prop('disabled', false).html('<i class="fas fa-heart mr-2"></i> Complete Donation');
        }
      });
    }
    return false;
  });

  $('#buttonDonation').removeClass('hidden');
  $('#paypal-button-container').addClass('hidden');
}

// ── PayPal ──
function renderPayPalButtons() {
  paypalButtonsInstance = paypal.Buttons({
    createOrder: function (data, actions) {
      const amount = $('#onlyNumber').val();
      const name   = $('#cardholder-name').val();
      const email  = $('#cardholder-email').val();
      if (!validateBasicFields(amount, name, email)) return Promise.reject('Validation failed');
      return $.ajax({
        url: URL_BASE + '/paypal/create-order.php', type: 'POST',
        data: {
          campaign_id: campaignId, amount, full_name: name, email,
          country: $('#country').val(), postal_code: $('#postal_code').val(),
          comment: $('#comment').val(), anonymous: $('input[name="anonymous"]').val() || '0',
          csrf_token: $('input[name="csrf_token"]').val()
        },
        dataType: 'json'
      }).then(function (r) {
        if (r.success) return r.orderId;
        throw new Error(r.error || 'Failed to create PayPal order');
      });
    },
    onApprove: function (data, actions) {
      // Capture happens server-side in success.php
      window.location.href = URL_BASE + '/paypal/success.php?token=' + data.orderID;
    },
    onError: function (err) {
      showError('An error occurred with PayPal. Please try again.');
      console.error('PayPal error:', err);
    }
  });
  paypalButtonsInstance.render('#paypal-button-container');
  paypalButtonsLoaded = true;
}

function loadPayPalButtons() {
  if (paypalButtonsLoaded) return;
  if (!paypalConfig || !paypalConfig.clientId) {
    showError('PayPal is not configured correctly.');
    return;
  }
  if (window.paypal) { renderPayPalButtons(); return; }
  const s = document.createElement('script');
  s.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(paypalConfig.clientId)}&currency=USD&intent=capture`;
  s.onload = function () { if (window.paypal) renderPayPalButtons(); };
  document.body.appendChild(s);
}

// ── Validation ──
function validateBasicFields(amount, name, email) {
  if (!amount || parseFloat(amount) < donationMinimum) {
    showError('Please enter a valid donation amount (minimum $' + donationMinimum + ')');
    return false;
  }
  if (!name || !name.trim()) { showError('Please enter your full name'); return false; }
  if (!email || !/^\S+@\S+\.\S+$/.test(email)) { showError('Please enter a valid email address'); return false; }
  return true;
}

function showError(msg) {
  $('#showErrorsDonation').html('<li><i class="fas fa-times-circle mr-1.5"></i>' + msg + '</li>');
  $('#errorDonation').removeClass('hidden');
  $('#showDonationMessages').addClass('hidden');
}

// ── Campaign updates ──
function showUpdateForm()  { document.getElementById('update-form').classList.remove('hidden'); }
function hideUpdateForm()  {
  document.getElementById('update-form').classList.add('hidden');
  document.getElementById('campaign-update-form').reset();
}
function editUpdate(id)    { alert('Edit functionality coming soon!'); }

// ── Share ──
const campaignShareUrl = '<?php echo BASE_URL . "/campaigns/public-view.php?id=" . $details['id']; ?>';

function copyShareLink() {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(campaignShareUrl).then(showCopySuccess).catch(() => fallbackCopy(campaignShareUrl));
  } else {
    fallbackCopy(campaignShareUrl);
  }
}

function fallbackCopy(text) {
  const el = document.createElement('textarea');
  el.value = text;
  el.style.cssText = 'position:fixed;top:0;left:0;opacity:0;';
  document.body.appendChild(el);
  el.focus(); el.select();
  try { document.execCommand('copy'); showCopySuccess(); } catch(e) { showCopyError(); }
  document.body.removeChild(el);
}

function showCopySuccess() {
  const el = document.createElement('div');
  el.className = 'fixed bottom-24 lg:bottom-6 right-4 lg:right-6 bg-slate-900 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-xl z-[300] flex items-center gap-2 transition-all';
  el.innerHTML = '<i class="fas fa-check text-emerald-400"></i> Link copied!';
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 2800);
}

function showCopyError() {
  const el = document.createElement('div');
  el.className = 'fixed bottom-24 lg:bottom-6 right-4 lg:right-6 bg-red-600 text-white text-sm font-semibold px-5 py-3 rounded-xl shadow-xl z-[300]';
  el.innerHTML = '<i class="fas fa-times mr-2"></i> Failed to copy';
  document.body.appendChild(el);
  setTimeout(() => el.remove(), 2800);
}

// ── Campaign update form submit ──
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('campaign-update-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      fetch('<?php echo BASE_URL; ?>/campaigns/add-update.php', { method:'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            Swal.fire({ title:'Posted!', text:'Update posted successfully.', icon:'success', confirmButtonColor:'#059669' }).then(() => location.reload());
          } else {
            Swal.fire({ title:'Error', text: data.error || 'Failed to post update', icon:'error', confirmButtonColor:'#dc2626' });
          }
        })
        .catch(() => Swal.fire({ title:'Error', text:'An error occurred.', icon:'error', confirmButtonColor:'#dc2626' }));
    });
  }
});

// ── Session flash messages ──
function checkForMessages() {
  <?php if (isset($_SESSION['success'])): ?>
    Swal.fire({ title:'Success!', text:'<?php echo addslashes($_SESSION['success']); ?>', icon:'success', confirmButtonColor:'#059669' });
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
    Swal.fire({ title:'Error', text:'<?php echo addslashes($_SESSION['error']); ?>', icon:'error', confirmButtonColor:'#dc2626' });
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
  <?php if (isset($_SESSION['donation_success'])): ?>
    Swal.fire({ title:'Thank You!', text:'<?php echo addslashes($_SESSION['donation_success']); ?>', icon:'success', confirmButtonColor:'#059669', timer:5000 });
    <?php unset($_SESSION['donation_success']); ?>
  <?php endif; ?>
  <?php if (isset($_SESSION['donation_error'])): ?>
    Swal.fire({ title:'Payment Issue', text:'<?php echo addslashes($_SESSION['donation_error']); ?>', icon:'error', confirmButtonColor:'#dc2626' });
    <?php unset($_SESSION['donation_error']); ?>
  <?php endif; ?>
  const p = new URLSearchParams(window.location.search);
  if (p.get('payment') === 'success') {
    Swal.fire({ title:'Thank You!', text:'Your donation was processed successfully.', icon:'success', confirmButtonColor:'#059669' });
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}
</script>

<?php include __DIR__ . '/../components/public-footer.php'; ?>
</body>
</html>
