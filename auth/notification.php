<?php
session_start();
header('Content-Type: application/json');
require '../connections/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
      exit;
}

$id = $_POST['id'] ?? null;

if (!is_numeric($id)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
      exit;
}

$stmt = $conn->prepare("
    DELETE FROM broadcast 
    WHERE id = :id 
    AND recipient = :recipient
");

$stmt->execute([
      ':id' => (int) $id,
      ':recipient' => $_SESSION['username']
]);

if ($stmt->rowCount() > 0) {
      echo json_encode(['status' => 'success']);
} else {
      echo json_encode(['status' => 'error', 'message' => 'Notification not found']);
}
