<?php
require '../connections/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$session_name = trim($_POST['session'] ?? '');
$term_name    = trim($_POST['term'] ?? '');

if (empty($session_name) || empty($term_name)) {
    echo json_encode(['success' => false, 'message' => 'Session and term required']);
    exit;
}

// Find the matching record in sch_session — MUST exist before we update the session
$stmt = $conn->prepare("SELECT id, session, term, session_end_date FROM sch_session WHERE session = :sess AND term = :term LIMIT 1");
$stmt->execute([':sess' => $session_name, ':term' => $term_name]);
$sess_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sess_data) {
    echo json_encode(['success' => false, 'message' => "The term \"$term_name\" for session \"$session_name\" does not exist in the system."]);
    exit;
}

// Only update session vars once we have a confirmed DB record
$_SESSION['active_session']    = $sess_data['session'];
$_SESSION['active_term']       = $sess_data['term'];
$_SESSION['active_session_id'] = $sess_data['id'];
$_SESSION['session_end_date']  = $sess_data['session_end_date'];

echo json_encode(['success' => true, 'message' => "Portal timeline switched to {$sess_data['term']} ({$sess_data['session']}). Refreshing..."]);
