<?php
require 'connections/db.php';
header('Content-Type: text/plain');

echo "--- EXAMS FOR YEAR 5 ---\n";
$stmt = $conn->prepare("SELECT id, subject, class, session, term FROM exams WHERE class LIKE '%Year 5%'");
$stmt->execute();
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($exams);

echo "\n--- EXAM RESULTS FOR YEAR 5 STUDENTS ---\n";
$stmt = $conn->prepare("SELECT r.id, r.user_id, r.exam_id, e.subject, e.session, e.term, u.first_name, u.surname, u.class 
                        FROM exam_results r 
                        JOIN exams e ON r.exam_id = e.id 
                        JOIN users u ON r.user_id = u.id 
                        WHERE u.class LIKE '%Year 5%'");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($results);

echo "\n--- ACTIVE SESSION/TERM FROM SESSION ---\n";
session_start();
echo "Session: " . ($_SESSION['active_session'] ?? 'NONE') . "\n";
echo "Term: " . ($_SESSION['active_term'] ?? 'NONE') . "\n";
