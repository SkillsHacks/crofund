<?php

require_once __DIR__ . '/Database.php';

class IdVerificationService
{
    public const STATUS_FAILED = 'failed';
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_VERIFIED = 'verified';

    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getLatestAttempt(int $userId): ?array
    {
        try {
            return $this->db->fetchOne(
                'SELECT * FROM identity_verification_attempts WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 1',
                [$userId]
            );
        } catch (Throwable $e) {
            return null;
        }
    }

    public function hasPendingReview(int $userId): bool
    {
        try {
            $attempt = $this->getLatestAttempt($userId);
            return $attempt ? $this->isAttemptPendingReview($attempt) : false;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function isAttemptPendingReview(array $attempt): bool
    {
        $status = strtolower(trim((string) ($attempt['status'] ?? '')));
        $decisionStatus = strtolower(trim((string) ($attempt['decision_status'] ?? '')));

        if ($status === self::STATUS_PENDING_REVIEW) {
            return true;
        }

        if ($status !== self::STATUS_UPLOADED) {
            return false;
        }

        return in_array($decisionStatus, ['manual_review', 'partial_match', 'matched', 'no_match', ''], true);
    }

    public function isAttemptVerified(array $attempt): bool
    {
        return strtolower(trim((string) ($attempt['status'] ?? ''))) === self::STATUS_VERIFIED;
    }

    public function isAttemptRejected(array $attempt): bool
    {
        return strtolower(trim((string) ($attempt['status'] ?? ''))) === self::STATUS_REJECTED;
    }

    public function getWorkflowStatusLabel(?array $attempt): string
    {
        if (!$attempt) {
            return 'No submission';
        }

        $status = strtolower(trim((string) ($attempt['status'] ?? '')));
        return match ($status) {
            self::STATUS_FAILED => 'Upload Failed',
            self::STATUS_UPLOADED => 'Uploaded',
            self::STATUS_PENDING_REVIEW => 'Under Review',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_VERIFIED => 'Verified',
            default => ucwords(str_replace('_', ' ', $status !== '' ? $status : 'unknown')),
        };
    }

    public function storeUploadedNationalId(array $file, int $userId): array
    {
        $attemptData = [
            'success' => false,
            'status' => 'failed',
            'stored_path' => null,
            'processed_path' => null,
            'original_name' => (string) ($file['name'] ?? ''),
            'mime_type' => null,
            'file_size' => (int) ($file['size'] ?? 0),
            'quality' => [
                'passes' => false,
                'message' => '',
                'width' => null,
                'height' => null,
                'brightness' => null,
                'blur_score' => null,
            ],
            'ocr' => [
                'available' => false,
                'status' => 'unavailable',
                'message' => '',
                'raw_text' => '',
                'fields' => [],
            ],
            'decision' => [
                'status' => 'manual_review',
                'message' => 'Manual review is required.',
                'score' => 0,
                'checks' => [],
            ],
            'failure_reason' => null,
        ];

        try {
            $this->assertUploadIsPresent($file);
            $detectedMimeType = $this->detectMimeType($file['tmp_name']);
            $attemptData['mime_type'] = $detectedMimeType;

            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!$detectedMimeType || !isset($allowedMimeTypes[$detectedMimeType])) {
                throw new Exception('Invalid ID image format. Please use JPG, PNG, or WEBP.');
            }

            if (($file['size'] ?? 0) > 10 * 1024 * 1024) {
                throw new Exception('The ID image is too large. Please upload an image smaller than 10MB.');
            }

            $imageSize = @getimagesize($file['tmp_name']);
            if (!$imageSize || empty($imageSize[0]) || empty($imageSize[1])) {
                throw new Exception('We could not read the uploaded ID image. Please try another image.');
            }

            $quality = $this->inspectImageQuality($file['tmp_name'], (int) $imageSize[0], (int) $imageSize[1]);
            $attemptData['quality'] = $quality;

            if (!$quality['passes']) {
                throw new Exception($quality['message']);
            }

            $identityDir = rtrim(UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'identity' . DIRECTORY_SEPARATOR;
            $processedDir = $identityDir . 'processed' . DIRECTORY_SEPARATOR;

            if (!is_dir($identityDir) && !mkdir($identityDir, 0775, true) && !is_dir($identityDir)) {
                throw new Exception('Unable to prepare secure ID upload storage.');
            }

            if (!is_dir($processedDir) && !mkdir($processedDir, 0775, true) && !is_dir($processedDir)) {
                throw new Exception('Unable to prepare processed ID storage.');
            }

            $extension = $allowedMimeTypes[$detectedMimeType];
            $filename = sprintf('user_%d_%s.%s', $userId, bin2hex(random_bytes(16)), $extension);
            $destination = $identityDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception('Unable to store the uploaded ID image. Please try again.');
            }

            $attemptData['stored_path'] = 'uploads/identity/' . $filename;

            $processedFilename = sprintf('processed_user_%d_%s.jpg', $userId, bin2hex(random_bytes(16)));
            $processedDestination = $processedDir . $processedFilename;
            $processedPublicPath = $this->createProcessedCopy($destination, $processedDestination);
            $attemptData['processed_path'] = $processedPublicPath;

            $ocr = $this->runOcrOnProcessedCopy($processedDestination);
            $attemptData['ocr'] = $ocr;

            $decision = $this->buildDecisionForUser($userId, $ocr['fields'] ?? []);
            $attemptData['decision'] = $decision;
            $attemptData['success'] = true;
            $attemptData['status'] = self::STATUS_PENDING_REVIEW;

            $this->persistAttempt($userId, $attemptData);
            return $attemptData;
        } catch (Exception $e) {
            $attemptData['failure_reason'] = $e->getMessage();
            $attemptData['quality']['message'] = $attemptData['quality']['message'] ?: $e->getMessage();
            $this->persistAttempt($userId, $attemptData);
            throw $e;
        }
    }

    private function persistAttempt(int $userId, array $attemptData): void
    {
        try {
            $this->db->query(
                'INSERT INTO identity_verification_attempts (
                    user_id,
                    document_type,
                    status,
                    decision_status,
                    decision_score,
                    failure_reason,
                    original_image_path,
                    processed_image_path,
                    original_filename,
                    mime_type,
                    file_size,
                    image_width,
                    image_height,
                    brightness_score,
                    blur_score,
                    ocr_status,
                    ocr_message,
                    ocr_raw_text,
                    extracted_fields,
                    decision_checks,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [
                    $userId,
                    'national_id',
                    (string) ($attemptData['status'] ?? self::STATUS_FAILED),
                    (string) ($attemptData['decision']['status'] ?? 'manual_review'),
                    (int) ($attemptData['decision']['score'] ?? 0),
                    $attemptData['failure_reason'],
                    $attemptData['stored_path'],
                    $attemptData['processed_path'],
                    (string) ($attemptData['original_name'] ?? ''),
                    $attemptData['mime_type'],
                    (int) ($attemptData['file_size'] ?? 0),
                    $attemptData['quality']['width'] ?? null,
                    $attemptData['quality']['height'] ?? null,
                    $attemptData['quality']['brightness'] ?? null,
                    $attemptData['quality']['blur_score'] ?? null,
                    (string) ($attemptData['ocr']['status'] ?? 'unavailable'),
                    (string) ($attemptData['ocr']['message'] ?? ''),
                    (string) ($attemptData['ocr']['raw_text'] ?? ''),
                    !empty($attemptData['ocr']['fields']) ? json_encode($attemptData['ocr']['fields']) : null,
                    !empty($attemptData['decision']['checks']) ? json_encode($attemptData['decision']['checks']) : null,
                ]
            );
        } catch (Throwable $e) {
            error_log('Failed to persist identity verification attempt: ' . $e->getMessage());
        }
    }

    private function assertUploadIsPresent(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            switch ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('The ID image is too large. Please upload an image smaller than 10MB.');
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('Please upload your National ID image before submitting for verification.');
                default:
                    throw new Exception('We could not process the uploaded ID image. Please try again.');
            }
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('The uploaded ID image is invalid. Please try again.');
        }
    }

    private function detectMimeType(string $tmpPath): ?string
    {
        $detectedMimeType = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedMimeType = finfo_file($finfo, $tmpPath) ?: null;
                finfo_close($finfo);
            }
        }

        if ($detectedMimeType === null && function_exists('mime_content_type')) {
            $detectedMimeType = mime_content_type($tmpPath) ?: null;
        }

        return $detectedMimeType;
    }

    private function inspectImageQuality(string $tmpPath, int $width, int $height): array
    {
        if ($width < 1000 || $height < 600) {
            return [
                'passes' => false,
                'message' => 'The uploaded ID image is too small. Please use a clearer, higher-resolution photo.',
                'width' => $width,
                'height' => $height,
                'brightness' => null,
                'blur_score' => null,
            ];
        }

        $brightness = null;
        $blurScore = null;

        if (function_exists('imagecreatefromstring')) {
            $contents = @file_get_contents($tmpPath);
            if ($contents !== false) {
                $image = @imagecreatefromstring($contents);
                if ($image !== false) {
                    $sampleWidth = min(200, imagesx($image));
                    $sampleHeight = min(200, imagesy($image));
                    $sample = imagecreatetruecolor($sampleWidth, $sampleHeight);
                    imagecopyresampled($sample, $image, 0, 0, 0, 0, $sampleWidth, $sampleHeight, imagesx($image), imagesy($image));

                    $pixelCount = 0;
                    $brightnessTotal = 0.0;
                    $contrastAccumulator = 0.0;
                    $previousGray = null;

                    for ($y = 0; $y < $sampleHeight; $y++) {
                        for ($x = 0; $x < $sampleWidth; $x++) {
                            $rgb = imagecolorat($sample, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            $gray = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

                            $brightnessTotal += $gray;
                            $pixelCount++;

                            if ($previousGray !== null) {
                                $contrastAccumulator += abs($gray - $previousGray);
                            }
                            $previousGray = $gray;
                        }
                    }

                    imagedestroy($sample);
                    imagedestroy($image);

                    if ($pixelCount > 0) {
                        $brightness = $brightnessTotal / $pixelCount;
                    }

                    if ($pixelCount > 1) {
                        $blurScore = $contrastAccumulator / ($pixelCount - 1);
                    }
                }
            }
        }

        if ($brightness !== null && $brightness < 55) {
            return [
                'passes' => false,
                'message' => 'The uploaded ID image is too dark. Please retake the photo in better lighting.',
                'width' => $width,
                'height' => $height,
                'brightness' => round($brightness, 2),
                'blur_score' => $blurScore !== null ? round($blurScore, 2) : null,
            ];
        }

        if ($brightness !== null && $brightness > 235) {
            return [
                'passes' => false,
                'message' => 'The uploaded ID image is too bright or washed out. Please avoid glare and retake the photo.',
                'width' => $width,
                'height' => $height,
                'brightness' => round($brightness, 2),
                'blur_score' => $blurScore !== null ? round($blurScore, 2) : null,
            ];
        }

        if ($blurScore !== null && $blurScore < 8) {
            return [
                'passes' => false,
                'message' => 'The uploaded ID image appears too blurry. Please upload a sharper photo with readable text.',
                'width' => $width,
                'height' => $height,
                'brightness' => $brightness !== null ? round($brightness, 2) : null,
                'blur_score' => round($blurScore, 2),
            ];
        }

        return [
            'passes' => true,
            'message' => 'Image uploaded and passed initial quality checks.',
            'width' => $width,
            'height' => $height,
            'brightness' => $brightness !== null ? round($brightness, 2) : null,
            'blur_score' => $blurScore !== null ? round($blurScore, 2) : null,
        ];
    }

    private function createProcessedCopy(string $sourcePath, string $processedDestination): string
    {
        $processedPublicPath = 'uploads/identity/processed/' . basename($processedDestination);

        if (!function_exists('imagecreatefromstring')) {
            if (!copy($sourcePath, $processedDestination)) {
                throw new Exception('Unable to prepare the processed verification copy.');
            }
            return $processedPublicPath;
        }

        $contents = @file_get_contents($sourcePath);
        if ($contents === false) {
            throw new Exception('Unable to read the stored ID image for processing.');
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            if (!copy($sourcePath, $processedDestination)) {
                throw new Exception('Unable to prepare the processed verification copy.');
            }
            return $processedPublicPath;
        }

        if (function_exists('imagefilter')) {
            @imagefilter($image, IMG_FILTER_GRAYSCALE);
            @imagefilter($image, IMG_FILTER_CONTRAST, -8);
            @imagefilter($image, IMG_FILTER_BRIGHTNESS, 5);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxWidth = 1800;

        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round(($height / $width) * $newWidth);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        if (!imagejpeg($image, $processedDestination, 90)) {
            imagedestroy($image);
            throw new Exception('Unable to save the processed verification copy.');
        }

        imagedestroy($image);
        return $processedPublicPath;
    }

    private function runOcrOnProcessedCopy(string $processedPath): array
    {
        $tesseractBinary = trim((string) @shell_exec('command -v tesseract 2>/dev/null'));
        if ($tesseractBinary === '') {
            return [
                'available' => false,
                'status' => 'unavailable',
                'message' => 'Server-side OCR is not available yet because Tesseract is not installed on this server.',
                'raw_text' => '',
                'fields' => [],
            ];
        }

        $escapedBinary = escapeshellcmd($tesseractBinary);
        $escapedPath = escapeshellarg($processedPath);
        $command = $escapedBinary . ' ' . $escapedPath . ' stdout -l eng 2>/dev/null';
        $rawText = trim((string) @shell_exec($command));

        if ($rawText === '') {
            return [
                'available' => true,
                'status' => 'no_text',
                'message' => 'OCR ran but could not extract readable text from the processed image.',
                'raw_text' => '',
                'fields' => [],
            ];
        }

        return [
            'available' => true,
            'status' => 'extracted',
            'message' => 'OCR completed and extracted candidate identity text.',
            'raw_text' => $rawText,
            'fields' => $this->extractIdentityFields($rawText),
        ];
    }

    private function extractIdentityFields(string $text): array
    {
        $fields = [
            'surname' => null,
            'date_of_birth' => null,
            'id_number' => null,
        ];

        $surnamePatterns = [
            '/surname[:\s]+([A-Z\s]+)/i',
            '/surname[:\s]*\n?\s*([A-Z\s]+)/i',
            '/REPUBLIC OF UGANDA[\s\S]*?([A-Z]{4,})/i',
            '/NATIONAL ID CARD[\s\S]*?([A-Z]{4,})/i',
        ];

        foreach ($surnamePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = trim((string) ($matches[1] ?? ''));
                if ($candidate !== '' && !in_array(strtoupper($candidate), ['SURNAME', 'REPUBLIC', 'UGANDA', 'NATIONAL'], true)) {
                    $fields['surname'] = preg_replace('/\s+/', ' ', $candidate);
                    break;
                }
            }
        }

        $dobPatterns = [
            '/date\s+of\s+birth[:\s]*([0-9]{2}[.\/-][0-9]{2}[.\/-][0-9]{4})/i',
            '/birth[:\s]*([0-9]{2}[.\/-][0-9]{2}[.\/-][0-9]{4})/i',
            '/([0-9]{2}[.\/-][0-9]{2}[.\/-][0-9]{4})/',
        ];

        foreach ($dobPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $fields['date_of_birth'] = trim((string) ($matches[1] ?? ''));
                break;
            }
        }

        if (preg_match('/([0-9]{10,})/', $text, $matches)) {
            $fields['id_number'] = trim((string) ($matches[1] ?? ''));
        }

        return $fields;
    }

    private function buildDecisionForUser(int $userId, array $fields): array
    {
        $user = $this->db->fetchOne('SELECT id, name, email, phone, country, city FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return [
                'status' => 'manual_review',
                'message' => 'We could not load the account identity details for comparison.',
                'score' => 0,
                'checks' => [],
            ];
        }

        $checks = [];
        $score = 0;

        $normalizedFullName = $this->normalizeText((string) ($user['name'] ?? ''));
        $nameParts = array_values(array_filter(explode(' ', $normalizedFullName)));
        $extractedSurname = $this->normalizeText((string) ($fields['surname'] ?? ''));

        $surnameMatched = false;
        if ($extractedSurname !== '') {
            foreach ($nameParts as $part) {
                if ($this->isStrongNameMatch($extractedSurname, $part)) {
                    $surnameMatched = true;
                    break;
                }
            }
        }

        $checks['surname'] = [
            'expected' => (string) ($user['name'] ?? ''),
            'extracted' => (string) ($fields['surname'] ?? ''),
            'matched' => $surnameMatched,
        ];

        if ($surnameMatched) {
            $score += 70;
        }

        $hasDateOfBirth = !empty($fields['date_of_birth']);
        $checks['date_of_birth'] = [
            'expected' => null,
            'extracted' => (string) ($fields['date_of_birth'] ?? ''),
            'matched' => false,
        ];
        if ($hasDateOfBirth) {
            $score += 10;
        }

        $hasIdNumber = !empty($fields['id_number']);
        $checks['id_number'] = [
            'expected' => null,
            'extracted' => (string) ($fields['id_number'] ?? ''),
            'matched' => false,
        ];
        if ($hasIdNumber) {
            $score += 10;
        }

        $hasAnyUsefulText = $extractedSurname !== '' || $hasDateOfBirth || $hasIdNumber;
        if ($hasAnyUsefulText) {
            $score += 10;
        }

        if ($surnameMatched && $score >= 80) {
            return [
                'status' => 'matched',
                'message' => 'The uploaded ID appears to match the account name and extracted identity details.',
                'score' => $score,
                'checks' => $checks,
            ];
        }

        if ($surnameMatched || $hasAnyUsefulText) {
            return [
                'status' => 'partial_match',
                'message' => 'The uploaded ID produced some matching identity signals, but it still requires manual review.',
                'score' => $score,
                'checks' => $checks,
            ];
        }

        return [
            'status' => 'no_match',
            'message' => 'We could not confidently match the uploaded ID details to the account identity.',
            'score' => $score,
            'checks' => $checks,
        ];
    }

    private function normalizeText(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9\s]/', ' ', $value);
        return preg_replace('/\s+/', ' ', (string) $value);
    }

    private function isStrongNameMatch(string $left, string $right): bool
    {
        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        if (str_contains($left, $right) || str_contains($right, $left)) {
            return true;
        }

        if (function_exists('levenshtein')) {
            $distance = levenshtein($left, $right);
            $longest = max(strlen($left), strlen($right));
            if ($longest > 0 && (($distance / $longest) <= 0.2)) {
                return true;
            }
        }

        return false;
    }
}
