<?php
require __DIR__ . '/../../auth/check.php';

header('Content-Type: application/json');

if ($user->role !== 'staff') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'upload') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['material_file'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit();
    }

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $class = $_POST['class'] ?? '';
    $teacher_id = $user->id;
    $session_id = $_SESSION['active_session_id'] ?? 0;

    $file = $_FILES['material_file'];
    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed_exts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'];

    if (!in_array(strtolower($file_ext), $allowed_exts)) {
        echo json_encode(['status' => 'error', 'message' => 'File type not allowed.']);
        exit();
    }

    if ($file['size'] > 10 * 1024 * 1024) { // 10MB
        echo json_encode(['status' => 'error', 'message' => 'File size exceeds 10MB limit.']);
        exit();
    }

    $upload_dir = '../../uploads/library/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $new_filename = 'material_' . uniqid() . '.' . $file_ext;
    $db_file_path = 'uploads/library/' . $new_filename;
    $full_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $full_path)) {
        $stmt = $conn->prepare("INSERT INTO materials (title, description, file_path, file_type, subject, class, session_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $db_file_path, $file['type'], $subject, $class, $session_id, $teacher_id])) {
            echo json_encode(['status' => 'success', 'message' => 'Material uploaded successfully!']);
        } else {
            @unlink($full_path);
            echo json_encode(['status' => 'error', 'message' => 'Database error while saving material.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file.']);
    }
} else if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $teacher_id = $user->id;

    $stmt = $conn->prepare("SELECT * FROM materials WHERE id = ? AND uploaded_by = ?");
    $stmt->execute([$id, $teacher_id]);
    $material = $stmt->fetch(PDO::FETCH_OBJ);

    if ($material) {
        $full_path = '../../' . $material->file_path;
        @unlink($full_path);
        
        $del_stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
        if ($del_stmt->execute([$id])) {
            echo json_encode(['status' => 'success', 'message' => 'Material deleted successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete material from database.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Material not found or access denied.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported action.']);
}
