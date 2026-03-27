<?php
require 'connections/db.php';
header('Content-Type: text/plain');
echo "--- SESSIONS & TERMS ---\n";
print_r($conn->query("SELECT session, term FROM sch_session")->fetchAll(PDO::FETCH_ASSOC));
