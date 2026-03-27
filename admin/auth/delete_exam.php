<?php
require 'check.php';

header('Content-Type: application/json');

if (!in_array($user->role, ['admin', 'super'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = (int) $_POST['id'];

try {
    $conn->beginTransaction();

    // Optionally delete related questions? Usually yes.
    $stmt = $conn->prepare("DELETE FROM questions WHERE exam_id = :id");
    $stmt->execute([':id' => $id]);

    $stmt = $conn->prepare("DELETE FROM exams WHERE id = :id");
    $deleted = $stmt->execute([':id' => $id]);

    if ($deleted) {
        recordActivity($conn, 'EXAM_DELETE', "Admin deleted Examination ID: $id (and its questions)", 'warning');
    }

    $conn->commit();

    echo json_encode([
        'success' => $deleted,
        'message' => $deleted ? 'Examination deleted successfully' : 'Failed to delete examination'
    ]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
