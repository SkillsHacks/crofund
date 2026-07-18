<?php
// Required if not already defined in the parent file
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
if (!isset($campaign)) {
    require_once dirname(__DIR__) . '/config/Campaign.php';
    $campaign = new Campaign();
}

$userCampaigns = $campaign->getUserCampaigns($_SESSION['user_id'], 'all', 'newest', '');
?>

<div class="bg-white rounded-lg p-4 shadow">
    <?php if (empty($userCampaigns)): ?>
        <div class="col-span-full flex flex-col items-center justify-center text-center p-8 bg-white rounded-lg border-2 border-dashed border-gray-300">
            <div class="w-16 h-16 mb-4 flex items-center justify-center rounded-full bg-emerald-100">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <h3 class="mb-2 text-lg font-medium text-gray-900">No campaigns yet</h3>
            <p class="mb-4 text-sm text-gray-500">Get started by creating your first fundraising campaign</p>
            <a href="<?php echo BASE_URL; ?>/campaigns/create.php" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Campaign
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($userCampaigns as $campaign): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-150">
                <div class="aspect-w-16 aspect-h-9">
                    <img src="<?php echo $campaign['image_path'] 
                        ? BASE_URL . '/' . $campaign['image_path']
                        : BASE_URL . '/assets/images/default-campaign.jpg'; ?>" 
                        alt="<?php echo htmlspecialchars($campaign['title']); ?>"
                        class="w-full h-48 object-cover rounded-t-lg"
                        onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                </div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                        <?php echo htmlspecialchars($campaign['title']); ?>
                    </h3>
                    <div class="mb-4">
                        <div class="relative pt-1">
                            <div class="overflow-hidden h-2 mb-2 text-xs flex rounded bg-emerald-100">
                                <?php 
                                $percentage = ($campaign['current_amount'] / $campaign['goal_amount']) * 100;
                                $percentage = min($percentage, 100);
                                ?>
                                <div style="width:<?php echo $percentage; ?>%" 
                                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-emerald-500 transition-all duration-300">
                                </div>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600">
                                <div class="flex flex-col">
                                    <span class="font-medium">$<?php echo number_format($campaign['current_amount'], 2); ?></span>
                                    <span class="text-xs text-gray-500">raised of $<?php echo number_format($campaign['goal_amount'], 2); ?></span>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="font-medium"><?php echo round($percentage); ?>%</span>
                                    <span class="text-xs text-gray-500">funded</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 mt-4">
                        <a href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo $campaign['id']; ?>" 
                           class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-emerald-600 text-sm font-medium rounded-lg text-emerald-600 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150">
                            View Details
                        </a>
                        <a href="<?php echo BASE_URL; ?>/campaigns/edit.php?id=<?php echo $campaign['id']; ?>"
                           class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-yellow-500 text-sm font-medium rounded-lg text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-400 transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6-6m2 2l-6 6m-2 2h2v2h2v-2h2v-2h-2v-2H9v2z"/>
                            </svg>
                            Edit
                        </a>
                        <button onclick="copyShareLink(<?php echo $campaign['id']; ?>, '<?php echo htmlspecialchars(addslashes($campaign['title'])); ?>')" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            Share
                        </button>
                        <button onclick="showDashboardUpdateForm(<?php echo $campaign['id']; ?>)" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-blue-500 text-sm font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-400 transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Add Update
                        </button>
                        <button onclick="confirmDeleteCampaign(<?php echo $campaign['id']; ?>, '<?php echo htmlspecialchars(addslashes($campaign['title'])); ?>')" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-red-500 text-sm font-medium rounded-lg text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-400 transition-colors duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 w-10 h-10 mx-auto bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Campaign</h3>
                <p class="text-sm text-gray-500 mb-6">
                    Are you sure you want to delete "<span id="campaignTitle"></span>"? This action cannot be undone.
                </p>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button onclick="deleteCampaign()" 
                            class="flex-1 px-4 py-2 bg-red-600 text-sm font-medium rounded-lg text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Delete Campaign
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let campaignToDelete = null;

function confirmDeleteCampaign(campaignId, campaignTitle) {
    campaignToDelete = campaignId;
    document.getElementById('campaignTitle').textContent = campaignTitle;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    campaignToDelete = null;
}

function deleteCampaign() {
    if (!campaignToDelete) return;
    
    // Show loading state
    const deleteBtn = document.querySelector('#deleteModal button[onclick="deleteCampaign()"]');
    const originalText = deleteBtn.textContent;
    deleteBtn.textContent = 'Deleting...';
    deleteBtn.disabled = true;
    
    // Make AJAX request to delete campaign
    fetch('<?php echo BASE_URL; ?>/campaigns/delete_campaign.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'campaign_id=' + campaignToDelete
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Campaign deleted successfully', 'success');
            // Reload the page to update the campaign list
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Show error message
            showNotification(data.error || 'Failed to delete campaign', 'error');
            // Reset button
            deleteBtn.textContent = originalText;
            deleteBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while deleting the campaign', 'error');
        // Reset button
        deleteBtn.textContent = originalText;
        deleteBtn.disabled = false;
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg text-white ${
        type === 'success' ? 'bg-green-600' : 'bg-red-600'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>
