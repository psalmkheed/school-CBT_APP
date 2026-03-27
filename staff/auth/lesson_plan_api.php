<?php
header('Content-Type: application/json');
require __DIR__ . '/../../connections/db.php';
require __DIR__ . '/../../auth/check.php';

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $teacher_id = $user->id;
    $assignment_parts = explode('|', $_POST['assignment']);
    $subject_id = (int)$assignment_parts[0];
    $class_name = $assignment_parts[1];
    $week = (int)$_POST['week_number'];
    $topic = $_POST['topic'];
    $content = $_POST['content'] ?? '';
    
    $file_path = null;
    if (isset($_FILES['plan_file']) && $_FILES['plan_file']['error'] === 0) {
        $ext = pathinfo($_FILES['plan_file']['name'], PATHINFO_EXTENSION);
        $filename = "LP_" . time() . "_" . $teacher_id . "." . $ext;
        $dest = __DIR__ . "/../../uploads/lesson_plans/" . $filename;
        
        if (!is_dir(__DIR__ . "/../../uploads/lesson_plans")) {
            mkdir(__DIR__ . "/../../uploads/lesson_plans", 0777, true);
        }
        
        if (move_uploaded_file($_FILES['plan_file']['tmp_name'], $dest)) {
            $file_path = $filename;
        }
    }

    $active_session_id = $_SESSION['active_session_id'] ?? 0;
    
    $stmt = $conn->prepare("
        INSERT INTO lesson_plans (teacher_id, subject_id, class_name, week_number, topic, content, file_path, session_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$teacher_id, $subject_id, $class_name, $week, $topic, $content, $file_path, $active_session_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Plan submitted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
}

if ($action === 'approve' || $action === 'reject') {
    if (!in_array($_SESSION['role'], ['super', 'admin'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    
    $id = (int)$_POST['id'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $feedback = $_POST['feedback'] ?? '';
    
    $stmt = $conn->prepare("UPDATE lesson_plans SET status = ?, admin_feedback = ? WHERE id = ?");
    if ($stmt->execute([$status, $feedback, $id])) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }
}

if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $teacher_id = $user->id;

    $stmt = $conn->prepare("SELECT file_path FROM lesson_plans WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$id, $teacher_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plan) {
        $stmt = $conn->prepare("DELETE FROM lesson_plans WHERE id = ? AND teacher_id = ?");
        if ($stmt->execute([$id, $teacher_id])) {
            if (!empty($plan['file_path'])) {
                $file = __DIR__ . "/../../uploads/lesson_plans/" . $plan['file_path'];
                if (file_exists($file)) @unlink($file);
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Plan not found or unauthorized']);
    }
}
