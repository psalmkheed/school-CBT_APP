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

$new_class = trim($_POST["class-name"] ?? '');

if (empty($new_class)) {
      echo json_encode([
            'status' => 'error',
            'message' => 'Input required'
      ]);
      exit;
}

$class_check = $conn->prepare("SELECT id FROM class WHERE class = :new_class");
$class_check->execute([
      ':new_class' => $new_class
]);

if ($class_check->fetch()) {
      echo json_encode(['status' => 'error', 'message' => $new_class . ' ' . 'class already created']);
      exit;
}

$stmt = $conn->prepare("INSERT INTO class (class) VALUES (:new_class)");

$stmt->execute([
      ':new_class' => $new_class
]);

echo json_encode([
      'status' => 'success',
      'message' => 'Class created successfully'
]);