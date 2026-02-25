<?php
session_start();
require '../connections/db.php';

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$id              = $_SESSION['user_id'];
$currentPassword = trim($_POST['current_password'] ?? '');
$newPassword     = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

// ── Validate inputs ──────────────────────────────────────────────────────
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New password and confirm password do not match.']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
    exit;
}

if ($currentPassword === $newPassword) {
    echo json_encode(['success' => false, 'message' => 'New password must be different from your current password.']);
    exit;
}

// ── Verify current password ──────────────────────────────────────────────
try {
    $stmt = $conn->prepare('SELECT password FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    if (!password_verify($currentPassword, $user->password)) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    // ── Update with new hashed password ──────────────────────────────────
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateStmt = $conn->prepare('UPDATE users SET password = :password WHERE id = :id');
    $updateStmt->execute([
        ':password' => $hashedPassword,
        ':id'       => $id,
    ]);

    if ($updateStmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made. Please try again.']);
    }

} catch (PDOException $e) {
    error_log('Change password DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
?>
