<?php
require '../../connections/db.php';
require '../auth/check.php';

header('Content-Type: application/json');

$status = (int) $_POST['status'];
$id = (int) $_POST['id'];
$first = trim($_POST['first_name']);
$last = trim($_POST['surname']);
$class = trim($_POST['class']);

$stmt = $conn->prepare("
    UPDATE users 
    SET first_name = :first, surname = :last, class = :class, status = :status
    WHERE id = :id
");

$ok = $stmt->execute([
      ':first' => $first,
      ':last' => $last,
      ':class' => $class,
      ':status' => $status,
      ':id' => $id
]);

echo json_encode([
      'success' => $ok,
      'message' => $ok ? 'Updated successfully' : 'Update failed'
]);
