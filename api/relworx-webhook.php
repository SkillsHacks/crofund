<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PremiumAccountService.php';

try {
    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Empty webhook payload.']);
        exit;
    }

    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload.']);
        exit;
    }

    $signatureHeader = $_SERVER['HTTP_RELWORX_SIGNATURE'] ?? $_SERVER['HTTP_X_RELWORX_SIGNATURE'] ?? '';
    $callbackUrl = BASE_URL . '/api/relworx-webhook.php';
    $webhookKey = RELWORX_WEBHOOK_KEY;

    if ($webhookKey !== '') {
        $verified = verifyRelworxWebhookSignature($callbackUrl, $signatureHeader, $payload, $webhookKey);
        if (!$verified) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid webhook signature.']);
            exit;
        }
    }

    $status = strtolower((string) ($payload['status'] ?? ''));
    $internalReference = trim((string) ($payload['internal_reference'] ?? ''));
    $paymentReference = trim((string) ($payload['customer_reference'] ?? ''));

    if ($internalReference === '' && $paymentReference === '') {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing transaction reference in webhook payload.']);
        exit;
    }

    $db = Database::getInstance()->getConnection();
    $premiumService = new PremiumAccountService($db);

    if ($status === 'success' || $status === 'completed') {
        $activated = false;

        if ($internalReference !== '') {
            $activated = $premiumService->completeUpgradeByReference($internalReference, 'relworx', $internalReference);
        }

        if (!$activated && $paymentReference !== '') {
            $activated = $premiumService->completeUpgradeByReference($paymentReference, 'relworx', $paymentReference);
        }

        if (!$activated) {
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'message' => 'No matching pending premium payment found.']);
            exit;
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Premium upgrade activated via webhook.']);
        exit;
    }

    if (in_array($status, ['failed', 'rejected', 'cancelled'], true)) {
        $db->prepare("UPDATE premium_payments SET status = 'failed', paid_at = CURRENT_TIMESTAMP WHERE payment_reference = :reference OR transaction_id = :reference OR payment_reference = :customer_reference OR transaction_id = :customer_reference LIMIT 1")
            ->execute([
                ':reference' => $internalReference,
                ':customer_reference' => $paymentReference,
            ]);

        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Premium payment marked failed.']);
        exit;
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Webhook received.']);
    exit;
} catch (Throwable $e) {
    error_log('[RELWORX_WEBHOOK_ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Webhook processing failed.']);
    exit;
}

function verifyRelworxWebhookSignature(string $url, string $signatureHeader, array $params, string $secret): bool
{
    if ($signatureHeader === '') {
        return false;
    }

    $parts = explode(',', $signatureHeader);
    $timestamp = null;
    $signature = null;

    foreach ($parts as $part) {
        $pair = explode('=', $part, 2);
        if (count($pair) !== 2) {
            continue;
        }

        $key = trim($pair[0]);
        $value = trim($pair[1]);

        if ($key === 't') {
            $timestamp = $value;
        }

        if ($key === 'v') {
            $signature = $value;
        }
    }

    if ($timestamp === null || $signature === null) {
        return false;
    }

    $payloadKeys = ['status', 'customer_reference', 'internal_reference'];
    $signedParams = [];

    foreach ($payloadKeys as $keyName) {
        if (isset($params[$keyName])) {
            $signedParams[$keyName] = (string) $params[$keyName];
        }
    }

    ksort($signedParams);
    $signedData = $url . $timestamp;

    foreach ($signedParams as $key => $value) {
        $signedData .= (string) $key . (string) $value;
    }

    $computedSignature = hash_hmac('sha256', $signedData, $secret, false);
    return hash_equals($computedSignature, $signature);
}
