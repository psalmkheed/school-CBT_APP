<?php
require 'connections/db.php';
$stmt1 = $conn->query("SHOW COLUMNS FROM finance_payments");
print_r($stmt1->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $conn->query("SHOW COLUMNS FROM finance_expenses");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
?>
