<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Get session ID from URL
$sessionId = $_GET['session_id'] ?? '';

// Store cancel message with more context
$_SESSION['info'] = 'Payment was cancelled. No charges were made. You can try again anytime.';

// Try to get campaign ID from session if available
if (isset($_SESSION['last_campaign_id'])) {
    $campaignId = $_SESSION['last_campaign_id'];
    unset($_SESSION['last_campaign_id']); // Clear it
    header("Location: " . BASE_URL . "/thank-you.php?campaign_id=" . $campaignId . "&cancelled=1");
} else {
    // Redirect back to campaigns page if no specific campaign
    header("Location: " . BASE_URL . "/campaigns/browse.php");
}
exit();
?> 