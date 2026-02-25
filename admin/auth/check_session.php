<?php
header('Content-Type: application/json');
require __DIR__ . '/../auth/check.php';

$session = trim($_POST['session'] ?? '');
$term = trim($_POST['term'] ?? '');

if (empty($session) || empty($term)) {
      echo json_encode(['exists' => false]);
      exit;
}

$stmt = $conn->prepare("
    SELECT id 
    FROM sch_session 
    WHERE session = :session AND term = :term
    LIMIT 1
");
$stmt->execute([
      ':session' => $session,
      ':term' => $term
]);

echo json_encode([
      'exists' => $stmt->rowCount() > 0
]);
