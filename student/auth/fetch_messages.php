<?php
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

header('Content-Type: application/json');

// Fetch messages newer than the given last_id OR changed after given timestamp
$last_id = (int)($_GET['last_id'] ?? 0);
$changed_since = $_GET['changed_since'] ?? null;

$query = "
    SELECT m.id, m.message, m.sent_at, m.attachment, m.attachment_type, m.is_deleted, m.is_edited, m.updated_at,
           u.first_name, u.surname, u.user_id,
           m.user_id AS sender_id
    FROM class_messages m
    JOIN users u ON m.user_id = u.id
    WHERE m.class = :class 
";

$params = [':class' => $user->class];

if ($changed_since) {
    $query .= " AND (m.id > :last_id OR m.updated_at > :changed_since)";
    $params[':last_id'] = $last_id;
    $params[':changed_since'] = $changed_since;
} else {
    $query .= " AND m.id > :last_id";
    $params[':last_id'] = $last_id;
}

$query .= " ORDER BY m.sent_at ASC LIMIT 100";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get current server time for the next poll
$serverTime = $conn->query("SELECT NOW()")->fetchColumn();

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'my_id' => $user->id,
    'server_time' => $serverTime
]);
