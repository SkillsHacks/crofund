<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

header('Content-Type: application/json');

try {
    $campaign = new Campaign();
    
    // Get parameters
    $category = $_GET['category'] ?? 'all';
    $sort = $_GET['sort'] ?? 'newest';
    $page = (int)($_GET['page'] ?? 1);
    $limit = 9;
    $offset = ($page - 1) * $limit;

    // Get campaigns
    $sql = "SELECT c.*, 
            u.name as creator_name,
            COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.id END) as donor_count,
            COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as current_amount
            FROM campaigns c 
            LEFT JOIN users u ON c.user_id = u.id 
            LEFT JOIN donations d ON c.id = d.campaign_id 
            WHERE c.status = 'active' 
            AND c.end_date > NOW()";

    // Add category filter
    if ($category !== 'all') {
        $sql .= " AND c.category = ?";
        $params[] = $category;
    }

    // Add grouping
    $sql .= " GROUP BY c.id, c.title, c.description, c.goal_amount, 
              c.image_path, c.category, c.status, c.created_at, 
              c.user_id, u.name";

    // Add sorting
    switch ($sort) {
        case 'most-funded':
            $sql .= " ORDER BY current_amount DESC";
            break;
        case 'ending-soon':
            $sql .= " ORDER BY c.end_date ASC";
            break;
        default: // newest
            $sql .= " ORDER BY c.created_at DESC";
    }

    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $campaigns = $campaign->getDB()->fetchAll($sql, $params ?? []);

    // Format campaign data
    foreach ($campaigns as &$camp) {
        $camp['donor_count'] = (int)$camp['donor_count'];
        $camp['current_amount'] = (float)$camp['current_amount'];
        $camp['goal_amount'] = (float)$camp['goal_amount'];
        $camp['progress'] = $camp['goal_amount'] > 0 ? 
            min(($camp['current_amount'] / $camp['goal_amount']) * 100, 100) : 0;
    }

    // Check if there are more campaigns
    $totalCampaigns = $campaign->getDB()->count("SELECT COUNT(*) FROM campaigns WHERE status = 'active'");
    $hasMore = ($offset + $limit) < $totalCampaigns;

    echo json_encode([
        'success' => true,
        'campaigns' => $campaigns,
        'hasMore' => $hasMore
    ]);

} catch (Exception $e) {
    error_log('campaigns API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load campaigns right now. Please try again.'
    ]);
}