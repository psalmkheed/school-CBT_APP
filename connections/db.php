<?php

require_once __DIR__ . '/functions.php';

date_default_timezone_set('Africa/Lagos');

try{
$host = 'localhost';
 $dbname = 'edu_app';
 $user = 'root';
 $pass = '';

 $conn = new PDO("mysql: host=$host; dbname=$dbname", $user, $pass);

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Professional Auto-Seed Mechanism ---
// Automatically creates a default admin if the users table is empty
// -------------------------------------------------------------
$check_users = $conn->query("SELECT COUNT(*) FROM users");
if ($check_users->fetchColumn() == 0) {
    $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $seed_stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, user_id, password, role, class) 
        VALUES (:f, :l, :u, :p, :r, :c)
    ");
    $seed_stmt->execute([
        ':f' => 'Default',
        ':l' => 'Admin',
        ':u' => 'admin',
        ':p' => $default_pass,
        ':r' => 'admin',
        ':c' => 'None'
    ]);
}
// -------------------------------------------------------------

} catch(PDOException $e){

die("Database connection failed: " . $e->getMessage());

}

// deleting notification if read and older than 1 day

$cleanup_interval = 14400; 

if (!isset($_SESSION['last_cleanup_time']) || (time() - $_SESSION['last_cleanup_time']) > $cleanup_interval) {
      try {
            $clean_stmt = $conn->prepare("
            DELETE FROM broadcast 
            WHERE is_read = 1 
            AND created_at < NOW() - INTERVAL 1 DAY
        ");
            $clean_stmt->execute();

            $_SESSION['last_cleanup_time'] = time();

      } catch (PDOException $e) {
            error_log("Background Cleanup Error: " . $e->getMessage());
      }
}

?>

