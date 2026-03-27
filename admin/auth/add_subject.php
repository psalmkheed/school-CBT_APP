<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
      echo json_encode(["status" => "error", "message" => "Unauthorized"]);
      exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode([
            'status' => 'error',
            'message' => 'Invalid request'
      ]);

      exit;
}

$subject_name = trim($_POST["subject"] ?? '');

if (empty($subject_name)) {
      echo json_encode([
            'status' => 'error',
            'message' => 'Enter subject name'
      ]);
      exit;
}

$subject_check = $conn->prepare("SELECT id FROM subjects WHERE subject = :subject");
$subject_check->execute([
      ':subject' => $subject_name
]);

if ($subject_check->fetch()) {
      echo json_encode(['status' => 'error', 'message' => $subject_name . ' already created']);
      exit;
}

$stmt = $conn->prepare("INSERT INTO subjects (subject) VALUES (:subject)");

$stmt->execute([
      ':subject' => $subject_name
]);

echo json_encode([
      'status' => 'success',
      'message' => $subject_name . ' added to Subject List'
]);