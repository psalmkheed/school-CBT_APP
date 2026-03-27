<?php
require __DIR__ . '/../../auth/check.php';

// Only staff can perform this action
if ($user->role !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit();
}

$selected_date = $_POST['attendance_date'] ?? date('Y-m-d');
$class = $_SESSION['class'] ?? '';
$session_id = $_SESSION['active_session_id'] ?? 0;
$teacher_id = $user->id;

try {
    $conn->beginTransaction();

    foreach ($_POST['status'] as $student_id => $status) {
        $student_id = (int)$student_id;
        $status = in_array($status, ['present', 'absent', 'late']) ? $status : 'present';

        // Use UPSERT (INSERT ... ON DUPLICATE KEY UPDATE) to handle both new and existing records
        $stmt = $conn->prepare("
            INSERT INTO attendance (student_id, class, status, attendance_date, session_id, marked_by)
            VALUES (:student_id, :class, :status, :attendance_date, :session_id, :marked_by)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                class = VALUES(class),
                session_id = VALUES(session_id),
                marked_by = VALUES(marked_by)
        ");

        $stmt->execute([
            ':student_id' => $student_id,
            ':class' => $class,
            ':status' => $status,
            ':attendance_date' => $selected_date,
            ':session_id' => $session_id,
            ':marked_by' => $teacher_id
        ]);
    }

    $conn->commit();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Attendance recorded successfully']);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
