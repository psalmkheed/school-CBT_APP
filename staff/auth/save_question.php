<?php
header('Content-Type: application/json');
require '../../connections/db.php';
require '../../auth/check.php';

if ($user->role !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_POST['existing_id'] ?? '';
$exam_id = $_POST['exam_id'] ?? '';
$q_num = $_POST['question_number'] ?? '';
$text = trim($_POST['question_text'] ?? '');
$q_type = in_array($_POST['question_type'] ?? '', ['mcq', 'fill_blank']) ? $_POST['question_type'] : 'mcq';

// For fill_blank: use the fill_correct_answer as correct answer
if ($q_type === 'fill_blank') {
    $a = 'N/A';
    $b = 'N/A';
    $c = 'N/A';
    $d = 'N/A';
    $correct = trim($_POST['fill_correct_answer'] ?? $_POST['correct_answer'] ?? '');
} else {
    $a = trim($_POST['option_a'] ?? '');
    $b = trim($_POST['option_b'] ?? '');
    $c = trim($_POST['option_c'] ?? '');
    $d = trim($_POST['option_d'] ?? '');
    $correct = trim($_POST['correct_answer'] ?? '');
}

if (empty($exam_id) || empty($q_num) || empty($text) || empty($correct)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
    exit;
}

try {
    if (!empty($id)) {
        // Update
        $stmt = $conn->prepare("UPDATE questions SET 
            question_text = :text,
            option_a = :a,
            option_b = :b,
            option_c = :c,
            option_d = :d,
            correct_answer = :correct,
            question_type = :qtype
            WHERE id = :id AND exam_id = :exam_id");
        $stmt->execute([
            ':text' => $text,
            ':a' => $a,
            ':b' => $b,
            ':c' => $c,
            ':d' => $d,
            ':correct' => $correct,
            ':qtype' => $q_type,
            ':id' => $id,
            ':exam_id' => $exam_id
        ]);
    } else {
        // Double check if question number already exists for this exam
        $check = $conn->prepare("SELECT id FROM questions WHERE exam_id = :exam_id AND question_number = :q_num");
        $check->execute([':exam_id' => $exam_id, ':q_num' => $q_num]);
        $existing = $check->fetch();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE questions SET 
                question_text = :text,
                option_a = :a,
                option_b = :b,
                option_c = :c,
                option_d = :d,
                correct_answer = :correct,
                question_type = :qtype
                WHERE id = :id");
            $stmt->execute([
                ':text' => $text,
                ':a' => $a,
                ':b' => $b,
                ':c' => $c,
                ':d' => $d,
                ':correct' => $correct,
                ':qtype' => $q_type,
                ':id' => $existing['id']
            ]);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_number, question_text, option_a, option_b, option_c, option_d, correct_answer, question_type) 
                VALUES (:exam_id, :q_num, :text, :a, :b, :c, :d, :correct, :qtype)");
            $stmt->execute([
                ':exam_id' => $exam_id,
                ':q_num' => $q_num,
                ':text' => $text,
                ':a' => $a,
                ':b' => $b,
                ':c' => $c,
                ':d' => $d,
                ':correct' => $correct,
                ':qtype' => $q_type,
            ]);
        }
    }
    
    // Check if all questions are set
    $stmt = $conn->prepare("SELECT num_quest FROM exams WHERE id = :id");
    $stmt->execute([':id' => $exam_id]);
    $total_needed = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM questions WHERE exam_id = :id");
    $stmt->execute([':id' => $exam_id]);
    $total_set = $stmt->fetchColumn();

    if ($total_set >= $total_needed) {
        $stmt = $conn->prepare("UPDATE exams SET exam_status = 'ready' WHERE id = :id AND exam_status = 'set up'");
        $stmt->execute([':id' => $exam_id]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
