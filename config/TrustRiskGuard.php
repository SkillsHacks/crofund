<?php

require_once __DIR__ . '/TrustRiskService.php';

class TrustRiskGuard
{
    public static function enforceOrAbort(int $userId, string $actionKey, bool $jsonResponse = false): void
    {
        $service = new TrustRiskService();

        try {
            $service->enforceForAction($userId, $actionKey);
        } catch (RuntimeException $e) {
            $httpCode = $e->getCode() >= 400 ? $e->getCode() : 403;
            http_response_code($httpCode);

            if ($jsonResponse) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
                exit();
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/account/notifications.php');
            exit();
        }
    }
}
