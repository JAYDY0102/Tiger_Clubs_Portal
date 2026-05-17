<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/_club-api-common.php';
api_json_headers();

[, $clubDir] = api_require_managed_club();

$file = $_FILES['banner'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    api_json_error('No file uploaded or upload error', 400);
}

$allowedMimes = ['image/png', 'image/jpeg', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes, true)) {
    api_json_error('Invalid file type. Allowed: PNG, JPG, WebP', 400);
}

if (move_uploaded_file($file['tmp_name'], club_banner_path($clubDir))) {
    $cacheDir = club_project_root() . '/cache/images';
    if (is_dir($cacheDir)) {
        foreach (glob($cacheDir . '/*') ?: [] as $cachedFile) {
            if (is_file($cachedFile)) {
                unlink($cachedFile);
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Banner uploaded successfully']);
    exit;
}

api_json_error('Failed to save banner', 500);
