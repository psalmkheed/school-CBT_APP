<?php
header('Content-Type: application/json');
require __DIR__ . '/../../connections/db.php';
require __DIR__ . '/../../auth/check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = (int)$_POST['subject_id'];
    $class_name = $_POST['class_name'];
    $session = $_SESSION['active_session'] ?? '';
    $term = $_SESSION['active_term'] ?? '';
    $scores = $_POST['scores'] ?? [];

    try {
        $conn->beginTransaction();
        
        foreach($scores as $student_id => $score) {
            $score = empty($score) ? 0 : (float)$score;
            
            $stmt = $conn->prepare("
                INSERT INTO continuous_assessment (student_id, subject_id, class_name, ca_score, term, session_year)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE ca_score = VALUES(ca_score)
            ");
            $stmt->execute([$student_id, $subject_id, $class_name, $score, $term, $session]);
        }
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Records updated']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
