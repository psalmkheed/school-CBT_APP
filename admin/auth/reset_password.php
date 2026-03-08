<?php
require '../../connections/db.php';
require '../auth/check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$new_password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (!$id || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'User ID and new password are required']);
    exit;
}

// Additional protection: Only Admin can reset passwords for anyone
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Admin can reset passwords']);
    exit;
}

try {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
    $result = $stmt->execute([
        ':password' => $hashed_password,
        ':id' => $id
    ]);

    if ($result) {
        // Log activity
        $user_stmt = $conn->prepare("SELECT user_id FROM users WHERE id = :id");
        $user_stmt->execute([':id' => $id]);
        $target_user_id = $user_stmt->fetchColumn();
        
        recordActivity($conn, 'PASSWORD_RESET', "Admin reset password for user: $target_user_id", 'info');
        
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
