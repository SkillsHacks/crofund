<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Campaign.php';

$donationId = $_GET['id'] ?? 0;

if (!$donationId) {
    header("Location: " . BASE_URL . "/campaigns/browse.php");
    exit();
}

// Get donation details (you'll need to implement this method)
// For now, we'll show a generic success message
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Completed - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://rsms.me/inter/inter.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
</head>
<body class="bg-gray-100 font-sans">
    
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center">
                <!-- Success Icon -->
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-emerald-100 mb-6">
                    <svg class="h-10 w-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                
                <!-- Success Message -->
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Thank You!</h1>
                <p class="text-lg text-gray-600 mb-6">Your donation has been processed successfully.</p>
                
                <!-- Donation ID -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600">Donation ID: <span class="font-mono font-medium"><?php echo $donationId; ?></span></p>
                    <p class="text-xs text-gray-500 mt-1">Keep this for your records</p>
                </div>
                
                <!-- What Happens Next -->
                <div class="bg-blue-50 rounded-lg p-4 mb-6 text-left">
                    <h3 class="font-medium text-blue-900 mb-2">What happens next?</h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• You'll receive a confirmation email</li>
                        <li>• The campaign organizer will be notified</li>
                        <li>• Your donation will appear in the campaign totals</li>
                        <li>• Campaign progress will be updated in real-time</li>
                    </ul>
                </div>
                
                <!-- Action Buttons -->
                <div class="space-y-3">
                    <a href="<?php echo BASE_URL; ?>/campaigns/browse.php" 
                       class="w-full bg-emerald-600 text-white py-3 px-4 rounded-md hover:bg-emerald-700 transition-colors font-medium">
                        Browse More Campaigns
                    </a>
                    <a href="<?php echo BASE_URL; ?>/" 
                       class="w-full bg-gray-200 text-gray-700 py-3 px-4 rounded-md hover:bg-gray-300 transition-colors">
                        Return to Homepage
                    </a>
                </div>
                
                <!-- Social Sharing -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <p class="text-sm text-gray-600 mb-3">Share your generosity:</p>
                    <div class="flex justify-center space-x-3">
                        <button onclick="shareOnFacebook()" 
                                class="bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </button>
                        <button onclick="shareOnTwitter()" 
                                class="bg-blue-400 text-white p-2 rounded-full hover:bg-blue-500 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </button>
                        <button onclick="copyLink()" 
                                class="bg-gray-600 text-white p-2 rounded-full hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent('I just made a donation on CroFunding! Join me in making a difference.');
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank');
        }
        
        function shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent('I just made a donation on CroFunding! Join me in making a difference.');
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
        }
        
        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Link copied to clipboard!');
            }).catch(() => {
                alert('Failed to copy link');
            });
        }
    </script>
</body>
</html>