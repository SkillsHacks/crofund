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

        $confirmedAmount = 0.0;
        foreach (['amount', 'amount_paid', 'paid_amount', 'total_amount'] as $amountKey) {
            if (isset($payload[$amountKey]) && is_numeric($payload[$amountKey])) {
                $confirmedAmount = (float) $payload[$amountKey];
                break;
            }
        }

        if ($confirmedAmount > 0.0) {
            $splitPayoutResult = sendRelworxSplitPayout($confirmedAmount, $internalReference ?: $paymentReference, $payload);
            if ($splitPayoutResult['success']) {
                error_log('[RELWORX_WEBHOOK_LOG] split payouts sent successfully for confirmed amount ' . $confirmedAmount . ' from ref=' . ($internalReference ?: $paymentReference));
            } else {
                error_log('[RELWORX_WEBHOOK_LOG] split payout failed for ref=' . ($internalReference ?: $paymentReference) . ' | ' . $splitPayoutResult['message']);
            }
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Premium upgrade activated via webhook.']);
        exit;
    }

    if (in_array($status, ['failed', 'rejected', 'cancelled'], true)) {
        $db->prepare("UPDATE premium_payments SET status = 'failed', paid_at = CURRENT_TIMESTAMP WHERE (payment_reference = :reference_a OR transaction_id = :reference_b OR payment_reference = :customer_reference_a OR transaction_id = :customer_reference_b) LIMIT 1")
            ->execute([
                ':reference_a' => $internalReference,
                ':reference_b' => $internalReference,
                ':customer_reference_a' => $paymentReference,
                ':customer_reference_b' => $paymentReference,
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

function sendRelworxSplitPayout(float $confirmedAmount, string $reference, array $payload): array
{
    if ($confirmedAmount <= 0.0) {
        return ['success' => false, 'message' => 'No confirmed amount available for split payout.'];
    }

    if (empty(RELWORX_API_KEY) || empty(RELWORX_ACCOUNT_NO)) {
        return ['success' => false, 'message' => 'Relworx API credentials missing.'];
    }

    $primaryAmount = round($confirmedAmount * 0.65, 2);
    $secondaryAmount = round($confirmedAmount * 0.30, 2);
    $remainingAmount = round($confirmedAmount - $primaryAmount - $secondaryAmount, 2);

    $payOuts = [
        ['msisdn' => RELWORX_SPLIT_PAYOUT_65_MSISDN, 'amount' => $primaryAmount, 'label' => '65%'],
        ['msisdn' => RELWORX_SPLIT_PAYOUT_30_MSISDN, 'amount' => $secondaryAmount, 'label' => '30%'],
    ];

    if ($remainingAmount > 0.0) {
        error_log('[RELWORX_WEBHOOK_LOG] confirmed amount ' . $confirmedAmount . ' leaves a remaining ' . $remainingAmount . ' not allocated to any recipient.');
    }

    foreach ($payOuts as $payout) {
        $payoutReference = 'PAYOUT_' . strtoupper(substr(hash('sha256', $reference . ':' . $payout['msisdn'] . ':' . $payout['amount'] . ':' . (string) time()), 0, 24));

        $sendPayload = [
            'account_no' => RELWORX_ACCOUNT_NO,
            'reference' => $payoutReference,
            'msisdn' => $payout['msisdn'],
            'currency' => 'UGX',
            'amount' => (float) $payout['amount'],
            'description' => 'Split payout for confirmed premium payment ref ' . $reference . ' (' . $payout['label'] . ')',
        ];

        $curl = curl_init(RELWORX_BASE_URL . '/send-payment');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/vnd.relworx.v2',
                'Authorization: Bearer ' . RELWORX_API_KEY,
            ],
            CURLOPT_POSTFIELDS => json_encode($sendPayload),
        ]);

        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false || $curlError !== '') {
            return ['success' => false, 'message' => 'Send-payment call failed for ' . $payout['msisdn'] . ': ' . ($curlError ?: 'unknown error')];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['success'])) {
            $message = $decoded['message'] ?? 'Relworx send payment rejected.';
            return ['success' => false, 'message' => 'Relworx payout to ' . $payout['msisdn'] . ' failed: ' . $message . ' HTTP=' . $httpCode];
        }

        error_log('[RELWORX_WEBHOOK_LOG] payout sent to ' . $payout['msisdn'] . ' amount=' . $payout['amount'] . ' reference=' . $payoutReference . ' http=' . $httpCode);
    }

    return ['success' => true, 'message' => 'Split payouts dispatched successfully.'];
}
