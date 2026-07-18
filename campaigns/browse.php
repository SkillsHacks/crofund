<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Check if user is logged in, if not redirect to homepage
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/");
    exit();
}


// Initialize Campaign object
$campaign = new Campaign();

// Get search parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'latest';

// Get paginated campaigns
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get campaigns with filters
$campaigns = $campaign->getBrowsableCampaigns($search, $category, $sort, $limit, $offset);
$totalCampaigns = $campaign->getTotalCampaigns($search, $category);
$totalPages = ceil($totalCampaigns / $limit);

// Get categories
$categories = $campaign->getCategories();

include __DIR__ . '/../components/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <title>Browse Campaigns - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen pt-16">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        
        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <div class="p-6">
                <!-- Search and Filters -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <form method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-2">
                                <input type="text" 
                                    name="search" 
                                    value="<?php echo htmlspecialchars($search); ?>" 
                                    placeholder="Search campaigns..."
                                    class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                            <div>
                                <select name="category" 
                                    class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <select name="sort" 
                                    class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest</option>
                                    <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                    <option value="funded" <?php echo $sort === 'funded' ? 'selected' : ''; ?>>Most Funded</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" 
                                class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                Search
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Campaigns Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($campaigns as $campaign): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div class="relative h-48">
                                <?php 
                                $imageUrl = $campaign['image_path'] 
                                    ? BASE_URL . '/' . $campaign['image_path']
                                    : BASE_URL . '/assets/images/default-campaign.jpg';
                                ?>
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                    alt="<?php echo htmlspecialchars($campaign['title']); ?>"
                                    class="w-full h-full object-cover"
                                    onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-campaign.jpg'">
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    <?php echo htmlspecialchars($campaign['title']); ?>
                                </h3>
                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars($campaign['description']); ?>
                                </p>
                                <div class="mb-4">
                                    <div class="h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-emerald-500 rounded-full" 
                                            style="width: <?php echo min(($campaign['current_amount'] / $campaign['goal_amount']) * 100, 100); ?>%">
                                        </div>
                                    </div>
                                    <div class="flex justify-between mt-2 text-sm text-gray-600">
                                        <span>$<?php echo number_format($campaign['current_amount'], 2); ?> raised</span>
                                        <span><?php echo round(($campaign['current_amount'] / $campaign['goal_amount']) * 100); ?>%</span>
                                    </div>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo $campaign['id']; ?>" 
                                    class="block w-full px-4 py-2 bg-emerald-600 text-white text-center rounded-lg hover:bg-emerald-700 transition-colors duration-200">
                                    View Campaign
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex justify-center">
                        <div class="flex space-x-2">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&sort=<?php echo urlencode($sort); ?>" 
                                    class="px-4 py-2 border-2 rounded-lg <?php echo $page === $i ? 'bg-emerald-600 text-white border-emerald-600' : 'border-gray-200 text-gray-600 hover:border-emerald-500'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>