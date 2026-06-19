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
$details = $campaign->getCampaignById($campaignId);

// Pagination for donors
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get total donor count first
$totalDonors = $campaign->getCampaignDonorCount($campaignId);
$totalPages = ceil($totalDonors / $perPage);

// Get donors for current page
$donors = $campaign->getCampaignDonors($campaignId, $perPage, $offset);

if (!$details) {
    $_SESSION['error'] = "Campaign not found";
    header("Location: " . BASE_URL . "/campaigns/view_compaigns.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle payment completion from Dodo Payments
if (isset($_GET['payment']) && $_GET['payment'] === 'success' && isset($_SESSION['pending_donation'])) {
    require_once __DIR__ . '/../config/dodo_config.php';
    
    try {
        // Get the session_id from the URL or session
        $sessionId = $_GET['session_id'] ?? $_SESSION['dodo_session_id'] ?? null;
        
        if ($sessionId) {
            // Initialize Dodo Payments client
            $client = getDodoClient();
            
            // Retrieve checkout session status
            $sessionStatus = $client->checkoutSessions->retrieve($sessionId);
            
            // Check if payment was successful
            if ($sessionStatus && isset($sessionStatus->paymentStatus)) {
                $paymentStatus = is_object($sessionStatus) 
                    ? ($sessionStatus->paymentStatus ?? null)
                    : ($sessionStatus['paymentStatus'] ?? null);
                
                // Payment statuses: 'succeeded', 'processing', 'failed', etc.
                if ($paymentStatus === 'succeeded' || $paymentStatus === 'completed') {
                    $pendingDonation = $_SESSION['pending_donation'];
                    
                    // Get payment ID if available
                    $paymentId = is_object($sessionStatus) 
                        ? ($sessionStatus->paymentID ?? null)
                        : ($sessionStatus['paymentID'] ?? null);
                    
                    // Prepare donation data
                    $donationData = [
                        'campaign_id' => $pendingDonation['campaign_id'],
                        'donor_name' => $pendingDonation['donor_name'],
                        'donor_email' => $pendingDonation['donor_email'],
                        'amount' => $pendingDonation['amount'],
                        'payment_gateway' => 'dodo_payments',
                        'status' => 'completed',
                        'country' => $pendingDonation['country'] ?? '',
                        'postal_code' => $pendingDonation['postal_code'] ?? '',
                        'comment' => $pendingDonation['message'] ?? '',
                        'anonymous' => $pendingDonation['anonymous'] ?? 0,
                        'transaction_id' => $paymentId,
                        'gateway_response' => json_encode($sessionStatus),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Check if donation was already processed (prevent duplicates)
                    $db = Database::getInstance();
                    $checkStmt = $db->prepare("SELECT id FROM donations WHERE transaction_id = ? AND payment_gateway = 'dodo_payments'");
                    $checkStmt->execute([$paymentId ?: $sessionId]);
                    $existingDonation = $checkStmt->fetch();
                    
                    if (!$existingDonation) {
                        // Create donation record
                        $donationId = $campaign->createDonation($donationData);
                        
                        if ($donationId) {
                            // Clear pending donation and session ID
                            unset($_SESSION['pending_donation']);
                            unset($_SESSION['dodo_session_id']);
                            
                            // Set success message
                            $_SESSION['donation_success'] = 'Thank you! Your donation has been processed successfully.';
                            
                            error_log("Donation processed successfully: Donation ID $donationId, Amount: {$pendingDonation['amount']}");
                        } else {
                            error_log("Failed to create donation record for session: $sessionId");
                            $_SESSION['donation_error'] = 'Payment was successful, but there was an issue recording your donation. Please contact support.';
                        }
                    } else {
                        // Donation already processed
                        unset($_SESSION['pending_donation']);
                        unset($_SESSION['dodo_session_id']);
                        $_SESSION['donation_success'] = 'Thank you! Your donation has already been processed.';
                    }
                } else {
                    error_log("Payment not completed. Status: $paymentStatus for session: $sessionId");
                    $_SESSION['donation_error'] = 'Payment was not completed successfully.';
                }
            } else {
                error_log("Could not retrieve payment status for session: $sessionId");
                $_SESSION['donation_error'] = 'Could not verify payment status. Please contact support.';
            }
        } else {
            error_log("No session ID found for payment completion");
            $_SESSION['donation_error'] = 'Payment session not found. Please contact support.';
        }
    } catch (Exception $e) {
        error_log("Error processing Dodo Payments completion: " . $e->getMessage());
        $_SESSION['donation_error'] = 'There was an error processing your payment. Please contact support.';
    }
    
    // Redirect to remove payment parameters from URL
    header("Location: " . BASE_URL . "/campaigns/public-view.php?id=" . $campaignId);
    exit();
}

// Store session_id if provided in URL (Dodo Payments might return it)
if (isset($_GET['session_id']) && !isset($_SESSION['dodo_session_id'])) {
    $_SESSION['dodo_session_id'] = $_GET['session_id'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($details['title']); ?> – CroFunding</title>
  
  <!-- Open Graph Meta Tags for Social Media Sharing -->
  <?php
  // Ensure we have absolute URLs for images
  $ogImageUrl = '';
  if (!empty($details['image_path'])) {
      // Remove any leading slashes and ensure proper path
      $imagePath = ltrim($details['image_path'], '/');
      $ogImageUrl = BASE_URL . '/' . $imagePath;
  } else {
      $ogImageUrl = BASE_URL . '/assets/images/default-campaign.jpg';
  }
  
  // Ensure the URL is absolute
  if (strpos($ogImageUrl, 'http') !== 0) {
      $ogImageUrl = 'https://' . $_SERVER['HTTP_HOST'] . $ogImageUrl;
  }
  
  // Debug: Log the values being used
  error_log("Campaign ID: " . $campaignId);
  error_log("Campaign Image Path: " . ($details['image_path'] ?? 'NULL'));
  error_log("OG Image URL: " . $ogImageUrl);
  error_log("Campaign Title: " . ($details['title'] ?? 'NULL'));
  ?>
  <meta property="og:title" content="<?php echo htmlspecialchars($details['title']); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($details['description']), 0, 160)); ?>">
  <meta property="og:image" content="<?php echo $ogImageUrl; ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="<?php echo htmlspecialchars($details['title']); ?> - Campaign Image">
  <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CroFunding">
  <meta property="og:locale" content="en_US">
  
  <!-- Twitter Card Meta Tags -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($details['title']); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars(substr(strip_tags($details['description']), 0, 160)); ?>">
  <meta name="twitter:image" content="<?php echo $ogImageUrl; ?>">
  
  <!-- Additional Meta Tags -->
  <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($details['description']), 0, 160)); ?>">
  <meta name="keywords" content="fundraising, <?php echo htmlspecialchars($details['category']); ?>, crowdfunding, donations">
  
  <!-- Additional Social Media Meta Tags -->
  <meta name="robots" content="index, follow">
  <meta name="author" content="CroFunding">
  <meta name="twitter:site" content="@CroFunding">
  <meta name="twitter:creator" content="@CroFunding">
  
  <!-- Additional Open Graph Tags -->
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:secure_url" content="<?php echo str_replace('http://', 'https://', $ogImageUrl); ?>">
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png" type="image/x-icon">

  <script>
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

  <style>
    .donor-avatar {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 18px;
      border-radius: 50%;
    }
    .sticky-donation {
      position: sticky;
      top: 100px;
    }
    @media (max-width: 1024px) {
      .sticky-donation {
        position: relative;
        top: 0;
      }
    }
    .campaign-hero {
      position: relative;
      width: 100%;
      overflow: hidden;
    }
    .campaign-hero img {
      width: 100%;
      height: auto;
      display: block;
      max-height: 400px;
      object-fit: contain;
      background-color: #f3f4f6;
    }

    .donation-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow-y: auto;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
    }
    .donation-modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .donation-modal-content {
      background-color: white;
      border-radius: 1.5rem;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      max-width: 800px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      animation: modalSlideIn 0.3s ease-out;
    }
    @keyframes modalSlideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body class="bg-slate-50 font-sans">

<?php include __DIR__ . '/../components/public-header.php'; ?>

<!-- Hero Section with Campaign Image -->
<div class="campaign-hero relative">
  <img 
    src="<?php echo $details['image_path'] ? BASE_URL . '/' . $details['image_path'] : BASE_URL . '/assets/images/default-campaign.jpg'; ?>" 
    alt="<?php echo htmlspecialchars($details['title']); ?>" 
    onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-12 md:pt-28 md:pb-12">
  <!-- Campaign Title & Status -->
  <div class="mb-6 md:mb-8">
    <div class="flex flex-wrap items-center gap-2 md:gap-3 mb-3 md:mb-4">
      <span class="inline-block px-3 md:px-4 py-1 md:py-1.5 text-xs md:text-sm font-semibold rounded-xl <?php
        if ($details['status'] === 'active') echo 'bg-emerald-100 text-emerald-700';
        elseif ($details['status'] === 'completed') echo 'bg-blue-100 text-blue-700';
        else echo 'bg-slate-100 text-slate-700';
      ?>">
        <?php echo ucfirst($details['status']); ?>
      </span>
      <span class="text-xs md:text-sm text-slate-600">
        Created on <?php echo date('F j, Y', strtotime($details['created_at'])); ?>
      </span>
    </div>
    <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-slate-900 mb-3 md:mb-4"><?php echo htmlspecialchars($details['title']); ?></h1>
    <p class="text-base md:text-lg text-slate-600"><?php echo ucfirst($details['category']); ?> Campaign</p>
  </div>

  <!-- Progress & Stats Section -->
  <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-4 md:p-8 mb-8 md:mb-12">
            <?php
              $percent = ($details['goal_amount'] > 0)
                ? min(100, ($details['current_amount'] / $details['goal_amount']) * 100)
                : 0;
            ?>
            <!-- Progress Bar -->
            <div class="mb-6 md:mb-8">
              <div class="w-full bg-slate-100 rounded-full h-2 md:h-3 mb-3 md:mb-4 overflow-hidden">
                <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" style="width: <?php echo $percent; ?>%"></div>
              </div>
              <div class="flex items-center justify-between text-xs md:text-sm">
                <span class="text-slate-700"><strong class="text-slate-900 text-base md:text-lg"><?php echo round($percent); ?>%</strong> funded</span>
                <span class="text-slate-700"><strong class="text-slate-900 text-base md:text-lg"><?php echo (int)$details['donor_count']; ?></strong> <?php echo (int)$details['donor_count'] == 1 ? 'donor' : 'donors'; ?></span>
              </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
              <div class="bg-emerald-50 rounded-xl p-4 md:p-6 border border-emerald-100">
                <div class="flex items-center gap-3 md:gap-4">
                  <div class="w-10 h-10 md:w-14 md:h-14 bg-emerald-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-white text-lg md:text-xl"></i>
                  </div>
                  <div class="flex-1">
                    <p class="text-[10px] md:text-xs text-slate-600 uppercase tracking-wide font-semibold">Raised</p>
                    <p class="text-xl md:text-3xl font-bold text-slate-900">$<?php echo number_format($details['current_amount'], 2); ?></p>
                  </div>
                </div>
              </div>
              
              <div class="bg-slate-50 rounded-xl p-4 md:p-6 border border-slate-100">
                <div class="flex items-center gap-3 md:gap-4">
                  <div class="w-10 h-10 md:w-14 md:h-14 bg-slate-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bullseye text-white text-lg md:text-xl"></i>
                  </div>
                  <div class="flex-1">
                    <p class="text-[10px] md:text-xs text-slate-600 uppercase tracking-wide font-semibold">Goal</p>
                    <p class="text-xl md:text-3xl font-bold text-slate-900">$<?php echo number_format($details['goal_amount'], 2); ?></p>
                  </div>
                </div>
              </div>
              
              <div class="bg-blue-50 rounded-xl p-4 md:p-6 border border-blue-100">
                <div class="flex items-center gap-3 md:gap-4">
                  <div class="w-10 h-10 md:w-14 md:h-14 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-white text-lg md:text-xl"></i>
                  </div>
                  <div class="flex-1">
                    <p class="text-[10px] md:text-xs text-slate-600 uppercase tracking-wide font-semibold">Supporters</p>
                    <p class="text-xl md:text-3xl font-bold text-slate-900"><?php echo (int)$details['donor_count']; ?></p>
                  </div>
                </div>
              </div>
            </div>
          </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
    <!-- Left Column: Main Content -->
    <div class="lg:col-span-2 space-y-10 order-2 lg:order-1">
      <!-- About Section -->
      <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-4 md:p-8">
        <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-4 md:mb-6 flex items-center gap-2 md:gap-3">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-info-circle text-emerald-700 text-sm md:text-base"></i>
          </div>
          About this campaign
        </h2>
        
        <div x-data="{ expanded: false }">
          <?php $cleanDescription = nl2br(htmlspecialchars($details['description'])); ?>
          <div class="text-slate-700 leading-relaxed text-base md:text-lg" :class="expanded ? '' : 'line-clamp-6'">
            <?php echo $cleanDescription; ?>
          </div>

          <button 
            @click="expanded = !expanded" 
            class="mt-4 md:mt-6 text-emerald-700 hover:text-emerald-800 font-semibold flex items-center gap-2 transition-colors text-sm md:text-base">
            <span x-text="expanded ? 'Show Less' : 'Read More'"></span>
            <svg class="w-4 h-4 md:w-5 md:h-5 transition-transform duration-300" 
                 :class="{ 'rotate-180': expanded }"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Campaign Updates Section -->
      <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-4 md:p-8">
        <div class="flex items-center justify-between mb-4 md:mb-6">
          <div class="flex items-center gap-2 md:gap-3">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <i class="fas fa-bullhorn text-blue-700 text-sm md:text-base"></i>
            </div>
            <h3 class="text-xl md:text-2xl font-bold text-slate-900">Campaign Updates</h3>
          </div>
          <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $details['user_id']): ?>
            <button onclick="showUpdateForm()" 
                    class="px-3 md:px-5 py-2 md:py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors font-semibold flex items-center gap-1 md:gap-2 shadow-soft text-xs md:text-sm">
              <i class="fas fa-plus text-xs md:text-sm"></i>
              <span>Add Update</span>
            </button>
          <?php endif; ?>
        </div>
        
        <div id="campaign-updates" class="space-y-3 md:space-y-4">
          <?php
          // Fetch campaign updates
          $updates = $campaign->getCampaignUpdates($campaignId);
          if (!empty($updates)):
            foreach ($updates as $update):
          ?>
            <div class="bg-slate-50 rounded-xl p-6 border-l-4 border-emerald-600 hover:shadow-soft transition-all">
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-4">
                    <span class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($update['title']); ?></span>
                    <span class="text-xs text-slate-600 bg-white px-3 py-1 rounded-full border border-slate-200"><?php echo date('M j, Y', strtotime($update['created_at'])); ?></span>
                  </div>
                  <p class="text-base text-slate-700 leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($update['content'])); ?></p>
                  <?php if (!empty($update['image_path'])): ?>
                    <img src="<?php echo BASE_URL . '/' . $update['image_path']; ?>" 
                         alt="Update image" 
                         class="w-full max-w-md rounded-xl mb-4 shadow-soft">
                  <?php endif; ?>
                </div>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $details['user_id']): ?>
                  <button onclick="editUpdate(<?php echo $update['id']; ?>)" 
                          class="text-slate-400 hover:text-emerald-600 transition-colors p-2 hover:bg-emerald-50 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php 
            endforeach;
          else:
          ?>
            <div class="text-center py-12 text-slate-600 bg-slate-50 rounded-xl">
              <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bullhorn text-slate-400 text-2xl"></i>
              </div>
              <p class="text-lg font-semibold text-slate-900 mb-1">No updates yet</p>
              <p class="text-base text-slate-600">Campaign organizers will post updates here</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Add Update Form (Hidden by default) -->
      <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $details['user_id']): ?>
        <div id="update-form" class="hidden bg-white rounded-2xl shadow-soft border border-slate-100 p-8">
          <h4 class="text-xl font-bold mb-6 text-slate-900 flex items-center gap-3">
            <i class="fas fa-edit text-emerald-700"></i>
            Add Campaign Update
          </h4>
          <form id="campaign-update-form" class="space-y-5">
            <input type="hidden" name="campaign_id" value="<?php echo $details['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div>
              <label for="update_title" class="block text-sm font-semibold text-slate-700 mb-2">Update Title</label>
              <input type="text" name="update_title" id="update_title" required 
                     class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
            </div>
            
            <div>
              <label for="update_content" class="block text-sm font-semibold text-slate-700 mb-2">Update Content</label>
              <textarea name="update_content" id="update_content" rows="6" required 
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                        placeholder="Share the latest news, progress, or thank you message..."></textarea>
            </div>
            
            <div>
              <label for="update_image" class="block text-sm font-semibold text-slate-700 mb-2">Image (Optional)</label>
              <input type="file" name="update_image" id="update_image" accept="image/*" 
                     class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
            </div>
            
            <div class="flex gap-3 pt-2">
              <button type="submit" 
                      class="flex-1 px-6 py-3 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-all font-semibold shadow-soft">
                <i class="fas fa-paper-plane mr-2"></i>Post Update
              </button>
              <button type="button" onclick="hideUpdateForm()" 
                      class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all font-semibold">
                Cancel
              </button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <!-- Recent Donors Section -->
      <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-4 md:p-8">
        <div class="flex items-center gap-3 mb-4 md:mb-6">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-pink-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-heart text-pink-700 text-sm md:text-base"></i>
          </div>
          <h3 class="text-xl md:text-2xl font-bold text-slate-900">Recent Donors</h3>
        </div>
        
        <?php if (!empty($donors)): ?>
          <div class="space-y-3 md:space-y-4">
            <?php foreach ($donors as $index => $donor): 
              $donorInitial = '';
              if (isset($donor['donor_name']) && !empty($donor['donor_name']) && (!isset($donor['anonymous']) || $donor['anonymous'] != 1)) {
                $donorInitial = strtoupper(substr($donor['donor_name'], 0, 1));
                $donorName = htmlspecialchars($donor['donor_name']);
              } else {
                $donorInitial = 'A';
                $donorName = 'Anonymous Donor';
              }
              
              $donatedAt = new DateTime($donor['donated_at']);
              $now = new DateTime();
              $diff = $now->diff($donatedAt);
              $timeAgo = '';
              if ($diff->y > 0) {
                $timeAgo = $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
              } elseif ($diff->m > 0) {
                $timeAgo = $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
              } elseif ($diff->d > 0) {
                $timeAgo = $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
              } elseif ($diff->h > 0) {
                $timeAgo = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
              } elseif ($diff->i > 0) {
                $timeAgo = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
              } else {
                $timeAgo = 'Just now';
              }
            ?>
              <div class="flex items-center gap-3 md:gap-4 p-3 md:p-5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors">
                <div class="donor-avatar flex-shrink-0" style="width: 36px; height: 36px; font-size: 14px;">
                  <?php echo $donorInitial; ?>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-slate-900 text-base md:text-lg"><?php echo $donorName; ?></p>
                  <p class="text-xs md:text-sm text-slate-600">
                    <span class="font-bold text-emerald-700 text-base md:text-lg">$<?php echo number_format($donor['amount'], 2); ?></span>
                    <span class="mx-1 md:mx-2">·</span>
                    <span><?php echo $timeAgo; ?></span>
                  </p>
                </div>
                <div class="text-emerald-600 text-sm">
                  <i class="fas fa-heart"></i>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
                    
          <!-- Pagination Controls -->
          <?php if ($totalPages > 1): ?>
            <div class="mt-10 flex items-center justify-center">
              <div class="flex items-center gap-2 flex-wrap">
                <?php if ($page > 1): ?>
                  <a href="?id=<?php echo $campaignId; ?>&page=<?php echo $page - 1; ?>" 
                     class="px-3 md:px-5 py-2.5 text-sm font-semibold text-emerald-700 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-all">
                    <i class="fas fa-chevron-left"></i>
                    <span class="hidden sm:inline ml-2">Previous</span>
                  </a>
                <?php endif; ?>
                
                <?php
                // Adjust page range based on screen size
                $isMobile = true; // For template logic, we'll handle via CSS
                $mobileRange = 1;
                $desktopRange = 2;
                $startPageMobile = max(1, $page - $mobileRange);
                $endPageMobile = min($totalPages, $page + $mobileRange);
                $startPageDesktop = max(1, $page - $desktopRange);
                $endPageDesktop = min($totalPages, $page + $desktopRange);
                ?>
                
                <?php if ($startPageDesktop > 1): ?>
                  <a href="?id=<?php echo $campaignId; ?>&page=1" 
                     class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                    1
                  </a>
                  <?php if ($startPageDesktop > 2): ?>
                    <span class="px-2 text-slate-500 hidden sm:inline">...</span>
                  <?php endif; ?>
                <?php endif; ?>
                
                <?php 
                // Show desktop page numbers
                for ($i = $startPageDesktop; $i <= $endPageDesktop; $i++): 
                  $isVisibleOnMobile = ($i >= $startPageMobile && $i <= $endPageMobile) || $i == 1 || $i == $totalPages;
                ?>
                  <a href="?id=<?php echo $campaignId; ?>&page=<?php echo $i; ?>" 
                     class="px-4 py-2.5 text-sm font-semibold <?php echo $i == $page ? 'text-white bg-emerald-600 border-emerald-600' : 'text-slate-700 bg-white border-slate-200 hover:bg-slate-50'; ?> border rounded-xl transition-all <?php echo $isVisibleOnMobile ? '' : 'hidden sm:inline-flex'; ?>">
                    <?php echo $i; ?>
                  </a>
                <?php endfor; ?>
                
                <?php if ($endPageDesktop < $totalPages): ?>
                  <?php if ($endPageDesktop < $totalPages - 1): ?>
                    <span class="px-2 text-slate-500 hidden sm:inline">...</span>
                  <?php endif; ?>
                  <a href="?id=<?php echo $campaignId; ?>&page=<?php echo $totalPages; ?>" 
                     class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                    <?php echo $totalPages; ?>
                  </a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                  <a href="?id=<?php echo $campaignId; ?>&page=<?php echo $page + 1; ?>" 
                     class="px-3 md:px-5 py-2.5 text-sm font-semibold text-emerald-700 bg-white border border-emerald-200 rounded-xl hover:bg-emerald-50 transition-all">
                    <span class="hidden sm:inline mr-2">Next</span>
                    <i class="fas fa-chevron-right"></i>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="text-center py-12 text-slate-600 bg-slate-50 rounded-xl">
            <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-heart text-slate-400 text-2xl"></i>
            </div>
            <p class="text-lg font-semibold text-slate-900 mb-1">No donations yet</p>
            <p class="text-base text-slate-600">Be the first to support this campaign!</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Browse Other Campaigns -->
      <div class="bg-emerald-600 rounded-2xl p-4 md:p-8 text-white shadow-soft">
        <div class="flex items-center gap-2 md:gap-3 mb-3 md:mb-4">
          <div class="w-8 h-8 md:w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
            <i class="fas fa-check-circle text-white text-sm md:text-base"></i>
          </div>
          <h3 class="text-lg md:text-xl font-bold">Looking for other campaigns?</h3>
        </div>
        <p class="text-emerald-100 mb-4 md:mb-6 text-base md:text-lg">Discover verified campaigns and make an impact today.</p>
        <a href="<?php echo BASE_URL; ?>/campaigns/view_compaigns.php"
           class="inline-flex items-center px-4 md:px-6 py-2 md:py-3 bg-white text-emerald-700 rounded-xl font-semibold hover:bg-slate-50 transition-all shadow-soft text-sm md:text-base">
          <i class="fas fa-check-circle mr-2"></i>
          Browse Verified Campaigns
        </a>
      </div>
    </div>

    <!-- Right Column: Sticky Donation Card -->
    <div class="lg:col-span-1 order-1 lg:order-2">
      <div class="sticky-donation">
        <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-4 md:p-8">
          <div class="text-center mb-6 md:mb-8">
            <div class="w-16 h-16 md:w-20 md:h-20 bg-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4 shadow-soft">
              <i class="fas fa-heart text-white text-2xl md:text-3xl"></i>
            </div>
            <h3 class="text-xl md:text-2xl font-bold text-slate-900 mb-2">Support This Campaign</h3>
            <p class="text-slate-600 text-sm md:text-base">Your donation can make a real difference</p>
          </div>
          
          <div class="space-y-3 md:space-y-4">
            <button onclick="openDonationModal()" 
                    class="w-full bg-emerald-600 text-white py-3 md:py-4 px-4 md:px-6 rounded-xl hover:bg-emerald-700 transition-all text-base md:text-lg font-bold shadow-soft flex items-center justify-center gap-2">
              <i class="fas fa-heart"></i>
              Donate Now
            </button>
            
            <button onclick="copyShareLink()" 
                    class="w-full border border-emerald-600 text-emerald-700 bg-white py-2.5 md:py-3 px-4 md:px-6 rounded-xl hover:bg-emerald-50 transition-all font-semibold flex items-center justify-center gap-2 text-sm md:text-base">
              <i class="fas fa-share-alt"></i>
              Share Campaign
            </button>
          </div>
          
          <div class="mt-6 md:mt-8 pt-6 md:pt-8 border-t border-slate-200 space-y-2 md:space-y-3">
            <div class="flex items-center justify-between text-xs md:text-sm text-slate-700">
              <span>Campaign Status</span>
              <span class="font-semibold text-slate-900 capitalize"><?php echo $details['status']; ?></span>
            </div>
            <div class="flex items-center justify-between text-xs md:text-sm text-slate-700">
              <span>Category</span>
              <span class="font-semibold text-slate-900"><?php echo htmlspecialchars($details['category']); ?></span>
            </div>
            <div class="flex items-center justify-between text-xs md:text-sm text-slate-700">
              <span>Created</span>
              <span class="font-semibold text-slate-900"><?php echo date('M j, Y', strtotime($details['created_at'])); ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Donation Modal -->
