<?php

header('Content-Type: application/json');
require __DIR__ . '/../auth/check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['success' => false, 'message' => 'Invalid request']);
      exit;
}

$session = trim($_POST['session'] ?? '');
$terms = $_POST['terms'] ?? [];

// Check if there is already an active session before creating a new one
$active_check = $conn->prepare("SELECT id FROM sch_session WHERE session_end_date >= CURRENT_DATE LIMIT 1");
$active_check->execute();
if ($active_check->rowCount() > 0) {
      echo json_encode(['success' => false, 'message' => 'Cannot create a new session while a term is still active.']);
      exit;
}

if (empty($session) || empty($terms)) {
      echo json_encode(['success' => false, 'message' => 'Session year and at least one term are required']);
      exit;
}

try {
      $conn->beginTransaction();

      $inserted_count = 0;
      $active_session_data = null;

      foreach ($terms as $index => $termData) {
            $term_name = trim($termData['term'] ?? '');
            $s_date = $termData['start_date'] ?? '';
            $e_date = $termData['end_date'] ?? '';

            if (empty($term_name) || empty($s_date) || empty($e_date)) continue;

            /* Duplicate check for this term in this session */
            $check = $conn->prepare("SELECT id FROM sch_session WHERE session = :session AND term = :term LIMIT 1");
            $check->execute([':session' => $session, ':term' => $term_name]);
            
            if ($check->rowCount() > 0) {
                  $conn->rollBack();
                  echo json_encode(['success' => false, 'message' => "The term '$term_name' already exists for session $session."]);
                  exit;
            }

            $stmt = $conn->prepare("
                INSERT INTO sch_session (session, term, session_start_date, session_end_date)
                VALUES (:session, :term, :session_start_date, :session_end_date)
            ");
            
            $stmt->execute([
                  ':session' => $session,
                  ':term' => $term_name,
                  ':session_start_date' => $s_date,
                  ':session_end_date' => $e_date
            ]);

            $lastId = $conn->lastInsertId();
            $inserted_count++;

            // Use the first term as the active one for the session, 
            // or better: find the one that fits current date
            if (!$active_session_data || (strtotime($s_date) <= time() && strtotime($e_date) >= time())) {
                  $active_session_data = [
                        'id' => $lastId,
                        'session' => $session,
                        'term' => $term_name
                  ];
            }
      }

      if ($inserted_count === 0) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'No valid terms were provided.']);
            exit;
      }

      $conn->commit();

      // Set global session variables
      if ($active_session_data) {
            $_SESSION['active_session'] = $active_session_data['session'];
            $_SESSION['active_term'] = $active_session_data['term'];
            $_SESSION['active_session_id'] = $active_session_data['id'];
      }

      echo json_encode([
            'success' => true,
            'message' => "Successfully created $inserted_count terms for session $session"
      ]);
      exit;

} catch (PDOException $e) {
      if ($conn->inTransaction()) $conn->rollBack();
      echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
      exit;
}
