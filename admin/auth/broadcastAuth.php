<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(["status" => "error", "message" => "Invalid request"]);
      exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
      echo json_encode(["status" => "error", "message" => "Unauthorized"]);
      exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$recipient = trim($_POST['recipient'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($recipient === '' || $subject === '' || $message === '') {
      echo json_encode([
            "status" => "error",
            "message" => "All fields are required"
      ]);
      exit;
}


try {
      $stmt = $conn->prepare("
        INSERT INTO broadcast (recipient, subject, message, user_id, username)
        VALUES (:recipient, :subject, :message, :user_id, :username)
    ");

      $stmt->execute([
            ':recipient' => $recipient,
            ':subject' => $subject,
            ':message' => $message,
            ':user_id' => $user_id,
            ':username' => $username
      ]);

      echo json_encode([
            "status" => "success",
            "message" => "Message sent successfully"
      ]);
} catch (PDOException $e) {
      echo json_encode([
            "status" => "error",
            "message" => "Failed to send message"
      ]);
}
exit;
