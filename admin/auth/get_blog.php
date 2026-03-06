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
    $stmt = $conn->prepare("SELECT * FROM blog WHERE id = :id");
    $stmt->execute([':id' => $_POST['id']]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($blog) {
        echo json_encode(["success" => true, "data" => $blog]);
    } else {
        echo json_encode(["success" => false, "message" => "Blog not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Failed to fetch blog"]);
}
