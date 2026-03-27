<?php
require '../../connections/db.php';
require '../auth/check.php';

header('Content-Type: application/json');

$class_id = (int) $_GET['class_id'];

if (!$class_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

try {
    // Fetch all subjects
    $stmt_sub = $conn->prepare("SELECT id, subject FROM subjects ORDER BY subject ASC");
    $stmt_sub->execute();
    $subjects = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all staff members
    $stmt_staff = $conn->prepare("SELECT id, first_name, surname FROM users WHERE role = 'staff' ORDER BY first_name ASC");
    $stmt_staff->execute();
    $staff = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);

    // Fetch current assignments for this class
    $stmt_assigned = $conn->prepare("SELECT subject_id, teacher_id FROM teacher_assignments WHERE class_id = :cid");
    $stmt_assigned->execute([':cid' => $class_id]);
    $assignments_raw = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);

    $assignments = [];
    foreach ($assignments_raw as $row) {
        $assignments[$row['subject_id']] = $row['teacher_id'];
    }

    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'staff' => $staff,
        'assignments' => $assignments
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
