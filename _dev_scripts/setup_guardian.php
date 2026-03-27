<?php
require 'connections/db.php';

try {
    $conn->exec("CREATE TABLE IF NOT EXISTS guardian_wards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guardian_id INT NOT NULL,
        student_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (guardian_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Guardian Wards table checked/created successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
