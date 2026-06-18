<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Check if campaign ID is provided
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/campaigns/browse.php");
    exit();
}

$campaignId = intval($_GET['id']);
$campaign = new Campaign();
$details = $campaign->getCampaignById($campaignId);

if (!$details) {
    $_SESSION['error'] = "Campaign not found";
    header("Location: " . BASE_URL . "/campaigns/browse.php");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate to <?php echo htmlspecialchars($details['title']); ?> - CroFunding</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Inter font -->
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png" type="image/x-icon">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- PayPal SDK -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=USD"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 font-sans">

<?php include __DIR__ . '/../components/public-header.php'; ?>

<main class="max-w-6xl mx-auto p-6 pt-24">
    <!-- Campaign Header -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
        <div class="md:flex">
            <div class="md:w-1/3">
                <img src="<?php echo $details['image_path'] ? BASE_URL . '/' . $details['image_path'] : BASE_URL . '/assets/images/default-campaign.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($details['title']); ?>" 
                     class="w-full h-64 md:h-full object-cover">
            </div>
            <div class="md:w-2/3 p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($details['title']); ?></h1>
                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars(substr($details['description'], 0, 200)) . '...'; ?></p>
                
                <!-- Campaign Progress -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>$<?php echo number_format($details['current_amount'], 2); ?> raised</span>
                        <span><?php echo round(($details['current_amount'] / $details['goal_amount']) * 100); ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-emerald-500 h-3 rounded-full transition-all duration-300" 
                             style="width: <?php echo min(($details['current_amount'] / $details['goal_amount']) * 100, 100); ?>%"></div>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Goal: $<?php echo number_format($details['goal_amount'], 2); ?></p>
                </div>
                
                <!-- Back to Campaign Button -->
                <a href="<?php echo BASE_URL; ?>/campaigns/public-view.php?id=<?php echo $details['id']; ?>" 
                   class="inline-flex items-center text-emerald-600 hover:text-emerald-700 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Campaign
                </a>
            </div>
        </div>
    </div>

    <!-- Donation Form Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Donation Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Make Your Donation</h2>
                
                <form method="POST" action="<?php echo BASE_URL; ?>/donate/<?php echo $details['id']; ?>" 
                      enctype="multipart/form-data" id="formDonation" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="campaign_id" value="<?php echo htmlspecialchars($details['id']); ?>">
                    
                    <!-- Donation Amount -->
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Donation Amount</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-4 py-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-lg font-medium">
                                $
                            </span>
                            <input type="number" min="5" autocomplete="off" id="onlyNumber" 
                                   class="flex-1 rounded-r-md border border-gray-300 px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none transition-colors" 
                                   name="amount" value="" placeholder="Enter amount (minimum $5)" required>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">Minimum donation amount is $5 USD</p>
                    </div>

                    <!-- Donor Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" id="cardholder-name" value="" name="full_name" 
                                   class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                   placeholder="First Name and Last Name" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" id="cardholder-email" value="" name="email" 
                                   class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                   placeholder="your@email.com" required>
                        </div>
                    </div>

                    <!-- Country and Postal Code -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                            <select id="country" name="country" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
                                <option value="">Select Country</option>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="DE">Germany</option>
                                <option value="FR">France</option>
                                <option value="AU">Australia</option>
                                <option value="JP">Japan</option>
                                <option value="CN">China</option>
                                <option value="IN">India</option>
                                <option value="BR">Brazil</option>
                                <option value="MX">Mexico</option>
                                <option value="ZA">South Africa</option>
                                <option value="NG">Nigeria</option>
                                <option value="KE">Kenya</option>
                                <option value="UG">Uganda</option>
                                <option value="GH">Ghana</option>
                                <option value="TZ">Tanzania</option>
                                <option value="ET">Ethiopia</option>
                                <option value="RW">Rwanda</option>
                                <option value="EG">Egypt</option>
                                <option value="SA">Saudi Arabia</option>
                                <option value="AE">United Arab Emirates</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                            <input type="text" id="postal_code" value="" name="postal_code" 
                                   class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                   placeholder="Postal Code">
                        </div>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" id="city" value="Default City" name="city">
                    <input type="hidden" id="state" name="state" value="Default State">
                    <input type="hidden" id="street_address" value="Default Address" name="street_address">

                    <!-- Comment -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Comment (Optional)</label>
                        <textarea id="comment" name="comment" rows="3"
                                  class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" 
                                  placeholder="Share why you're donating or leave a message..."></textarea>
                    </div>

               
                    <!-- Anonymous Donation -->
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                        <input class="h-5 w-5 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded" 
                               id="customControlInline" name="anonymous" type="checkbox" value="1">
                        <label class="ml-3 text-base text-gray-700" for="customControlInline">
                            Make this donation anonymous
                        </label>
                    </div>

                    <!-- Error Messages -->
                    <div class="hidden" id="errorDonation">
                        <ul class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-md p-4" id="showErrorsDonation"></ul>
                    </div>

                    <!-- Success Messages -->
                    <div class="hidden" id="showDonationMessages"></div>

                    <!-- Payment Info -->
                    <div class="p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                        <div class="text-sm text-emerald-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Note:</strong> Payment processor fees apply, and 10% platform fee is included in your donation.
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="buttonDonation" 
                            class="w-full bg-emerald-600 text-white py-4 px-6 rounded-lg hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-colors text-lg font-semibold">
                        <i class="fas fa-heart mr-2"></i> Complete Donation
                    </button>
                </form>
                
                <!-- PayPal Button Container (for PayPal payments) -->
                <div id="paypal-button-container" class="mt-6 hidden">
                    <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200 mb-4">
                        <p class="text-base text-blue-700 mb-2">
                            <i class="fab fa-paypal mr-2"></i>
                            <strong>PayPal Selected</strong>
                        </p>
                        <p class="text-sm text-blue-600">
                            Complete your donation securely with PayPal below.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Campaign Summary Card -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6 sticky top-24">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Summary</h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Current Amount:</span>
                        <span class="font-semibold text-emerald-600">$<?php echo number_format($details['current_amount'], 2); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Goal Amount:</span>
                        <span class="font-semibold text-gray-900">$<?php echo number_format($details['goal_amount'], 2); ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Progress:</span>
                        <span class="font-semibold text-emerald-600"><?php echo round(($details['current_amount'] / $details['goal_amount']) * 100); ?>%</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Donors:</span>
                        <span class="font-semibold text-gray-900"><?php echo (int)$details['donor_count']; ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Days Left:</span>
                        <span class="font-semibold text-gray-900">
                            <?php 
                            $endDate = new DateTime($details['end_date'] ?? 'now');
                            $now = new DateTime();
                            $daysLeft = $now->diff($endDate)->days;
                            echo $daysLeft > 0 ? $daysLeft : 'Ended';
                            ?>
                        </span>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <!-- Security Badges -->
                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-shield-alt text-emerald-500 mr-2"></i>
                        Secure Payment Processing
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-lock text-emerald-500 mr-2"></i>
                        SSL Encrypted
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check-circle text-emerald-500 mr-2"></i>
                        Verified Campaign
                    </div>
                </div>
            </div>

            <!-- Need Help Card -->
            <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
               
            </div>
        </div>
    </div>
</main>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Multi-Gateway Payment System Variables
var URL_BASE = "<?php echo BASE_URL; ?>";
var campaignId = <?php echo $details['id']; ?>;
var currencySymbol = "$";
var currencyCode = "USD";
var donationMinimum = 5;

// Campaign data from the actual campaign being viewed
const ACTUAL_CAMPAIGN_ID = <?php echo $details['id']; ?>;
const ACTUAL_CAMPAIGN_TITLE = <?php echo json_encode($details['title']); ?>;
const ACTUAL_CAMPAIGN_DESCRIPTION = <?php echo json_encode(substr($details['description'], 0, 100)); ?>;

// Use the actual campaign data for payments
function getCampaignId() {
  return ACTUAL_CAMPAIGN_ID;
}

function getCampaignTitle() {
  return ACTUAL_CAMPAIGN_TITLE;
}

// Initialize the payment system
function initializePaymentSystem() {
  // Since we only have PayPal, no need for payment gateway selection logic

  // Handle form submission - prevent main form submission since we only use PayPal
  $('#formDonation').on('submit', function(e) {
    e.preventDefault();
    console.log('Form submission prevented - use PayPal buttons instead');
    showError('Please use the PayPal button below to complete your donation');
    return false;
  });
}

// Main donation submission handler
function handleDonationSubmission() {
  var payment = $('input[name=payment_gateway]:checked').val();
  
  if (!payment) {
    showError('Please select a payment method');
    return;
  }

  // Route to appropriate payment handler
  switch(payment) {
    case '1': // PayPal
      // PayPal buttons are already rendered and handle payment automatically
      console.log('PayPal selected - buttons should handle payment');
      break;
    case '6': // Credit/Debit Card
      handleStripePayment();
      break;
    default:
      handleStandardDonation();
      break;
  }
}

// Render PayPal buttons with actual campaign data
function renderPayPalButtons() {
  // Clear existing buttons first
  $('#paypal-button-container').empty();
  
  // Create PayPal order with actual campaign data
  paypal.Buttons({
    createOrder: (data, actions) => {
      var donationAmount = $('#onlyNumber').val();
      var fullName = $('#cardholder-name').val();
      var email = $('#cardholder-email').val();
      
      if (!validateBasicFields(donationAmount, fullName, email)) {
        return false;
      }
      
      // Use actual campaign data from the page
      return actions.order.create({
        purchase_units: [{
          amount: { value: donationAmount },
          description: getCampaignTitle(), // Actual campaign title
          custom_id: getCampaignId().toString() // Actual campaign ID
        }],
        payer: {
          name: { given_name: fullName },
          email_address: email
        }
      });
    },
    onApprove: (data, actions) => {
      var donationAmount = $('#onlyNumber').val();
      var fullName = $('#cardholder-name').val();
      var email = $('#cardholder-email').val();
      
      // Send to actual campaign being viewed
      return fetch(URL_BASE + '/campaigns/capture.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          orderID: data.orderID,
          campaign_id: getCampaignId(), // Actual campaign ID
          donor_name: fullName,
          donor_email: email
        })
      }).then(res => res.json())
        .then(res => {
          console.log('PayPal capture response:', res);
          if (res.success) {
            // Redirect directly to thank you page
            console.log('PayPal success, redirecting to:', res.redirectUrl);
            window.location.href = res.redirectUrl;
          } else {
            console.error('PayPal payment failed:', res.error);
            showError('PayPal payment failed: ' + res.error);
          }
        });
    },
    onError: err => showError('PayPal error: ' + err)
  }).render('#paypal-button-container');
}

