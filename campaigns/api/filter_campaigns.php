<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Campaign.php';

header('Content-Type: application/json');

try {
    // Get filter parameters
    $category = $_GET['category'] ?? 'all';
    $sort = $_GET['sort'] ?? ''; // No default sorting
    $page = (int)($_GET['page'] ?? 1);
    
    // Initialize Campaign object
    $campaign = new Campaign();
    
    // Get filtered campaigns
    $result = $campaign->getBrowseCampaigns($category, $sort, $page, 1000); // Large number to show all campaigns
    $campaigns = $result['campaigns'] ?? [];
    
    // Generate HTML for campaigns
    $html = '';
    
    if (empty($campaigns)) {
        $html = '
        <div class="col-span-2 text-center py-12">
          <div class="text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No campaigns found</h3>
            <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or check back later.</p>
          </div>
        </div>';
    } else {
        foreach ($campaigns as $index => $camp) {
            $title = htmlspecialchars($camp['title'] ?? '');
            $description = htmlspecialchars($camp['description'] ?? '');
            $category = htmlspecialchars($camp['category'] ?? '');
            $current = (float)($camp['current_amount'] ?? 0);
            $goal = (float)($camp['goal_amount'] ?? 0);
            $donors = (int)($camp['donor_count'] ?? 0);
            $id = (int)($camp['id'] ?? 0);
            $percent = $goal > 0 ? min(($current / $goal) * 100, 100) : 0;
            $imageUrl = !empty($camp['image_path']) ? BASE_URL . '/' . ltrim($camp['image_path'], '/') : BASE_URL . '/assets/images/default-campaign.jpg';
            
            $html .= '
            <div class="group bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden max-w-md mx-auto" x-data="{ expanded: false }">
              <div class="flex flex-row sm:flex-col">
                <!-- Image -->
                <div class="w-1/4 h-32 sm:w-full sm:h-48 overflow-hidden relative">
                  <img src="' . $imageUrl . '" alt="' . $title . '" class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105" onerror="this.src=\'' . BASE_URL . '/assets/images/default-campaign.jpg\'" />
                  <span class="absolute top-3 right-3 px-2 py-1 text-xs font-semibold text-white bg-emerald-500 rounded-full shadow">
                    ' . $category . '
                  </span>
                </div>

                <!-- Content -->
                <div class="w-3/4 sm:w-full p-3 sm:p-6 flex flex-col justify-between">
                  <h3 class="text-sm sm:text-lg font-semibold text-gray-900 mb-1 group-hover:text-emerald-600 leading-snug line-clamp-2" title="' . $title . '">
                    ' . $title . '
                  </h3>

                  <div class="relative mb-3 text-xs text-gray-600 flex-1">
                    <p :class="{ \'line-clamp-3\': !expanded }">' . $description . '</p>';
                    
            if (strlen($camp['description']) > 140) {
                $html .= '
                    <button @click="expanded = !expanded" class="mt-1 text-emerald-600 hover:text-emerald-700 text-xs font-medium flex items-center">
                      <span x-text="expanded ? \'Show Less\' : \'Read More\'"></span>
                      <svg class="w-4 h-4 ml-1 transition-transform" :class="{ \'rotate-180\': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>';
            }
            
            $html .= '
                  </div>

                  <!-- Updated Progress Section with Donors Inline -->
                  <div class="mb-3">
                    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full" style="width: ' . round($percent) . '%"></div>
                    </div>
                    <div class="flex justify-between items-center mt-2 text-xs text-gray-700">
                      <span><strong>$' . number_format($current, 2) . '</strong> <span class="text-gray-500">of $' . number_format($goal, 2) . '</span></span>
                      <div class="flex items-center gap-2 text-emerald-600 font-semibold">
                        <span>' . round($percent) . '%</span>
                        <span class="text-gray-400">|</span>
                        <span class="text-gray-600">' . $donors . ' donors</span>
                      </div>
                    </div>
                  </div>

                  <div class="flex justify-between pt-2 border-t border-gray-100">
                    <div class="flex space-x-2">
                      <button onclick="copyShareLink(' . $id . ', \'' . addslashes($title) . '\')" class="px-3 py-1 text-xs font-medium text-gray-700 border border-gray-300 rounded hover:bg-gray-100">Share</button>
                      <a href="public-view.php?id=' . $id . '" class="px-4 py-1.5 text-xs font-medium text-white rounded bg-emerald-600 hover:bg-emerald-700 transition">Donate</a>
                    </div>
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
        'filters' => [
            'category' => $category,
            'sort' => $sort,
            'page' => $page
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'html' => '
        <div class="col-span-2 text-center py-12">
          <div class="text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Error loading campaigns</h3>
            <p class="mt-1 text-sm text-gray-500">Please try again later.</p>
          </div>
        </div>',
        'count' => 0
    ]);
}
?> 