<div id="donationModal" class="donation-modal">
  <div class="donation-modal-content">
    <div class="sticky top-0 bg-white border-b border-slate-200 p-6 flex items-center justify-between z-10">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center">
          <i class="fas fa-heart text-white text-sm"></i>
        </div>
        <div>
          <h2 class="text-xl font-bold text-slate-900">Make Your Donation</h2>
          <p class="text-sm text-slate-600">Support this campaign with a secure donation</p>
        </div>
      </div>
      <button onclick="closeDonationModal()" class="text-slate-400 hover:text-slate-700 transition-colors p-2 hover:bg-slate-50 rounded-lg">
        <i class="fas fa-times text-2xl"></i>
      </button>
    </div>
    <div class="p-6">
      <div id="donationFormLoader" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-emerald-600 border-t-transparent"></div>
        <p class="mt-4 text-slate-600">Loading donation form...</p>
      </div>
      <div id="donationFormContainer" class="hidden"></div>
    </div>
  </div>
</div>

<script>
// Basic variables for the page
const URL_BASE = '<?php echo BASE_URL; ?>';
const campaignId = <?php echo $details['id']; ?>;
const donationMinimum = 5;

// Donation Modal Functions
function openDonationModal() {
  const modal = document.getElementById('donationModal');
  const formContainer = document.getElementById('donationFormContainer');
  const formLoader = document.getElementById('donationFormLoader');
  
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
  
  // Reset form container
  formContainer.classList.add('hidden');
  formLoader.classList.remove('hidden');
  formContainer.innerHTML = '';
  
  // Load donation form via AJAX
  $.ajax({
    url: URL_BASE + '/campaigns/get-donation-form.php?id=' + campaignId,
    type: 'GET',
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        formLoader.classList.add('hidden');
        formContainer.innerHTML = response.html;
        formContainer.classList.remove('hidden');
        
        // Initialize form handlers
        initializeDonationForm();
      } else {
        showError('Failed to load donation form. Please try again.');
      }
    },
    error: function() {
      formLoader.classList.add('hidden');
      formContainer.innerHTML = '<div class="text-center py-8 text-red-700"><i class="fas fa-exclamation-triangle text-4xl mb-4"></i><p>Failed to load donation form. Please refresh the page and try again.</p></div>';
      formContainer.classList.remove('hidden');
    }
  });
}

