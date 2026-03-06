<?php
require 'connections/db.php';
try {
    $conn->exec("ALTER TABLE class_messages ADD COLUMN attachment VARCHAR(255) DEFAULT NULL, ADD COLUMN attachment_type VARCHAR(50) DEFAULT NULL");
    echo "Columns added successfully";
} catch (PDOException $e) {
    echo "Error adding columns: " . $e->getMessage();
}
