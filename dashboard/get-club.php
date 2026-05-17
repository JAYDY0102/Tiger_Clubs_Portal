<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/club-utils.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$clubDir = $_GET['club'] ?? '';
if (!is_string($clubDir) || $clubDir === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing club']);
    exit;
}

$resolved = club_resolve_for_management($_SESSION['user'], $clubDir);
if (!$resolved['ok']) {
    http_response_code($resolved['status']);
    echo json_encode(['error' => $resolved['error']]);
    exit;
}

$drawerData = $resolved['drawer'];
$drawerData['image'] = file_exists(club_banner_path($clubDir)) ? '/' . $clubDir . '/main.png' : '';

echo json_encode(['success' => true, 'club' => $drawerData, 'dirName' => $clubDir]);
