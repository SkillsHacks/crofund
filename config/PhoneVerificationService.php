<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PhoneValidationService.php';

class PhoneVerificationService {
    private $db;
    private $validator;
    private $lookupEnabled;
    private $verifyEnabled;

    public function __construct(PhoneValidationService $validator) {
        $this->db = Database::getInstance();
        $this->validator = $validator;
        $this->lookupEnabled = defined('TWILIO_LOOKUP_ENABLED') && TWILIO_LOOKUP_ENABLED;
        $this->verifyEnabled = defined('TWILIO_VERIFY_ENABLED') && TWILIO_VERIFY_ENABLED;
    }

    public function validatePhoneForVerification(string $phoneInput, string $countryCode, string $deviceId, string $ipAddress): array {
        $localValidation = $this->validator->validateForCountry($phoneInput, $countryCode);

        if (!$localValidation['valid']) {
            return [
                'success' => false,
                'error' => $localValidation['message'],
                'validation' => $localValidation,
            ];
        }

        $context = $this->getRiskContext($localValidation['normalized_phone'], $deviceId, $ipAddress);
        $lookupResult = $this->lookupPhoneNumber($localValidation['normalized_phone']);
        $risk = $this->validator->scoreLookupRisk($countryCode, $lookupResult, $context);

        return [
            'success' => $risk['allowed'],
            'error' => $risk['allowed'] ? null : $risk['public_message'],
            'validation' => $localValidation,
            'lookup' => $lookupResult,
            'risk' => $risk,
        ];
    }

    public function canRequestOtp(string $phone, string $deviceId, string $ipAddress): array {
        $phoneWindow = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM phone_otp_requests WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$phone]
        );
        $deviceWindow = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM phone_otp_requests WHERE device_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
            [$deviceId]
        );
        $cooldown = $this->db->fetchOne(
            "SELECT created_at FROM phone_otp_requests WHERE phone = ? ORDER BY created_at DESC LIMIT 1",
            [$phone]
        );

        if (($phoneWindow['count'] ?? 0) >= 5 || ($deviceWindow['count'] ?? 0) >= 8) {
            return ['allowed' => false, 'message' => 'We are unable to verify this number. Please try again later.'];
        }

        if ($cooldown && (time() - strtotime($cooldown['created_at'])) < 60) {
            return ['allowed' => false, 'message' => 'Please wait before requesting another verification code.'];
        }

        return ['allowed' => true, 'message' => null];
    }

    public function logOtpRequest(string $phone, string $deviceId, string $ipAddress, int $riskScore): void {
        $this->db->query(
            "INSERT INTO phone_otp_requests (phone, device_id, ip_address, risk_score, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$phone, $deviceId, $ipAddress, $riskScore]
        );
    }

    public function logOtpAttempt(string $phone, string $deviceId, string $ipAddress, bool $success): void {
        $this->db->query(
            "INSERT INTO phone_otp_attempts (phone, device_id, ip_address, success, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$phone, $deviceId, $ipAddress, $success ? 1 : 0]
        );
    }

    public function sendOtp(string $phone): array {
        if (!$this->verifyEnabled || !defined('TWILIO_VERIFY_SERVICE_SID') || TWILIO_VERIFY_SERVICE_SID === '' || TWILIO_ACCOUNT_SID === '' || TWILIO_AUTH_TOKEN === '') {
            return [
                'success' => false,
                'error' => 'Phone verification is not configured yet.',
            ];
        }

        $response = $this->twilioPost(
            TWILIO_VERIFY_BASE_URL . rawurlencode(TWILIO_VERIFY_SERVICE_SID) . '/Verifications',
            [
                'To' => $phone,
                'Channel' => 'sms',
            ]
        );

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'We are unable to verify this number. Please try another number.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Verification code sent successfully.',
        ];
    }

    public function verifyOtp(string $phone, string $code): array {
        if (!$this->verifyEnabled || !defined('TWILIO_VERIFY_SERVICE_SID') || TWILIO_VERIFY_SERVICE_SID === '' || TWILIO_ACCOUNT_SID === '' || TWILIO_AUTH_TOKEN === '') {
            return [
                'success' => false,
                'error' => 'Phone verification is not configured yet.',
            ];
        }

        $response = $this->twilioPost(
            TWILIO_VERIFY_BASE_URL . rawurlencode(TWILIO_VERIFY_SERVICE_SID) . '/VerificationCheck',
            [
                'To' => $phone,
                'Code' => $code,
            ]
        );

        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'We are unable to verify this number. Please try another number.',
            ];
        }

        $data = $response['data'];
        $status = $data['status'] ?? 'unknown';

        if ($status !== 'approved') {
            error_log('Twilio verification check not approved. Status: ' . $status . ' Phone: ' . $phone);

            return [
                'success' => false,
                'error' => 'Invalid or expired verification code. Please request a new code and try again.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Phone number verified successfully.',
        ];
    }

    private function getRiskContext(string $phone, string $deviceId, string $ipAddress): array {
        $failedAttempts = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM phone_otp_attempts WHERE phone = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$phone]
        );
        $recentPhoneRequests = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM phone_otp_requests WHERE phone = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$phone]
        );
        $recentDeviceRequests = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM phone_otp_requests WHERE device_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$deviceId]
        );
        $recentIpPatterns = $this->db->fetchOne(
            "SELECT COUNT(*) AS count FROM phone_otp_requests WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$ipAddress]
        );

        return [
            'failed_attempts' => (int) ($failedAttempts['count'] ?? 0),
            'recent_phone_requests' => (int) ($recentPhoneRequests['count'] ?? 0),
            'recent_device_requests' => (int) ($recentDeviceRequests['count'] ?? 0),
            'recent_ip_patterns' => (int) ($recentIpPatterns['count'] ?? 0),
        ];
    }

    private function lookupPhoneNumber(string $phone): array {
        if (!$this->lookupEnabled || !defined('TWILIO_ACCOUNT_SID') || !defined('TWILIO_AUTH_TOKEN') || TWILIO_ACCOUNT_SID === '' || TWILIO_AUTH_TOKEN === '') {
            return [
                'provider' => 'none',
                'line_type' => 'unknown',
                'carrier_name' => null,
            ];
        }

        $url = TWILIO_LOOKUP_BASE_URL . rawurlencode($phone) . '?Fields=line_type_intelligence';
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERPWD => TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            error_log('Twilio Lookup failed: ' . ($error ?: 'HTTP ' . $httpCode));

            return [
                'provider' => 'twilio',
                'line_type' => 'unknown',
                'carrier_name' => null,
            ];
        }

        $data = json_decode($response, true);
        $lineType = strtolower((string) ($data['line_type_intelligence']['type'] ?? 'unknown'));
        $carrierName = $data['line_type_intelligence']['carrier_name'] ?? null;

        return [
            'provider' => 'twilio',
            'line_type' => $lineType,
            'carrier_name' => $carrierName,
        ];
    }

    private function twilioPost(string $url, array $payload): array {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_USERPWD => TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            error_log('Twilio Verify failed: ' . ($error ?: 'HTTP ' . $httpCode));

            return [
                'success' => false,
                'data' => null,
            ];
        }

        return [
            'success' => true,
            'data' => json_decode($response, true),
        ];
    }
}
