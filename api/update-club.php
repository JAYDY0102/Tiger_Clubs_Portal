<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/_club-api-common.php';
api_json_headers();

[, $clubDir, $resolved] = api_require_managed_club();

$data = $_POST['data'] ?? null;
if (!is_string($data)) {
    api_json_error('No data provided', 400);
}

$updateData = json_decode($data, true);
if (!is_array($updateData)) {
    api_json_error('Invalid JSON data', 400);
}

$drawerData = $resolved['drawer'];

$allowedFields = [
    'name',
    'type',
    'day',
    'members',
    'summary',
    'about',
    'advisor',
    'contactEmail',
    'instagram',
    'website',
    'meeting'
];

foreach ($allowedFields as $field) {
    if (array_key_exists($field, $updateData)) {
        $drawerData[$field] = $updateData[$field];
    }
}

$json = json_encode($drawerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false || file_put_contents(club_drawer_path($clubDir), $json) === false) {
    api_json_error('Failed to save changes', 500);
}

echo json_encode(['success' => true, 'message' => 'Club updated successfully']);
