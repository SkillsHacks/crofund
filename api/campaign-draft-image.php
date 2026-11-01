<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/CampaignDraft.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit();
}

$draft = CampaignDraft::get();
if ($draft === null || !CampaignDraft::isStagedImagePath($draft['image_path'])) {
    http_response_code(404);
    exit();
}

$imagePath = realpath($draft['image_path']);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if ($imagePath === false || $finfo === false) {
    http_response_code(500);
    exit();
}

$mimeType = finfo_file($finfo, $imagePath);
finfo_close($finfo);
$imageSize = filesize($imagePath);

if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
    http_response_code(404);
    exit();
}

if ($imageSize === false || !is_readable($imagePath)) {
    error_log('[campaign-draft-image] Saved campaign image cannot be read.');
    http_response_code(500);
    exit();
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $imageSize);
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($imagePath);

