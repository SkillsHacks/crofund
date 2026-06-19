<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Campaign.php';
require_once __DIR__ . '/../../config/Database.php'; // Directly include Database to get total count

header('Content-Type: application/json');

try {
    // Get filter parameters
    $category = $_GET['category'] ?? 'all';
    $sort = $_GET['sort'] ?? '';
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 12);

    // Initialize Campaign object
    $campaign = new Campaign();

    // Get campaigns via Campaign's method
    $result = $campaign->getBrowseCampaigns($category, $sort, $page, $perPage);
    $campaigns = $result['campaigns'] ?? [];

    // Get total count for pagination - using Database class directly
    $db = Database::getInstance();
    $countSql = "SELECT COUNT(*) as total FROM campaigns WHERE status = 'active'";
    $countParams = [];
    if ($category !== 'all') {
        $countSql .= " AND category = ?";
        $countParams[] = $category;
    }
    $countResult = $db->fetchOne($countSql, $countParams);
    $totalCount = (int)($countResult['total'] ?? 0);
    $totalPages = (int)ceil($totalCount / $perPage);

    // Generate HTML for campaigns
    $html = '';

    if (empty($campaigns)) {
        $html = '
          <div class="col-span-full text-center py-16">
            <div class="w-20 h-20 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
              <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">No campaigns found</h3>
            <p class="text-slate-600">Try adjusting your filters or check back later.</p>
          </div>';
    } else {
        foreach ($campaigns as $camp) {
            $title = htmlspecialchars($camp['title'] ?? '');
            $description = htmlspecialchars($camp['description'] ?? '');
            $campCategory = htmlspecialchars($camp['category'] ?? '');
            $current = (float)($camp['current_amount'] ?? 0);
            $goal = (float)($camp['goal_amount'] ?? 0);
            $donors = (int)($camp['donor_count'] ?? 0);
            $id = (int)($camp['id'] ?? 0);
            $percent = $goal > 0 ? min(($current / $goal) * 100, 100) : 0;
            $imageUrl = !empty($camp['image_path']) ? BASE_URL . '/' . ltrim($camp['image_path'], '/') : BASE_URL . '/assets/images/default-campaign.jpg';

            $html .= '
          <div class="group bg-white rounded-2xl border border-slate-100 shadow-soft hover:shadow-medium transition-all duration-300 overflow-hidden flex flex-col">
            <!-- Image -->
            <a href="public-view.php?id=' . $id . '" class="block relative h-56 overflow-hidden flex-shrink-0">
              <img src="' . $imageUrl . '" alt="' . $title . '" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src=\'' . BASE_URL . '/assets/images/default-campaign.jpg\'" />
              <span class="absolute top-4 left-4 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 rounded-xl shadow">
                ' . $campCategory . '
              </span>
            </a>

            <!-- Content -->
            <div class="p-6 flex flex-col flex-1 campaign-card-content">
              <h3 class="text-lg font-semibold text-slate-900 mb-3 line-clamp-2 group-hover:text-emerald-700 transition-colors flex-shrink-0">
                <a href="public-view.php?id=' . $id . '">
                  ' . $title . '
                </a>
              </h3>

              <div class="text-slate-600 text-sm line-clamp-3 mb-6 flex-grow min-h-[4.5rem]">
                ' . $description . '
              </div>

              <!-- Progress Section -->
              <div class="mb-4 flex-shrink-0">
                <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden mb-3">
                  <div class="h-full bg-emerald-600 rounded-full" style="width: ' . round($percent) . '%"></div>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-slate-600"><strong class="text-slate-900">$' . number_format($current, 2) . '</strong> of $' . number_format($goal, 2) . '</span>
                  <span class="font-semibold text-emerald-700">' . round($percent) . '%</span>
                </div>
              </div>

              <div class="flex items-center justify-between pt-4 border-t border-slate-100 flex-shrink-0">
                <div class="flex items-center text-slate-600 text-sm">
                  <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                  </svg>
                  ' . $donors . ' donors
                </div>
                <div class="flex items-center gap-2">
                  <button onclick="copyShareLink(' . $id . ', \'' . addslashes($title) . '\')" class="px-3 py-1.5 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors whitespace-nowrap">
                    Share
                  </button>
                  <a href="public-view.php?id=' . $id . '" class="px-4 py-1.5 text-xs font-semibold text-white rounded-xl bg-emerald-600 hover:bg-emerald-700 transition-colors whitespace-nowrap">
                    Donate
                  </a>
                </div>
              </div>
            </div>
          </div>';
        }
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => count($campaigns),
        'total_count' => $totalCount,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'per_page' => $perPage,
        'filters' => [
            'sort' => $sort,
            'category' => $category
        ]
    ]);

} catch (Exception $e) {
    // Return error response
    error_log("Error in filter_campaigns.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'html' => '',
        'count' => 0,
        'total_count' => 0,
        'total_pages' => 1,
        'current_page' => 1
    ]);
}
?>