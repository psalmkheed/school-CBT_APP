<?php
header('Content-Type: application/json');
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_POST['id'] ?? '';
$exam_id = $_POST['exam_id'] ?? '';

if (empty($id) || empty($exam_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    // Get image before deleting record
    $img_stmt = $conn->prepare("SELECT question_image FROM questions WHERE id = :id AND exam_id = :exam_id");
    $img_stmt->execute([':id' => $id, ':exam_id' => $exam_id]);
    $img = $img_stmt->fetchColumn();

    if ($img && file_exists("../../uploads/questions/" . $img)) {
        unlink("../../uploads/questions/" . $img);
    }

    $stmt = $conn->prepare("DELETE FROM questions WHERE id = :id AND exam_id = :exam_id");
    $stmt->execute([':id' => $id, ':exam_id' => $exam_id]);

    recordActivity($conn, 'QUESTION_DELETE', "Staff deleted Question ID: $id from Exam ID: $exam_id", 'warning');

    // Check if exam status needs to be reverted to 'set up'
    $stmt = $conn->prepare("SELECT num_quest FROM exams WHERE id = :id");
    $stmt->execute([':id' => $exam_id]);
    $total_needed = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE exam_id = :id");
    $stmt->execute([':id' => $exam_id]);
    $total_set = $stmt->fetchColumn();

    if ($total_set < $total_needed) {
        $stmt = $conn->prepare("UPDATE exams SET exam_status = 'set up' WHERE id = :id AND exam_status = 'ready'");
        $stmt->execute([':id' => $exam_id]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