// Handle Stripe credit card payments with actual campaign data
function handleStripePayment() {
  console.log('Starting Stripe checkout for actual campaign...');
  
  var donationAmount = $('#onlyNumber').val();
  var fullName = $('#cardholder-name').val();
  var email = $('#cardholder-email').val();
  var country = $('#country').val();
  var postalCode = $('#postal_code').val();
  
  if (!validateBasicFields(donationAmount, fullName, email)) {
    return;
  }
  
  if (!country || country === 'Other' || !validateCountryCode(country)) {
    showError('Please select a valid country');
    return;
  }

  // Show loading state
  $('#buttonDonation').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
  
  // Store actual campaign ID in session
  $.ajax({
    url: URL_BASE + '/stripe/store-campaign-session.php',
    type: 'POST',
    data: {
      campaign_id: getCampaignId(), // Actual campaign ID
      csrf_token: $('input[name="csrf_token"]').val()
    },
    dataType: 'json',
    success: function() {
      // Now create Stripe checkout session with actual campaign
      createStripeCheckoutSession();
    },
    error: function() {
      // Continue anyway if session storage fails
      createStripeCheckoutSession();
    }
  });
  
  function createStripeCheckoutSession() {
    $.ajax({
      url: URL_BASE + '/stripe/checkout-session.php',
      type: 'POST',
      data: {
        amount: donationAmount,
        full_name: fullName,
        email: email,
        campaign_id: getCampaignId(), // Actual campaign ID
        country: country,
        postal_code: postalCode,
        comment: $('#comment').val(),
        anonymous: $('#customControlInline').prop('checked') ? 1 : 0,
        csrf_token: $('input[name="csrf_token"]').val()
      },
      dataType: 'json',
      success: function(response) {
        if (response.success && response.url) {
          console.log('Redirecting to Stripe checkout for actual campaign:', response.url);
          // Redirect to Stripe Checkout
          window.location.href = response.url;
        } else {
          showError(response.error || 'Failed to create checkout session');
          $('#buttonDonation').prop('disabled', false).html('<i class="fas fa-heart mr-2"></i> Complete Donation');
        }
      },
      error: function(xhr, status, error) {
        showError('Failed to create checkout session. Please try again.');
        console.error('Checkout session error:', error);
        $('#buttonDonation').prop('disabled', false).html('<i class="fas fa-heart mr-2"></i> Complete Donation');
      }
    });
  }
}

