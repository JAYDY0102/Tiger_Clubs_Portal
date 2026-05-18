<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

// Helper: send JSON and exit
function jsonExit($data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['user'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        jsonExit(['error' => 'Not authenticated'], 401);
    }
    http_response_code(401);
    exit('Not authenticated');
}

$currentUser = $_SESSION['user'];
$currentUserRole = (string)($currentUser['role'] ?? '');
$currentUserEmail = (string)($currentUser['email'] ?? '');

// load DB
$pdo = require __DIR__ . '/../auth/db.php';

// Collect and normalize inputs
$rawEmails = $_POST['emails'] ?? ($_POST['email'] ?? '');
$role = $_POST['role'] ?? '';
$clubDir = isset($_POST['clubDir']) ? trim((string)$_POST['clubDir']) : '';
$clubAction = isset($_POST['clubAction']) ? trim((string)$_POST['clubAction']) : '';

// Validate role
$allowedRoles = ['student','advisor','executive','admin'];
if (!in_array($role, $allowedRoles, true)) {
    jsonExit(['error' => 'Invalid role'], 400);
}

// Parse multiple emails from newline/comma/space separated input
function parseEmails(string $s): array {
    $parts = preg_split('/[\r\n,;]+/', $s);
    $out = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        // allow only valid emails
        if (!filter_var($p, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        $out[] = strtolower($p);
    }
    // dedupe
    return array_values(array_unique($out));
}

$emails = parseEmails((string)$rawEmails);

if (empty($emails)) {
    jsonExit(['error' => 'No valid emails provided.'], 400);
}

// Optional club validation
$projectRoot = dirname(__DIR__);
$clubEdited = null;
$updatedExecs = null;

// If assigning the 'executive' role, require a club to be specified.
// This prevents creating a global executive role with no club association.
if ($role === 'executive' || $role === 'advisor') {
    if ($clubDir === '') {
        jsonExit(['error' => 'clubDir is required when assigning the executive or advisor role'], 400);
    }

    if (!preg_match('/^[a-z0-9\-_]+$/', $clubDir)) {
        jsonExit(['error' => 'Invalid club directory name'], 400);
    }

    $clubPath = $projectRoot . '/' . $clubDir;
    $drawerFile = $clubPath . '/drawer.json';
    if (!is_dir($clubPath) || !file_exists($drawerFile)) {
        jsonExit(['error' => 'Club not found: ' . $clubDir], 404);
    }

    if (!in_array($clubAction, ['', 'add', 'remove'], true)) {
        jsonExit(['error' => 'Invalid club action'], 400);
    }

    $clubEdited = $clubDir;
} else {
    $clubDir = '';
}

$isAdmin = $currentUserRole === 'admin';
$isAdvisorUser = $currentUserRole === 'advisor';
$isExecutiveUser = $currentUserRole === 'executive';

if (!$isAdmin) {
    if ($clubEdited !== null) {
        if ($isAdvisorUser) {
            if (!club_user_is_advisor_of($clubEdited, $currentUserEmail)) {
                jsonExit(['error' => 'Forbidden: you are not an advisor for this club'], 403);
            }
        } elseif ($isExecutiveUser) {
            if (!club_user_is_executive_of($clubEdited, $currentUserEmail)) {
                jsonExit(['error' => 'Forbidden: you are not an executive for this club'], 403);
            }
        } else {
            jsonExit(['error' => 'Forbidden'], 403);
        }
    } else {
        jsonExit(['error' => 'Forbidden'], 403);
    }
}

// Decide what DB role to write based on requested role and clubAction
// For 'executive' we only set DB role when adding executives (clubAction === 'add').
// For other roles, 'remove' means revoke -> set to 'student'; otherwise set to the requested role.
$dbRoleForUpdate = null;
if ($role === 'executive') {
    $dbRoleForUpdate = ($clubAction === 'add') ? 'executive' : null;
} else {
    $dbRoleForUpdate = ($clubAction === 'remove') ? 'student' : $role;
}

// Results per email
$results = [];

foreach ($emails as $email) {
    $row = ['email' => $email, 'created' => false, 'role_updated' => false, 'error' => null];

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Only update DB role if we computed a role to set for this operation
            if ($dbRoleForUpdate !== null && $user['role'] !== $dbRoleForUpdate) {
                $u = $pdo->prepare("UPDATE users SET role = ? WHERE email = ?");
                $u->execute([$dbRoleForUpdate, $email]);
                $row['role_updated'] = true;
            }
        } else {
            // Create a minimal user entry. If $dbRoleForUpdate is null, default to 'student'
            $name = explode('@', $email)[0];
            $createRole = $dbRoleForUpdate ?? 'student';
            $ins = $pdo->prepare("INSERT INTO users (name, email, google_id, role) VALUES (?, ?, ?, ?)");
            $ins->execute([$name, $email, null, $createRole]);
            $row['created'] = true;
            // consider this a role change if created with a non-student role
            if ($createRole !== 'student') {
                $row['role_updated'] = true;
            }
        }
    } catch (Exception $e) {
        $row['error'] = 'DB error: ' . $e->getMessage();
    }

    $results[] = $row;
}

