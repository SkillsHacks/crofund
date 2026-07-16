<?php

require_once __DIR__ . '/PhoneRestrictionUtils.php';

class PhoneValidationService {
    private $rules;
    private $unsupportedDialCodes;
    private $unsupportedCountryMessage;

    public function __construct(array $rules, array $unsupportedDialCodes, string $unsupportedCountryMessage) {
        $this->rules = $rules;
        $this->unsupportedDialCodes = $unsupportedDialCodes;
        $this->unsupportedCountryMessage = $unsupportedCountryMessage;
    }

    public function getRule(string $countryCode): array {
        return $this->rules[$countryCode] ?? $this->rules['_default'];
    }

    public function getPlaceholder(string $countryCode): string {
        $rule = $this->getRule($countryCode);
        $dialCode = $rule['dial_code'] ?? '';
        $example = $rule['example'] ?? '123 456 789';

        return trim(sprintf('Enter phone number e.g. %s %s', $dialCode, $example));
    }

    public function validateForCountry(string $phoneInput, string $countryCode): array {
        $rule = $this->getRule($countryCode);
        $dialCode = $rule['dial_code'] ?? '';
        $dialDigits = ltrim($dialCode, '+');
        $raw = trim($phoneInput);
        $digits = preg_replace('/\D+/', '', $raw);

        if ($dialCode === '') {
            return $this->invalidResult($countryCode, 'Please select a valid country code.');
        }

        if ($raw === '') {
            return $this->invalidResult($countryCode, 'Phone number is required.');
        }

        if (strpos($raw, '+') === 0) {
            if (strpos($digits, $dialDigits) !== 0) {
                return $this->invalidResult($countryCode, 'Please enter a valid phone number for the selected country.');
            }

            $nationalNumber = substr($digits, strlen($dialDigits));
        } else {
            $nationalNumber = $digits;
        }

        if (($rule['strip_leading_zero'] ?? true) && strpos($nationalNumber, '0') === 0) {
            $nationalNumber = substr($nationalNumber, 1);
        }

        $normalizedPhone = '+' . $dialDigits . $nationalNumber;

        if (isUnsupportedPhoneCountry($normalizedPhone, $this->unsupportedDialCodes)) {
            return $this->invalidResult($countryCode, $this->unsupportedCountryMessage);
        }

        $length = strlen($nationalNumber);
        $minLength = $rule['min_length'] ?? 7;
        $maxLength = $rule['max_length'] ?? 12;

        if ($length < $minLength || $length > $maxLength) {
            return $this->invalidResult($countryCode, 'Please enter a valid phone number for the selected country.');
        }

        $allowedPrefixes = $rule['allowed_prefixes'] ?? [];
        if (!empty($allowedPrefixes)) {
            $prefixMatches = false;

            foreach ($allowedPrefixes as $prefix) {
                if (strpos($nationalNumber, $prefix) === 0) {
                    $prefixMatches = true;
                    break;
                }
            }

            if (!$prefixMatches) {
                return $this->invalidResult($countryCode, 'Please enter a valid mobile number for the selected country.');
            }
        }

        $formattedPhone = $this->formatNationalNumber($dialCode, $nationalNumber, $rule['grouping'] ?? []);

        return [
            'valid' => true,
            'message' => '',
            'country_code' => $countryCode,
            'dial_code' => $dialCode,
            'placeholder' => $this->getPlaceholder($countryCode),
            'normalized_phone' => $normalizedPhone,
            'formatted_phone' => $formattedPhone,
            'national_number' => $nationalNumber,
            'risk_level' => $rule['risk_level'] ?? 'medium',
        ];
    }

    public function scoreLookupRisk(string $countryCode, array $lookupResult, array $context = []): array {
        $score = 0;
        $lineType = strtolower((string) ($lookupResult['line_type'] ?? 'unknown'));
        $countryRisk = $this->getRule($countryCode)['risk_level'] ?? 'medium';

        if ($countryRisk === 'medium') {
            $score += 10;
        } elseif ($countryRisk === 'high') {
            $score += 25;
        }

        if (in_array($lineType, ['nonfixedvoip', 'fixedvoip', 'voip', 'tollfree', 'premium'], true)) {
            $score += 90;
        } elseif (in_array($lineType, ['landline', 'fixedline'], true)) {
            $score += 80;
        } elseif ($lineType === 'unknown') {
            $score += 25;
        }

        $failedAttempts = (int) ($context['failed_attempts'] ?? 0);
        $recentPhoneRequests = (int) ($context['recent_phone_requests'] ?? 0);
        $recentDeviceRequests = (int) ($context['recent_device_requests'] ?? 0);
        $recentIpPatterns = (int) ($context['recent_ip_patterns'] ?? 0);

        $score += min($failedAttempts * 8, 24);
        $score += min(max($recentPhoneRequests - 1, 0) * 5, 15);
        $score += min(max($recentDeviceRequests - 2, 0) * 5, 20);
        $score += min(max($recentIpPatterns - 2, 0) * 5, 20);

        $allowed = $score < 70 && !in_array($lineType, ['nonfixedvoip', 'fixedvoip', 'voip', 'tollfree', 'premium', 'landline', 'fixedline'], true);
        $publicMessage = $allowed
            ? 'This number is ready for verification.'
            : 'This phone number cannot be used for verification.';

        return [
            'score' => $score,
            'allowed' => $allowed,
            'line_type' => $lineType === '' ? 'unknown' : $lineType,
            'public_message' => $publicMessage,
        ];
    }

    private function invalidResult(string $countryCode, string $message): array {
        return [
            'valid' => false,
            'message' => $message,
            'country_code' => $countryCode,
            'placeholder' => $this->getPlaceholder($countryCode),
            'normalized_phone' => null,
            'formatted_phone' => null,
            'national_number' => null,
            'risk_level' => $this->getRule($countryCode)['risk_level'] ?? 'medium',
        ];
    }

    private function formatNationalNumber(string $dialCode, string $nationalNumber, array $grouping): string {
        if ($nationalNumber === '') {
            return trim($dialCode);
        }

        if (empty($grouping)) {
            return trim($dialCode . ' ' . $nationalNumber);
        }

        $offset = 0;
        $segments = [];

        foreach ($grouping as $groupSize) {
            if ($offset >= strlen($nationalNumber)) {
                break;
            }

            $segments[] = substr($nationalNumber, $offset, $groupSize);
            $offset += $groupSize;
        }

        if ($offset < strlen($nationalNumber)) {
            $segments[] = substr($nationalNumber, $offset);
        }

        return trim($dialCode . ' ' . implode(' ', array_filter($segments)));
    }
}
