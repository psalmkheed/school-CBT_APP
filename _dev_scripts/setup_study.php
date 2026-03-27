<?php
require 'connections/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS edu_app.study_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Other Download', -- 'Upload Content', 'Assignment', 'Syllabus', 'Other Download'
    target_class VARCHAR(100) NULL, -- Can be 'All' or specific class
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL, -- e.g. pdf, jpg, mp4
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

try {
    $conn->exec($sql);
    echo "Study materials table created successfully.\n";
} catch(PDOException $e) {
    echo "Creation error: " . $e->getMessage() . "\n";
}
?>