// Handle standard donations (fallback) with actual campaign data
function handleStandardDonation() {
  var donationAmount = $('#onlyNumber').val();
  var fullName = $('#cardholder-name').val();
  var email = $('#cardholder-email').val();
  
  if (!validateBasicFields(donationAmount, fullName, email)) {
    return;
  }

  // Submit to actual campaign being viewed
  submitDonation({
    payment_gateway: '1', // Default to PayPal
    amount: donationAmount,
    full_name: fullName,
    email: email,
            campaign_id: getCampaignId() // Actual campaign ID
  });
}

// Validate basic form fields
function validateBasicFields(amount, name, email) {
  if (!amount || parseFloat(amount) < donationMinimum) {
    showError('Please enter a valid donation amount (minimum $' + donationMinimum + ')');
    return false;
  }
  
  if (!name.trim()) {
    showError('Please enter your full name');
    return false;
  }
  
  if (!email.trim() || !/^\S+@\S+\.\S+$/.test(email)) {
    showError('Please enter a valid email address');
    return false;
  }
  
  return true;
}

// Validate country code
function validateCountryCode(countryCode) {
  const validCountries = ['US', 'CA', 'GB', 'DE', 'FR', 'AU', 'JP', 'CN', 'IN', 'BR', 'MX', 'ZA', 'NG', 'KE', 'UG', 'GH', 'TZ', 'ET', 'RW', 'EG', 'SA', 'AE'];
  return validCountries.includes(countryCode);
}

