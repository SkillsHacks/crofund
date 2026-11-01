<?php

class CampaignDraft {
    private const SESSION_KEY = 'campaign_creation_draft';
    private const LIFETIME_SECONDS = 86400;
    private const MAX_IMAGE_SIZE = 10 * 1024 * 1024;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private static function storageDirectory(): string {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'crofund_campaign_drafts';
    }

    private static function ensureStorageDirectory(): string {
        $directory = self::storageDirectory();
        if (is_link($directory)) {
            throw new RuntimeException('Campaign draft storage is temporarily unavailable.');
        }

        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Campaign draft storage is temporarily unavailable.');
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('Campaign draft storage is temporarily unavailable.');
        }

        return $directory;
    }

    private static function removeImage(?string $path): void {
        if ($path !== null && self::isStagedImagePath($path) && is_file($path)) {
            if (!unlink($path)) {
                error_log('[CampaignDraft] Unable to remove staged image: ' . $path);
            }
        }
    }

    private static function purgeExpiredImages(string $directory): void {
        $files = scandir($directory);
        if ($files === false) {
            throw new RuntimeException('Campaign draft storage could not be inspected.');
        }

        foreach ($files as $file) {
            if (!preg_match('/^[a-f0-9]{32}-(\d{10})-[a-f0-9]{16}\.(jpg|png|gif|webp)$/', $file, $matches)) {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if ((int) $matches[1] < time() && is_file($path) && !unlink($path)) {
                error_log('[CampaignDraft] Unable to remove expired staged image: ' . $path);
            }
        }
    }

    private static function stageImage(array $upload, string $draftId, int $expiresAt): string {
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                throw new InvalidArgumentException('Image size must be 10MB or less.');
            }
            throw new InvalidArgumentException('Please choose a valid campaign image.');
        }

        $temporaryPath = (string) ($upload['tmp_name'] ?? '');
        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new InvalidArgumentException('The campaign image upload could not be verified.');
        }

        $size = filesize($temporaryPath);
        if ($size === false || $size < 1 || $size > self::MAX_IMAGE_SIZE) {
            throw new InvalidArgumentException('Image size must be 10MB or less.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Image validation is temporarily unavailable.');
        }
        $mimeType = finfo_file($finfo, $temporaryPath);
        finfo_close($finfo);

        if (!is_string($mimeType) || !isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new InvalidArgumentException('Please use a JPG, PNG, GIF, or WEBP image.');
        }

        $directory = self::ensureStorageDirectory();
        self::purgeExpiredImages($directory);
        $stagedPath = $directory . DIRECTORY_SEPARATOR . $draftId . '-' . $expiresAt . '-' . bin2hex(random_bytes(8)) . '.' . self::ALLOWED_MIME_TYPES[$mimeType];

        if (!move_uploaded_file($temporaryPath, $stagedPath)) {
            throw new RuntimeException('The campaign image could not be saved temporarily. Please try again.');
        }

        return $stagedPath;
    }

    public static function isStagedImagePath(string $path): bool {
        $storageDirectory = self::storageDirectory();
        if (is_link($storageDirectory)) {
            return false;
        }

        $directory = realpath($storageDirectory);
        $realPath = realpath($path);
        if ($directory === false || $realPath === false || !is_file($realPath)) {
            return false;
        }

        $parentDirectory = dirname($realPath);
        $sameDirectory = DIRECTORY_SEPARATOR === '\\'
            ? strcasecmp($parentDirectory, $directory) === 0
            : $parentDirectory === $directory;

        return $sameDirectory
            && preg_match('/^[a-f0-9]{32}-\d{10}-[a-f0-9]{16}\.(jpg|png|gif|webp)$/', basename($realPath)) === 1;
    }

    public static function get(): ?array {
        $directory = self::storageDirectory();
        if (is_link($directory)) {
            throw new RuntimeException('Campaign draft storage is temporarily unavailable.');
        }
        if (is_dir($directory)) {
            self::purgeExpiredImages($directory);
        }

        $draft = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($draft)) {
            return null;
        }

        $path = isset($draft['image_path']) && is_string($draft['image_path']) ? $draft['image_path'] : null;
        $valid = isset($draft['id'], $draft['expires_at'], $draft['data'])
            && is_string($draft['id'])
            && preg_match('/^[a-f0-9]{32}$/', $draft['id']) === 1
            && is_int($draft['expires_at'])
            && $draft['expires_at'] > time()
            && is_array($draft['data'])
            && $path !== null
            && self::isStagedImagePath($path);

        if (!$valid) {
            self::removeImage($path);
            unset($_SESSION[self::SESSION_KEY]);
            return null;
        }

        return $draft;
    }

    public static function save(array $data, array $upload): array {
        $id = bin2hex(random_bytes(16));
        $expiresAt = time() + self::LIFETIME_SECONDS;
        $imagePath = self::stageImage($upload, $id, $expiresAt);
        $previousDraft = $_SESSION[self::SESSION_KEY] ?? null;

        $_SESSION[self::SESSION_KEY] = [
            'id' => $id,
            'data' => $data,
            'image_path' => $imagePath,
            'expires_at' => $expiresAt,
        ];

        if (is_array($previousDraft) && isset($previousDraft['image_path']) && is_string($previousDraft['image_path'])) {
            self::removeImage($previousDraft['image_path']);
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function update(array $data, string $id, ?array $upload = null, bool $removeImage = false): array {
        $draft = self::get();
        if ($draft === null || !hash_equals($draft['id'], $id)) {
            throw new RuntimeException('Your saved fundraiser draft has expired. Please start again.');
        }

        $hasUpload = $upload !== null && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($removeImage && !$hasUpload) {
            throw new InvalidArgumentException('Please choose a campaign image before continuing.');
        }

        $imagePath = $draft['image_path'];
        if ($hasUpload) {
            $newImagePath = self::stageImage($upload, $draft['id'], $draft['expires_at']);
            self::removeImage($imagePath);
            $imagePath = $newImagePath;
        }

        $updatedDraft = [
            'id' => $draft['id'],
            'data' => $data,
            'image_path' => $imagePath,
            'expires_at' => $draft['expires_at'],
        ];
        $_SESSION[self::SESSION_KEY] = $updatedDraft;

        return $updatedDraft;
    }

    public static function remove(): void {
        $draft = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_array($draft) && isset($draft['image_path']) && is_string($draft['image_path'])) {
            self::removeImage($draft['image_path']);
        }
        unset($_SESSION[self::SESSION_KEY]);
    }
}
