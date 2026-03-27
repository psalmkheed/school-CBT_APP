<?php
header('Content-Type: application/json');
require '../../auth/check.php';

if (!in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied: Insufficient permissions']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $class = $_GET['class'] ?? '';
    $session_id = $_SESSION['active_session_id'] ?? 0;
    // Fetch slots and join with users to get teacher name
    $stmt = $conn->prepare("
        SELECT t.*, CONCAT(u.first_name, ' ', u.surname) AS teacher_name
        FROM timetables t
        LEFT JOIN users u ON t.teacher_id = u.id
        WHERE t.class = :class AND t.session_id = :session_id
        ORDER BY 
            FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), 
            t.start_time ASC
    ");
    $stmt->execute([':class' => $class, ':session_id' => $session_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $data]);
    exit;
}

if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $class = $_POST['class'] ?? '';
    $day = $_POST['day'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';

    if (empty($class) || empty($day) || empty($subject) || empty($start_time) || empty($end_time) || empty($teacher_id)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    try {
        $session_id = $_SESSION['active_session_id'] ?? 0;
        if (empty($id)) {
            // Check for overlaps (simplistic check for same day, same class, same session)
            $stmt = $conn->prepare("
                SELECT id FROM timetables 
                WHERE class = :class AND day = :day AND session_id = :session_id AND start_time < :end AND end_time > :start
            ");
            $stmt->execute([':class' => $class, ':day' => $day, ':session_id' => $session_id, ':start' => $start_time, ':end' => $end_time]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Time conflict detected for this class.']);
                exit;
            }

            // Insert new slot
            $stmt = $conn->prepare("INSERT INTO timetables (class, day, subject, start_time, end_time, teacher_id, session_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$class, $day, $subject, $start_time, $end_time, $teacher_id, $session_id]);
            echo json_encode(['status' => 'success', 'message' => 'Slot saved successfully']);
        } else {
            // Update existing slot
            $stmt = $conn->prepare("UPDATE timetables SET class=?, day=?, subject=?, start_time=?, end_time=?, teacher_id=? WHERE id=?");
            $stmt->execute([$class, $day, $subject, $start_time, $end_time, $teacher_id, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Slot updated successfully']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        $stmt = $conn->prepare("DELETE FROM timetables WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
