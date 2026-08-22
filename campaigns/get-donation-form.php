<?php
ob_start();
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/Campaign.php';
    require_once __DIR__ . '/../config/TrustRiskService.php';

    if (!isset($_GET['id'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Campaign ID is required']);
        exit();
    }

    $campaignId = intval($_GET['id']);
    $campaign   = new Campaign();
    $details    = $campaign->getCampaignById($campaignId);

    if (!$details) {
        ob_end_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Campaign not found']);
        exit();
    }

    $trustRisk = new TrustRiskService();
    $trustRisk->enforceForAction((int) $details['user_id'], 'receive_donations');

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $paymentGateways = getEnabledPaymentGateways();

    // Build form HTML in a nested buffer
    ob_start();
    ?>
    <form method="POST" id="formDonation">
        <input type="hidden" name="csrf_token"   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="campaign_id"  value="<?php echo (int)$details['id']; ?>">

        <!-- Amount -->
        <div class="form-section">
            <div class="form-section-title mb-3">
                <h3 class="text-sm font-bold text-slate-900">Donation Amount</h3>
            </div>

            <div class="grid grid-cols-4 gap-2 mb-4">
                <?php foreach ([25, 50, 100, 250] as $preset): ?>
                <button type="button"
                    class="donation-amount-btn py-3 border-2 border-slate-200 rounded-xl text-sm font-semibold text-slate-700 bg-white hover:border-emerald-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all<?php echo $preset === 100 ? ' active' : ''; ?>"
                    data-amount="<?php echo $preset; ?>">
                    $<?php echo $preset; ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="input-group">
                <input type="number" min="5" step="0.01" id="onlyNumber" name="amount" value="100"
                    class="w-full input-with-icon border-2 border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                    placeholder="Or enter custom amount" required>
            </div>
            <p class="text-xs text-slate-500 mt-2">
                Minimum donation: $5 USD
            </p>
        </div>

        <!-- Payment Method -->
        <?php if (!empty($paymentGateways)): ?>
        <div class="form-section">
            <div class="form-section-title mb-3">
                <h3 class="text-sm font-bold text-slate-900">Payment Method</h3>
            </div>
            <div class="space-y-2">
                <?php foreach ($paymentGateways as $i => $gw): ?>
                <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all gateway-option <?php echo $i === 0 ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white hover:border-emerald-300'; ?>"
                       data-gateway="<?php echo htmlspecialchars($gw['code']); ?>">
                    <input type="radio" name="payment_gateway" value="<?php echo htmlspecialchars($gw['code']); ?>" class="sr-only" <?php echo $i === 0 ? 'checked' : ''; ?>>
                    <div class="flex items-center gap-3 w-full">
                        <div class="w-5 h-5 flex items-center justify-center border-2 rounded-full <?php echo $i === 0 ? 'border-emerald-500' : 'border-slate-300'; ?>">
                            <div class="w-2.5 h-2.5 rounded-full <?php echo $i === 0 ? 'bg-emerald-500' : ''; ?>"></div>
                        </div>
                        <span class="text-sm font-semibold text-slate-900">
                            <?php echo $gw['code'] === 'stripe' ? 'Credit / Debit Card' : htmlspecialchars($gw['name']); ?>
                        </span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="form-section">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                No payment methods are currently enabled. Please contact support.
            </div>
        </div>
        <?php endif; ?>

        <!-- Donor Info -->
        <div class="form-section non-stripe-fields">
            <div class="form-section-title mb-3">
                <h3 class="text-sm font-bold text-slate-900">Your Information</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="input-group">
                    <input type="text" id="cardholder-name" name="full_name"
                        class="w-full input-with-icon border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                        placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <input type="email" id="cardholder-email" name="email"
                        class="w-full input-with-icon border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                        placeholder="Email Address" required>
                </div>
            </div>
        </div>

        <!-- Location -->
        <div class="form-section non-stripe-fields">
            <div class="form-section-title mb-3">
                <h3 class="text-sm font-bold text-slate-900">Location</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="input-group">
                    <select id="country" name="country"
                        class="w-full input-with-icon border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all appearance-none" required>
                        <option value="">Select Country</option>
                        <option value="US">United States</option>
                        <option value="CA">Canada</option>
                        <option value="GB">United Kingdom</option>
                        <option value="AU">Australia</option>
                        <option value="DE">Germany</option>
                        <option value="FR">France</option>
                        <option value="JP">Japan</option>
                        <option value="IN">India</option>
                        <option value="BR">Brazil</option>
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
                        <option value="MX">Mexico</option>
                        <option value="CN">China</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="input-group">
                    <input type="text" id="postal_code" name="postal_code"
                        class="w-full input-with-icon border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                        placeholder="Postal Code">
                </div>
            </div>
        </div>

        <input type="hidden" name="city"           value="Default City">
        <input type="hidden" name="state"          value="Default State">
        <input type="hidden" name="street_address" value="Default Address">

        <!-- Message -->
        <div class="form-section">
            <div class="form-section-title mb-3">
                <h3 class="text-sm font-bold text-slate-900">Leave a Message <span class="font-normal text-slate-400">(Optional)</span></h3>
            </div>
            <textarea id="comment" name="comment" rows="3"
                class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none"
                placeholder="Share why you're donating..."></textarea>
        </div>

        <!-- Credit or debit card details -->
        <div id="card-fields" class="form-section hidden">
            <div class="mb-3">
                <h3 class="text-sm font-bold text-slate-900">Credit or debit card</h3>
            </div>
            <div class="space-y-3">
                <input type="email" id="card-email"
                    class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                    placeholder="Email Address" autocomplete="email">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="text" id="card-first-name"
                        class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                        placeholder="First name" autocomplete="given-name">
                    <input type="text" id="card-last-name"
                        class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                        placeholder="Last name" autocomplete="family-name">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" id="use-billing-name" class="h-4 w-4 accent-emerald-600">
                    Use as billing name
                </label>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Card number</label>
                    <input type="text" id="card-number" inputmode="numeric" autocomplete="cc-number"
                        class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                        placeholder="Card number">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">MM/YY</label>
                        <input type="text" id="card-expiry" inputmode="numeric" autocomplete="cc-exp"
                            class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                            placeholder="MM/YY">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">CVV</label>
                        <input type="password" id="card-cvv" inputmode="numeric" autocomplete="cc-csc"
                            class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                            placeholder="CVV">
                    </div>
                </div>
                    <input type="text" id="cardholder-name-card"
                    class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all"
                    placeholder="Name on card" autocomplete="cc-name">
                <select id="card-country"
                    class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-900 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 focus:outline-none transition-all">
                    <option value="UG" selected>Uganda</option>
                    <option value="US">United States</option>
                    <option value="GB">United Kingdom</option>
                    <option value="CA">Canada</option>
                    <option value="KE">Kenya</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>

        <input type="hidden" name="anonymous" value="0">

        <!-- Errors -->
        <div class="hidden mb-4" id="errorDonation">
            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                <ul class="text-sm text-red-700 space-y-1" id="showErrorsDonation"></ul>
            </div>
        </div>

        <div class="hidden mb-4" id="showDonationMessages"></div>

        <!-- PayPal button container -->
        <div id="paypal-button-container" class="w-full mt-4 hidden"></div>

        <!-- Stripe / default submit -->
        <button type="submit" id="buttonDonation"
            class="w-full bg-gradient-to-br from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white py-4 px-4 rounded-xl font-bold text-sm shadow-lg hover:shadow-xl transition-all active:scale-[0.98]">
            Complete Donation
        </button>
    </form>
    <?php
    $formHtml = ob_get_clean();

    // Discard any stray output accumulated before this point
    ob_end_clean();

    echo json_encode([
        'success'          => true,
        'html'             => $formHtml,
        'paypal_client_id' => PAYPAL_CLIENT_ID,
        'paypal_mode'      => PAYPAL_MODE,
    ]);

} catch (Throwable $e) {
    // Discard any partial output so we always return valid JSON
    if (ob_get_level() > 0) ob_end_clean();
    error_log('get-donation-form.php error: ' . $e->getMessage());
    $code = (int) $e->getCode();
    if ($code >= 400) {
        http_response_code($code);
    } else {
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'error'   => 'Unable to load the donation form. Please try again.',
    ]);
}
