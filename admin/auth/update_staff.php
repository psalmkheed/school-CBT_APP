<?php
require '../../connections/db.php';
require '../auth/check.php';

header('Content-Type: application/json');

$status = (int) $_POST['status'];
$id = (int) $_POST['id'];
$first = trim($_POST['first_name']);
$last = trim($_POST['last_name']);

// Get old name first to update exams table
$get_old = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = :id");
$get_old->execute([':id' => $id]);
$old_user = $get_old->fetch(PDO::FETCH_OBJ);
$old_name = $old_user ? $old_user->first_name . ' ' . $old_user->last_name : '';

$stmt = $conn->prepare("
    UPDATE users 
    SET first_name = :first, last_name = :last, status = :status
    WHERE id = :id
");

$ok = $stmt->execute([
      ':first' => $first,
      ':last' => $last,
      ':status' => $status,
      ':id' => $id
]);

if ($ok && $old_name) {
      $new_name = $first . ' ' . $last;
      // Update exams table if teacher name matches
      $exam_stmt = $conn->prepare("UPDATE exams SET subject_teacher = :new_name WHERE subject_teacher = :old_name");
      $exam_stmt->execute([
            ':new_name' => $new_name,
            ':old_name' => $old_name
      ]);
}

echo json_encode([
      'success' => $ok,
      'message' => $ok ? 'Updated successfully' : 'Update failed'
]);
