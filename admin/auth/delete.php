<?php
require __DIR__ . '/../auth/check.php';

header('Content-Type: application/json');

// Admin only
if ($user->role !== 'admin') {
      echo json_encode(['success' => false, 'message' => 'Unauthorized']);
      exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['del_id'])) {
      echo json_encode(['success' => false, 'message' => 'Invalid request']);
      exit;
}

$id = (int) $_POST['del_id'];

$stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
$deleted = $stmt->execute([':id' => $id]);

echo json_encode([
      'success' => $deleted,
      'message' => $deleted ? 'User deleted' : 'Delete failed'
]);
