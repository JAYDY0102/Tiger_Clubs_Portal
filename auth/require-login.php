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
        <span><a href="../index.php">Tiger Clubs Portal</a></span>
    </div>
    <nav class="nav">
        <a href="../index.php" class="inactive">Home</a>
        <a href="#" class="inactive">Feed</a>
        <a href="#" class="inactive">Calendar</a>
        <a href="#" class="inactive">Dashboard</a>
    </nav>
</header>

<main class="auth-page">
    <div class="auth-card">
        <h1>Login Required</h1>
        <p>You must be logged in to access this feature.</p>
        <p style="font-size: 14px; color: var(--muted); margin-top: 12px;">Please sign in with your SIS account to continue.</p>
        <div class="auth-choice">
            <a class="btn btn-primary" href="login.php">
                Sign In
            </a>
            <a class="btn btn-primary" href="../index.php">
                Back to Home
            </a>
        </div>
    </div>
</main>
</body>
</html>
