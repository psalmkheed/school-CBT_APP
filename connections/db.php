<?php

require_once __DIR__ . '/functions.php';

date_default_timezone_set('Africa/Lagos');

// Build APP_URL dynamically so it works in both subdirectories and virtual host roots
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$app_root = str_replace('\\', '/', dirname(__DIR__));
// connections/db.php is in /connections, so __DIR__ is .../school_app/connections
$base_path = str_ireplace($doc_root, '', $app_root);
$base_path = '/' . ltrim($base_path, '/');
$base_path = rtrim($base_path, '/');
if ($base_path === '') {
    $base_path = '/';
} else {
    $base_path .= '/';
}

if (!defined('APP_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define("APP_URL", "$scheme://$host$base_path");
}
$base = APP_URL;


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
    $user = 'blaqdev';
    $pass = 'codingscience';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

    // Disable strict sql_mode for compatibility with MySQL 8.0+
    // This resolves ONLY_FULL_GROUP_BY and other strict mode errors
    $conn->exec("SET SESSION sql_mode = ''");

    // --- Auto-Seed: Create default admin and super if users table is empty ---
    $check_users = $conn->query("SELECT COUNT(*) FROM users");
    if ($check_users->fetchColumn() == 0) {
        $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $seed_stmt = $conn->prepare("
            INSERT INTO users (first_name, surname, user_id, password, role, class) 
            VALUES (:f, :l, :u, :p, :r, :c)
        ");
        // Seed Admin
        $seed_stmt->execute([
            ':f' => 'Super',
            ':l' => 'Admin',
            ':u' => 'admin',
            ':p' => $default_pass,
            ':r' => 'admin',
            ':c' => 'None'
        ]);

        // Seed Super User (Finance Manager)
        $super_pass = password_hash('super123', PASSWORD_DEFAULT);
        $seed_stmt->execute([
            ':f' => 'Finance',
            ':l' => 'Manager',
            ':u' => 'super',
            ':p' => $super_pass,
            ':r' => 'super',
            ':c' => 'None'
        ]);
    }

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
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
