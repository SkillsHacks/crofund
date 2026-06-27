<?php
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Ensure CSRF token exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$sessionId = $_GET['session_id'] ?? '';
$donationData = $_SESSION['stripe_donation_' . $sessionId] ?? null;

if (!$donationData) {
    header("Location: " . BASE_URL . "/campaigns/browse.php");
    exit();
}

// Get campaign details
try {
    $campaign = new Campaign();
    $campaignDetails = $campaign->getCampaignById($donationData['campaign_id']);
    
    if (!$campaignDetails) {
        header("Location: " . BASE_URL . "/campaigns/browse.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error in process-payment.php: " . $e->getMessage());
    header("Location: " . BASE_URL . "/campaigns/browse.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
    <link rel="icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png" type="image/x-icon">
</head>
<body class="bg-gray-100 font-sans">
    
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center">
                <!-- Loading Spinner -->
                <div id="loadingSpinner" class="mb-6">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-emerald-600 mx-auto"></div>
                    <p class="mt-4 text-lg font-medium text-gray-700">Processing your payment...</p>
                    <p class="mt-2 text-sm text-gray-500">Please wait while we complete your donation</p>
                </div>
                
                <!-- Success Message (Hidden initially) -->
                <div id="successMessage" class="hidden mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-emerald-100 mb-4">
                        <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Payment Successful!</h3>
                    <p class="text-sm text-gray-500">Your donation has been processed successfully</p>
                </div>
                
                <!-- Payment Details -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h4 class="font-medium text-gray-900 mb-3">Payment Details</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Campaign:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($campaignDetails['title']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Amount:</span>
                            <span class="font-medium text-emerald-600">$<?php echo number_format($donationData['amount'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Donor:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($donationData['full_name']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($donationData['email']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div id="actionButtons" class="hidden space-y-3">
                    <a href="<?php echo BASE_URL; ?>/campaigns/public-view.php?id=<?php echo $donationData['campaign_id']; ?>" 
                       class="w-full bg-emerald-600 text-white py-2 px-4 rounded-md hover:bg-emerald-700 transition-colors">
                        Return to Campaign
                    </a>
                    <a href="<?php echo BASE_URL; ?>/campaigns/browse.php" 
                       class="w-full bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 transition-colors">
                        Browse Other Campaigns
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simulate payment processing
        setTimeout(() => {
            // Hide loading spinner
            document.getElementById('loadingSpinner').classList.add('hidden');
            
            // Show success message
            document.getElementById('successMessage').classList.remove('hidden');
            
            // Show action buttons
            document.getElementById('actionButtons').classList.remove('hidden');
            
            // Process the donation in the background
            processDonation();
        }, 3000); // 3 second delay to simulate processing
        
        function processDonation() {
            console.log('Processing donation...');
            console.log('Session ID:', '<?php echo $sessionId; ?>');
            console.log('CSRF Token:', '<?php echo $_SESSION['csrf_token']; ?>');
            
            // Send donation data to backend
            fetch('<?php echo BASE_URL; ?>/stripe/complete-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: '<?php echo $sessionId; ?>',
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text(); // Get response as text first
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        console.log('Donation processed successfully');
                    } else {
                        console.error('Error processing donation:', data.error);
                    }
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    console.error('Raw response:', text);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
            });
        }
    </script>
</body>
</html> 