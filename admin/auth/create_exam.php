<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';

$response = [
      "status" => "error",
      "message" => ""
];

if ($_SESSION['role'] !== 'admin') {
      echo json_encode([
            "status" => "error", 
            "message" => "Unauthorized"]);
      exit;
}

// Collect & trim inputs
$class = trim($_POST["class"] ?? '');
$subject = trim($_POST["subject"] ?? '');
$num_of_question = trim($_POST["num_of_question"] ?? '');
$time_allowed = trim($_POST["time_allowed"] ?? '');
$subject_teacher = $_POST["subject_teacher"] ?? '';
$due_date = $_POST["due_date"] ?? '';
$exam_type = $_POST["exam_type"] ?? '';
$paper_type = $_POST["paper_type"] ?? '';
$exam_id = uniqid();


// Validate required fields
if (
      empty($class) ||
      empty($subject) ||
      empty($num_of_question) ||
      empty($time_allowed) ||
      empty($subject_teacher) ||
      empty($due_date) ||
      empty($exam_type) ||
      empty($paper_type)
) {
      
      echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required'
      ]);
      exit;
}


// Check if exam already exists
$exam_check = $conn->prepare("SELECT id FROM exams WHERE subject = :subject && class = :class && exam_type = :exam_type && paper_type = :paper_type");
$exam_check->execute([
      ':subject' => $subject,
      ':class' => $class,
      ':exam_type' => $exam_type,
      ':paper_type' => $paper_type
]);

if ($exam_check->fetch()) {
      echo json_encode([
            'status' => 'error',
            'message' => "$subject already created for $class"
      ]);
      exit;
}

// Insert exam
$stmt = $conn->prepare("
INSERT INTO exams (exam_id, class, subject, num_quest, time_allowed, subject_teacher, session, term, due_date, exam_type, paper_type) 
VALUES (:exam_id, :class, :subject, :num_quest, :time_allowed, :subject_teacher, :session, :term, :due_date, :exam_type, :paper_type)
");

$stmt->execute([
      ':exam_id' => $exam_id,
      ':class' => $class,
      ':subject' => $subject,
      ':num_quest' => $num_of_question,
      ':time_allowed' => $time_allowed,
      ':subject_teacher' => $subject_teacher,
      ':session' => $_SESSION['active_session'] ?? '',
      ':term' => $_SESSION['active_term'] ?? '',
      ':due_date' => $due_date,
      ':exam_type' => $exam_type,
      ':paper_type' => $paper_type
]);

echo json_encode([
      'status' => 'success',
      'message' => 'Exam created successfully'
]);
exit;
