<?php
require 'connections/db.php';
$stmt = $conn->query("SHOW COLUMNS FROM assignments");
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->query("SHOW COLUMNS FROM assignment_submissions");
$submissions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
print_r($assignments);
print_r($submissions);
