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
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM broadcast WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    recordActivity($conn, 'BROADCAST_DELETE', "Admin deleted Broadcast Message ID: $id", 'warning');
    
    echo json_encode(["success" => true, "message" => "Broadcast deleted successfully"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Failed to delete broadcast"]);
}
