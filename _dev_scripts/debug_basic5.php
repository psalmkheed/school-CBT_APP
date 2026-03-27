<?php
require 'connections/db.php';
header('Content-Type: text/plain');

echo "--- EXAMS FOR Basic 5 ---\n";
$stmt = $conn->prepare("SELECT id, subject, class, session, term FROM exams WHERE class = 'Basic 5'");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n--- RESULTS FOR Basic 5 ---\n";
$stmt = $conn->prepare("SELECT r.id, e.session, e.term, u.first_name, u.class 
                        FROM exam_results r 
                        JOIN exams e ON r.exam_id = e.id 
                        JOIN users u ON r.user_id = u.id 
                        WHERE u.class = 'Basic 5'");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
