<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/TrustRiskGuard.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

if (
    !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
    !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request token']);
    exit();
}

try {
    TrustRiskGuard::enforceOrAbort((int) $_SESSION['user_id'], 'edit_campaign', true);

    $campaign = new Campaign();
    
    // Validate required fields
    if (empty($_POST['campaign_id']) || empty($_POST['update_title']) || empty($_POST['update_content'])) {
        echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
        exit();
    }
    
    $campaignId = intval($_POST['campaign_id']);
    $userId = intval($_SESSION['user_id']);
    
    // Verify user owns this campaign
    $campaignDetails = $campaign->getCampaignById($campaignId);
    if (!$campaignDetails || $campaignDetails['user_id'] != $userId) {
        echo json_encode(['success' => false, 'error' => 'You can only update your own campaigns']);
        exit();
    }
    
    // Handle image upload if provided
    $imagePath = null;
    if (isset($_FILES['update_image']) && $_FILES['update_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['update_image']['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid image format. Please use JPG, PNG, or GIF']);
            exit();
        }
        
        if ($_FILES['update_image']['size'] > 5 * 1024 * 1024) { // 5MB limit
            echo json_encode(['success' => false, 'error' => 'Image size must be less than 5MB']);
            exit();
        }
        
        $uploadDir = __DIR__ . '/../uploads/updates/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = uniqid() . '_' . time() . '_' . basename($_FILES['update_image']['name']);
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['update_image']['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/updates/' . $filename;
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
            exit();
        }
    }
    
    // Prepare update data
    $updateData = [
        'campaign_id' => $campaignId,
        'user_id' => $userId,
        'title' => trim($_POST['update_title']),
        'content' => trim($_POST['update_content']),
        'image_path' => $imagePath
    ];
    
    // Add the update
    $updateId = $campaign->addCampaignUpdate($updateData);
    
    if ($updateId) {
        echo json_encode(['success' => true, 'update_id' => $updateId]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add update']);
    }
    
} catch (Exception $e) {
    error_log("Error adding campaign update: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred while adding the update']);
}
?> 