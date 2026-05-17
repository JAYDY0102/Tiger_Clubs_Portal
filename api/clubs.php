<?php
declare(strict_types=1);

header('Content-Type: application/json');

$rootDir = __DIR__ . '/..';
$clubsListFile = $rootDir . '/clubs.json';

if (!file_exists($clubsListFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'clubs.json not found']);
    exit;
}

$clubDirs = json_decode(file_get_contents($clubsListFile), true);

if (!is_array($clubDirs)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid clubs.json format']);
    exit;
}

$clubs = [];

foreach ($clubDirs as $dirName) {
    $clubPath = $rootDir . '/' . $dirName;
    $drawerFile = $clubPath . '/drawer.json';
    $bannerFile = $clubPath . '/main.png';

    if (!is_dir($clubPath) || !file_exists($drawerFile)) {
        continue;
    }

    $drawerData = json_decode(file_get_contents($drawerFile), true);

    if (!is_array($drawerData)) {
        continue;
    }

    $drawerData['image'] = file_exists($bannerFile)
        ? '/' . $dirName . '/main.png'
        : 'https://via.placeholder.com/400x300?text=' . urlencode($drawerData['name'] ?? 'Club');

    $drawerData['dirName'] = $dirName;

    $clubs[] = $drawerData;
}

echo json_encode($clubs);