function closeDonationModal() {
  const modal = document.getElementById('donationModal');
  modal.classList.remove('active');
  document.body.style.overflow = '';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
  const modal = document.getElementById('donationModal');
  if (event.target === modal) {
    closeDonationModal();
  }
});

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeDonationModal();
  }
});

// Initialize donation form handlers
function initializeDonationForm() {
  // Handle quick amount buttons
  $('.donation-amount-btn').off('click').on('click', function() {
    $('.donation-amount-btn').removeClass('active');
    $(this).addClass('active');
    const amount = $(this).data('amount');
    $('#onlyNumber').val(amount);
    $('#onlyNumber').focus();
  });
  
  // Clear active state when custom amount is entered
  $('#onlyNumber').off('input').on('input', function() {
    $('.donation-amount-btn').removeClass('active');
  });
  
  // Handle form submission - create Dodo Payments checkout session
  $('#formDonation').off('submit').on('submit', function(e) {
    e.preventDefault();
    
    const donationAmount = $('#onlyNumber').val();
    const fullName = $('#cardholder-name').val();
    const email = $('#cardholder-email').val();
    
    if (!validateBasicFields(donationAmount, fullName, email)) {
      return false;
    }
    // Show loading state
    $('#buttonDonation').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
    
    // Create Dodo Payments checkout session
    $.ajax({
      url: URL_BASE + '/campaigns/create-dodo-checkout.php',
      type: 'POST',
      data: {
        campaign_id: campaignId,
        amount: donationAmount,
        full_name: fullName,
        email: email,
        country: $('#country').val(),
        postal_code: $('#postal_code').val(),
        comment: $('#comment').val(),
        anonymous: $('input[name="anonymous"]').val() || '0',
        csrf_token: $('input[name="csrf_token"]').val()
      },
      dataType: 'json',
      success: function(response) {
        if (response.success && response.checkout_url) {
          // Redirect to Dodo Payments checkout
          window.location.href = response.checkout_url;
        } else {
          showError(response.error || 'Failed to create checkout session. Please try again.');
          $('#buttonDonation').prop('disabled', false).html('<i class="fas fa-heart mr-2"></i> Complete Donation');
        }
      },
      error: function(xhr, status, error) {
        showError('An error occurred. Please try again.');
        console.error('Checkout error:', error);
        $('#buttonDonation').prop('disabled', false).html('<i class="fas fa-heart mr-2"></i> Complete Donation');
      }
    });
    
    return false;
  });
  
  // Show submit button
  $('#buttonDonation').removeClass('hidden');
}

