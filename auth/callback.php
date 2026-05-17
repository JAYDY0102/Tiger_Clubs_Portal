<?php
declare(strict_types=1);

session_start();

$config = require __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';

$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

if (!$code) {
    http_response_code(400);
    exit('Missing authorization code.');
}

if (!in_array($state, ['student', 'staff'], true)) {
    http_response_code(400);
    exit('Invalid login state.');
}

$tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'code' => $code,
            'client_id' => $config['google_client_id'],
            'client_secret' => $config['google_client_secret'],
            'redirect_uri' => $config['google_redirect_uri'],
            'grant_type' => 'authorization_code',
        ]),
    ]
]));

if ($tokenResponse === false) {
    http_response_code(500);
    exit('Failed to fetch Google token.');
}

$tokenData = json_decode($tokenResponse, true);
if (!isset($tokenData['access_token'])) {
    http_response_code(500);
    exit('Google token response invalid.');
}

$userResponse = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, stream_context_create([
    'http' => [
        'header' => "Authorization: Bearer " . $tokenData['access_token'] . "\r\n"
    ]
]));

if ($userResponse === false) {
    http_response_code(500);
    exit('Failed to fetch Google user info.');
}

$userData = json_decode($userResponse, true);

$email = $userData['email'] ?? '';
$name = $userData['name'] ?? '';
$googleId = $userData['id'] ?? '';

if (!$email || !$name || !$googleId) {
    http_response_code(400);
    exit('Incomplete Google profile.');
}

$isStudent = str_ends_with($email, '@' . $config['student_domain']);
$isStaff = str_ends_with($email, '@' . $config['staff_domain']);

if ($state === 'student' && !$isStudent) {
    exit('Student accounts must use a stu.siskorea.org email.');
}

if ($state === 'staff' && !$isStaff) {
    exit('Staff accounts must use a siskorea.org email.');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $update = $pdo->prepare("UPDATE users SET name = ?, google_id = ? WHERE email = ?");
    $update->execute([$name, $googleId, $email]);
} else {
    $insert = $pdo->prepare("
        INSERT INTO users (name, email, google_id, role)
        VALUES (?, ?, ?, ?)
    ");
    $insert->execute([$name, $email, $googleId, $state === 'staff' ? 'teacher' : 'student']);
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

$_SESSION['user'] = $user;

header('Location: ../index.php');
exit;
