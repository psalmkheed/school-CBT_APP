<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../connections/db.php';
require_once __DIR__ . '/../../auth/check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$exam_id = isset($_POST['exam_id']) ? (int)$_POST['exam_id'] : 0;
$answers_json = $_POST['answers'] ?? '{}';
$answers = json_decode($answers_json, true);

if (!$exam_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid exam ID']);
    exit;
}

// Check if already taken to prevent double submission
$check_stmt = $conn->prepare("SELECT id FROM exam_results WHERE exam_id = :exam_id AND user_id = :user_id");
$check_stmt->execute([':exam_id' => $exam_id, ':user_id' => $user->id]);
if ($check_stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already submitted this exam.']);
    exit;
}

try {
    $conn->beginTransaction();

    // Fetch correct answers + question type
    $stmt = $conn->prepare("SELECT id, correct_answer, question_type FROM questions WHERE exam_id = :exam_id");
    $stmt->execute([':exam_id' => $exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_questions = count($questions);
    $score = 0;

    foreach ($questions as $q) {
        $q_id = $q['id'];
        $qtype = $q['question_type'] ?? 'mcq';

        if ($qtype === 'fill_blank') {
            // Case-insensitive, trimmed text comparison
            $correct_ans = strtolower(trim($q['correct_answer']));
            $student_ans = strtolower(trim($answers[$q_id] ?? ''));
        } else {
            // MCQ: single letter comparison
            $correct_ans = strtoupper($q['correct_answer']);
            $student_ans = isset($answers[$q_id]) ? strtoupper($answers[$q_id]) : '';
        }

        if ($student_ans !== '' && $student_ans === $correct_ans) {
            $score++;
        }
    }

    $percentage = ($total_questions > 0) ? ($score / $total_questions) * 100 : 0;

    $time_taken = isset($_POST['time_taken']) ? (int)$_POST['time_taken'] : 0;

    // Save result
    $insert_stmt = $conn->prepare("
        INSERT INTO exam_results (exam_id, user_id, score, total_questions, percentage, student_answers, time_taken, taken_at) 
        VALUES (:exam_id, :user_id, :score, :total, :percent, :answers, :time, NOW())
    ");
    
    $insert_stmt->execute([
        ':exam_id' => $exam_id,
        ':user_id' => $user->id,
        ':score' => $score,
        ':total' => $total_questions,
        ':percent' => $percentage,
        ':answers' => $answers_json,
        ':time' => $time_taken
    ]);

    recordActivity($conn, 'EXAM_SUBMIT', "Student submitted Exam ID: $exam_id (Score: $score/$total_questions)");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'score' => $score,
        'total' => $total_questions,
        'percentage' => $percentage
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
