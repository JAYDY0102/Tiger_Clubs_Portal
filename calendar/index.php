<?php
// SPDX-License-Identifier: MIT
// Copyright (c) 2026 Hyunjun Oh
declare(strict_types=1);

session_start();
$isLoggedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tiger Clubs Portal</title>
    <link rel="stylesheet" href="../styles.css" />
    <script>
        const isLoggedIn = <?= json_encode($isLoggedIn) ?>;
    </script>
</head>
<body>
<header class="topbar">
    <div class="brand">
        <div class="brand-mark">S</div>
        <span><a href="../index.php">Tiger Clubs Portal</a></span>
    </div>

    <nav class="nav">
        <a href="../index.php" class="inactive">Home</a>
        <a href="../feed" class="inactive">Feed</a>
        <a href="index.php" class="active">Calendar</a>
        <?php if ($isLoggedIn && $user['role'] === 'admin'): ?>
            <a href="../dashboard/dashboard.php" class="inactive">Admin Dashboard</a>
        <?php elseif ($isLoggedIn && $user['role'] === 'executive' ): ?>
            <a href="../dashboard/executive.php" class="inactive">Executive Dashboard</a>
        <?php elseif ($isLoggedIn && $user['role'] === 'advisor'): ?>
            <a href="../dashboard/advisor.php" class="inactive">Advisor Dashboard</a>
        <?php else: ?>
            <a href="#">Dashboard</a>
        <?php endif; ?>
    </nav>

    <div class="auth">
        <?php if ($isLoggedIn): ?>
            <a class="btn btn-ghost" >
                <?= htmlspecialchars($user['name']) ?>
            </a>
            <a class="btn btn-ghost" href="../auth/logout.php">Logout</a>
        <?php else: ?>
            <a class="btn btn-ghost" href="../auth/login.php">Log In</a>
            <a class="btn btn-primary" href="../auth/login.php">Sign Up</a>
        <?php endif; ?>
    </div>
</header>