// Validate basic form fields
function validateBasicFields(amount, name, email) {
  if (!amount || parseFloat(amount) < donationMinimum) {
    showError('Please enter a valid donation amount (minimum $' + donationMinimum + ')');
    return false;
  }
  
  if (!name || !name.trim()) {
    showError('Please enter your full name');
    return false;
  }
  
  if (!email || !email.trim() || !/^\S+@\S+\.\S+$/.test(email)) {
    showError('Please enter a valid email address');
    return false;
  }
  
  return true;
}

// Show error in modal
function showError(message) {
  $('#showErrorsDonation').html('<li><i class="fas fa-times-circle mr-2"></i> ' + message + '</li>');
  $('#errorDonation').removeClass('hidden');
  $('#showDonationMessages').addClass('hidden');
}

// Campaign Updates JavaScript Functions
function showUpdateForm() {
  document.getElementById('update-form').classList.remove('hidden');
}

function hideUpdateForm() {
  document.getElementById('update-form').classList.add('hidden');
  document.getElementById('campaign-update-form').reset();
}

function editUpdate(updateId) {
  // TODO: Implement edit functionality
  alert('Edit functionality coming soon!');
}

// Copy Share Link Function
function copyShareLink() {
  const shareUrl = '<?php echo BASE_URL . "/campaigns/public-view.php?id=" . $details['id']; ?>';
  
  // Try to use modern clipboard API
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(shareUrl).then(() => {
      showCopySuccess();
    }).catch(() => {
      // Fallback to old method
      fallbackCopyTextToClipboard(shareUrl);
    });
  } else {
    // Fallback for older browsers
    fallbackCopyTextToClipboard(shareUrl);
  }
}

