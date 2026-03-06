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
    $stmt = $conn->prepare("SELECT * FROM broadcast WHERE id = :id");
    $stmt->execute([':id' => $_POST['id']]);
    $broadcast = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($broadcast) {
        echo json_encode(["success" => true, "data" => $broadcast]);
    } else {
        echo json_encode(["success" => false, "message" => "Broadcast not found"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Failed to fetch broadcast"]);
}