// Submit donation to backend with actual campaign data
function submitDonation(donationData) {
  // Use actual campaign ID from the page
  donationData.csrf_token = $('input[name="csrf_token"]').val();
  donationData.campaign_id = getCampaignId(); // Actual campaign ID
  
  // Show loading state
  $('#buttonDonation').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing...');
  
  $.ajax({
    url: URL_BASE + '/donate/' + getCampaignId(), // Actual campaign ID in URL
    type: 'POST',
    data: donationData,
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        showSuccess('Donation submitted successfully!');
        // Optionally redirect or refresh
        setTimeout(() => {
          location.reload();
        }, 2000);
      } else {
        showError(response.error || 'Donation failed. Please try again.');
      }
    },
    error: function(xhr, status, error) {
      showError('An error occurred. Please try again.');
      console.error('Donation error:', error);
    },
    complete: function() {
      // Reset button state
      $('#buttonDonation').prop('disabled', false).html('<i class="fas fa-heart mr-2"></i> Complete Donation');
    }
  });
}

// Utility functions for showing messages
function showError(message) {
  $('#showErrorsDonation').html('<li><i class="fas fa-times-circle mr-2"></i> ' + message + '</li>');
  $('#errorDonation').removeClass('hidden');
  $('#showDonationMessages').addClass('hidden');
}

function showSuccess(message) {
  $('#showDonationMessages').html('<div class="alert alert-success">' + message + '</div>').removeClass('hidden');
  $('#errorDonation').addClass('hidden');
}

function showInfo(message) {
  $('#showDonationMessages').html('<div class="alert alert-info">' + message + '</div>').removeClass('hidden');
  $('#errorDonation').addClass('hidden');
}

// Initialize when document is ready
$(document).ready(function() {
  initializePaymentSystem();
  
  // Always show PayPal buttons since it's the only payment method
  $('#paypal-button-container').removeClass('hidden');
  $('#buttonDonation').addClass('hidden');
  renderPayPalButtons();
});
</script>

<?php include __DIR__ . '/../components/public-footer.php'; ?>

</body>
</html> 