// Fallback copy function for older browsers
function fallbackCopyTextToClipboard(text) {
  const textArea = document.createElement('textarea');
  textArea.value = text;
  textArea.style.top = '0';
  textArea.style.left = '0';
  textArea.style.position = 'fixed';
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  
  try {
    document.execCommand('copy');
    showCopySuccess();
  } catch (err) {
    console.error('Fallback: Oops, unable to copy', err);
    showCopyError();
  }
  
  document.body.removeChild(textArea);
}

// Show success message
function showCopySuccess() {
  // Create a temporary success message
  const successMsg = document.createElement('div');
  successMsg.className = 'fixed bottom-6 right-6 bg-emerald-600 text-white px-6 py-3 rounded-xl shadow-large z-50 transition-all duration-300';
  successMsg.innerHTML = '<i class="fas fa-check mr-2"></i>Link copied to clipboard!';
  
  document.body.appendChild(successMsg);
  
  // Remove after 3 seconds
  setTimeout(() => {
    successMsg.remove();
  }, 3000);
}

// Show error message
function showCopyError() {
  // Create a temporary error message
  const errorMsg = document.createElement('div');
  errorMsg.className = 'fixed bottom-6 right-6 bg-red-600 text-white px-6 py-3 rounded-xl shadow-large z-50 transition-all duration-300';
  errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Failed to copy link';
  
  document.body.appendChild(errorMsg);
  
  // Remove after 3 seconds
  setTimeout(() => {
    errorMsg.remove();
  }, 3000);
}

