<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/TrustRiskGuard.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please login to delete campaigns']);
    exit();
}

// Check if campaign ID is provided
if (!isset($_POST['campaign_id']) || empty($_POST['campaign_id'])) {
    echo json_encode(['success' => false, 'error' => 'Campaign ID is required']);
    exit();
}

$campaign_id = (int)$_POST['campaign_id'];

TrustRiskGuard::enforceOrAbort((int) $_SESSION['user_id'], 'delete_campaign', true);

try {
    $campaign = new Campaign();
    
    // Log the deletion attempt
    error_log("Campaign deletion attempt for user " . $_SESSION['user_id'] . " for campaign ID: " . $campaign_id);
    
    // Check if campaign exists and belongs to the user
    $existing_campaign = $campaign->getCampaignById($campaign_id);
    
    if (!$existing_campaign) {
        error_log("Campaign deletion failed for user " . $_SESSION['user_id'] . ": Campaign not found with ID: " . $campaign_id);
        echo json_encode(['success' => false, 'error' => 'Campaign not found']);
        exit();
    }
    
    if ($existing_campaign['user_id'] != $_SESSION['user_id']) {
        error_log("Campaign deletion failed for user " . $_SESSION['user_id'] . ": Unauthorized access to campaign ID: " . $campaign_id);
        echo json_encode(['success' => false, 'error' => 'You are not authorized to delete this campaign']);
        exit();
    }
    
    // Check if campaign has donations
    $db = Database::getInstance();
    
    // First, let's check all donations for this campaign (for debugging)
    $all_donations = $db->fetchAll(
        "SELECT id, status, amount FROM donations WHERE campaign_id = ?",
        [$campaign_id]
    );
    
    error_log("Campaign deletion check for user " . $_SESSION['user_id'] . ": Campaign ID: " . $campaign_id . ", All donations: " . json_encode($all_donations));
    
    // Count only completed donations with actual money
    $donation_result = $db->fetchOne(
        "SELECT COUNT(*) as donation_count, COALESCE(SUM(amount), 0) as total_amount FROM donations WHERE campaign_id = ? AND status = 'completed' AND amount > 0",
        [$campaign_id]
    );
    
    $donation_count = $donation_result['donation_count'] ?? 0;
    $total_amount = $donation_result['total_amount'] ?? 0;
    
    error_log("Campaign deletion check for user " . $_SESSION['user_id'] . ": Campaign ID: " . $campaign_id . ", Completed donations count: " . $donation_count . ", Total amount: " . $total_amount);
    
    if ($donation_count > 0 && $total_amount > 0) {
        error_log("Campaign deletion failed for user " . $_SESSION['user_id'] . ": Campaign has completed donations with money, ID: " . $campaign_id . ", donations: " . $donation_count . ", amount: " . $total_amount);
        echo json_encode(['success' => false, 'error' => 'Cannot delete campaign with existing donations. Please contact support.']);
        exit();
    }
    
    // Delete the campaign
    $result = $campaign->deleteCampaign($campaign_id);
    
    if ($result) {
        error_log("Campaign deleted successfully for user " . $_SESSION['user_id'] . " with ID: " . $campaign_id);
        echo json_encode(['success' => true, 'message' => 'Campaign deleted successfully']);
    } else {
        error_log("Campaign deletion failed for user " . $_SESSION['user_id'] . ": Database deletion failed for ID: " . $campaign_id);
        echo json_encode(['success' => false, 'error' => 'Failed to delete campaign. Please try again.']);
    }
    
} catch (Exception $e) {
    // Log the error details
    error_log("Campaign deletion error for user " . $_SESSION['user_id'] . ": " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return generic error message
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
} catch (Error $e) {
    // Log PHP errors (fatal errors, etc.)
    error_log("Campaign deletion PHP error for user " . $_SESSION['user_id'] . ": " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return generic error message
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}
?>
