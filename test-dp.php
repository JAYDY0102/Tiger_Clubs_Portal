
<?php
$host = 'localhost';
$dbname = 'club_portal';
$user = 'club_user';
$pass = 'club_password_123';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );
    echo "✓ Database connection successful!";

    $result = $pdo->query("SELECT COUNT(*) FROM users");
    echo "<br>✓ Users table exists, " . $result->fetchColumn() . " users in database.";
} catch (PDOException $e) {
    echo "✗ Failed: " . $e->getMessage();
}
?>
