<?php

declare(strict_types=1);

class PremiumAccountService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPlans(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, code, title, price, currency, duration_days, features, is_active
             FROM premium_plans
             WHERE is_active = 1
             ORDER BY price ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlanByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM premium_plans
             WHERE code = :code
               AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':code' => $code]);

        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        return $plan ?: null;
    }

    public function hasPremiumAccess(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1
             FROM premium_memberships
             WHERE user_id = :user_id
               AND status = 'active'
               AND expires_at > CURRENT_TIMESTAMP
             LIMIT 1"
        );
        $stmt->execute([':user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public function createUpgradeRequest(int $userId, string $planCode): array
    {
        $plan = $this->getPlanByCode($planCode);

        if ($plan === null) {
            throw new InvalidArgumentException('Selected premium plan is not available.');
        }

        if ($this->hasPremiumAccess($userId)) {
            throw new RuntimeException('This account already has an active premium plan.');
        }

        $transactionId = 'PREM_' . strtoupper(bin2hex(random_bytes(6)));
        $amount = (float) $plan['price'];

        $stmt = $this->pdo->prepare(
            "INSERT INTO premium_payments (
                user_id,
                plan_id,
                transaction_id,
                amount,
                currency,
                gateway,
                status,
                created_at
            ) VALUES (
                :user_id,
                :plan_id,
                :transaction_id,
                :amount,
                :currency,
                'pending',
                'pending',
                CURRENT_TIMESTAMP
            )"
        );

        $stmt->execute([
            ':user_id' => $userId,
            ':plan_id' => (int) $plan['id'],
            ':transaction_id' => $transactionId,
            ':amount' => $amount,
            ':currency' => $plan['currency'],
        ]);

        return [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $plan['currency'],
            'plan_title' => $plan['title'],
            'plan_code' => $plan['code'],
        ];
    }

    public function completeUpgrade(int $userId, string $transactionId, string $gateway, ?string $reference = null): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT pp.*, p.duration_days, p.code, p.title
             FROM premium_payments pp
             INNER JOIN premium_plans p ON p.id = pp.plan_id
             WHERE pp.user_id = :user_id
               AND pp.transaction_id = :transaction_id
               AND pp.status = 'pending'
             LIMIT 1"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':transaction_id' => $transactionId,
        ]);

        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment === false) {
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $updateStmt = $this->pdo->prepare(
                "UPDATE premium_payments
                 SET status = 'paid',
                     gateway = :gateway,
                     payment_reference = :reference,
                     paid_at = CURRENT_TIMESTAMP
                 WHERE id = :payment_id"
            );
            $updateStmt->execute([
                ':gateway' => $gateway,
                ':reference' => $reference,
                ':payment_id' => (int) $payment['id'],
            ]);

            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int) $payment['duration_days'] . ' days'));

            $existingMembership = $this->pdo->prepare(
                "SELECT id
                 FROM premium_memberships
                 WHERE user_id = :user_id
                 LIMIT 1"
            );
            $existingMembership->execute([':user_id' => $userId]);
            $membership = $existingMembership->fetch(PDO::FETCH_ASSOC);

            if ($membership) {
                $membershipStmt = $this->pdo->prepare(
                    "UPDATE premium_memberships
                     SET plan_code = :plan_code,
                         plan_title = :plan_title,
                         starts_at = CURRENT_TIMESTAMP,
                         expires_at = :expires_at,
                         status = 'active',
                         updated_at = CURRENT_TIMESTAMP
                     WHERE user_id = :user_id"
                );
                $membershipStmt->execute([
                    ':user_id' => $userId,
                    ':plan_code' => $payment['code'],
                    ':plan_title' => $payment['title'],
                    ':expires_at' => $expiresAt,
                ]);
            } else {
                $membershipStmt = $this->pdo->prepare(
                    "INSERT INTO premium_memberships (
                        user_id,
                        plan_code,
                        plan_title,
                        starts_at,
                        expires_at,
                        status,
                        created_at
                    ) VALUES (
                        :user_id,
                        :plan_code,
                        :plan_title,
                        CURRENT_TIMESTAMP,
                        :expires_at,
                        'active',
                        CURRENT_TIMESTAMP
                    )"
                );
                $membershipStmt->execute([
                    ':user_id' => $userId,
                    ':plan_code' => $payment['code'],
                    ':plan_title' => $payment['title'],
                    ':expires_at' => $expiresAt,
                ]);
            }

            $this->pdo->commit();

            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
