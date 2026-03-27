<?php
header('Content-Type: application/json');
require '../../auth/check.php';

$action = $_GET['action'] ?? '';

// Endpoints accessible by everyone (to fetch active announcements)
if ($action === 'get_active') {
    $role = $_SESSION['role'] ?? 'guest';
    
    // Admins see 'staff' or 'all' depending.
    // If role is super/admin/staff, they fall under 'staff' or 'all'.
    // If student, 'students' or 'all'.
    $recipient_filter = "recipient = 'all'";
    if (in_array($role, ['super', 'admin', 'staff'])) {
        $recipient_filter = "(recipient = 'all' OR recipient = 'staff')";
    } else if ($role === 'student') {
        $recipient_filter = "(recipient = 'all' OR recipient = 'students')";
    }

    $stmt = $conn->query("
        SELECT * FROM announcements 
        WHERE status = 'active' AND $recipient_filter 
        ORDER BY created_at DESC
    ");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $announcements]);
    exit;
}

// Admins only below this point
if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied']);
    exit;
}

if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = trim($_POST['type'] ?? 'info');
    $recipient = trim($_POST['recipient'] ?? 'all');

    if (!$title || !$message) {
        echo json_encode(['status' => 'error', 'message' => 'Title and message are required.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO announcements (title, message, type, recipient) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$title, $message, $type, $recipient])) {
        echo json_encode(['status' => 'success', 'message' => 'Announcement published successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to publish announcement.']);
    }
    exit;
}

if ($action === 'toggle_status') {
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'inactive');

    $stmt = $conn->prepare("UPDATE announcements SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Announcement deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete announcement.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid API Action']);
