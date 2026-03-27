<?php
require 'connections/db.php';
header('Content-Type: text/plain');
echo "--- CLASSES ---\n";
print_r($conn->query("SELECT class FROM class ORDER BY class ASC")->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- SESSIONS & TERMS ---\n";
print_r($conn->query("SELECT session, term FROM sch_session")->fetchAll(PDO::FETCH_ASSOC));
