<?php
require 'connections/db.php';
$stmt = $conn->query('DESCRIBE exams');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $conn->query('DESCRIBE questions');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
