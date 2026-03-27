<?php
require '../../connections/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super', 'staff'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'upload') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'Other Download';
    $target_class = $_POST['target_class'] ?? 'All';

    if(empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'Title is required.']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Valid file is required.']);
        exit;
    }

    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $allowed = ['jpg','png','jpeg','pdf','doc','docx','mp4','mp3'];
    if (!in_array($ext, $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Supported: ' . implode(',', $allowed)]);
        exit;
    }

    // create upload dir
    $uploadDir = '../../uploads/study_materials/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uniqueName = uniqid() . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $stmt = $conn->prepare("INSERT INTO study_materials (title, description, category, target_class, file_name, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if($stmt->execute([$title, $description, $category, $target_class, $file['name'], $uniqueName, $ext, $_SESSION['user_id']])) {
            echo json_encode(['status' => 'success', 'message' => 'Content uploaded successfully.']);
        } else {
            // unlink on db fail
            @unlink($dest);
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file.']);
    }
    exit;
}

if ($action === 'get_list') {
    $cat = $_GET['category'] ?? ''; // Assignment, Syllabus, Other Download
    
    $sql = "SELECT s.*, u.first_name, u.surname, u.role FROM study_materials s 
            LEFT JOIN users u ON s.uploaded_by = u.id ";
    $params = [];
    
    if(!empty($cat) && $cat !== 'All') {
        $sql .= "WHERE s.category = ? ";
        $params[] = $cat;
    }

    $sql .= "ORDER BY s.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    foreach($records as &$rec) {
        $rec['is_owner'] = ($role === 'super' || $role === 'admin' || $rec['uploaded_by'] == $user_id);
    }

    echo json_encode(['status' => 'success', 'data' => $records]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    
    $stmt = $conn->prepare("SELECT file_path, uploaded_by FROM study_materials WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if($res) {
        // Only allow admin or the owner to delete
        if($_SESSION['role'] === 'staff' && $res['uploaded_by'] != $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete this file.']);
            exit;
        }

        $file = '../../uploads/study_materials/' . $res['file_path'];
        if(file_exists($file) && !is_dir($file)) {
            @unlink($file);
        }
        $conn->prepare("DELETE FROM study_materials WHERE id = ?")->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Record not found.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
