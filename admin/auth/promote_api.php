<?php
require '../../connections/db.php';
require '../../auth/check.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['super', 'admin', 'staff'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_students') {
    $class = $_POST['class'] ?? '';
    
    if (empty($class)) {
        echo json_encode(['status' => 'error', 'message' => 'Class is required']);
        exit;
    }
    
    // For staff, they might be restricted to their assigned classes, but let's allow fetching by class if they have access to this page.
    $stmt = $conn->prepare("SELECT id, user_id, first_name, surname, profile_photo FROM users WHERE role = 'student' AND class = ? ORDER BY first_name ASC");
    $stmt->execute([$class]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $students]);
    exit;
}

if ($action === 'promote') {
    $from_class = $_POST['from_class'] ?? '';
    $to_class = $_POST['to_class'] ?? '';
    $student_ids = $_POST['student_ids'] ?? [];
    $promoter_id = $_SESSION['user_id'];
    
    if (empty($from_class) || empty($to_class) || empty($student_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields for promotion.']);
        exit;
    }
    
    if($from_class === $to_class) {
        echo json_encode(['status' => 'error', 'message' => 'Destination class must be different from current class.']);
        exit;
    }
    
    try {
        $conn->beginTransaction();
        
        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
        
        // Update students
        $update_params = array_merge([$to_class], $student_ids);
        $update_stmt = $conn->prepare("UPDATE users SET class = ? WHERE role = 'student' AND id IN ($placeholders)");
        $update_stmt->execute($update_params);
        $promoted_count = $update_stmt->rowCount();
        
        // Log to promotion_history
        $history_stmt = $conn->prepare("INSERT INTO promotion_history (student_id, from_class, to_class, promoted_by) VALUES (?, ?, ?, ?)");
        foreach ($student_ids as $sid) {
            $history_stmt->execute([$sid, $from_class, $to_class, $promoter_id]);
        }
        
        // Log Activity
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
        $details = "Promoted $promoted_count student(s) from $from_class to $to_class";
        $log_stmt->execute([$promoter_id, 'batch_promote', $details]);
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => "Successfully promoted $promoted_count student(s) to $to_class."]);
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
