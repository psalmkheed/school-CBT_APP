<?php
require 'connections/db.php';
try {
    $conn->exec('ALTER TABLE school_config ADD COLUMN groq_api_key VARCHAR(255) NULL');
    echo 'Added groq_api_key column successfully.';
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo 'Column already exists.';
    } else {
        echo 'Error: ' . $e->getMessage();
    }
}