// Handle campaign update form submission
document.addEventListener('DOMContentLoaded', function() {
  const updateForm = document.getElementById('campaign-update-form');
  if (updateForm) {
    updateForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('<?php echo BASE_URL; ?>/campaigns/add-update.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            title: 'Success!',
            text: 'Update posted successfully!',
            icon: 'success',
            confirmButtonText: 'Great!',
            confirmButtonColor: '#059669'
          }).then(() => location.reload());
        } else {
          Swal.fire({
            title: 'Error!',
            text: data.error || 'Failed to post update',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc2626'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          title: 'Error!',
          text: 'Error posting update. Please try again.',
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#dc2626'
        });
      });
    });
  }

  // Check for success/error messages and show SweetAlert
  checkForMessages();
});

// Country code to name mapping
const countryNames = {
  'US': 'United States',
  'CA': 'Canada',
  'GB': 'United Kingdom',
  'DE': 'Germany',
  'FR': 'France',
  'AU': 'Australia',
  'JP': 'Japan',
  'CN': 'China',
  'IN': 'India',
  'BR': 'Brazil',
  'MX': 'Mexico',
  'ZA': 'South Africa',
  'NG': 'Nigeria',
  'KE': 'Kenya',
  'UG': 'Uganda',
  'GH': 'Ghana',
  'TZ': 'Tanzania',
  'ET': 'Ethiopia',
  'RW': 'Rwanda',
  'EG': 'Egypt',
  'SA': 'Saudi Arabia',
  'AE': 'United Arab Emirates'
};

