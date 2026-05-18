<?php
declare(strict_types=1);

session_start();
$pdo = require __DIR__ . '/../auth/db.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';

    $allowedRoles = ['student', 'advisor', 'executive', 'admin'];

    if ($email && in_array($role, $allowedRoles, true)) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE email = ?");
        $stmt->execute([$role, $email]);

        if ($stmt->rowCount() > 0) {
            $message = "Role updated successfully for $email";
            $users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
        } else {
            $message = "User not found: $email";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Tiger Clubs Portal</title>
    <link rel="stylesheet" href="../styles.css" />
</head>
<body>
<header class="topbar">
    <div class="brand">
        <div class="brand-mark">S</div>
        <span>Admin Dashboard</span>
    </div>
    <nav class="nav">
        <a href="../index.php">Home</a>
        <a href="../auth/logout.php">Logout</a>
    </nav>
</header>

<main class="admin-page">
    <section class="admin-panel">
        <h1>Assign Roles</h1>

        <?php if ($message): ?>
            <p style="color: var(--accent); margin-bottom: 16px;"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="post" class="admin-form">
            <label>
                Email
                <input type="email" name="email" required />
            </label>

            <label>
                Role
                <select name="role" required>
                    <option value="">-- Select a role --</option>
                    <option value="student">student</option>
                    <option value="advisor">advisor</option>
                    <option value="executive">executive</option>
                    <option value="admin">admin</option>
                </select>
            </label>

            <button class="btn btn-primary" type="submit">Update Role</button>
        </form>
    </section>

    <section class="admin-panel">
        <h2>Registered Users</h2>
        <div class="admin-list">
            <?php if ($users): ?>
                <?php foreach ($users as $user): ?>
                    <div class="admin-user">
                        <strong><?= htmlspecialchars($user['name']) ?></strong>
                        <div><?= htmlspecialchars($user['email']) ?></div>
                        <small><?= htmlspecialchars($user['role']) ?> • <?= htmlspecialchars($user['created_at']) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--muted);">No users registered yet.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
