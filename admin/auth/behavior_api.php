<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'log_incident') {
    $student_id = $_POST['student_id'] ?? 0;
    $type = $_POST['type'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (empty($student_id) || empty($type) || empty($description)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    try {
        $session_id = $_SESSION['active_session_id'] ?? 0;
        $stmt = $conn->prepare("INSERT INTO behavior_logs (student_id, logged_by, session_id, type, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $user->id, $session_id, $type, $description]);
        echo json_encode(['status' => 'success', 'message' => 'Incident successfully logged.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    try {
        $stmt = $conn->prepare("DELETE FROM behavior_logs WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Log deleted.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