// If club edit requested, update drawer.json
if ($clubEdited !== null && $clubAction !== '') {
    $drawerFile = $projectRoot . '/' . $clubEdited . '/drawer.json';
    $drawer = json_decode(file_get_contents($drawerFile), true);
    if (!is_array($drawer)) {
        jsonExit(['error' => 'Invalid drawer.json for club: ' . $clubEdited], 500);
    }
    $targetKey = ($role === 'advisor') ? 'advisorEmails' : 'executiveEmails';
    $existing = array_map('strtolower', $drawer[$targetKey] ?? []);

    if ($clubAction === 'add') {
        $beforeAdd = $existing;
        $merged = array_unique(array_merge($existing, $emails));
        $drawer[$targetKey] = array_values($merged);

        $addedToClub = array_diff($merged, $beforeAdd);
        if (!empty($addedToClub)) {
            foreach ($results as &$r) {
                if (in_array($r['email'], $addedToClub, true)) {
                    $r['club_added'] = $clubEdited;
                }
            }
            unset($r);
        }
    } elseif ($clubAction === 'remove') {
        $beforeRemove = $existing;
        $afterRemove = array_values(array_filter($existing, function($e) use ($emails) {
            return !in_array($e, $emails, true);
        }));
        $drawer[$targetKey] = $afterRemove;

        $removedFromClub = array_diff($beforeRemove, $afterRemove);
        if (!empty($removedFromClub)) {
            foreach ($results as &$r) {
                if (in_array($r['email'], $removedFromClub, true)) {
                    $r['club_removed'] = $clubEdited;
                }
            }
            unset($r);
        }
    }

    if (file_put_contents($drawerFile, json_encode($drawer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        jsonExit(['error' => 'Failed to write drawer.json for club: ' . $clubEdited], 500);
    }

    $updatedExecs = $drawer['executiveEmails'];
}

// --- Start: Demote users who are no longer executives in any club ---
// Build maps of executive and advisor membership
try {
    $clubsFile = $projectRoot . '/clubs.json';
    $allClubDirs = json_decode((string) file_get_contents($clubsFile), true);
    if (!is_array($allClubDirs)) $allClubDirs = [];

    $execMap = [];
    $advisorMap = [];

    foreach ($allClubDirs as $cdir) {
        $drawerPath = $projectRoot . '/' . $cdir . '/drawer.json';
        if (!file_exists($drawerPath)) continue;

        $d = json_decode((string) file_get_contents($drawerPath), true);
        if (!is_array($d)) continue;

        $execs = array_map('strtolower', $d['executiveEmails'] ?? []);
        $advisors = array_map('strtolower', $d['advisorEmails'] ?? []);

        foreach ($execs as $e) {
            $execMap[$e] = true;
        }
        foreach ($advisors as $a) {
            $advisorMap[$a] = true;
        }
    }

    foreach ($emails as $email) {
        $emailLower = strtolower($email);
        $stillExec = isset($execMap[$emailLower]);
        $stillAdvisor = isset($advisorMap[$emailLower]);

        if (!$stillExec) {
            $demoteStmt = $pdo->prepare("UPDATE users SET role = 'student' WHERE email = ? AND role = 'executive'");
            $demoteStmt->execute([$email]);
            if ($demoteStmt->rowCount() > 0) {
                foreach ($results as &$r) {
                    if (isset($r['email']) && strtolower($r['email']) === $emailLower) {
                        $r['role_updated'] = true;
                        $r['demoted'] = true;
                        break;
                    }
                }
                unset($r);
            }
        }

        if (!$stillAdvisor) {
            $demoteStmt = $pdo->prepare("UPDATE users SET role = 'student' WHERE email = ? AND role = 'advisor'");
            $demoteStmt->execute([$email]);
            if ($demoteStmt->rowCount() > 0) {
                foreach ($results as &$r) {
                    if (isset($r['email']) && strtolower($r['email']) === $emailLower) {
                        $r['role_updated'] = true;
                        $r['demoted'] = true;
                        break;
                    }
                }
                unset($r);
            }
        }
    }
} catch (Exception $e) {
    if (!empty($results)) {
        $results[0]['error'] = ($results[0]['error'] ? $results[0]['error'] . '; ' : '') . 'Demotion error: ' . $e->getMessage();
    }
}
// --- End: Demote users who are no longer executives in any club ---

// Determine if request is AJAX (fetch from front-end)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

if ($isAjax) {
    $response = ['success' => true, 'results' => $results];
    if ($clubEdited !== null) {
        // Read current drawer to return authoritative lists
        $drawer = json_decode((string) file_get_contents($projectRoot . '/' . $clubEdited . '/drawer.json'), true);
        $response['club'] = [
            'dirName' => $clubEdited,
            'executiveEmails' => $drawer['executiveEmails'] ?? [],
            'advisorEmails' => $drawer['advisorEmails'] ?? []
        ];
    }
    jsonExit($response, 200);
}

// Non-AJAX fallback: redirect back to dashboard
header('Location: dashboard.php');
exit;
