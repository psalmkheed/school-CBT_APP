<?php
require __DIR__ . '/../auth/check.php';

header('Content-Type: application/json');

// Admin only
if (!in_array($user->role, ['admin', 'super'])) {
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

if ($deleted) {
      recordActivity($conn, 'USER_DELETE', "Admin deleted user account (ID: $id)", 'warning');
}

echo json_encode([
      'success' => $deleted,
      'message' => $deleted ? 'User deleted' : 'Delete failed'
]);
