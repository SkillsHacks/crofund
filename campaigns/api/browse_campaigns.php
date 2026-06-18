
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

try {
    $campaign = new Campaign();
    
    // Get parameters with proper defaults
    $category = $_GET['category'] ?? 'all';
    $sort = $_GET['sort'] ?? 'newest';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 12;
    $offset = ($page - 1) * $limit;

    // Get campaigns
    $campaigns = $campaign->getBrowsableCampaigns($category, $sort, $limit, $offset);
    
    // Get total count for pagination
    $totalCampaigns = $campaign->getTotalCampaigns($category);
    $hasMore = ($page * $limit) < $totalCampaigns;

    echo json_encode([
        'success' => true,
        'campaigns' => $campaigns,
        'hasMore' => $hasMore,
        'total' => $totalCampaigns,
        'page' => $page
    ]);

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch campaigns'
    ]);
}