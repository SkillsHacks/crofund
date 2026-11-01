<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Campaign.php';
require_once __DIR__ . '/../../config/Database.php'; // Directly include Database to get total count
require_once __DIR__ . '/../../config/TrustRiskService.php';

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
        foreach ($campaigns as $index => $camp) {
            $title = htmlspecialchars($camp['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $current = (float)($camp['current_amount'] ?? 0);
            $goal = (float)($camp['goal_amount'] ?? 0);
            $donors = (int)($camp['donor_count'] ?? 0);
            $id = (int)($camp['id'] ?? 0);
            $percent = $goal > 0 ? min(($current / $goal) * 100, 100) : 0;
            $imageUrl = htmlspecialchars(
                !empty($camp['image_path']) ? BASE_URL . '/' . ltrim($camp['image_path'], '/') : BASE_URL . '/assets/images/default-campaign.jpg',
                ENT_QUOTES,
                'UTF-8'
            );
            $donorLabel = number_format($donors);

            $html .= '
          <div class="group min-w-0 ' . ($index === 0 ? 'col-span-2 md:row-span-2' : '') . '">
            <a href="public-view.php?id=' . $id . '" class="block h-full min-w-0">
              <div class="relative ' . ($index === 0 ? 'aspect-[1.55/1] md:aspect-[1.4/1]' : 'aspect-[1.55/1]') . ' overflow-hidden rounded-lg bg-slate-100">
                <img src="' . $imageUrl . '" alt="' . $title . '" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src=\'' . BASE_URL . '/assets/images/default-campaign.jpg\'" />
                <span class="absolute bottom-2 left-2 rounded bg-slate-950/75 px-2 py-1 text-[10px] font-semibold text-white sm:text-xs">
                  ' . $donorLabel . ' donations
                </span>
              </div>
              <div class="' . ($index === 0 ? 'pt-2.5' : 'pt-2') . '">
                <h3 class="line-clamp-2 ' . ($index === 0 ? 'text-base font-semibold sm:text-lg' : 'text-xs font-semibold sm:text-sm') . ' leading-snug tracking-tight text-slate-900 transition-colors group-hover:text-emerald-800">
                  ' . $title . '
                </h3>
                <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Fundraising progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . round($percent) . '">
                  <div class="h-full rounded-full bg-gradient-to-r from-lime-400 to-emerald-600" style="width: ' . round($percent) . '%"></div>
                </div>
              </div>
            </a>
            <div class="mt-1.5 flex items-center justify-between gap-2">
              <p class="text-xs font-medium text-slate-700 sm:text-sm">$' . number_format($current) . ' raised</p>
              <button type="button" onclick="copyShareLink(' . $id . ')" class="text-[10px] font-semibold text-slate-500 transition hover:text-emerald-700 sm:text-xs">Share</button>
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
    error_log("Error in filter_campaigns.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Unable to filter campaigns right now. Please try again.',
        'html' => '',
        'count' => 0,
        'total_count' => 0,
        'total_pages' => 1,
        'current_page' => 1
    ]);
}
?>