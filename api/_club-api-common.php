<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/club-utils.php';

function api_json_headers(): void
{
    header('Content-Type: application/json');
}

function api_json_error(string $message, int $status): never
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

/**
 * Ensures a logged-in admin/executive and resolves managed club drawer.
 * Returns [user, clubDir, resolved].
 */
function api_require_managed_club(): array
{
    if (!isset($_SESSION['user'])) {
        api_json_error('Not logged in', 401);
    }

    $user = $_SESSION['user'];
    $isAdmin = ($user['role'] ?? '') === 'admin';
    $isExecutive = ($user['role'] ?? '') === 'executive';
    $isAdvisor = ($user['role'] ?? '') === 'advisor';

    if (!$isAdmin && !$isExecutive && !$isAdvisor) {
        api_json_error('Permission denied', 403);
    }

    $clubDir = $_POST['clubDir'] ?? '';
    if (!is_string($clubDir) || $clubDir === '') {
        api_json_error('Invalid club directory', 400);
    }

    $resolved = club_resolve_for_management($user, $clubDir);
    if (!$resolved['ok']) {
        api_json_error($resolved['error'], $resolved['status']);
    }

    return [$user, $clubDir, $resolved];
}
