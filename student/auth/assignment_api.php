<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if ($user->role !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'submit') {
    $assignment_id = $_POST['assignment_id'] ?? 0;
    $text_answer = trim($_POST['text_answer'] ?? '');

    $attachment_filename = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($_FILES['attachment']['type'], $allowed_types)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF, DOCX, JPG, PNG allowed.']);
            exit();
        }
        if ($_FILES['attachment']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'File size cannot exceed 5MB.']);
            exit();
        }
        $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $attachment_filename = 'ans_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_dir = '../../uploads/assignments/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $attachment_filename);
    }

    if (empty($text_answer) && !$attachment_filename) {
        echo json_encode(['status' => 'error', 'message' => 'You must provide a text answer or attach a file.']);
        exit();
    }

    // Check if already submitted
    $chk = $conn->prepare("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $chk->execute([$assignment_id, $user->id]);
    if ($chk->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'You have already submitted this assignment.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, text_answer, attachment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$assignment_id, $user->id, $text_answer, $attachment_filename]);
        echo json_encode(['status' => 'success', 'message' => 'Assignment submitted successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
