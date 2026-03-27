<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    
    try {
        // Fetch file path first to delete the file from server
        $stmt_fetch = $conn->prepare("SELECT file_path FROM materials WHERE id = ?");
        $stmt_fetch->execute([$id]);
        $row = $stmt_fetch->fetch(PDO::FETCH_OBJ);

        if ($row) {
            $filepath = __DIR__ . '/../../' . ltrim($row->file_path, '/');
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }

        // Delete from DB
        $stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Material deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
