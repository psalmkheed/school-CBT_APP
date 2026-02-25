<?php
header('Content-Type: application/json');
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$exam_id = $_POST['exam_id'] ?? '';

if (empty($exam_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT question_number FROM questions WHERE exam_id = :id");
    $stmt->execute([':id' => $exam_id]);
    $set_questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true, 
        'count' => count($set_questions),
        'set_questions' => $set_questions
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
