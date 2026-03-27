<?php
// 1. SILENCE ERRORS & START BUFFERING IMMEDIATELY
error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_level()) ob_end_clean(); // Kill any existing buffers
ob_start();

// 2. INCLUDES
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

// 3. SET HEADERS & CLEAN AGAIN
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// 4. AUTH CHECK
if (!isset($user) || $user->role !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? 'get_performance';

if ($action === 'get_performance') {
    try {
        $active_session = $_SESSION['active_session'] ?? '';
        $active_term = $_SESSION['active_term'] ?? '';

        // 1. Performance Trend (Last 7 exams)
        $trend_stmt = $conn->prepare("
            SELECT e.subject, r.percentage, r.taken_at 
            FROM exam_results r
            JOIN exams e ON r.exam_id = e.id
            WHERE r.user_id = :user_id AND e.session = :sess AND e.term = :term
            ORDER BY r.taken_at ASC 
            LIMIT 7
        ");
        $trend_stmt->execute([':user_id' => $user->id, ':sess' => $active_session, ':term' => $active_term]);
        $trend_data = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Subject Mastery (Average score per subject)
        $mastery_stmt = $conn->prepare("
            SELECT e.subject, AVG(r.percentage) as avg_score
            FROM exam_results r
            JOIN exams e ON r.exam_id = e.id
            WHERE r.user_id = :user_id AND e.session = :sess AND e.term = :term
            GROUP BY e.subject 
            ORDER BY avg_score DESC
            LIMIT 5
        ");
        $mastery_stmt->execute([':user_id' => $user->id, ':sess' => $active_session, ':term' => $active_term]);
        $mastery_data = $mastery_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Output as JSON
        echo json_encode([
            'status'  => 'success',
            'trend'   => $trend_data   ?: [],
            'mastery' => $mastery_data ?: []
        ], JSON_NUMERIC_CHECK);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Internal Engine Error']);
    }
    exit();
}
