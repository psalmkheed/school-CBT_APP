<?php
require '../../connections/db.php';
require '../auth/check.php';

header('Content-Type: application/json');

$status = (int) ($_POST['status'] ?? 1);
$id = (int) $_POST['id'];
$first = trim($_POST['first_name']);
$last = trim($_POST['surname']);
$assignments = isset($_POST['assignments']) ? $_POST['assignments'] : [];

try {
      $conn->beginTransaction();

      // Get old name first to update exams table
      $get_old = $conn->prepare("SELECT first_name, surname FROM users WHERE id = :id");
      $get_old->execute([':id' => $id]);
      $old_user = $get_old->fetch(PDO::FETCH_OBJ);
      $old_name = $old_user ? $old_user->first_name . ' ' . $old_user->surname : '';

      $stmt = $conn->prepare("
          UPDATE users 
          SET first_name = :first, surname = :last, status = :status
          WHERE id = :id
      ");

      $ok = $stmt->execute([
            ':first' => $first,
            ':last' => $last,
            ':status' => $status,
            ':id' => $id
      ]);

      if ($ok) {
            // Update assigned subjects and classes
            $conn->prepare("DELETE FROM teacher_assignments WHERE teacher_id = ?")->execute([$id]);

            if (!empty($assignments) && is_array($assignments)) {
                  $sub_stmt = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, class_id) VALUES (?, ?, ?)");
                  foreach ($assignments as $sub_id => $class_ids) {
                        if (!is_array($class_ids))
                              continue;
                        foreach ($class_ids as $cl_id) {
                              $sub_stmt->execute([$id, $sub_id, $cl_id]);
                        }
                  }
            }

            if ($old_name) {
                  $new_name = $first . ' ' . $last;
                  // Update exams table if teacher name matches
                  $exam_stmt = $conn->prepare("UPDATE exams SET subject_teacher = :new_name WHERE subject_teacher = :old_name");
                  $exam_stmt->execute([
                        ':new_name' => $new_name,
                        ':old_name' => $old_name
                  ]);
            }
      }

      $conn->commit();
      echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Updated successfully' : 'Update failed'
      ]);
} catch (Exception $e) {
      $conn->rollBack();
      echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
      ]);
}
