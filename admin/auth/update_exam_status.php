<?php
require 'check.php';

header('Content-Type: application/json');

if ($user->role !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id']) || empty($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int) $_POST['id'];
$new_status = $_POST['status'];

// Validation: only allow toggling between 'ready' and 'published'
// or maybe 'published' back to 'ready'.
// If status is 'set up', it cannot be published.

try {
    // Check current status
    $stmt = $conn->prepare("SELECT exam_status FROM exams WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $current_status = $stmt->fetchColumn();

    if (!$current_status) {
        echo json_encode(['success' => false, 'message' => 'Exam not found']);
        exit;
    }

    if ($new_status === 'published') {
        if ($current_status !== 'ready') {
            echo json_encode(['success' => false, 'message' => 'Only exams with "Ready" status can be published.']);
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE exams SET exam_status = :status WHERE id = :id");
    $updated = $stmt->execute([':status' => $new_status, ':id' => $id]);

    echo json_encode([
        'success' => $updated,
        'message' => $updated ? "Examination status updated to $new_status" : 'Failed to update status'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
