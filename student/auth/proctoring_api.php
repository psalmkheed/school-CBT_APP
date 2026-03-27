<?php
header('Content-Type: application/json');
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'log_alert') {
    $exam_id = (int)$_POST['exam_id'];
    $reason = $_POST['reason'] ?? 'Suspicious Activity';

    // Verify if exam active
    $check = $conn->prepare("SELECT id FROM exams WHERE id = ?");
    $check->execute([$exam_id]);
    if (!$check->fetch()) {
        exit(json_encode(['status' => 'error', 'message' => 'Invalid exam']));
    }

    try {
        // Ensure table exists
        $conn->exec("CREATE TABLE IF NOT EXISTS proctoring_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT,
            student_id INT,
            reason VARCHAR(255),
            logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $conn->prepare("INSERT INTO proctoring_logs (exam_id, student_id, reason) VALUES (?, ?, ?)");
        $stmt->execute([$exam_id, $user->id, $reason]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
