<?php
declare(strict_types=1);

header('Cache-Control: public, max-age=86400');

$path = $_GET['path'] ?? '';
$width = (int)($_GET['w'] ?? 400);
$height = (int)($_GET['h'] ?? 300);

// Security: prevent path traversal
if (strpos($path, '..') !== false || strpos($path, '//') !== false) {
    http_response_code(400);
    exit('Invalid path');
}

$projectRoot = dirname(__DIR__);
$filePath = $projectRoot . '/' . $path;

// Normalize and check if file exists
$filePath = realpath($filePath);
if ($filePath === false || !file_exists($filePath)) {
    http_response_code(404);
    exit('File not found');
}

// Only allow image files
$allowed = ['image/png', 'image/jpeg', 'image/webp'];
$mimeType = mime_content_type($filePath);
if (!in_array($mimeType, $allowed)) {
    http_response_code(403);
    exit('Invalid file type');
}

// Cache directory for resized images
$cacheDir = $projectRoot . '/cache/images';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

// Generate cache filename
$cacheKey = md5($path . $width . $height);
$cacheFile = $cacheDir . '/' . $cacheKey . '.webp';

// Serve from cache if exists and fresh
if (file_exists($cacheFile)) {
    header('Content-Type: image/webp');
    readfile($cacheFile);
    exit;
}

// If GD is available, resize and cache
if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
    $imageData = file_get_contents($filePath);
    $image = imagecreatefromstring($imageData);

    if ($image === false) {
        http_response_code(500);
        exit('Could not process image');
    }

    // Calculate aspect ratio - for 2:1 (width:height), be more lenient
    $origWidth = imagesx($image);
    $origHeight = imagesy($image);
    $origRatio = $origWidth / $origHeight;
    $targetRatio = $width / $height; // 2:1 = 2.0

// Fit image into 2:1 without aggressive cropping
// Letterbox if original is narrower than 2:1
    if ($origRatio < $targetRatio) {
        // Original is too narrow - reduce height, keep width
        $newWidth = $origWidth;
        $newHeight = intval($origWidth / $targetRatio);
        $x = 0;
        $y = intval(($origHeight - $newHeight) / 2);
    } else {
        // Original is wider or fits - keep as is
        $newWidth = intval($origHeight * $targetRatio);
        $newHeight = $origHeight;
        $x = intval(($origWidth - $newWidth) / 2);
        $y = 0;
    }

    $cropped = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $newWidth, 'height' => $newHeight]);
    imagedestroy($image);

    $resized = imagescale($cropped, $width, $height);
    imagedestroy($cropped);

    header('Content-Type: image/webp');
    imagewebp($resized, $cacheFile, 80);
    readfile($cacheFile);
    imagedestroy($resized);
    exit;
}

// Fallback: serve original image
header('Content-Type: ' . $mimeType);
readfile($filePath);
