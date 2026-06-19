<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Check if campaign ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Campaign ID is required']);
    exit();
}

$campaignId = intval($_GET['id']);
$campaign = new Campaign();
$details = $campaign->getCampaignById($campaignId);

if (!$details) {
    http_response_code(404);
    echo json_encode(['error' => 'Campaign not found']);
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Return only the form HTML
ob_start();
?>

<form method="POST" action="<?php echo BASE_URL; ?>/donate/<?php echo $details['id']; ?>" 
      enctype="multipart/form-data" id="formDonation">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="campaign_id" value="<?php echo htmlspecialchars($details['id']); ?>">
    
    <!-- Donation Amount Section -->
    <div class="form-section">
        <div class="form-section-title mb-3">
            <div class="w-8 h-8 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-dollar-sign text-emerald-700 text-sm"></i>
            </div>
            <h3 class="text-sm md:text-base font-bold text-slate-900">Donation Amount</h3>
        </div>
        
        <!-- Quick Amount Buttons -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-4">
            <button type="button" class="donation-amount-btn py-3.5 border-2 border-slate-200 rounded-2xl text-sm md:text-base font-semibold text-slate-700 bg-white hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all duration-200 active:scale-[0.98]" data-amount="25">
                $25
            </button>
            <button type="button" class="donation-amount-btn py-3.5 border-2 border-slate-200 rounded-2xl text-sm md:text-base font-semibold text-slate-700 bg-white hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all duration-200 active:scale-[0.98]" data-amount="50">
                $50
            </button>
            <button type="button" class="donation-amount-btn py-3.5 border-2 border-slate-200 rounded-2xl text-sm md:text-base font-semibold text-slate-700 bg-white hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all duration-200 active:scale-[0.98]" data-amount="100">
                $100
            </button>
            <button type="button" class="donation-amount-btn py-3.5 border-2 border-slate-200 rounded-2xl text-sm md:text-base font-semibold text-slate-700 bg-white hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all duration-200 active:scale-[0.98]" data-amount="250">
                $250
            </button>
        </div>
        
        <!-- Custom Amount Input -->
        <div class="input-group">
            <i class="input-icon fas fa-dollar-sign"></i>
            <input type="number" min="5" step="0.01" autocomplete="off" id="onlyNumber" 
                   class="w-full input-with-icon border-2 border-slate-200 rounded-2xl px-4 py-3.5 pl-10 text-sm md:text-base font-medium text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all duration-200" 
                   name="amount" value="" placeholder="Enter custom amount" required>
        </div>
        <p class="text-xs md:text-sm text-slate-500 mt-2.5 flex items-center gap-1.5">
            <i class="fas fa-info-circle text-emerald-600 text-xs"></i>
            Minimum donation amount is $5 USD
        </p>
    </div>

    <!-- Donor Information Section -->
    <div class="form-section">
        <div class="form-section-title mb-3">
            <div class="w-8 h-8 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user text-blue-700 text-sm"></i>
            </div>
            <h3 class="text-sm md:text-base font-bold text-slate-900">Your Information</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <div class="input-group">
                <i class="input-icon fas fa-user"></i>
                <input type="text" id="cardholder-name" value="" name="full_name" 
                       class="w-full input-with-icon border-2 border-slate-200 rounded-2xl px-4 py-3.5 pl-10 text-sm md:text-base text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all duration-200" 
                       placeholder="Full Name" required>
            </div>
            <div class="input-group">
                <i class="input-icon fas fa-envelope"></i>
                <input type="email" id="cardholder-email" value="" name="email" 
                       class="w-full input-with-icon border-2 border-slate-200 rounded-2xl px-4 py-3.5 pl-10 text-sm md:text-base text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all duration-200" 
                       placeholder="Email Address" required>
            </div>
        </div>
    </div>

    <!-- Location Information Section -->
    <div class="form-section">
        <div class="form-section-title mb-3">
            <div class="w-8 h-8 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-map-marker-alt text-purple-700 text-sm"></i>
            </div>
            <h3 class="text-sm md:text-base font-bold text-slate-900">Location</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            <div class="input-group">
                <i class="input-icon fas fa-globe"></i>
                <select id="country" name="country" class="w-full input-with-icon border-2 border-slate-200 rounded-2xl px-4 py-3.5 pl-10 text-sm md:text-base text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 appearance-none" required>
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
            <div class="input-group">
                <i class="input-icon fas fa-mail-bulk"></i>
                <input type="text" id="postal_code" value="" name="postal_code" 
                       class="w-full input-with-icon border-2 border-slate-200 rounded-2xl px-4 py-3.5 pl-10 text-sm md:text-base text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all duration-200" 
                       placeholder="Postal Code">
            </div>
        </div>
    </div>

    <!-- Hidden Fields -->
    <input type="hidden" id="city" value="Default City" name="city">
    <input type="hidden" id="state" name="state" value="Default State">
    <input type="hidden" id="street_address" value="Default Address" name="street_address">

    <!-- Comment Section -->
    <div class="form-section">
        <div class="form-section-title mb-3">
            <div class="w-8 h-8 bg-pink-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-comment text-pink-700 text-sm"></i>
            </div>
            <h3 class="text-sm md:text-base font-bold text-slate-900">Leave a Message (Optional)</h3>
        </div>
        <textarea id="comment" name="comment" rows="3"
                  class="w-full border-2 border-slate-200 rounded-2xl px-4 py-3.5 text-sm md:text-base text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 resize-none" 
                  placeholder="Share why you're donating or leave an encouraging message..."></textarea>
    </div>

    <!-- Hidden input to maintain form structure -->
    <input type="hidden" name="payment_gateway" value="1">
    <input type="hidden" name="anonymous" value="0">

    <!-- Error Messages -->
    <div class="hidden" id="errorDonation" class="mb-5">
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <ul class="text-xs md:text-sm text-red-700" id="showErrorsDonation"></ul>
        </div>
    </div>

    <!-- Success Messages -->
    <div class="hidden" id="showDonationMessages" class="mb-5"></div>

    <!-- Submit Button -->
    <button type="submit" id="buttonDonation" 
            class="w-full bg-gradient-to-br from-emerald-600 to-teal-600 text-white py-3.5 px-4 rounded-2xl hover:from-emerald-700 hover:to-teal-700 focus:ring-4 focus:ring-emerald-500/30 transition-all duration-200 text-sm md:text-base font-semibold shadow-lg hover:shadow-xl active:scale-[0.98]">
        <i class="fas fa-heart mr-2"></i> Complete Donation
    </button>
</form>

<?php
$formHtml = ob_get_clean();
echo json_encode(['success' => true, 'html' => $formHtml]);
?>

