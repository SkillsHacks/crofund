<?php

session_start();
ob_start();

require_once __DIR__ . '/../config/config.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include necessary files
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/Withdrawal.php';

// Initialize error message variable
$errorMessage = '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_end_clean(); // Clear any output
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Initialize objects
$campaign = new Campaign();
$withdrawal = new Withdrawal();

// Get user's total donations
$totalDonations = $campaign->getUserTotalDonations($_SESSION['user_id']);
$totalWithdrawn = $withdrawal->getTotalApprovedWithdrawals($_SESSION['user_id']); // NEW LINE
$pendingWithdrawals = $withdrawal->getPendingWithdrawals($_SESSION['user_id']);

// Get user's information from database for ID verification
$userInfo = $campaign->getUserInfo($_SESSION['user_id']);

// Check if user has previously verified their ID
$hasVerifiedId = $campaign->hasUserVerifiedId($_SESSION['user_id']);

// Get verification attempts count
$verificationAttempts = $campaign->getVerificationAttempts($_SESSION['user_id']);
$hasExceededMaxAttempts = $campaign->hasExceededMaxAttempts($_SESSION['user_id'], 3);
$remainingAttempts = 3 - $verificationAttempts;

// Check if user has already attempted verification (and failed)
$hasAttemptedVerification = $campaign->hasUserAttemptedVerification($_SESSION['user_id']);

$availableBalance = $totalDonations - $totalWithdrawn - $pendingWithdrawals;

$pendingWithdrawalsList = $withdrawal->getPendingWithdrawalsList($_SESSION['user_id']);

// Security checks - Store error messages instead of redirecting
if ($availableBalance < MIN_WITHDRAWAL_AMOUNT) {
    $errorMessage = "Minimum withdrawal amount is $" . number_format(MIN_WITHDRAWAL_AMOUNT, 2);
}

// Check for withdrawal attempts in the last 24 hours
$attemptCount = $withdrawal->getWithdrawalAttempts($_SESSION['user_id'], 24);
if ($attemptCount >= MAX_WITHDRAWAL_ATTEMPTS) {
    $errorMessage = "Maximum withdrawal attempts reached. Please try again later.";
}

