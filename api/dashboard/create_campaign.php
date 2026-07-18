<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/Campaign.php';
    require_once __DIR__ . '/../../config/User.php';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed.'
        ]);
        exit();
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Please login to create a campaign.'
        ]);
        exit();
    }

    $user = new User();
    if (!$user->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Your session has expired. Please login again.'
        ]);
        exit();
    }

    $requiredFields = ['title', 'category', 'goal', 'description'];
    $fieldErrors = [];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $fieldErrors[$field] = 'This field is required.';
        }
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $goal = $_POST['goal'] ?? null;
    $description = trim((string) ($_POST['description'] ?? ''));

    if ($title !== '' && mb_strlen($title) > 255) {
        $fieldErrors['title'] = 'Campaign title must be 255 characters or less.';
    }

    if ($goal !== null && (!is_numeric($goal) || (float) $goal < 100)) {
        $fieldErrors['goal'] = 'Please enter a valid goal amount of at least $100.';
    }

    if (!isset($_FILES['campaign_image']) || ($_FILES['campaign_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $fieldErrors['campaign_image'] = 'Campaign image is required.';
    }

    if (!empty($fieldErrors)) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Please correct the highlighted fields and try again.',
            'errors' => $fieldErrors,
        ]);
        exit();
    }

    $campaignData = [
        'title' => $title,
        'category' => $category,
        'goal' => (float) $goal,
        'description' => $description,
        'user_id' => $_SESSION['user_id'],
        'campaign_image' => $_FILES['campaign_image'] ?? null,
    ];

    $campaign = new Campaign();
    $campaignId = $campaign->create($campaignData);

    if (!$campaignId) {
        throw new Exception('Campaign creation failed. Please try again.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Campaign created successfully.',
        'campaign_id' => (int) $campaignId,
        'redirect_url' => BASE_URL . '/campaigns/view.php?id=' . (int) $campaignId,
    ]);
} catch (Throwable $e) {
    $bufferedOutput = ob_get_contents();
    if ($bufferedOutput !== false && trim($bufferedOutput) !== '') {
        error_log('[api/dashboard/create_campaign] Unexpected output before JSON: ' . trim($bufferedOutput));
    }

    if (ob_get_level() > 0) {
        ob_clean();
    }

    error_log('[api/dashboard/create_campaign] ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ]);
}

if (ob_get_level() > 0) {
    ob_end_flush();
}
