<?php
require __DIR__ . '/../auth/check.php';
header('Content-Type: application/json');

if (!isset($conn)) {
      echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
      exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
      exit;
}

$id = $_POST['id'] ?? null;

if (!isset($id) || !is_numeric($id)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
      exit;
}

$id = (int) $id;

try {
      // Reset notification to unread for testing
      $stmt = $conn->prepare("
          UPDATE broadcast 
          SET is_read = 0 
          WHERE id = :id AND recipient = :recipient
      ");
      $success = $stmt->execute([
            ':id' => $id,
            ':recipient' => $_SESSION['username']
      ]);
      
      if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Notification reset to unread']);
      } else {
            echo json_encode(['status' => 'error', 'message' => 'Notification not found']);
      }
} catch (PDOException $e) {
      error_log("Reset notification error: " . $e->getMessage());
      echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
