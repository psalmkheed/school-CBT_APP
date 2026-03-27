<?php
require __DIR__ . '/../auth/check.php';
header('Content-Type: application/json');

// Check if database connection exists
if (!isset($conn)) {
      echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
      exit;
}

// Log the request method for debugging
error_log("Mark read - Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Mark read - POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['status' => 'error', 'message' => 'Invalid request method: ' . $_SERVER['REQUEST_METHOD']]);
      exit;
}

$id = $_POST['id'] ?? null;

if (!isset($id) || !is_numeric($id)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
      exit;
}

$id = (int) $id;

try {
      // Only mark notifications that belong to logged-in user
      if (in_array($_SESSION['role'], ['admin', 'super'])) {
            $stmt = $conn->prepare("
                UPDATE broadcast 
                SET is_read = 1 
                WHERE id = :id AND (recipient = :recipient OR recipient = 'ADMIN_SUPPORT')
            ");
      } else {
            $stmt = $conn->prepare("
                UPDATE broadcast 
                SET is_read = 1 
                WHERE id = :id AND recipient = :recipient
            ");
      }

      $success = $stmt->execute([
            ':id' => $id,
            ':recipient' => $_SESSION['username'] ?? ''
      ]);
      
      // Check if any row was actually updated
      if ($success && $stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success']);
      } elseif ($success && $stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Notification not found or already read']);
      } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
      }
} catch (PDOException $e) {
      // Log the error for debugging (don't expose to user)
      error_log("Mark read error: " . $e->getMessage());
      echo json_encode(['status' => 'error', 'message' => 'An error occurred while updating the notification']);
}
