<?php
header('Content-Type: application/json');
require '../../connections/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super', 'admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$import_type = $_POST['import_type'] ?? '';
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Please upload a valid CSV file']);
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$handle = fopen($file, "r");

if (!$handle) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to open file']);
    exit;
}

// Skip header
fgetcsv($handle);

$success_count = 0;
$error_count = 0;
$errors = [];

try {
    if ($import_type === 'users') {
        $stmt = $conn->prepare("INSERT INTO users (first_name, surname, user_id, password, role, class) VALUES (:f, :l, :u, :p, :r, :c) ON DUPLICATE KEY UPDATE first_name = :f, surname = :l");
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 5) continue; // Basic skip for empty/malformed rows
            
            $first_name = trim($data[0]);
            $surname = trim($data[1]);
            $user_id = trim($data[2]);
            $password = password_hash(trim($data[3]), PASSWORD_DEFAULT);
            $role = strtolower(trim($data[4]));
            $class = isset($data[5]) ? trim($data[5]) : '';

            try {
                $stmt->execute([
                    ':f' => $first_name,
                    ':l' => $surname,
                    ':u' => $user_id,
                    ':p' => $password,
                    ':r' => $role,
                    ':c' => $class
                ]);
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
                $errors[] = "User $user_id: " . $e->getMessage();
            }
        }
    } elseif ($import_type === 'questions') {
        $exam_id = $_POST['exam_id'] ?? 0;
        if (!$exam_id) {
            echo json_encode(['status' => 'error', 'message' => 'No target exam selected']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_number, question_text, option_a, option_b, option_c, option_d, correct_answer, question_type) VALUES (:eid, :qn, :txt, :oa, :ob, :oc, :od, :ans, :typ)");

        while (($data = fgetcsv($handle, 2000, ",")) !== FALSE) {
            if (count($data) < 8) continue;

            try {
                $stmt->execute([
                    ':eid' => $exam_id,
                    ':qn'  => $data[0],
                    ':txt' => $data[1],
                    ':oa'  => $data[2],
                    ':ob'  => $data[3],
                    ':oc'  => $data[4],
                    ':od'  => $data[5],
                    ':ans' => strtoupper($data[6]),
                    ':typ' => strtolower($data[7])
                ]);
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
                $errors[] = "Ques " . ($data[0] ?? '?') . ": " . $e->getMessage();
            }
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid import type']);
        exit;
    }

    fclose($handle);

    $msg = "Successfully imported $success_count records.";
    if ($error_count > 0) {
        $msg .= " Failed: $error_count. Check logs.";
    }

    echo json_encode([
        'status' => 'success',
        'message' => $msg,
        'details' => $errors
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
}
