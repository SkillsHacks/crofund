<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Verify CSRF token
    if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }

    // Increment verification attempts
    $campaign = new Campaign();
    $result = $campaign->incrementVerificationAttempts($_SESSION['user_id']);

    if ($result) {
        // Get current attempt count
        $attempts = $campaign->getVerificationAttempts($_SESSION['user_id']);
        $remaining = 3 - $attempts;
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification attempt recorded successfully',
            'attempts' => $attempts,
            'remaining' => $remaining
        ]);
    } else {
        throw new Exception('Failed to record verification attempt');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
