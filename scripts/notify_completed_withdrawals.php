<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Mail.php';

$lockName = 'crofund_withdrawal_completion_email';
$lockStatement = $pdo->prepare('SELECT GET_LOCK(?, 0)');
$lockStatement->execute([$lockName]);

if ((int) $lockStatement->fetchColumn() !== 1) {
    fwrite(STDOUT, "Another withdrawal notification run is already active.\n");
    exit(0);
}

$exitCode = 0;

try {
    $stmt = $pdo->query(
        "SELECT w.id, w.amount, w.method, u.name, u.email
         FROM withdrawals w
         INNER JOIN users u ON u.id = w.user_id
         LEFT JOIN withdrawal_completion_notifications n ON n.withdrawal_id = w.id
         WHERE w.status = 'completed'
           AND n.withdrawal_id IS NULL
         ORDER BY COALESCE(w.updated_at, w.created_at), w.id
         LIMIT 100"
    );
    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $mailer = new Mail();
    $sentCount = 0;

    foreach ($withdrawals as $withdrawal) {
        $withdrawalId = (int) $withdrawal['id'];
        $email = trim((string) ($withdrawal['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log('Withdrawal completion email skipped for withdrawal ' . $withdrawalId . ': user has no valid email.');
            continue;
        }

        $method = match ((string) $withdrawal['method']) {
            'paypal' => 'PayPal',
            'bank' => 'Bank Transfer',
            'mobile' => 'Mobile Money',
            default => ucfirst((string) $withdrawal['method']),
        };

        try {
            $sent = $mailer->sendWithdrawalCompletedEmail(
                $email,
                (string) ($withdrawal['name'] ?? 'User'),
                (float) $withdrawal['amount'],
                $method
            );

            if (!$sent) {
                throw new RuntimeException('Mailer did not confirm delivery.');
            }

            $markSent = $pdo->prepare(
                "INSERT INTO withdrawal_completion_notifications (withdrawal_id, status, notified_at)
                 VALUES (?, 'sent', NOW())"
            );
            $markSent->execute([$withdrawalId]);
            $sentCount++;
        } catch (Throwable $e) {
            error_log(
                'Withdrawal completion notification failed for withdrawal '
                . $withdrawalId . ': ' . $e->getMessage()
            );
        }
    }

    error_log(
        'Withdrawal completion notification run finished. Found '
        . count($withdrawals) . ' eligible withdrawals; sent ' . $sentCount . '.'
    );
} catch (Throwable $e) {
    error_log('Withdrawal completion notification run failed: ' . $e->getMessage());
    $exitCode = 1;
} finally {
    try {
        $releaseLock = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $releaseLock->execute([$lockName]);
    } catch (Throwable $e) {
        error_log('Unable to release withdrawal notification lock: ' . $e->getMessage());
        $exitCode = 1;
    }
}

exit($exitCode);
