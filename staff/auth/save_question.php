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

if (empty($exam_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing Exam ID']);
    exit;
}
if (empty($q_num)) {
    echo json_encode(['success' => false, 'message' => 'Missing Question Number']);
    exit;
}
if ($text === '') {
    echo json_encode(['success' => false, 'message' => 'Question text cannot be empty']);
    exit;
}

if ($q_type === 'mcq') {
    if ($correct === '') {
        echo json_encode(['success' => false, 'message' => 'Please select a correct answer (A, B, C, or D)']);
        exit;
    }
    if ($a === '' || $b === '' || $c === '' || $d === '') {
        echo json_encode(['success' => false, 'message' => 'Please fill in all MCQ options']);
        exit;
    }
} else {
    // Fill in the blank
    if ($correct === '') {
        echo json_encode(['success' => false, 'message' => 'Please provide the correct answer for the blank']);
        exit;
    }
}

// Handle File Upload
$question_image = null;
$remove_existing = (int) ($_POST['remove_existing_image'] ?? 0);

// Fetch current image if updating
$current_image = null;
if (!empty($id)) {
    $img_stmt = $conn->prepare("SELECT question_image FROM questions WHERE id = :id");
    $img_stmt->execute([':id' => $id]);
    $current_image = $img_stmt->fetchColumn();
} elseif (!empty($exam_id) && !empty($q_num)) {
    $img_stmt = $conn->prepare("SELECT question_image FROM questions WHERE exam_id = :exam_id AND question_number = :q_num");
    $img_stmt->execute([':exam_id' => $exam_id, ':q_num' => $q_num]);
    $current_image = $img_stmt->fetchColumn();
}

$question_image = $current_image;

if ($remove_existing == 1) {
    if ($current_image && file_exists("../../uploads/questions/" . $current_image)) {
        unlink("../../uploads/questions/" . $current_image);
    }
    $question_image = null;
}

if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "../../uploads/questions/";
    $file_ext = strtolower(pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (in_array($file_ext, $allowed_ext)) {
        // Delete old image if it exists
        if ($current_image && file_exists($upload_dir . $current_image)) {
            unlink($upload_dir . $current_image);
        }

        $new_filename = "q_" . $exam_id . "_" . $q_num . "_" . time() . "." . $file_ext;
        if (move_uploaded_file($_FILES['question_image']['tmp_name'], $upload_dir . $new_filename)) {
            $question_image = $new_filename;
        }
    }
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
            question_type = :qtype,
            question_image = :qimage
            WHERE id = :id AND exam_id = :exam_id");
        $stmt->execute([
            ':text' => $text,
            ':a' => $a,
            ':b' => $b,
            ':c' => $c,
            ':d' => $d,
            ':correct' => $correct,
            ':qtype' => $q_type,
            ':qimage' => $question_image,
            ':id' => $id,
            ':exam_id' => $exam_id
        ]);
    } else {
        // Double check if question number already exists for this exam
        $check = $conn->prepare("SELECT id FROM questions WHERE exam_id = :exam_id AND question_number = :q_num");
        $check->execute([':exam_id' => $exam_id, ':q_num' => $q_num]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $conn->prepare("UPDATE questions SET 
                question_text = :text,
                option_a = :a,
                option_b = :b,
                option_c = :c,
                option_d = :d,
                correct_answer = :correct,
                question_type = :qtype,
                question_image = :qimage
                WHERE id = :id");
            $stmt->execute([
                ':text' => $text,
                ':a' => $a,
                ':b' => $b,
                ':c' => $c,
                ':d' => $d,
                ':correct' => $correct,
                ':qtype' => $q_type,
                ':qimage' => $question_image,
                ':id' => $existing['id']
            ]);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_number, question_text, option_a, option_b, option_c, option_d, correct_answer, question_type, question_image) 
                VALUES (:exam_id, :q_num, :text, :a, :b, :c, :d, :correct, :qtype, :qimage)");
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
                ':qimage' => $question_image,
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
