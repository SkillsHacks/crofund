<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$campaign = new Campaign();
$campaignId = $_GET['id'] ?? null;
$error = '';
$success = '';

if (!$campaignId) {
    $error = "No campaign selected.";
} else {
    $campaignData = $campaign->getCampaignById($campaignId);

    // Check ownership
    if (!$campaignData || $campaignData['user_id'] != $_SESSION['user_id']) {
        $error = "You do not have permission to edit this campaign.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    try {
        $updateData = [
            'title' => $_POST['title'],
            'category' => $_POST['category'],
            'goal' => $_POST['goal'],
            'end_date' => $_POST['end_date'],
            'description' => $_POST['description'],
        ];
        // Handle image upload if a new image is provided
        if (!empty($_FILES['campaign_image']['name'])) {
            $updateData['campaign_image'] = $_FILES['campaign_image'];
        }
        $campaign->update($campaignId, $updateData);
        $success = "Campaign updated successfully!";
        // Refresh data
        $campaignData = $campaign->getCampaignById($campaignId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <title>Edit Campaign - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../components/header.php'; ?>
    <div class="flex h-screen pt-16">
        <?php include '../components/sidebar.php'; ?>

        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <div class="max-w-2xl mx-auto mt-12 bg-white p-8 rounded-lg shadow">
                <h1 class="text-2xl font-bold mb-6 text-gray-900">Edit Campaign</h1>
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif ($success): ?>
                    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (!empty($campaignData) && empty($error)): ?>
                <form method="post" enctype="multipart/form-data" class="space-y-5">
                    <div>
                        <label class="block mb-1 font-medium text-gray-700">Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($campaignData['title']); ?>" required class="w-full px-4 py-2 border rounded focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block mb-1 font-medium text-gray-700">Category</label>
                        <select name="category" required class="w-full px-4 py-2 border rounded focus:ring-emerald-500">
                            <?php
                            $categories = ['medical', 'education', 'emergency', 'community', 'business', 'others'];
                            foreach ($categories as $cat) {
                                $selected = $campaignData['category'] === $cat ? 'selected' : '';
                                echo "<option value=\"$cat\" $selected>" . ucfirst($cat) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium text-gray-700">Goal Amount ($)</label>
                        <input type="number" name="goal" min="100" value="<?php echo htmlspecialchars($campaignData['goal_amount']); ?>" required class="w-full px-4 py-2 border rounded focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block mb-1 font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" value="<?php echo htmlspecialchars($campaignData['end_date']); ?>" required class="w-full px-4 py-2 border rounded focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block mb-1 font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="5" required class="w-full px-4 py-2 border rounded focus:ring-emerald-500"><?php echo htmlspecialchars($campaignData['description']); ?></textarea>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium text-gray-700">Campaign Image</label>
                        <?php if (!empty($campaignData['image_path'])): ?>
                            <img src="<?php echo BASE_URL . '/' . $campaignData['image_path']; ?>" alt="Campaign Image" class="mb-2 w-32 h-20 object-cover rounded">
                        <?php endif; ?>
                        <input type="file" name="campaign_image" accept="image/*" class="w-full">
                        <p class="text-xs text-gray-500 mt-1">Leave blank to keep current image.</p>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-emerald-600 text-white py-2 px-4 rounded hover:bg-emerald-700 transition">Update Campaign</button>
                    </div>
                </form>
                <?php endif; ?>
                <div class="mt-6">
                    <a href="index.php" class="text-emerald-600 hover:underline">&larr; Back to My Campaigns</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>