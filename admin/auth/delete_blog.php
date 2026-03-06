<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

try {
    // get image to delete it from disk
    $stmt = $conn->prepare("SELECT blog_image FROM blog WHERE id = :id");
    $stmt->execute([':id' => $_POST['id']]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($blog && !empty($blog['blog_image'])) {
        $imgPath = '../../uploads/blogs/' . $blog['blog_image'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM blog WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    recordActivity($conn, 'BLOG_DELETE', "Admin deleted blog post ID: $id", 'warning');
    
    echo json_encode(["success" => true, "message" => "Blog deleted successfully"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Failed to delete blog"]);
}
