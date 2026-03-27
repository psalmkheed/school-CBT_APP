<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if ($user->role !== 'staff') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';

    if (empty($title) || empty($class) || empty($due_date)) {
        echo json_encode(['status' => 'error', 'message' => 'Title, class, and due date are required.']);
        exit();
    }

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
        $attachment_filename = 'ass_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_dir = '../../uploads/assignments/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $attachment_filename);
    }

    try {
        $active_session_id = $_SESSION['active_session_id'] ?? 0;
        $stmt = $conn->prepare("INSERT INTO assignments (title, description, class, teacher_id, due_date, attachment, session_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $class, $user->id, $due_date, $attachment_filename, $active_session_id]);
        echo json_encode(['status' => 'success', 'message' => 'Assignment published successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    try {
        $stmt = $conn->prepare("DELETE FROM assignments WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$id, $user->id]);
        echo json_encode(['status' => 'success', 'message' => 'Assignment deleted.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} elseif ($action === 'submissions') {
    $id = $_GET['id'] ?? 0;
    
    // Check ownership
    $check = $conn->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ?");
    $check->execute([$id, $user->id]);
    if(!$check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT s.*, u.first_name, u.surname 
        FROM assignment_submissions s 
        JOIN users u ON s.student_id = u.id 
        WHERE s.assignment_id = ? 
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$id]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $submissions]);

} elseif ($action === 'grade') {
    $sub_id = $_POST['submission_id'] ?? 0;
    $assignment_id = $_POST['assignment_id'] ?? 0;
    $grade = (float)($_POST['grade'] ?? 0);

    // Verify ownership
    $check = $conn->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ?");
    $check->execute([$assignment_id, $user->id]);
    if(!$check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE assignment_submissions SET grade = ? WHERE id = ? AND assignment_id = ?");
        $stmt->execute([$grade, $sub_id, $assignment_id]);
        echo json_encode(['status' => 'success']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
