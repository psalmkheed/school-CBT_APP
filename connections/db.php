<?php

require_once __DIR__ . '/functions.php';

date_default_timezone_set('Africa/Lagos');

if (!ob_get_level()) {
    ob_start();
}

// Start session safely (only if one isn't already active)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $host = 'localhost';
    $dbname = 'edu_app';
    $user = 'root';
    $pass = '';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    // --- Auto-Seed: Create default admin if users table is empty ---
    $check_users = $conn->query("SELECT COUNT(*) FROM users");
    if ($check_users->fetchColumn() == 0) {
        $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $seed_stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, user_id, password, role, class) 
            VALUES (:f, :l, :u, :p, :r, :c)
        ");
        $seed_stmt->execute([
            ':f' => 'Super',
            ':l' => 'Admin',
            ':u' => 'admin',
            ':p' => $default_pass,
            ':r' => 'admin',
            ':c' => 'None'
        ]);
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ── Broadcast Cleanup (runs once per day per session) ─────────────────────
// ✅ Fixed: 86400 = 1 full day in seconds (was 14400 = 4 hours)
$cleanup_interval = 86400;

if (!isset($_SESSION['last_cleanup_time']) || (time() - $_SESSION['last_cleanup_time']) > $cleanup_interval) {
    try {
        // Delete READ messages older than 1 day
        $conn->prepare("
            DELETE FROM broadcast 
            WHERE is_read = 1 
            AND created_at < NOW() - INTERVAL 1 DAY
        ")->execute();

        // Also delete UNREAD messages older than 30 days (prevent DB bloat)
        $conn->prepare("
            DELETE FROM broadcast 
            WHERE is_read = 0 
            AND created_at < NOW() - INTERVAL 30 DAY
        ")->execute();

        $_SESSION['last_cleanup_time'] = time();

    } catch (PDOException $e) {
        error_log("Background Cleanup Error: " . $e->getMessage());
    }
}

