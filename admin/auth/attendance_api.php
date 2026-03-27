<?php
header('Content-Type: application/json');
require '../../connections/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'get_trends') {
    $days = (int)($_GET['days'] ?? 7);
    $session_id = $_SESSION['active_session_id'] ?? 0;
    
    // Get last $days dates
    $data_stmt = $conn->prepare("
        SELECT 
            attendance_date,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
        FROM attendance
        WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
        AND session_id = :session_id
        GROUP BY attendance_date
        ORDER BY attendance_date ASC
    ");
    $data_stmt->execute([':days' => $days, ':session_id' => $session_id]);
    $rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $present = [];
    $absent = [];
    $late = [];

    foreach($rows as $r) {
        $labels[] = date('M j', strtotime($r['attendance_date']));
        $present[] = (int)$r['present'];
        $absent[] = (int)$r['absent'];
        $late[] = (int)$r['late'];
    }

    echo json_encode([
        'status' => 'success',
        'labels' => $labels,
        'present' => $present,
        'absent' => $absent,
        'late' => $late
    ]);
}
