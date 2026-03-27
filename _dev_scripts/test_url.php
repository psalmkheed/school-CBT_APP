<?php
require 'connections/db.php';
$stmt = $conn->query("SELECT school_logo, signature FROM school_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$path = $config['school_logo'];
if(strpos($path, 'http') !== 0) {
    if(strpos($path, 'uploads/') !== 0) {
        $path = 'uploads/' . ltrim($path, '/');
    }
    $logo_src = APP_URL . $path;
} else {
    $logo_src = $path;
}

echo "APP_URL is: " . APP_URL . "\n";
echo "DB logo: " . $config['school_logo'] . "\n";
echo "Resolved logo src: " . $logo_src . "\n";
?>
