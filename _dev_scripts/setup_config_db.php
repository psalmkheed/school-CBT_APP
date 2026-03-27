<?php
require 'connections/db.php';

// Check if school_config exists, or create it if not
$conn->exec("
CREATE TABLE IF NOT EXISTS school_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) DEFAULT 'Zenith High School',
    school_tagline VARCHAR(255),
    school_logo VARCHAR(255),
    school_primary VARCHAR(50) DEFAULT '#2563eb',
    school_secondary VARCHAR(50) DEFAULT '#3b82f6',
    school_address VARCHAR(255),
    school_phone_number VARCHAR(50),
    school_email VARCHAR(100),
    signature VARCHAR(255),
    academic_session VARCHAR(50) DEFAULT '2025/2026',
    active_term VARCHAR(20) DEFAULT '1st Term',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
");

// In case the table exists already and is missing columns, gracefully add them
$cols_to_add = [
    'school_tagline' => 'VARCHAR(255)',
    'school_logo' => 'VARCHAR(255)',
    'school_primary' => "VARCHAR(50) DEFAULT '#2563eb'",
    'school_secondary' => "VARCHAR(50) DEFAULT '#3b82f6'",
    'school_address' => 'VARCHAR(255)',
    'school_phone_number' => 'VARCHAR(50)',
    'school_email' => 'VARCHAR(100)',
    'signature' => 'VARCHAR(255)',
    'academic_session' => "VARCHAR(50) DEFAULT '2025/2026'",
    'active_term' => "VARCHAR(20) DEFAULT '1st Term'"
];

foreach ($cols_to_add as $col => $type) {
    try {
        $conn->exec("ALTER TABLE school_config ADD COLUMN $col $type");
    } catch(Exception $e) {
        // Column likely exists
    }
}

// Ensure there is at least one row
$count = $conn->query("SELECT COUNT(*) FROM school_config")->fetchColumn();
if ($count == 0) {
    $conn->exec("INSERT INTO school_config (school_name) VALUES ('Zenith High School')");
}

$stmt = $conn->query("SHOW COLUMNS FROM school_config");
echo implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));
echo "\nDatabase schema configured successfully!";
?>
