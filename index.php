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
    <link rel="stylesheet" href="styles.css" />
    <script>
        const isLoggedIn = <?= json_encode($isLoggedIn) ?>;
    </script>
</head>
<body>
<header class="topbar">
    <div class="brand">
        <div class="brand-mark">S</div>
        <span><a href="index.php">Tiger Clubs Portal</a></span>
    </div>

    <nav class="nav">
        <a href="index.php" class="active">Home</a>
        <a href="feed" class="inactive">Feed</a>
        <a href="calendar" class="inactive">Calendar</a>
        <?php if ($isLoggedIn && $user['role'] === 'admin'): ?>
            <a href="dashboard/dashboard.php" class="inactive">Admin Dashboard</a>
        <?php elseif ($isLoggedIn && $user['role'] === 'executive' ): ?>
            <a href="dashboard/executive.php" class="inactive">Executive Dashboard</a>
        <?php elseif ($isLoggedIn && $user['role'] === 'advisor'): ?>
            <a href="dashboard/advisor.php" class="inactive">Advisor Dashboard</a>
        <?php else: ?>
            <a href="#" class="inactive">Dashboard</a>
        <?php endif; ?>
    </nav>

    <div class="auth">
        <?php if ($isLoggedIn): ?>
            <a class="btn btn-ghost" >
                <?= htmlspecialchars($user['name']) ?>
            </a>
            <a class="btn btn-ghost" href="auth/logout.php">Logout</a>
        <?php else: ?>
            <a class="btn btn-ghost" href="auth/login.php">Log In</a>
            <a class="btn btn-primary" href="auth/login.php">Sign Up</a>
        <?php endif; ?>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Discover Clubs <span>at SIS</span></h1>
            <p>Find your passion, make lasting memories, and develop new skills through our diverse range of student clubs.</p>

            <div class="search-wrap">
                <label for="searchInput" class="sr-only">Search clubs</label>
                <input id="searchInput" type="text" placeholder="Search clubs by name or interest..." />
            </div>
        </div>
    </section>

    <section class="content">
        <div class="section-head">
            <div>
                <h2>Discover Clubs</h2>
                <p>Browse clubs and click a card to see more details.</p>
            </div>
        </div>

        <div class="filters">
            <div class="filter-row" id="typeFilters">
                <button class="chip active" data-filter="all">All</button>
                <button class="chip" data-filter="STEM">STEM</button>
                <button class="chip" data-filter="Academic">Academic</button>
                <button class="chip" data-filter="Arts & Culture">Arts & Culture</button>
                <button class="chip" data-filter="Community Service">Community Service</button>
                <button class="chip" data-filter="Journalism">Journalism</button>
                <button class="chip" data-filter="Sports">Sports</button>
            </div>

            <div class="filter-row" id="dayFilters">
                <button class="chip active" data-filter="all">All Days</button>
                <button class="chip" data-filter="Monday">Monday</button>
                <button class="chip" data-filter="Wednesday">Wednesday</button>
                <button class="chip" data-filter="Thursday (A)">Thursday (A)</button>
                <button class="chip" data-filter="Thursday (B)">Thursday (B)</button>
                <button class="chip" data-filter="Friday">Friday</button>
                <button class="chip" data-filter="Other">Other</button>
            </div>
        </div>

        <div class="layout">
            <section class="grid" id="clubGrid"></section>

            <aside class="drawer" id="drawer">
                <button class="drawer-close" id="closeDrawer">×</button>
                <div id="drawerContent" class="drawer-content">
                    <div class="drawer-empty">
                        <h3>Select a club</h3>
                        <p>Click a club card to view its details here.</p>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</main>

<template id="clubCardTemplate">
    <article class="club-card">
        <div class="club-banner">
            <img class="club-image" alt="" />
            <div class="club-tags">
                <span class="tag type-tag"></span>
                <span class="tag day-tag"></span>
            </div>
        </div>
        <div class="club-body">
            <h3 class="club-name"></h3>
            <div class="club-meta">
                <span class="club-type"></span>
                <span class="club-members"></span>
            </div>
            <p class="club-summary"></p>
        </div>
    </article>
</template>

<script src="script.js?v=<?= htmlspecialchars((string)time(), ENT_QUOTES, 'UTF-8') ?>"></script>
</html>