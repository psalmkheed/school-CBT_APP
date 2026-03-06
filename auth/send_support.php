<?php
header('Content-Type: application/json');
require_once __DIR__ . '/check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = htmlspecialchars(trim($_POST['subject'] ?? 'No Subject'));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    
    if (empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty.']);
        exit;
    }

    try {
        $recipient = 'ADMIN_SUPPORT';

        // Message to admin
        $stmt = $conn->prepare("INSERT INTO broadcast (recipient, subject, message, user_id, username, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)");
        $stmt->execute([$recipient, $subject, $message, $user_id, $username]);

        echo json_encode(['status' => 'success', 'message' => 'Support request sent successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
