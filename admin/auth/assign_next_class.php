<?php
require '../../connections/db.php';
require '../../auth/check.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['admin', 'super'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'] ?? null;
    $next_class_id = $_POST['next_class_id'] ?? null;

    if (empty($class_id)) {
        echo json_encode(['success' => false, 'message' => 'Class ID is required']);
        exit;
    }

    try {
        if (empty($next_class_id)) {
            $next_class_id = null; // Graduated
        }

        $stmt = $conn->prepare("UPDATE class SET next_class_id = :next_id WHERE id = :id");
        $stmt->execute([
            ':next_id' => $next_class_id,
            ':id' => $class_id
        ]);

        echo json_encode(['success' => true, 'message' => 'Promotion Path updated!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
