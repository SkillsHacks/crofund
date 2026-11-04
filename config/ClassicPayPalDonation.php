<?php

function completeClassicPayPalDonation(
    PDO $pdo,
    array $donation,
    string $transactionId,
    float $grossAmount,
    array $gatewayResponse
): bool {
    $donationId = (int) $donation['id'];
    $campaignId = (int) $donation['campaign_id'];
    $recordedAmount = round($grossAmount * 0.7, 2);
    $encodedResponse = json_encode(
        $gatewayResponse,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
    );

    $pdo->beginTransaction();

    try {
        $updateDonation = $pdo->prepare(
            "UPDATE donations
             SET status = 'completed',
                 amount = ?,
                 original_amount = ?,
                 transaction_id = ?,
                 gateway_response = ?,
                 updated_at = NOW(),
                 donated_at = NOW()
             WHERE id = ?
               AND payment_token = ?
               AND payment_gateway = 'paypal_standard'
               AND status = 'pending'"
        );
        $updateDonation->execute([
            $recordedAmount,
            $grossAmount,
            $transactionId,
            $encodedResponse,
            $donationId,
            $donation['payment_token'],
        ]);

        if ($updateDonation->rowCount() !== 1) {
            $pdo->rollBack();
            return false;
        }

        $updateCampaign = $pdo->prepare(
            'UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?'
        );
        $updateCampaign->execute([$recordedAmount, $campaignId]);
        if ($updateCampaign->rowCount() !== 1) {
            throw new RuntimeException('Campaign total could not be updated for the completed donation.');
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    try {
        $campaignTitleStmt = $pdo->prepare('SELECT title FROM campaigns WHERE id = ? LIMIT 1');
        $campaignTitleStmt->execute([$campaignId]);
        $campaignTitle = $campaignTitleStmt->fetchColumn() ?: 'Unknown campaign';

        (new Mail())->sendDonationAdminNotification([
            'campaign_title' => $campaignTitle,
            'donor_name' => $donation['donor_name'],
            'donor_email' => $donation['donor_email'],
            'amount' => $recordedAmount,
            'original_amount' => $grossAmount,
            'payment_gateway' => 'paypal_standard',
            'transaction_id' => $transactionId,
            'country' => $donation['country'] ?? null,
            'comment' => $donation['comment'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        error_log('Failed to send classic PayPal admin donation notification: ' . $e->getMessage());
    }

    return true;
}
