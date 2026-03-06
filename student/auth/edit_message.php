<?php
session_start();
require_once __DIR__ . '/../../connections/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$msg_id = (int)($_POST['msg_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($msg_id <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Check ownership
$stmt = $conn->prepare("SELECT user_id, is_deleted FROM class_messages WHERE id = ?");
$stmt->execute([$msg_id]);
$msg = $stmt->fetch(PDO::FETCH_OBJ);

if (!$msg) {
    echo json_encode(['success' => false, 'message' => 'Message not found']);
    exit;
}

if ($msg->user_id != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You cannot edit this message']);
    exit;
}

if ($msg->is_deleted) {
    echo json_encode(['success' => false, 'message' => 'Cannot edit a deleted message']);
    exit;
}

$update = $conn->prepare("UPDATE class_messages SET message = ?, is_edited = 1 WHERE id = ?");
$update->execute([$message, $msg_id]);

echo json_encode(['success' => true]);
