<?php
session_start();
require_once __DIR__ . '/../../connections/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$msg_id = (int)($_POST['msg_id'] ?? 0);

if ($msg_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Check ownership
$stmt = $conn->prepare("SELECT user_id, message, is_deleted FROM class_messages WHERE id = ?");
$stmt->execute([$msg_id]);
$msg = $stmt->fetch(PDO::FETCH_OBJ);

if (!$msg) {
    echo json_encode(['success' => false, 'message' => 'Message not found']);
    exit;
}

if ($msg->user_id != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete this message']);
    exit;
}

if ($msg->is_deleted) {
    echo json_encode(['success' => false, 'message' => 'Message already deleted']);
    exit;
}

$update = $conn->prepare("UPDATE class_messages SET message = '', attachment = NULL, attachment_type = NULL, is_deleted = 1 WHERE id = ?");
$update->execute([$msg_id]);

echo json_encode(['success' => true]);
