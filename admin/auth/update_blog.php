<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';
require_once '../../connections/functions.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

$id = $_POST['blog_id'] ?? '';
$blog_title = trim($_POST['blog_title'] ?? '');
$blog_category = trim($_POST['blog_category'] ?? '');
$blog_message = $_POST['blog_message'] ?? '';

if (empty($id) || empty($blog_title) || empty($blog_category) || empty($blog_message)) {
    echo json_encode(["status" => "error", "message" => "All fields except image are required"]);
    exit;
}

try {
    $query = "UPDATE blog SET blog_title = ?, blog_category = ?, blog_message = ? WHERE id = ?";
    $params = [$blog_title, $blog_category, $blog_message, $id];

    // Handle optional image update
    if (!empty($_FILES['blog_image']['name']) && $_FILES['blog_image']['error'] === 0) {
        $uploadDir = '../../uploads/blogs/';
        
        $fileName = $_FILES['blog_image']['name'];
        $tmpName = $_FILES['blog_image']['tmp_name'];
        $fileSize = $_FILES['blog_image']['size'];
        
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid image type']);
            exit;
        }
        
        if ($fileSize > 2 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'Image must be less than 2MB']);
            exit;
        }
        
        // delete old image first
        $stmt = $conn->prepare("SELECT blog_image FROM blog WHERE id = ?");
        $stmt->execute([$id]);
        $oldImage = $stmt->fetchColumn();
        if ($oldImage && file_exists($uploadDir . $oldImage)) {
            unlink($uploadDir . $oldImage);
        }

        $newName = uniqid('blog_', true) . '.' . $ext;
        $destination = $uploadDir . $newName;
        
        if (move_uploaded_file($tmpName, $destination)) {
            $query = "UPDATE blog SET blog_title = ?, blog_category = ?, blog_message = ?, blog_image = ? WHERE id = ?";
            $params = [$blog_title, $blog_category, $blog_message, $newName, $id];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload new image']);
            exit;
        }
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    recordActivity($conn, 'BLOG_UPDATE', "Admin updated blog post: '$blog_title' (ID: $id)");
    
    echo json_encode(["status" => "success", "message" => "Blog updated successfully"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Failed to update blog"]);
}
