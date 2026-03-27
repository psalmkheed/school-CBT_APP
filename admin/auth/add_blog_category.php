<?php
require '../../connections/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add_category') {
    $category_name = trim($_POST['blog_category'] ?? '');

    if (empty($category_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
        exit;
    }

    try {
        // Check if category already exists
        $check = $conn->prepare("SELECT id FROM categories WHERE blog_category = ?");
        $check->execute([$category_name]);
        if ($check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'This category already exists.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO categories (blog_category) VALUES (?)");
        $stmt->execute([$category_name]);

        if (function_exists('recordActivity')) {
            recordActivity($conn, "BLOG_CAT_CREATED", "Created blog category: $category_name");
        }

        echo json_encode(['status' => 'success', 'message' => 'Category added successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
?>
