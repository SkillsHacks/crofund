<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Get session ID from URL
$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    header("Location: " . BASE_URL . "/campaigns/browse.php");
    exit();
}

try {
    // Initialize Stripe
    require_once __DIR__ . '/../vendor/autoload.php';
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Retrieve the checkout session
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
    
    // Store metadata for redirect
    $metadata = $session->metadata;
    
    if ($session->payment_status === 'paid') {
        // Payment was successful, create donation record
        
        // Convert country code to full name
        $countryNames = [
            'US' => 'United States', 'CA' => 'Canada', 'GB' => 'United Kingdom',
            'DE' => 'Germany', 'FR' => 'France', 'AU' => 'Australia',
            'JP' => 'Japan', 'CN' => 'China', 'IN' => 'India',
            'BR' => 'Brazil', 'MX' => 'Mexico', 'ZA' => 'South Africa',
            'NG' => 'Nigeria', 'KE' => 'Kenya', 'UG' => 'Uganda',
            'GH' => 'Ghana', 'TZ' => 'Tanzania', 'ET' => 'Ethiopia',
            'RW' => 'Rwanda', 'EG' => 'Egypt', 'SA' => 'Saudi Arabia',
            'AE' => 'United Arab Emirates'
        ];
        
        $countryFullName = $countryNames[$metadata->country] ?? $metadata->country;
        
        // Create donation record
        $campaign = new Campaign();
        $donationRecord = [
            'campaign_id' => $metadata->campaign_id,
            'donor_name' => $metadata->donor_name,
            'donor_email' => $session->customer_email,
            'amount' => $session->amount_total / 100, // Convert from cents
            'payment_gateway' => 'stripe',
            'status' => 'completed',
            'country' => $countryFullName,
            'postal_code' => $metadata->postal_code,
            'comment' => $metadata->comment,
            'anonymous' => intval($metadata->anonymous),
            'transaction_id' => $session->payment_intent,
            'gateway_response' => json_encode($session),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $donationId = $campaign->createDonation($donationRecord);
        
        if ($donationId) {
            // Update campaign amount
            $campaign->updateCampaignAmount($metadata->campaign_id, $donationRecord['amount']);
            
            // Store success message
            $_SESSION['success'] = 'Thank you! Your donation has been processed successfully.';
        } else {
            $_SESSION['error'] = 'Payment processed but there was an issue recording your donation. Please contact support.';
        }
    } else {
        $_SESSION['error'] = 'Payment was not completed successfully.';
    }
    
} catch (Exception $e) {
    error_log("Stripe success page error: " . $e->getMessage());
    $_SESSION['error'] = 'There was an issue processing your payment. Please contact support.';
}

// Redirect to thank you page with success message
if (isset($metadata->campaign_id)) {
    $amount = $session->amount_total / 100; // Convert from cents
    $donorName = $metadata->donor_name;
    header("Location: " . BASE_URL . "/thank-you.php?campaign_id=" . $metadata->campaign_id . "&amount=" . $amount . "&donor=" . urlencode($donorName) . "&gateway=stripe");
} else {
    header("Location: " . BASE_URL . "/campaigns/browse.php");
}
exit();
?> 