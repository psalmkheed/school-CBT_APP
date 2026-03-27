<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin', 'staff'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

$user_id = trim($_POST['user_id'] ?? '');
if (empty($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Empty User ID']);
    exit;
}

try {
    // 1. Verify Student
    $stmt = $conn->prepare("SELECT id, first_name, surname, class FROM users WHERE user_id = :uid AND role = 'student'");
    $stmt->execute([':uid' => $user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['status' => 'error', 'message' => "$user_id is not a registered student"]);
        exit;
    }

    $fullname = $student['first_name'] . ' ' . $student['surname'];
    $today = date('Y-m-d');
    $session_id = $_SESSION['active_session_id'] ?? 0;
    
    // Check if already checked in today
    $check = $conn->prepare("SELECT id, status FROM attendance WHERE attendance_date = :dt AND student_id = :sid");
    $check->execute([':dt' => $today, ':sid' => $student['id']]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($existing && $existing['status'] === 'present') {
        echo json_encode(['status' => 'error', 'message' => "$fullname is already marked Present today!"]);
        exit;
    }

    $admin_id = $_SESSION['user_id'] ?? 0;

    $stmt = $conn->prepare("
        INSERT INTO attendance (student_id, class, status, attendance_date, session_id, marked_by)
        VALUES (:student_id, :class, :status, :attendance_date, :session_id, :marked_by)
        ON DUPLICATE KEY UPDATE 
            status = VALUES(status),
            marked_by = VALUES(marked_by)
    ");

    $stmt->execute([
        ':student_id' => $student['id'],
        ':class' => $student['class'],
        ':status' => 'present',
        ':attendance_date' => $today,
        ':session_id' => $session_id,
        ':marked_by' => $admin_id
    ]);

    echo json_encode([
        'status' => 'success', 
        'name' => $fullname,
        'message' => 'Successfully checked in'
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Err: ' . $e->getMessage()]);
}
