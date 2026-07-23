<?php

require_once __DIR__ . '/Database.php';

class TrustRiskService
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';
    public const STATUS_LIMITED = 'LIMITED';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_BANNED = 'BANNED';

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getUserRiskProfile(int $userId): array
    {
        $user = $this->db->fetchOne(
            "SELECT id, name, email, risk_status, risk_status_reason, risk_review_expires_at, risk_last_action_at, risk_last_action_by, is_admin
             FROM users
             WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            throw new Exception('User not found.');
        }

        $status = strtoupper((string) ($user['risk_status'] ?? self::STATUS_ACTIVE));
        $resolvedStatus = $this->resolveExpiredStatus($userId, $status, $user['risk_review_expires_at'] ?? null);
        if ($resolvedStatus !== $status) {
            $user['risk_status'] = $resolvedStatus;
            $status = $resolvedStatus;
        }

        return [
            'user' => $user,
            'status' => $status,
            'restrictions' => $this->getRestrictionsForStatus($status),
            'message' => $this->getUserFacingStatusMessage($status, (string) ($user['risk_status_reason'] ?? '')),
        ];
    }

    public function enforceForAction(int $userId, string $actionKey): void
    {
        $profile = $this->getUserRiskProfile($userId);
        $status = $profile['status'];
        $restrictions = $profile['restrictions'];

        if (empty($restrictions[$actionKey])) {
            return;
        }

        $httpCode = in_array($status, [self::STATUS_SUSPENDED, self::STATUS_BANNED], true) ? 423 : 403;
        throw new RuntimeException($profile['message']['short'], $httpCode);
    }

    public function canLogin(string $status): bool
    {
        return strtoupper($status) !== self::STATUS_BANNED;
    }

    public function canAccessFullApp(string $status): bool
    {
        return !in_array(strtoupper($status), [self::STATUS_SUSPENDED, self::STATUS_BANNED], true);
    }

    public function getRestrictionsForStatus(string $status): array
    {
        $status = strtoupper($status);

        $base = [
            'create_campaign' => false,
            'edit_campaign' => false,
            'delete_campaign' => false,
            'withdraw_funds' => false,
            'receive_donations' => false,
            'transfer_campaign_ownership' => false,
            'update_sensitive_profile' => false,
            'update_payout_methods' => false,
        ];

        return match ($status) {
            self::STATUS_UNDER_REVIEW => array_merge($base, [
                'create_campaign' => true,
                'withdraw_funds' => true,
                'receive_donations' => true,
            ]),
            self::STATUS_LIMITED => array_merge($base, [
                'create_campaign' => true,
                'edit_campaign' => true,
                'withdraw_funds' => true,
                'transfer_campaign_ownership' => true,
                'update_sensitive_profile' => true,
                'update_payout_methods' => true,
            ]),
            self::STATUS_SUSPENDED => array_merge($base, [
                'create_campaign' => true,
                'edit_campaign' => true,
                'delete_campaign' => true,
                'withdraw_funds' => true,
                'receive_donations' => true,
                'transfer_campaign_ownership' => true,
                'update_sensitive_profile' => true,
                'update_payout_methods' => true,
            ]),
            self::STATUS_BANNED => array_merge($base, [
                'create_campaign' => true,
                'edit_campaign' => true,
                'delete_campaign' => true,
                'withdraw_funds' => true,
                'receive_donations' => true,
                'transfer_campaign_ownership' => true,
                'update_sensitive_profile' => true,
                'update_payout_methods' => true,
            ]),
            default => $base,
        };
    }

    public function getUserFacingStatusMessage(string $status, string $reason = ''): array
    {
        $reasonText = trim($reason);
        $reasonSuffix = $reasonText !== '' ? ' Reason: ' . $reasonText : '';

        return match (strtoupper($status)) {
            self::STATUS_UNDER_REVIEW => [
                'short' => 'Your account is currently under review. Some actions are temporarily unavailable.',
                'full' => 'Your account is currently under review because we detected activity that needs additional checks. You can still access your dashboard, but campaign creation, withdrawals, and new donations are temporarily disabled. Please complete any requested verification steps or contact support.' . $reasonSuffix,
            ],
            self::STATUS_LIMITED => [
                'short' => 'Your account has limited access. Some features are disabled until the review is resolved.',
                'full' => 'Your account has limited access due to trust and safety restrictions. Campaign creation, campaign editing, withdrawals, payout updates, and sensitive profile changes are currently disabled. Please complete any requested verification or contact support.' . $reasonSuffix,
            ],
            self::STATUS_SUSPENDED => [
                'short' => 'Your account is suspended. Most platform actions are currently locked.',
                'full' => 'Your account is temporarily suspended while we investigate trust and safety concerns. Campaign activity, donations, and withdrawals are blocked. You may only access limited account areas if permitted by platform policy.' . $reasonSuffix,
            ],
            self::STATUS_BANNED => [
                'short' => 'Your account has been permanently disabled.',
                'full' => 'Your account has been permanently disabled due to severe or repeated policy violations. Access to the platform is blocked and funds will be handled according to platform policy.' . $reasonSuffix,
            ],
            default => [
                'short' => 'Your account is active.',
                'full' => 'Your account is active and has full access.',
            ],
        };
    }



    private function resolveExpiredStatus(int $userId, string $status, ?string $reviewExpiresAt): string
    {
        if (!in_array($status, [self::STATUS_UNDER_REVIEW, self::STATUS_LIMITED], true)) {
            return $status;
        }

        if ($reviewExpiresAt === null || $reviewExpiresAt === '') {
            return $status;
        }

        if (strtotime($reviewExpiresAt) === false || strtotime($reviewExpiresAt) > time()) {
            return $status;
        }

        $this->db->query(
            'UPDATE users SET risk_status = ?, risk_status_reason = ?, risk_review_expires_at = NULL, risk_last_action_at = NOW() WHERE id = ?',
            [self::STATUS_ACTIVE, 'Review period expired automatically.', $userId]
        );

        return self::STATUS_ACTIVE;
    }


}
