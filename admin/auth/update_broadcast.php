<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

$id = $_POST['id'] ?? '';
$recipient = trim($_POST['recipient'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($id) || empty($recipient) || empty($subject) || empty($message)) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE broadcast SET recipient = :recipient, subject = :subject, message = :message WHERE id = :id");
    $stmt->execute([
        ':id' => $id,
        ':recipient' => $recipient,
        ':subject' => $subject,
        ':message' => $message
    ]);
    
    echo json_encode(["success" => true, "message" => "Broadcast updated successfully"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Failed to update broadcast"]);
}
