<?php
declare(strict_types=1);

function club_project_root(): string
{
    return dirname(__DIR__);
}

function club_is_valid_slug(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9\-_]+$/', $slug);
}

function club_path(string $slug): string
{
    return club_project_root() . '/' . $slug;
}

function club_drawer_path(string $slug): string
{
    return club_path($slug) . '/drawer.json';
}

function club_banner_path(string $slug): string
{
    return club_path($slug) . '/main.png';
}

function club_load_drawer(string $slug): array|false
{
    $drawerFile = club_drawer_path($slug);

    if (!is_dir(club_path($slug)) || !file_exists($drawerFile)) {
        return false;
    }

    $data = json_decode((string) file_get_contents($drawerFile), true);
    return is_array($data) ? $data : false;
}

function club_user_is_executive_of(string $slug, string $email): bool
{
    $drawer = club_load_drawer($slug);
    if ($drawer === false) {
        return false;
    }

    $execs = array_map('strtolower', $drawer['executiveEmails'] ?? []);
    return in_array(strtolower($email), $execs, true);
}

function club_resolve_for_management(array $user, string $clubDir): array
{
    $isAdmin = ($user['role'] ?? '') === 'admin';
    $isExecutive = ($user['role'] ?? '') === 'executive';

    if (!club_is_valid_slug($clubDir)) {
        return ['ok' => false, 'status' => 400, 'error' => 'Invalid club name'];
    }

    $drawerData = club_load_drawer($clubDir);
    if ($drawerData === false) {
        return ['ok' => false, 'status' => 404, 'error' => 'Club not found'];
    }

    if (!$isAdmin) {
        if (!$isExecutive || !club_user_is_executive_of($clubDir, (string)($user['email'] ?? ''))) {
            return ['ok' => false, 'status' => 403, 'error' => 'Forbidden'];
        }
    }

    return ['ok' => true, 'clubDir' => $clubDir, 'drawer' => $drawerData];
}
