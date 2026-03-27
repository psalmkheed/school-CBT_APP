<?php
require 'connections/db.php';
try {
    $conn->exec('ALTER TABLE class ADD COLUMN next_class_id INT NULL DEFAULT NULL');
    echo "Added successfully";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