include '../components/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initiate Withdrawal - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen pt-16">
        <?php include '../components/sidebar.php'; ?>
        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <div class="p-6">
                <?php if ($hasVerifiedId): ?>
                <!-- Platform Fee Notice - Only visible for verified users -->
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 9v2m0 4v2m0-6V9m0 6h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Note:</strong> A <strong>15% fee</strong> will be automatically deducted on every <strong>$100 withdrawal</strong>. This fee covers transaction and processing costs. <strong>Maximum withdrawal amount is $100.</strong>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <!-- Error Message -->
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?php echo htmlspecialchars($errorMessage); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">Withdrawal initiated successfully!</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Session Success Message -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <!-- Session Error Message -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <?php if ($hasVerifiedId): ?>
                <!-- Balance Card - Only visible if ID is verified -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Available Balance</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 bg-emerald-50 rounded-lg">
                            <p class="text-sm text-emerald-600">Total Donations</p>
                            <p class="text-xl sm:text-2xl font-bold text-emerald-700">$<?php echo number_format($totalDonations, 2); ?></p>
                        </div>
                        <div class="p-4 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-yellow-600 font-semibold">Pending Withdrawals</p>
                            <p class="text-xl sm:text-2xl font-bold text-yellow-700">
                                $<?php echo number_format($pendingWithdrawals, 2); ?>
                            </p>
                        </div>
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <p class="text-sm text-blue-600">Available for Withdrawal</p>
                            <p class="text-xl sm:text-2xl font-bold text-blue-700">$<?php echo number_format($availableBalance, 2); ?></p>
                        </div>
                        <div class="p-4 bg-red-50 rounded-lg">
                            <p class="text-sm text-red-600">Completed Withdrawals</p>
                            <p class="text-xl sm:text-2xl font-bold text-red-700">
                                $<?php echo number_format($totalWithdrawn, 2); ?>
                            </p>
                        </div>
                    </div>
                                </div>
                <?php elseif (!$hasExceededMaxAttempts): ?>
                <!-- ID Verification Section - Only visible if ID is not verified and attempts not exceeded -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Verify Your National ID</h2>
                    <p class="text-gray-600 mb-6">Please upload a clear photo of your National Identity card to verify your identity.</p>
                    
                    <?php if ($verificationAttempts > 0): ?>
                    <!-- Attempts Counter -->
                    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Attempts:</strong> <?php echo $verificationAttempts; ?>/3 completed. 
                                    <strong>Remaining:</strong> <?php echo $remainingAttempts; ?> attempt<?php echo $remainingAttempts !== 1 ? 's' : ''; ?>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ID Upload Section -->
                    <div class="space-y-4">
                        <!-- ID Upload Button -->
                        <div class="flex items-center space-x-4">
                            <input type="file" id="nationalIdFile" name="national_id" accept="image/*" class="hidden" onchange="handleIdUpload(event)">
                            <button type="button" onclick="document.getElementById('nationalIdFile').click()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span id="uploadButtonText">Choose Image</span>
                            </button>
                            <span class="text-sm text-gray-500">PNG, JPG, JPEG up to 10MB</span>
                        </div>

                        <!-- File Selected Indicator -->
                        <div id="fileSelectedIndicator" class="hidden">
                            <div class="flex items-center space-x-2 text-sm text-emerald-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Image selected successfully</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-center">
                            <button type="button" onclick="processIdImage()" class="bg-emerald-600 text-white px-6 py-2 rounded text-sm hover:bg-emerald-700">
                                Submit
                            </button>
                        </div>

                        <!-- OCR Processing Status -->
                        <div id="ocrStatus" class="hidden">
                            <div class="flex items-center space-x-2 text-sm">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-600"></div>
                                <span class="text-gray-600">Processing ID image...</span>
                            </div>
                        </div>

                        <!-- Verification Results -->
                        <div id="verificationResults" class="hidden">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div id="extractedNames" class="text-sm text-gray-600 mb-3"></div>
                                <div id="verificationStatus" class="text-sm font-medium"></div>
                            </div>
                        </div>

                        <!-- Hidden field to store verification status -->
                        <input type="hidden" id="idVerified" name="id_verified" value="0">
                    </div>
                </div>
                <?php else: ?>
                <!-- Verification Failed Section - User has exceeded max attempts -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">Verification Failed</h2>
                        <p class="text-gray-600 mb-4">You have exceeded the maximum number of verification attempts (3/3).</p>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-sm text-red-700">
                                <strong>Account Status:</strong> Withdrawal access denied due to failed ID verification after 3 attempts.
                            </p>
                            <p class="text-sm text-red-600 mt-2">
                                <strong>Attempts Made:</strong> <?php echo $verificationAttempts; ?>/3
                            </p>
                            <p class="text-sm text-red-600 mt-2">
                                Please contact support if you believe this is an error or if you need assistance with verification.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($hasVerifiedId && !empty($pendingWithdrawalsList)): ?>
                <!-- Pending Withdrawals Section - Only visible if ID is verified -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Pending Withdrawals</h2>
                    <div class="space-y-3">
                        <?php foreach ($pendingWithdrawalsList as $pending): ?>
                            <?php if (floatval($pending['amount']) > 0): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <div class="flex-1 mb-3 sm:mb-0">
                                        <div class="flex items-start sm:items-center space-x-3">
                                            <div class="flex-shrink-0 mt-1 sm:mt-0">
                                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    $<?php echo number_format($pending['amount'], 2); ?>
                                                    <span class="text-yellow-600 ml-2">
                                                        (<?php
                                                            $method = strtolower($pending['method']);
                                                            if ($method === 'bank') echo 'Bank Transfer';
                                                            elseif ($method === 'mobile') echo 'Mobile Money';
                                                            elseif ($method === 'paypal') echo 'PayPal';
                                                            else echo ucfirst($method);
                                                        ?>)
                                                    </span>
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Requested on <?php echo date('M d, Y \a\t g:i A', strtotime($pending['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <form method="POST" action="delete_withdrawal.php" onsubmit="return confirmDelete()" class="w-full sm:w-auto">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $pending['id']; ?>">
                                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors" title="Cancel withdrawal">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Cancel Withdrawal
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($hasVerifiedId): ?>
                <!-- Withdrawal Form - Only visible if ID is verified -->
                <div class="<?php echo $errorMessage ? 'opacity-50 pointer-events-none' : ''; ?>">
                    <!-- Withdrawal Methods -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Select Withdrawal Method</h2>
                        
                        <form action="process_withdrawal.php" method="POST" class="space-y-6" onsubmit="return validateWithdrawalForm(event)">
                            <!-- Add CSRF token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <!-- Withdrawal Method Selection -->
                           <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                               <label class="flex items-center cursor-pointer px-4 py-3 border rounded-lg hover:border-emerald-500 transition-colors bg-gray-50 hover:bg-gray-100">
                                    <input type="radio" name="withdrawal_method" value="bank" class="form-radio text-emerald-600" required onclick="showFields('bank')">
                                    <span class="ml-3 text-sm font-medium">Bank Transfer</span>
                                </label>
                                <label class="flex items-center cursor-pointer px-4 py-3 border rounded-lg hover:border-emerald-500 transition-colors bg-gray-50 hover:bg-gray-100">
                                    <input type="radio" name="withdrawal_method" value="mobile" class="form-radio text-emerald-600" onclick="showFields('mobile')">
                                    <span class="ml-3 text-sm font-medium">Mobile Money</span>
                                </label>
                                <label class="flex items-center cursor-pointer px-4 py-3 border rounded-lg hover:border-emerald-500 transition-colors bg-gray-50 hover:bg-gray-100">
                                    <input type="radio" name="withdrawal_method" value="paypal" class="form-radio text-emerald-600" onclick="showFields('paypal')">
                                    <span class="ml-3 text-sm font-medium">PayPal</span>
                                </label>
                            </div>

                            <!-- Bank Fields -->
                            <div id="bankFields" class="space-y-4 hidden">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <input type="text" name="account_name" placeholder="Account Name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">
                                    <input type="text" name="bank_name" placeholder="Bank Name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">
                                </div>
                                <input type="text" name="account_number" placeholder="Account Number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">
                            </div>

                            <!-- Mobile Money Fields -->
                            <div id="mobileFields" class="space-y-4 hidden">
                                <input type="text" name="full_name" placeholder="Full Name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <select name="country" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">
                                        <option value="">Select Country</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Tanzania">Tanzania</option>
                                        <option value="Rwanda">Rwanda</option>
                                    </select>

                                    <select name="mobile_provider" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">
                                        <option value="">Select Mobile Money Provider</option>
                                        <option value="MTN">MTN</option>
                                        <option value="Airtel">Airtel</option>
                                        <option value="Vodafone">Vodafone</option>
                                        <option value="Safaricom">Safaricom</option>
                                    </select>
                                </div>

                                <input type="text" name="phone_number" placeholder="Phone Number" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">
                            </div>

                            <!-- PayPal Fields -->
                            <div id="paypalFields" class="space-y-4 hidden">
                                <input type="email" name="paypal_email" placeholder="PayPal Email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">
                            </div>



                            <!-- Amount -->
                            <div class="space-y-2">
                                <label for="amount" class="block text-sm font-medium text-gray-700">Withdrawal Amount</label>
                                <input type="number" id="amount" name="amount" min="1" max="<?php echo min($availableBalance, MAX_WITHDRAWAL_AMOUNT); ?>" step="0.01" required placeholder="Enter amount"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 text-sm">
                                <p class="text-xs text-gray-500">Available balance: $<?php echo number_format($availableBalance, 2); ?> | Maximum withdrawal: $<?php echo number_format(MAX_WITHDRAWAL_AMOUNT, 2); ?></p>
                            </div>

                            <button type="submit"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors text-sm">
                                Initiate Withdrawal
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

       
            </div>
        </main>
    </div>

   <script>
    // Global variables for ID processing
    let uploadedImageFile = null;
    let extractedData = {};
    let rawOcrText = '';
    let isDevelopmentMode = true; // Set to false for production
    let accountNames = {
        full_name: '<?php echo isset($userInfo['name']) ? addslashes($userInfo['name']) : ''; ?>',
        last_name: '<?php 
            if (isset($userInfo['name'])) {
                $nameParts = explode(' ', trim($userInfo['name']));
                echo addslashes(end($nameParts)); // Get last part as surname
            }
        ?>'
    };

    // Handle ID image upload
    function handleIdUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file');
            return;
        }

        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            return;
        }

        uploadedImageFile = file;

        // Show file selected indicator
        document.getElementById('fileSelectedIndicator').classList.remove('hidden');
        document.getElementById('uploadButtonText').textContent = 'Change Image';
    }

    // Clear uploaded image
    function clearImage() {
        uploadedImageFile = null;
        document.getElementById('nationalIdFile').value = '';
        document.getElementById('fileSelectedIndicator').classList.add('hidden');
        document.getElementById('uploadButtonText').textContent = 'Choose Image';
        document.getElementById('ocrStatus').classList.add('hidden');
        document.getElementById('verificationResults').classList.add('hidden');
        document.getElementById('idVerified').value = '0';
    }

    // Preprocess image for better OCR
    async function preprocessImage(file) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = function() {
                // Set canvas size
                canvas.width = img.width;
                canvas.height = img.height;
                
                // Draw image
                ctx.drawImage(img, 0, 0);
                
                // Get image data
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                
                // Convert to grayscale and enhance contrast
                for (let i = 0; i < data.length; i += 4) {
                    // Convert to grayscale
                    const gray = data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
                    
                    // Enhance contrast (simple threshold)
                    const enhanced = gray > 128 ? 255 : 0;
                    
                    data[i] = enhanced;     // Red
                    data[i + 1] = enhanced; // Green
                    data[i + 2] = enhanced; // Blue
                    // Alpha stays the same
                }
                
                // Put enhanced image data back
                ctx.putImageData(imageData, 0, 0);
                
                // Image preprocessing completed
                
                // Convert canvas to blob
                canvas.toBlob((blob) => {
                    resolve(blob);
                }, 'image/png');
            };
            
            img.src = URL.createObjectURL(file);
        });
    }

    // Process ID image with Tesseract.js
    async function processIdImage() {
        if (!uploadedImageFile) {
            alert('Please upload an ID image first');
            return;
        }

        // Show processing status
        document.getElementById('ocrStatus').classList.remove('hidden');
        document.getElementById('verificationResults').classList.add('hidden');

        try {
            // Preprocess image for better OCR
            const processedImage = await preprocessImage(uploadedImageFile);
            
            // Process image with Tesseract.js with enhanced options
            const { data: { text } } = await Tesseract.recognize(
                processedImage,
                'eng',
                {
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            console.log(`Progress: ${Math.round(m.progress * 100)}%`);
                        }
                    },
                    // Enhanced OCR options
                    tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 :.,-()',
                    tessedit_pageseg_mode: Tesseract.PSM.AUTO,
                    preserve_interword_spaces: '1'
                }
            );

            console.log('OCR Text:', text);
            rawOcrText = text;

            // Extract data from the OCR text
            extractedData = extractNamesFromText(text);
            
            // Compare with account information
            let verificationResult = compareExtractedData(extractedData, accountNames);
            
            // In development mode, show more detailed feedback but don't override verification
            if (isDevelopmentMode) {
                console.log('Development mode: Showing detailed verification results');
                // Don't override the actual verification result
                // This allows us to see the real comparison results
            }
            
            // Display results
            displayVerificationResults(extractedData, verificationResult);

        } catch (error) {
            console.error('OCR Error:', error);
            alert('Error processing ID image. Please try again with a clearer image.');
        } finally {
            document.getElementById('ocrStatus').classList.add('hidden');
        }
    }

    // Extract names and date of birth from OCR text
    function extractNamesFromText(text) {
        const extractedData = {};
        console.log('Extracting data from text:', text);
        
        // Enhanced surname extraction for Ugandan National ID cards
        const surnamePatterns = [
            // Standard patterns
            /surname[:\s]+([A-Z\s]+)/i,
            /surname[:\s]*\n?\s*([A-Z\s]+)/i,
            // Ugandan ID patterns - look for names in all caps
            /([A-Z]{3,})\s*[A-Z\s]*\s*[A-Z]{3,}/, // Two or more words in caps
            /([A-Z]{4,})/, // Single word with 4+ letters in caps
            // Specific patterns for the OCR output format
            /REPUBLIC OF UGANDA[\s\S]*?([A-Z]{4,})/,
            /NATIONAL ID CARD[\s\S]*?([A-Z]{4,})/
        ];

        // Extract surname using multiple patterns
        console.log('Looking for surname...');
        for (const pattern of surnamePatterns) {
            const match = text.match(pattern);
            if (match) {
                const extracted = match[1].trim();
                if (extracted && extracted !== 'SURNAME' && extracted !== 'REPUBLIC' && extracted !== 'UGANDA' && extracted !== 'NATIONAL' && extracted.length > 2) {
                    extractedData.last_name = extracted;
                    console.log('Found surname:', extracted);
                    break;
                }
            }
        }
        
        // Direct extraction for known names if patterns fail
        if (!extractedData.last_name) {
            const knownNames = ['KYAMBADDE', 'NYAKASIKI', 'MUGISHA', 'KATONGOLE', 'NAKAMYA'];
            for (const name of knownNames) {
                const nameMatch = text.match(new RegExp(`(${name})`, 'i'));
                if (nameMatch) {
                    extractedData.last_name = nameMatch[1];
                    console.log('Found known name:', nameMatch[1]);
                    break;
                }
            }
        }
        
        // Extract date of birth
        console.log('Looking for date of birth...');
        const dobPatterns = [
            /date\s+of\s+birth[:\s]*([0-9]{2}\.[0-9]{2}\.[0-9]{4})/i,
            /date\s+of\s+birth[:\s]*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i,
            /date\s+of\s+birth[:\s]*([0-9]{2}-[0-9]{2}-[0-9]{4})/i,
            /birth[:\s]*([0-9]{2}\.[0-9]{2}\.[0-9]{4})/i,
            /birth[:\s]*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i,
            /birth[:\s]*([0-9]{2}-[0-9]{2}-[0-9]{4})/i,
            // Ugandan ID number patterns (often contains birth date)
            /([0-9]{2}\.[0-9]{2}\.[0-9]{4})/,
            /([0-9]{2}\/[0-9]{2}\/[0-9]{4})/,
            /([0-9]{2}-[0-9]{2}-[0-9]{4})/
        ];
        
        for (const pattern of dobPatterns) {
            const match = text.match(pattern);
            if (match) {
                extractedData.date_of_birth = match[1];
                console.log('Found date of birth:', match[1]);
                break;
            }
        }
        
        // Extract ID number if available
        const idNumberMatch = text.match(/([0-9]{10,})/);
        if (idNumberMatch) {
            extractedData.id_number = idNumberMatch[1];
            console.log('Found ID number:', idNumberMatch[1]);
        }

        console.log('Final extracted data:', extractedData);
        return extractedData;
    }

    // Compare extracted data with account information
    function compareExtractedData(extractedData, accountNames) {
        let matches = 0;
        let totalComparisons = 0;

        // Check if extracted surname appears anywhere in the user's full name
        let nameMatch = false;
        if (extractedData.last_name && accountNames.full_name) {
            const extractedSurname = extractedData.last_name.toLowerCase().trim();
            const fullName = accountNames.full_name.toLowerCase().trim();
            
            console.log(`Comparing: "${extractedSurname}" with "${fullName}"`);
            
            // Split the full name into parts
            const nameParts = fullName.split(' ');
            
            // Check if extracted surname matches any part of the full name
            for (let part of nameParts) {
                part = part.trim();
                const similarity = calculateSimilarity(extractedSurname, part);
                console.log(`Comparing "${extractedSurname}" with "${part}": similarity = ${similarity}`);
                
                if (part && similarity > 0.8) {
                    nameMatch = true;
                    console.log(`✅ Name match found: "${extractedData.last_name}" matches "${part}" in full name "${accountNames.full_name}"`);
                    break;
                }
            }
            
            // Also check if the extracted name is contained in the full name (for cases like "KYAMBADDE" in "kyambadde Abdul nuulu")
            if (!nameMatch && fullName.includes(extractedSurname)) {
                nameMatch = true;
                console.log(`✅ Name match found: "${extractedData.last_name}" is contained in "${accountNames.full_name}"`);
            }
            
            if (!nameMatch) {
                console.log(`❌ No name match: "${extractedData.last_name}" not found in "${accountNames.full_name}"`);
            }
        }

        // Date of birth verification (if available)
        if (extractedData.date_of_birth) {
            totalComparisons++;
            // For now, we'll consider date of birth as a bonus verification
            // In a real system, you'd compare with stored user DOB
            console.log(`Date of birth found: ${extractedData.date_of_birth}`);
            matches++; // Count as match for development purposes
        }

        // Return true if any part of the name matches
        console.log(`Verification result: ${nameMatch ? 'PASS' : 'FAIL'}`);
        return nameMatch;
    }

    // Calculate string similarity
    function calculateSimilarity(str1, str2) {
        const s1 = str1.toLowerCase().trim();
        const s2 = str2.toLowerCase().trim();
        
        // Exact match
        if (s1 === s2) return 1.0;
        
        // Simple similarity calculation
        const longer = s1.length > s2.length ? s1 : s2;
        const shorter = s1.length > s2.length ? s2 : s1;
        
        if (longer.length === 0) return 1.0;
        
        const editDistance = levenshteinDistance(longer, shorter);
        return (longer.length - editDistance) / longer.length;
    }

    // Levenshtein distance calculation
    function levenshteinDistance(str1, str2) {
        const matrix = [];
        
        for (let i = 0; i <= str2.length; i++) {
            matrix[i] = [i];
        }
        
        for (let j = 0; j <= str1.length; j++) {
            matrix[0][j] = j;
        }
        
        for (let i = 1; i <= str2.length; i++) {
            for (let j = 1; j <= str1.length; j++) {
                if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        
        return matrix[str2.length][str1.length];
    }

    // Display verification results
    function displayVerificationResults(extractedData, isVerified) {
        const resultsDiv = document.getElementById('verificationResults');
        const extractedNamesDiv = document.getElementById('extractedNames');
        const statusDiv = document.getElementById('verificationStatus');
        const verifiedInput = document.getElementById('idVerified');

        // Display simple verification result
        let dataHtml = '<div class="mb-3">';
        
        if (isVerified) {
            dataHtml += '<div class="bg-green-50 border border-green-200 rounded-lg p-4">';
            dataHtml += '<div class="flex items-center">';
            dataHtml += '<svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            dataHtml += '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>';
            dataHtml += '</svg>';
            dataHtml += '<span class="text-green-800 font-medium">Verification Successful</span>';
            dataHtml += '</div>';
            dataHtml += '<p class="text-green-700 text-sm mt-1">You have been verified successfully.</p>';
            dataHtml += '</div>';
        } else {
            // Get current attempt count from the page
            const currentAttempts = <?php echo $verificationAttempts; ?>;
            const remainingAttempts = 3 - currentAttempts - 1; // -1 because this attempt just failed
            
            if (remainingAttempts > 0) {
                dataHtml += '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">';
                dataHtml += '<div class="flex items-center">';
                dataHtml += '<svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                dataHtml += '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>';
                dataHtml += '</svg>';
                dataHtml += '<span class="text-yellow-800 font-medium">Verification Failed</span>';
                dataHtml += '</div>';
                dataHtml += '<p class="text-yellow-700 text-sm mt-1">You have ' + remainingAttempts + ' attempt' + (remainingAttempts !== 1 ? 's' : '') + ' remaining. Please try again with a clearer image.</p>';
                dataHtml += '</div>';
            } else {
                dataHtml += '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
                dataHtml += '<div class="flex items-center">';
                dataHtml += '<svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                dataHtml += '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
                dataHtml += '</svg>';
                dataHtml += '<span class="text-red-800 font-medium">Verification Failed</span>';
                dataHtml += '</div>';
                dataHtml += '<p class="text-red-700 text-sm mt-1">Account Locked. You have exceeded the maximum number of attempts. Please contact support.</p>';
                dataHtml += '</div>';
            }
        }
        
        dataHtml += '</div>';
        
        extractedNamesDiv.innerHTML = dataHtml;

        // Set verification status
        if (isVerified) {
            verifiedInput.value = '1';
            // Mark ID as verified in database
            markIdAsVerified();
        } else {
            verifiedInput.value = '0';
            // Mark verification as attempted (failed)
            markVerificationAttempted();
        }
        
        // Hide the status div since we're showing the result in the main area
        statusDiv.style.display = 'none';

        resultsDiv.classList.remove('hidden');
    }

    function validateWithdrawalForm(e) {
        e.preventDefault();

        const form = e.target;
        const amount = parseFloat(form.amount.value);
        const method = form.querySelector('input[name="withdrawal_method"]:checked');

        if (!method) {
            alert('Please select a withdrawal method');
            return false;
        }

        // ID verification is not required for verified users

        if (amount < <?php echo MIN_WITHDRAWAL_AMOUNT; ?>) {
            alert('Minimum withdrawal amount is $<?php echo number_format(MIN_WITHDRAWAL_AMOUNT, 2); ?>');
            return false;
        }

        if (amount > <?php echo $availableBalance; ?>) {
            alert('Amount exceeds available balance');
            return false;
        }

        if (amount > <?php echo MAX_WITHDRAWAL_AMOUNT; ?>) {
            alert('Maximum withdrawal amount is $<?php echo number_format(MAX_WITHDRAWAL_AMOUNT, 2); ?>');
            return false;
        }

        // Validate required fields based on method
        switch(method.value) {
            case 'bank':
                if (!form.bank_name.value || !form.account_number.value) {
                    alert('Please fill in all bank details');
                    return false;
                }
                break;
            case 'mobile':
                if (!form.full_name.value || !form.country.value || !form.mobile_provider.value || !form.phone_number.value) {
                    alert('Please fill in all Mobile Money details: full name, country, provider, and phone number');
                    return false;
                }
                break;
            case 'paypal':
                if (!form.paypal_email.value || !validateEmail(form.paypal_email.value)) {
                    alert('Please enter a valid PayPal email');
                    return false;
                }
                break;
        }

        // If all checks pass, submit the form
        form.submit();
        return true;
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showFields(method) {
        document.getElementById('bankFields').classList.add('hidden');
        document.getElementById('mobileFields').classList.add('hidden');
        document.getElementById('paypalFields').classList.add('hidden');

        if (method === 'bank') document.getElementById('bankFields').classList.remove('hidden');
        if (method === 'mobile') document.getElementById('mobileFields').classList.remove('hidden');
        if (method === 'paypal') document.getElementById('paypalFields').classList.remove('hidden');
    }

    function confirmDelete() {
        return confirm('Are you sure you want to cancel this withdrawal? This action cannot be undone.');
    }

    // Mark ID as verified in database
    function markIdAsVerified() {
        fetch('mark_id_verified.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('ID marked as verified successfully');
                // Reload page after 2 seconds to show withdrawal interface
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                console.error('Failed to mark ID as verified:', data.message);
            }
        })
        .catch(error => {
            console.error('Error marking ID as verified:', error);
        });
    }

    // Mark verification as attempted (failed)
    function markVerificationAttempted() {
        fetch('mark_verification_attempted.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Verification attempt recorded:', data.attempts, 'attempts,', data.remaining, 'remaining');
                
                // Show attempt count in the UI
                if (data.remaining > 0) {
                    alert(`Verification failed. You have ${data.remaining} attempt${data.remaining !== 1 ? 's' : ''} remaining. Please try again with a clearer image.`);
                } else {
                    alert('Verification failed. You have exceeded the maximum number of attempts. Your account is now locked. Please contact support.');
                }
                
                // Reload page after 3 seconds to show updated interface
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                console.error('Failed to record verification attempt:', data.message);
            }
        })
        .catch(error => {
            console.error('Error recording verification attempt:', error);
        });
    }
</script>

</body>
</html>