<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

session_start();

function buildGoogleAuthUrl(string $clientId, string $redirectUri, string $state): string {
    $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $state,
    ]);

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
}

$studentUrl = buildGoogleAuthUrl(
        $config['google_client_id'],
        $config['google_redirect_uri'],
        'student'
);

$staffUrl = buildGoogleAuthUrl(
        $config['google_client_id'],
        $config['google_redirect_uri'],
        'staff'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Tiger Clubs Portal</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
<header class="topbar">
    <div class="brand">
        <div class="brand-mark">S</div>
        <span>Tiger Clubs Portal</span>
    </div>
    <nav class="nav">
        <a href="../index.php">Home</a>
    </nav>
</header>

<main class="auth-page">
    <div class="auth-card">
        <h1>Sign in with Google</h1>
        <p>Please choose the account type you are signing in as.</p>

        <div class="auth-choice">
            <a class="btn btn-primary" href="<?= htmlspecialchars($studentUrl, ENT_QUOTES) ?>">
                Student Sign In
            </a>
            <a class="btn btn-ghost" href="<?= htmlspecialchars($staffUrl, ENT_QUOTES) ?>">
                Staff Sign In
            </a>
        </div>

        <p class="auth-note">
            Students must use <strong>@stu.siskorea.org</strong><br />
            Staff must use <strong>@siskorea.org</strong>
        </p>
    </div>
</main>
</body>
</html>
