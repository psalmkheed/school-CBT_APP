<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if ($_SESSION['role'] !== 'staff') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'issue') {
    $student_id = $_POST['student_id'] ?? 0;
    $reason = trim($_POST['reason'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $duration = (int)($_POST['duration'] ?? 15); // in minutes

    if (empty($student_id) || empty($reason) || empty($destination)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    $expires_at = date('Y-m-d H:i:s', strtotime("+$duration minutes"));

    try {
        $stmt = $conn->prepare("INSERT INTO hall_passes (student_id, issued_by, reason, destination, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $user->id, $reason, $destination, $expires_at]);
        echo json_encode(['status' => 'success', 'message' => 'Pass successfully issued.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($action === 'revoke') {
    $id = $_POST['id'] ?? 0;
    try {
        // Staff can only revoke passes they issued
        $stmt = $conn->prepare("UPDATE hall_passes SET status = 'returned', returned_at = CURRENT_TIMESTAMP WHERE id = ? AND issued_by = ?");
        $stmt->execute([$id, $user->id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Pass marked as returned.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Pass not found or you are not authorized to return it.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    try {
        // Staff can only delete passes they issued
        $stmt = $conn->prepare("DELETE FROM hall_passes WHERE id = ? AND issued_by = ?");
        $stmt->execute([$id, $user->id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Pass securely deleted.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Pass not found or you are not authorized to delete it.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
