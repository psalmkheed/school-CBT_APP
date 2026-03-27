<?php
header('Content-Type: application/json');
require '../../connections/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super', 'admin', 'staff'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'list_students') {
    $class = $_POST['class'] ?? '';
    $session = $_POST['session'] ?? '';
    $term = $_POST['term'] ?? '';

    $sess = trim($session);
    $trm = trim($term);

    // Normalize term for comparison (e.g. "2nd Term" -> "Second Term")
    // We'll use a CASE statement or simple REPLACE logic if needed, but for now 
    // let's try to match both variations by expanding the WHERE clause.
    $term_variations = [$trm];
    if (strpos($trm, 'First') !== false) $term_variations[] = str_replace('First', '1st', $trm);
    if (strpos($trm, '1st') !== false) $term_variations[] = str_replace('1st', 'First', $trm);
    if (strpos($trm, 'Second') !== false) $term_variations[] = str_replace('Second', '2nd', $trm);
    if (strpos($trm, '2nd') !== false) $term_variations[] = str_replace('2nd', 'Second', $trm);
    if (strpos($trm, 'Third') !== false) $term_variations[] = str_replace('Third', '3rd', $trm);
    if (strpos($trm, '3rd') !== false) $term_variations[] = str_replace('3rd', 'Third', $trm);
    
    $term_placeholders = [];
    foreach($term_variations as $i => $v) {
        $key = ":trm_$i";
        $term_placeholders[] = "TRIM(e.term) = $key";
        $params[$key] = $v;
    }
    $term_sql = "(" . implode(" OR ", $term_placeholders) . ")";

    $query = "
        SELECT 
            u.id, 
            u.first_name, 
            u.surname, 
            u.user_id, 
            u.class,
            (SELECT COUNT(*) FROM exam_results r JOIN exams e ON r.exam_id = e.id WHERE r.user_id = u.id AND TRIM(e.session) = :sess AND $term_sql) as subjects_count,
            (SELECT COALESCE(AVG(r.percentage), 0) FROM exam_results r JOIN exams e ON r.exam_id = e.id WHERE r.user_id = u.id AND TRIM(e.session) = :sess AND $term_sql) as avg_percentage
        FROM users u
        WHERE u.role = 'student'
    ";

    $params[':sess'] = $sess;

    if ($class) {
        $query .= " AND u.class = :cls";
        $params[':cls'] = $class;
    }

    $query .= " ORDER BY u.first_name ASC";

    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

if ($action === 'get_student_results') {
    $student_id = $_POST['student_id'] ?? '';
    $session = $_POST['session'] ?? '';
    $term = $_POST['term'] ?? '';

    $sess = trim($session);
    $trm = trim($term);
    $term_variations = [$trm];
    if (strpos($trm, 'First') !== false) $term_variations[] = str_replace('First', '1st', $trm);
    if (strpos($trm, '1st') !== false) $term_variations[] = str_replace('1st', 'First', $trm);
    if (strpos($trm, 'Second') !== false) $term_variations[] = str_replace('Second', '2nd', $trm);
    if (strpos($trm, '2nd') !== false) $term_variations[] = str_replace('2nd', 'Second', $trm);
    if (strpos($trm, 'Third') !== false) $term_variations[] = str_replace('Third', '3rd', $trm);
    if (strpos($trm, '3rd') !== false) $term_variations[] = str_replace('3rd', 'Third', $trm);

    $in_placeholders = implode(',', array_fill(0, count($term_variations), '?'));
    $params = [$student_id, $sess];
    foreach($term_variations as $v) $params[] = $v;

    $stmt = $conn->prepare("
        SELECT r.id, e.subject, e.exam_type, r.score, r.total_questions, r.percentage, r.taken_at
        FROM exam_results r
        JOIN exams e ON r.exam_id = e.id
        WHERE r.user_id = ? AND TRIM(e.session) = ? AND TRIM(e.term) IN ($in_placeholders)
        ORDER BY r.taken_at DESC
    ");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $data]);
}

if ($action === 'delete_result') {
    if ($_SESSION['role'] !== 'super' && $_SESSION['role'] !== 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'Only administrators can reset exams']);
        exit;
    }

    $result_id = $_POST['result_id'] ?? '';
    
    $stmt = $conn->prepare("DELETE FROM exam_results WHERE id = ?");
    if ($stmt->execute([$result_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Exam reset successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reset exam']);
    }
}
