<?php
session_start();
require '../connections/db.php';

header('Content-Type: application/json');

// if session is not set, redirect to login page
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// if request not POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profile_photo'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$file      = $_FILES['profile_photo'];
$id        = $_SESSION['user_id'];
$uploadDir = __DIR__ . '/../uploads/profile_photos/';

// Create the upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Validate: no upload error
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error. Please try again.']);
    exit;
}

// Validate: allowed types (using finfo to prevent spoofing)
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo        = finfo_open(FILEINFO_MIME_TYPE);
$mimeType     = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.']);
    exit;
}

// Validate: max 2MB
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 2MB.']);
    exit;
}

// ── Fetch the user's CURRENT photo so we can delete it after success ──────────
try {
    $fetchStmt = $conn->prepare('SELECT profile_photo FROM users WHERE id = :id');
    $fetchStmt->execute([':id' => $id]);
    $currentUser  = $fetchStmt->fetch(PDO::FETCH_OBJ);
    $oldPhotoFile = $currentUser ? $currentUser->profile_photo : null;
} catch (PDOException $e) {
    error_log('Profile photo fetch error: ' . $e->getMessage());
    $oldPhotoFile = null;
}

// ── Generate a safe unique filename & move the upload ─────────────────────────
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = 'user_' . $id . '_' . time() . '.' . strtolower($ext);
$destPath = $uploadDir . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save the file. Please try again.']);
    exit;
}

// ── Update the database ───────────────────────────────────────────────────────
try {
    $stmt = $conn->prepare('UPDATE users SET profile_photo = :profile_photo WHERE id = :id');
    $stmt->execute([
        ':profile_photo' => $fileName,
        ':id'            => $id,
    ]);

    if ($stmt->rowCount() > 0) {

        // ── Delete the OLD photo from disk (if it exists & isn't the default) ─
        if (!empty($oldPhotoFile)) {
            $oldPath = $uploadDir . $oldPhotoFile;
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $photoUrl = '../uploads/profile_photos/' . $fileName;
        echo json_encode([
            'success'   => true,
            'message'   => 'Profile photo updated successfully!',
            'photo_url' => $photoUrl,
        ]);
    } else {
        // DB didn't update — clean up the newly moved file
        @unlink($destPath);
        echo json_encode(['success' => false, 'message' => 'No changes were made. Please try again.']);
    }
} catch (PDOException $e) {
    error_log('Profile photo DB error: ' . $e->getMessage());
    // Clean up the newly moved file on DB failure
    @unlink($destPath);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
?>