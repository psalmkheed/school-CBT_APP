<?php
require 'connections/db.php';
$stmt1 = $conn->query("SHOW COLUMNS FROM finance_student_fees");
print_r($stmt1->fetchAll(PDO::FETCH_ASSOC));
?>
