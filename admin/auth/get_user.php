<?php
require '../../connections/db.php';
require '../auth/check.php';

header('Content-Type: application/json');

$id = (int) $_POST['id'];

$stmt = $conn->prepare("SELECT first_name, last_name, user_id, class, status FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
      'success' => (bool) $user,
      'data' => $user
]);
