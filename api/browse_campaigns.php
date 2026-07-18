<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

try {
    $campaign = new Campaign();
    
    // Get parameters
    $category = $_GET['category'] ?? 'all';
    $sort = $_GET['sort'] ?? 'newest';
    $page = (int)($_GET['page'] ?? 1);
    $perPage = 9;

    // Get campaigns based on filters
    $campaignsData = $campaign->getBrowseCampaigns($category, $sort, $page, $perPage);
    
    // Format response
    $response = [
        'success' => true,
        'campaigns' => $campaignsData['campaigns'],
        'hasMore' => $campaignsData['hasMore']
    ];

    echo json_encode($response);
} catch (Exception $e) {
    error_log('browse_campaigns API error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load campaigns right now. Please try again.'
    ]);
}