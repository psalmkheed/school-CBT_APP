<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

header('Content-Type: application/json');

$message = trim($_POST['message'] ?? '');
$attachment = null;
$attachment_type = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    if (!is_dir('../../uploads/chat')) {
        mkdir('../../uploads/chat', 0777, true);
    }
    
    $file_tmp = $_FILES['attachment']['tmp_name'];
    $file_name = $_FILES['attachment']['name'];
    $file_size = $_FILES['attachment']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    if (in_array($file_ext, $allowed)) {
        if ($file_size <= 10 * 1024 * 1024) { // 10MB limit
            $new_name = uniqid('chat_', true) . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, '../../uploads/chat/' . $new_name)) {
                $attachment = $new_name;
                $attachment_type = (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) ? 'image' : 'file';
            }
        }
    }
}

if (empty($message) && empty($attachment)) {
    echo json_encode(['success' => false, 'message' => 'Message or attachment cannot be empty.']);
    exit;
}

if (strlen($message) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Message is too long (max 1000 characters).']);
    exit;
}

// Only students can send class messages
if ($user->role !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Only students can send class messages.']);
    exit;
}

// Store raw text — encoding happens at display time, not at storage time
$stmt = $conn->prepare("INSERT INTO class_messages (user_id, class, message, attachment, attachment_type) VALUES (:user_id, :class, :message, :attachment, :attachment_type)");
$stmt->execute([
    ':user_id' => $user->id,
    ':class'   => $user->class,
    ':message' => $message,
    ':attachment' => $attachment,
    ':attachment_type' => $attachment_type,
]);

echo json_encode(['success' => true, 'id' => $conn->lastInsertId(), 'attachment' => $attachment, 'attachment_type' => $attachment_type]);
