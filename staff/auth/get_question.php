<?php
header('Content-Type: application/json');
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$exam_id = $_POST['exam_id'] ?? '';
$q_num = $_POST['q_num'] ?? '';

if (empty($exam_id) || empty($q_num)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM questions WHERE exam_id = :exam_id AND question_number = :q_num");
    $stmt->execute([':exam_id' => $exam_id, ':q_num' => $q_num]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'question' => $question]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
