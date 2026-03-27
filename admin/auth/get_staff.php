<?php
require '../../connections/db.php';
require '../auth/check.php';

header('Content-Type: application/json');

$id = (int) $_POST['id'];

$stmt = $conn->prepare("SELECT first_name, surname, status FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all subjects
$stmt_all = $conn->prepare("SELECT id, subject FROM subjects ORDER BY subject ASC");
$stmt_all->execute();
$all_subjects = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

// Fetch all classes
$stmt_classes = $conn->prepare("SELECT id, class FROM class ORDER BY class ASC");
$stmt_classes->execute();
$all_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

// Fetch assignments grouped by subject
$stmt_assigned = $conn->prepare("SELECT subject_id, class_id FROM teacher_assignments WHERE teacher_id = :id");
$stmt_assigned->execute([':id' => $id]);
$raw_assignments = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);

$assignments = [];
foreach ($raw_assignments as $row) {
      if (!isset($assignments[$row['subject_id']]))
            $assignments[$row['subject_id']] = [];
      $assignments[$row['subject_id']][] = $row['class_id'];
}

echo json_encode([
      'success' => (bool) $user,
      'data' => $user,
      'assignments' => $assignments,
      'all_subjects' => $all_subjects,
      'all_classes' => $all_classes
]);
