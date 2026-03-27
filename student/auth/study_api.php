<?php
header('Content-Type: application/json');
require '../../connections/db.php';
require '../../auth/check.php';
require '../../auth/fee_check.php';

if ($_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (!isFeeCleared($conn, $user->id)) {
    echo json_encode([
        'status' => 'fee_restriction', 
        'message' => 'Your access to the AI Study Path is limited due to outstanding fee payments.'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$class = $_SESSION['class'] ?? '';

$active_session = $_SESSION['active_session'] ?? '';
$active_term = $_SESSION['active_term'] ?? '';

// 1. Get Subject Averages for current session/term
$stmt = $conn->prepare("
    SELECT e.subject, AVG(r.percentage) as avg_p, COUNT(r.id) as attempts
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.user_id = :uid AND e.session = :sess AND e.term = :term
    GROUP BY e.subject
    ORDER BY avg_p ASC
");
$stmt->execute([':uid' => $user->id, ':sess' => $active_session, ':term' => $active_term]);
$performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Identify Weak Subjects (< 60%)
$weak_subjects = [];
$mastered_subjects = [];
foreach($performance as $p) {
    if($p['avg_p'] < 60) $weak_subjects[] = $p['subject'];
    else if($p['avg_p'] >= 80) $mastered_subjects[] = $p['subject'];
}

// 3. Find Materials for Weak Subjects
$recommendations = [];
if(!empty($weak_subjects)) {
    $placeholders = implode(',', array_fill(0, count($weak_subjects), '?'));
    $mat_stmt = $conn->prepare("
        SELECT id, title, subject, file_path, file_type 
        FROM materials 
        WHERE TRIM(LOWER(class)) = TRIM(LOWER(?)) 
        AND subject IN ($placeholders)
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $mat_params = array_merge([$class], $weak_subjects);
    $mat_stmt->execute($mat_params);
    $recommendations = $mat_stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'status' => 'success',
    'performance' => $performance,
    'weak_subjects' => $weak_subjects,
    'mastered_subjects' => $mastered_subjects,
    'recommendations' => $recommendations
]);
