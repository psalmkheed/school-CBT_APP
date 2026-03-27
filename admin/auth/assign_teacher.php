<?php
require '../../auth/check.php';
header('Content-Type: application/json');

// Security Check: Only admins can assign teachers
if (!in_array($user->role, ['admin', 'super'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$class_id   = $_POST['class_id']   ?? null;
$teacher_id = $_POST['teacher_id'] ?? null;

if (empty($class_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID provided.']);
    exit;
}

try {
    // If teacher_id is empty, it means we're unassigning
    $new_teacher_id = empty($teacher_id) ? null : $teacher_id;

    $stmt = $conn->prepare("UPDATE class SET teacher_id = :teacher_id WHERE id = :id");
    $result = $stmt->execute([
        ':teacher_id' => $new_teacher_id,
        ':id' => $class_id
    ]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Teacher assignment updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update record.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
