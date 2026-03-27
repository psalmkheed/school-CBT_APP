<?php
require '../../connections/db.php';
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $visibility = $_POST['visibility'] ?? 'all';
    $type = $_POST['type'] ?? 'event';
    $created_by = $_SESSION['user_id'] ?? 0;

    if (empty($title) || empty($start_date) || empty($end_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO school_events (title, description, start_date, end_date, visibility, type, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $start_date, $end_date, $visibility, $type, $created_by]);
        echo json_encode(['status' => 'success', 'message' => 'Event created successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    try {
        $stmt = $conn->prepare("DELETE FROM school_events WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Event deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
