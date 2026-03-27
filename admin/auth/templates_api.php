<?php
require '../../connections/db.php';
require '../../auth/check.php';

header('Content-Type: application/json');

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
    exit;
}

$action = $_GET['action'] ?? '';

// SAVE TEMPLATE
if ($action === 'save_template') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $type = trim($_POST['type'] ?? 'certificate');
    $html_content = $_POST['html_content'] ?? '';
    
    if (empty($title) || empty($html_content)) {
        echo json_encode(['status' => 'error', 'message' => 'Template Title and Content are required.']);
        exit;
    }
    
    // Process bg_image upload
    $bg_image = null;
    if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/templates/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['bg_image']['name']));
        $targetFile = $uploadDir . $fileName;
        
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $valid_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($imageFileType, $valid_extensions)) {
            if (move_uploaded_file($_FILES['bg_image']['tmp_name'], $targetFile)) {
                $bg_image = $fileName;
            }
        } else {
             echo json_encode(['status' => 'error', 'message' => 'Invalid image format. Only JPG, PNG, WEBP allowed.']);
             exit;
        }
    }

    try {
        if ($id > 0) {
            // Check if there is a new background, otherwise retain old
            if ($bg_image) {
                $stmt = $conn->prepare("UPDATE document_templates SET title = ?, type = ?, html_content = ?, bg_image = ? WHERE id = ?");
                $stmt->execute([$title, $type, $html_content, $bg_image, $id]);
            } else {
                $stmt = $conn->prepare("UPDATE document_templates SET title = ?, type = ?, html_content = ? WHERE id = ?");
                $stmt->execute([$title, $type, $html_content, $id]);
            }
            $msg = 'Template updated successfully.';
        } else {
            $stmt = $conn->prepare("INSERT INTO document_templates (title, type, html_content, bg_image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $type, $html_content, $bg_image]);
            $msg = 'Template created successfully.';
        }
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error']);
    }
    exit;
}

// DELETE TEMPLATE
if ($action === 'delete_template') {
    $id = $_POST['id'] ?? 0;
    
    $stmt = $conn->prepare("DELETE FROM document_templates WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Template deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete template.']);
    }
    exit;
}

// GET TEMPLATE DATA
if ($action === 'get_template') {
    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("SELECT * FROM document_templates WHERE id = ?");
    $stmt->execute([$id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($template) {
        echo json_encode(['status' => 'success', 'data' => $template]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Template not found']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
