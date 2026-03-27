<?php
require '../../connections/db.php';
require '../auth/check.php';

header('Content-Type: application/json');

if (!in_array($user->role, ['admin', 'super'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$class_id = (int) $_POST['class_id'];
$subjects = $_POST['subjects'] ?? []; // Associative array: subject_id => teacher_id

if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID provided.']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Delete existing assignments for this class
    $stmt_del = $conn->prepare("DELETE FROM teacher_assignments WHERE class_id = :cid");
    $stmt_del->execute([':cid' => $class_id]);

    // 2. Insert new assignments
    if (!empty($subjects) && is_array($subjects)) {
        $stmt_ins = $conn->prepare("INSERT INTO teacher_assignments (teacher_id, subject_id, class_id) VALUES (?, ?, ?)");
        
        foreach ($subjects as $subject_id => $teacher_id) {
            $teacher_id = (int) $teacher_id;
            $subject_id = (int) $subject_id;
            
            // Only assign if a teacher was selected for the subject
            if ($teacher_id > 0) {
                $stmt_ins->execute([$teacher_id, $subject_id, $class_id]);
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Subject teachers assigned successfully.']);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