// Check for success/error messages and show SweetAlert
function checkForMessages() {
  <?php if (isset($_SESSION['success'])): ?>
    Swal.fire({
      title: 'Success!',
      text: '<?php echo addslashes($_SESSION['success']); ?>',
      icon: 'success',
      confirmButtonText: 'Great!',
      confirmButtonColor: '#059669'
    });
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['error'])): ?>
    Swal.fire({
      title: 'Error!',
      text: '<?php echo addslashes($_SESSION['error']); ?>',
      icon: 'error',
      confirmButtonText: 'OK',
      confirmButtonColor: '#dc2626'
    });
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['info'])): ?>
    Swal.fire({
      title: 'Information',
      text: '<?php echo addslashes($_SESSION['info']); ?>',
      icon: 'info',
      confirmButtonText: 'Got it!',
      confirmButtonColor: '#3b82f6'
    });
    <?php unset($_SESSION['info']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['donation_success'])): ?>
    Swal.fire({
      title: 'Thank You!',
      text: '<?php echo addslashes($_SESSION['donation_success']); ?>',
      icon: 'success',
      confirmButtonText: 'Great!',
      confirmButtonColor: '#059669',
      timer: 5000
    });
    <?php unset($_SESSION['donation_success']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['donation_error'])): ?>
    Swal.fire({
      title: 'Payment Issue',
      text: '<?php echo addslashes($_SESSION['donation_error']); ?>',
      icon: 'error',
      confirmButtonText: 'OK',
      confirmButtonColor: '#dc2626',
      timer: 5000
    });
    <?php unset($_SESSION['donation_error']); ?>
  <?php endif; ?>
  
  // Check for Dodo Payments success from URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('payment') === 'success') {
    Swal.fire({
      title: 'Success!',
      text: 'Thank you! Your donation has been processed successfully.',
      icon: 'success',
      confirmButtonText: 'Great!',
      confirmButtonColor: '#059669'
    });
    // Clean URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}
</script>

<?php include __DIR__ . '/../components/public-footer.php'; ?>
</body>
</html>
