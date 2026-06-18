<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance();
    
    // Get search parameters
    $searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    
    // Build the search query
    $whereConditions = ["status = 'active'"];
    $params = [];
    
    if (!empty($searchTerm)) {
        $searchConditions = [
            "title LIKE ?",
            "description LIKE ?", 
            "category LIKE ?"
        ];
        $whereConditions[] = "(" . implode(" OR ", $searchConditions) . ")";
        
        // Add search term parameter multiple times for each condition
        $searchParam = "%{$searchTerm}%";
        $params[] = $searchParam; // for title
        $params[] = $searchParam; // for description
        $params[] = $searchParam; // for category
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    // Count total results for pagination
    $countQuery = "SELECT COUNT(*) as total FROM campaigns WHERE {$whereClause}";
    $totalResult = $db->fetchOne($countQuery, $params);
    $totalCampaigns = $totalResult['total'];
    
    // Get campaigns with pagination
    $query = "
        SELECT 
            c.id,
            c.title,
            c.description,
            c.category,
            c.goal_amount,
            c.image_path,
            c.created_at,
            COALESCE(d.donor_count, 0) as donor_count,
            COALESCE(d.total_amount, 0) as current_amount
        FROM campaigns c
        LEFT JOIN (
            SELECT 
                campaign_id, 
                COUNT(*) as donor_count,
                SUM(amount) as total_amount
            FROM donations 
            WHERE status = 'completed'
            GROUP BY campaign_id
        ) d ON c.id = d.campaign_id
        WHERE {$whereClause}
        ORDER BY COALESCE(d.total_amount, 0) DESC, c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    // Add pagination parameters
    $params[] = $limit;
    $params[] = $offset;
    
    $campaigns = $db->fetchAll($query, $params);
    
    // Calculate pagination info
    $totalPages = ceil($totalCampaigns / $limit);
    $hasMore = $page < $totalPages;
    
    // Format campaign data
    $formattedCampaigns = array_map(function($camp) {
        $percentage = min(($camp['goal_amount'] > 0 ? ($camp['current_amount'] / $camp['goal_amount']) * 100 : 0), 100);
        
        return [
            'id' => $camp['id'],
            'title' => $camp['title'],
            'description' => $camp['description'],
            'category' => $camp['category'],
            'goal_amount' => floatval($camp['goal_amount']),
            'current_amount' => floatval($camp['current_amount']),
            'percentage' => round($percentage),
            'donor_count' => intval($camp['donor_count']),
            'image_path' => $camp['image_path'],
            'created_at' => $camp['created_at']
        ];
    }, $campaigns);
    
    echo json_encode([
        'success' => true,
        'campaigns' => $formattedCampaigns,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_campaigns' => $totalCampaigns,
            'has_more' => $hasMore,
            'limit' => $limit
        ],
        'search' => [
            'term' => $searchTerm,
            'results_count' => count($formattedCampaigns)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Search campaigns error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while searching campaigns',
        'debug' => DEBUG ? $e->getMessage() : null
    ]);
}
?> 