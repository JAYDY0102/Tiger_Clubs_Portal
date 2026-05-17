<?php
declare(strict_types=1);
session_start();
$isLoggedIn = isset($_SESSION['user']);
if ($isLoggedIn) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Required - Tiger Clubs Portal</title>
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
        <h1>Login Required</h1>
        <p>You must be logged in to access this feature.</p>
        <p style="font-size: 14px; color: var(--muted); margin-top: 12px;">Please sign in with your SIS account to continue.</p>

        <div style="margin-top: 24px;">
            <a class="btn btn-primary" href="login.php" style="width: 100%; text-align: center; display: inline-block;">
                Sign In
            </a>
        </div>

        <p style="margin-top: 16px; text-align: center;">
            <a href="../index.php" style="color: var(--accent); text-decoration: none;">Back to Home</a>
        </p>
    </div>
</main>
</body>
</html>
