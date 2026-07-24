<?php
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }

    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Self-service ID verification is disabled. Your submitted ID must be reviewed and approved by the CroFund team before withdrawal access is granted.'
    ]);
    exit();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
