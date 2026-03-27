<?php
require 'connections/db.php';
header('Content-Type: text/plain');

echo "--- ALL EXAMS ---\n";
$stmt = $conn->query("SELECT id, subject, class, session, term FROM exams");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($exams);

echo "\n--- ALL EXAM RESULTS ---\n";
$stmt = $conn->query("SELECT r.id, r.user_id, r.exam_id, e.subject, e.session, e.term, u.first_name, u.surname, u.class 
                      FROM exam_results r 
                      JOIN exams e ON r.exam_id = e.id 
                      JOIN users u ON r.user_id = u.id");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($results);

echo "\n--- USER CLASSES IN SYSTEM ---\n";
$stmt = $conn->query("SELECT DISTINCT class FROM users WHERE role = 'student'");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($classes);
