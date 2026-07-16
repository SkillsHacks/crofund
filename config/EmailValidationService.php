<?php

class EmailValidationService {
    private $restrictions;

    public function __construct(array $restrictions) {
        $this->restrictions = $restrictions;
    }

    public function validateEmail(string $email): array {
        $email = trim(strtolower($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->invalidResult('invalid_format');
        }

        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($localPart === '' || $domain === '') {
            return $this->invalidResult('unsupported_format');
        }

        if (!$this->isValidDomainStructure($domain)) {
            return $this->invalidResult('unsupported_format');
        }

        if ($this->isBlockedDomain($domain)) {
            return $this->invalidResult('temporary_email');
        }

        $mxRecords = [];
        $mxHosts = [];
        $hasMx = $this->lookupMxRecords($domain, $mxHosts, $mxRecords);
        $aRecordFallback = false;

        if (!$hasMx) {
            $aRecordFallback = checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
            if (!$aRecordFallback) {
                return $this->invalidResult('cannot_verify');
            }
        }

        return [
            'valid' => true,
            'email' => $email,
            'local_part' => $localPart,
            'domain' => $domain,
            'has_mx' => $hasMx,
            'mx_records' => $mxHosts,
            'a_record_fallback' => $aRecordFallback,
            'is_role_account' => $this->isRoleAccount($localPart),
            'is_disposable' => false,
            'message' => '',
        ];
    }

    public function scoreRisk(array $validation, array $context = []): array {
        $score = 0;
        $publicMessages = $this->restrictions['public_messages'] ?? [];

        if (!$validation['valid']) {
            return [
                'score' => 100,
                'allowed' => false,
                'public_message' => $validation['message'] ?: ($publicMessages['cannot_verify'] ?? 'We cannot verify this email address. Please try another email.'),
                'risk_level' => 'high',
            ];
        }

        if (!empty($validation['is_role_account'])) {
            $score += 20;
        }

        if (empty($validation['has_mx']) && !empty($validation['a_record_fallback'])) {
            $score += 25;
        }

        $recentRequestsForEmail = (int) ($context['recent_requests_for_email'] ?? 0);
        $recentRequestsForIp = (int) ($context['recent_requests_for_ip'] ?? 0);
        $recentRequestsForDevice = (int) ($context['recent_requests_for_device'] ?? 0);
        $failedAttempts = (int) ($context['failed_attempts'] ?? 0);
        $similarEmailCount = (int) ($context['similar_email_count'] ?? 0);
        $existingUsageCount = (int) ($context['existing_usage_count'] ?? 0);

        $score += min(max($recentRequestsForEmail - 1, 0) * 10, 30);
        $score += min(max($recentRequestsForIp - 3, 0) * 5, 20);
        $score += min(max($recentRequestsForDevice - 3, 0) * 5, 20);
        $score += min($failedAttempts * 8, 24);
        $score += min(max($similarEmailCount - 2, 0) * 10, 20);
        $score += min($existingUsageCount * 25, 25);

        $riskLevel = $score >= 70 ? 'high' : ($score >= 35 ? 'medium' : 'low');
        $allowed = $score < 70;

        return [
            'score' => $score,
            'allowed' => $allowed,
            'public_message' => $allowed
                ? 'Email address looks good.'
                : ($publicMessages['cannot_verify'] ?? 'We cannot verify this email address. Please try another email.'),
            'risk_level' => $riskLevel,
        ];
    }

    private function invalidResult(string $messageKey): array {
        $messages = $this->restrictions['public_messages'] ?? [];

        return [
            'valid' => false,
            'email' => null,
            'local_part' => null,
            'domain' => null,
            'has_mx' => false,
            'mx_records' => [],
            'a_record_fallback' => false,
            'is_role_account' => false,
            'is_disposable' => $messageKey === 'temporary_email',
            'message' => $messages[$messageKey] ?? ($messages['cannot_verify'] ?? 'We cannot verify this email address. Please try another email.'),
        ];
    }

    private function isValidDomainStructure(string $domain): bool {
        if (strlen($domain) > 253 || strpos($domain, '.') === false) {
            return false;
        }

        return (bool) preg_match('/^(?=.{1,253}$)(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i', $domain);
    }

    private function isBlockedDomain(string $domain): bool {
        $blockedDomains = array_map('strtolower', $this->restrictions['blocked_domains'] ?? []);
        if (in_array($domain, $blockedDomains, true)) {
            return true;
        }

        foreach (($this->restrictions['blocked_patterns'] ?? []) as $pattern) {
            if (@preg_match($pattern, $domain)) {
                if (preg_match($pattern, $domain)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isRoleAccount(string $localPart): bool {
        return in_array(strtolower($localPart), array_map('strtolower', $this->restrictions['role_prefixes'] ?? []), true);
    }

    private function lookupMxRecords(string $domain, array &$mxHosts, array &$mxWeights): bool {
        $mxHosts = [];
        $mxWeights = [];

        if (function_exists('getmxrr')) {
            return getmxrr($domain, $mxHosts, $mxWeights);
        }

        return false;
    }
}
