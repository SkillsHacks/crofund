<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/Campaign.php';
    require_once __DIR__ . '/../../config/CampaignDraft.php';
    require_once __DIR__ . '/../../config/User.php';
    require_once __DIR__ . '/../../config/TrustRiskGuard.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit();
    }

    if (($_POST['action'] ?? '') === 'discard_draft') {
        CampaignDraft::remove();
        if (($_SESSION['redirect_after_login'] ?? '') === '/campaigns/create.php?resume=1') {
            unset($_SESSION['redirect_after_login']);
        }
        echo json_encode(['success' => true]);
        exit();
    }

    $draftId = trim((string) ($_POST['campaign_draft_id'] ?? ''));
    $isPublishingDraft = $draftId !== '';
    $user = new User();
    $isAuthenticated = isset($_SESSION['user_id']) && $user->isLoggedIn();

    if (!$isAuthenticated && $isPublishingDraft) {
        $_SESSION['redirect_after_login'] = '/campaigns/create.php?resume=1';
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Please sign in again to review and publish your saved draft.',
            'redirect_url' => BASE_URL . '/login.php',
        ]);
        exit();
    }

    $draft = null;
    if ($isPublishingDraft) {
        $draft = CampaignDraft::get();
        if ($draft === null || !hash_equals($draft['id'], $draftId)) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Your saved fundraiser draft has expired. Please start again.',
            ]);
            exit();
        }
    }

    $requiredFields = ['title', 'category', 'goal', 'description'];
    $fieldErrors = [];
    foreach ($requiredFields as $field) {
        if (trim((string) ($_POST[$field] ?? '')) === '') {
            $fieldErrors[$field] = 'This field is required.';
        }
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $goal = $_POST['goal'] ?? null;
    $description = trim((string) ($_POST['description'] ?? ''));
    $imageUpload = $_FILES['campaign_image'] ?? null;
    $hasImageUpload = is_array($imageUpload)
        && (int) ($imageUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $removeImage = ($_POST['remove_campaign_image'] ?? '') === '1';

    if ($title !== '' && mb_strlen($title) > 255) {
        $fieldErrors['title'] = 'Campaign title must be 255 characters or less.';
    }
    if ($goal !== null && (!is_numeric($goal) || (float) $goal < 100)) {
        $fieldErrors['goal'] = 'Please enter a valid goal amount of at least $100.';
    }
    if (!$isPublishingDraft && !$hasImageUpload) {
        $fieldErrors['campaign_image'] = 'Campaign image is required.';
    } elseif ($isPublishingDraft && ($removeImage || !CampaignDraft::isStagedImagePath($draft['image_path'])) && !$hasImageUpload) {
        $fieldErrors['campaign_image'] = 'Please choose a campaign image.';
    }

    if ($fieldErrors) {
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
        'user_id' => (int) ($_SESSION['user_id'] ?? 0),
    ];

    if (!$isAuthenticated) {
        $savedDraft = CampaignDraft::save($campaignData, $imageUpload);
        $_SESSION['redirect_after_login'] = '/campaigns/create.php?resume=1';

        echo json_encode([
            'success' => true,
            'message' => 'Your fundraiser draft is saved in this browser session for up to 24 hours.',
            'redirect_url' => BASE_URL . '/register.php?campaign_draft=1',
            'draft_id' => $savedDraft['id'],
        ]);
        exit();
    }

    TrustRiskGuard::enforceOrAbort((int) $_SESSION['user_id'], 'create_campaign', true);

    $campaign = new Campaign();
    if (!$campaign->canCreateCampaign((int) $_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Free accounts can create only one campaign. Upgrade to premium to manage more than one campaign.',
        ]);
        exit();
    }

    if ($isPublishingDraft) {
        $draft = CampaignDraft::update(
            $campaignData,
            $draftId,
            $hasImageUpload ? $imageUpload : null,
            $removeImage
        );
        $campaignId = $campaign->createFromDraft($campaignData, $draft['image_path']);
        if (!$campaignId) {
            throw new Exception('Campaign creation failed. Please try again.');
        }

        CampaignDraft::remove();
    } else {
        $campaignData['campaign_image'] = $imageUpload;
        $campaignId = $campaign->create($campaignData);
        if (!$campaignId) {
            throw new Exception('Campaign creation failed. Please try again.');
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Campaign created successfully.',
        'campaign_id' => (int) $campaignId,
        'redirect_url' => BASE_URL . '/campaigns/view.php?id=' . (int) $campaignId,
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'errors' => ['campaign_image' => $e->getMessage()],
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
        'message' => 'Unable to create the campaign right now. Please try again.',
    ]);
}

if (ob_get_level() > 0) {
    ob_end_flush();
}
