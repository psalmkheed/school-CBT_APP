<?php
require 'connections/db.php';
$stmt = $conn->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
