<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/EmailValidationService.php';

class EmailVerificationService {
    private $db;
    private $validator;

    public function __construct(EmailValidationService $validator) {
        $this->db = Database::getInstance();
        $this->validator = $validator;
    }

    public function validateEmailForRegistration(string $email, string $deviceId, string $ipAddress): array {
        $validation = $this->validator->validateEmail($email);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['message'],
                'validation' => $validation,
                'risk' => [
                    'score' => 100,
                    'allowed' => false,
                    'public_message' => $validation['message'],
                    'risk_level' => 'high',
                ],
            ];
        }

        $context = $this->getRiskContext($validation['email'], $validation['local_part'], $validation['domain'], $deviceId, $ipAddress);
        $risk = $this->validator->scoreRisk($validation, $context);

        return [
            'success' => $risk['allowed'],
            'error' => $risk['allowed'] ? null : $risk['public_message'],
            'validation' => $validation,
            'risk' => $risk,
        ];
    }

    public function canRequestOtp(string $email, string $deviceId, string $ipAddress): array {
        $emailWindow = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM email_otp_requests WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$email]
        );
        $deviceWindow = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM email_otp_requests WHERE device_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$deviceId]
        );
        $ipWindow = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM email_otp_requests WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$ipAddress]
        );
        $cooldown = $this->db->fetchOne(
            "SELECT created_at FROM email_otp_requests WHERE email = ? ORDER BY created_at DESC LIMIT 1",
            [$email]
        );

        if (($emailWindow['count'] ?? 0) >= 5 || ($deviceWindow['count'] ?? 0) >= 8 || ($ipWindow['count'] ?? 0) >= 10) {
            return ['allowed' => false, 'message' => 'We cannot verify this email address right now. Please try again later.'];
        }

        if ($cooldown && (time() - strtotime($cooldown['created_at'])) < 60) {
            return ['allowed' => false, 'message' => 'Please wait before requesting another verification code.'];
        }

        return ['allowed' => true, 'message' => null];
    }

    public function logOtpRequest(string $email, string $deviceId, string $ipAddress, int $riskScore): void {
        $this->db->query(
            "INSERT INTO email_otp_requests (email, device_id, ip_address, risk_score, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$email, $deviceId, $ipAddress, $riskScore]
        );
    }

    public function logOtpAttempt(string $email, string $deviceId, string $ipAddress, bool $success): void {
        $this->db->query(
            "INSERT INTO email_otp_attempts (email, device_id, ip_address, success, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$email, $deviceId, $ipAddress, $success ? 1 : 0]
        );
    }

    private function getRiskContext(string $email, string $localPart, string $domain, string $deviceId, string $ipAddress): array {
        $recentRequestsForEmail = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM email_otp_requests WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$email]
        );
        $recentRequestsForIp = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM email_otp_requests WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$ipAddress]
        );
        $recentRequestsForDevice = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM email_otp_requests WHERE device_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$deviceId]
        );
        $failedAttempts = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM email_otp_attempts WHERE email = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$email]
        );
        $similarEmailCount = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM email_otp_requests WHERE ip_address = ? AND email LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$ipAddress, $localPart . '%@' . $domain]
        );
        $existingUsageCount = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM users WHERE email = ?",
            [$email]
        );

        return [
            'recent_requests_for_email' => (int) ($recentRequestsForEmail['count'] ?? 0),
            'recent_requests_for_ip' => (int) ($recentRequestsForIp['count'] ?? 0),
            'recent_requests_for_device' => (int) ($recentRequestsForDevice['count'] ?? 0),
            'failed_attempts' => (int) ($failedAttempts['count'] ?? 0),
            'similar_email_count' => (int) ($similarEmailCount['count'] ?? 0),
            'existing_usage_count' => (int) ($existingUsageCount['count'] ?? 0),
        ];
    }
}
