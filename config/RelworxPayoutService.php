<?php

declare(strict_types=1);

class RelworxPayoutService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function dispatchForReference(string $reference, float $amount): array
    {
        if ($reference === '' || $amount <= 0.0) {
            return ['success' => false, 'message' => 'A valid payment reference and amount are required.'];
        }

        $paymentStmt = $this->pdo->prepare(
            "SELECT id, payout_status
             FROM premium_payments
             WHERE payment_reference = :reference
                OR transaction_id = :reference
             ORDER BY created_at DESC
             LIMIT 1"
        );
        $paymentStmt->execute([':reference' => $reference]);
        $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            return ['success' => false, 'message' => 'No premium payment found for payout reference ' . $reference . '.'];
        }

        if ($payment['payout_status'] === 'sent') {
            return ['success' => true, 'message' => 'Payout was already sent for this payment.'];
        }

        $claimStmt = $this->pdo->prepare(
            "UPDATE premium_payments
             SET payout_status = 'processing', payout_error = NULL, payout_started_at = CURRENT_TIMESTAMP
             WHERE id = :id AND payout_status IN ('pending', 'failed')"
        );
        $claimStmt->execute([':id' => (int) $payment['id']]);

        if ($claimStmt->rowCount() < 1) {
            return ['success' => true, 'message' => 'Payout is already being processed for this payment.'];
        }

        $primaryAmount = round($amount * 0.65, 2);
        $secondaryAmount = round($amount * 0.30, 2);
        $payouts = [
            ['msisdn' => RELWORX_SPLIT_PAYOUT_65_MSISDN, 'amount' => $primaryAmount, 'label' => '65%'],
            ['msisdn' => RELWORX_SPLIT_PAYOUT_30_MSISDN, 'amount' => $secondaryAmount, 'label' => '30%'],
        ];

        try {
            if (empty(RELWORX_API_KEY) || empty(RELWORX_ACCOUNT_NO)) {
                throw new RuntimeException('Relworx API credentials missing.');
            }

            foreach ($payouts as $payout) {
                $payoutReference = 'PAYOUT_' . strtoupper(substr(hash('sha256', $reference . ':' . $payout['msisdn'] . ':' . $payout['amount']), 0, 24));
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

                error_log('[RELWORX_PAYOUT_DEBUG] reference=' . $reference . ' msisdn=' . $payout['msisdn'] . ' amount=' . $payout['amount'] . ' http=' . $httpCode . ' curl_error=' . ($curlError ?: 'none') . ' response=' . (is_string($response) ? $response : json_encode($response)));

                $decoded = json_decode((string) $response, true);
                if ($response === false || $curlError !== '' || !is_array($decoded) || empty($decoded['success']) || $httpCode >= 400) {
                    $message = is_array($decoded) ? ($decoded['message'] ?? 'Relworx rejected the payout.') : 'Invalid Relworx payout response.';
                    throw new RuntimeException('Payout to ' . $payout['msisdn'] . ' failed: ' . $message . ' HTTP=' . $httpCode);
                }
            }

            $this->pdo->prepare("UPDATE premium_payments SET payout_status = 'sent', payout_completed_at = CURRENT_TIMESTAMP WHERE id = :id")
                ->execute([':id' => (int) $payment['id']]);

            return ['success' => true, 'message' => 'Split payouts dispatched successfully.'];
        } catch (Throwable $e) {
            $this->pdo->prepare("UPDATE premium_payments SET payout_status = 'failed', payout_error = :error WHERE id = :id")
                ->execute([':error' => $e->getMessage(), ':id' => (int) $payment['id']]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}