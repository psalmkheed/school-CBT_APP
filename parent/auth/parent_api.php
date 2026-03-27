<?php
require '../../connections/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $first_name = trim($_POST['first_name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($first_name) || empty($surname) || empty($user_id) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    try {
        // Check if user_id (email) already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
        $stmt_check->execute([$user_id]);
        if ($stmt_check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email/Username already exists.']);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role = 'guardian';

        $stmt = $conn->prepare("
            INSERT INTO users (first_name, surname, user_id, password, role)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$first_name, $surname, $user_id, $hashed, $role]);

        if (function_exists('recordActivity')) {
            recordActivity($conn, "GUARDIAN_REGISTERED", "New parent/guardian registered: $user_id");
        }

        // TRIGGER: Welcome Email Notifications
        require_once '../../connections/mailer.php';
        $check_config = $conn->query("SELECT school_name, notify_welcome_email FROM school_config LIMIT 1");
        $config = $check_config->fetch(PDO::FETCH_ASSOC);
        
        if ($config && ($config['notify_welcome_email'] ?? 0) == 1) {
            $school_name = htmlspecialchars($config['school_name'] ?? 'School');
            $subject = "Welcome to the $school_name Parent Portal";
            $messageHTML = "
            <div style='font-family: Arial, sans-serif; background-color: #f6f9fc; padding: 30px;'>
                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                    <h2 style='color: #2563eb; margin-bottom: 15px'>Welcome, " . htmlspecialchars($first_name) . "!</h2>
                    <p style='color: #4b5563; font-size: 16px; line-height: 1.6;'>
                        Your parent/guardian account for <strong>$school_name</strong> has been created successfully. 
                        You can now log in using this email address to link your students and monitor their academic progress, financial records, and school activities.
                    </p>
                    <div style='margin-top: 25px; text-align: center'>
                        <a href='" . APP_URL . "auth/login.php' style='display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>Login to Portal</a>
                    </div>
                </div>
            </div>";
            $messageAlt = "Welcome, $first_name! Your parent account for $school_name has been created. You can now login using this email to link your students.";
            
            // Fire and forget, we don't necessarily want to break registration if STMP fails
            send_school_email($conn, $user_id, $subject, $messageHTML, $messageAlt);
        }

        echo json_encode(['status' => 'success', 'message' => 'Your parent account has been created successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Below actions require the user to be logged in as guardian
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guardian') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$guardian_id = $_SESSION['user_id']; // This is the actual DB primary key because we set it as $user->id during login

if ($action === 'link_child') {
    $student_reg_no = trim($_POST['student_id'] ?? '');

    if (empty($student_reg_no)) {
        echo json_encode(['status' => 'error', 'message' => 'Student ID is required.']);
        exit;
    }

    try {
        // 1. Find the student
        $stmt_stu = $conn->prepare("SELECT id, first_name, surname FROM users WHERE user_id = ? AND role = 'student'");
        $stmt_stu->execute([$student_reg_no]);
        $student = $stmt_stu->fetch(PDO::FETCH_OBJ);

        if (!$student) {
            echo json_encode(['status' => 'error', 'message' => 'No student found with that ID or Admission Number.']);
            exit;
        }

        // 2. Check if already linked
        $check_link = $conn->prepare("SELECT id FROM guardian_wards WHERE guardian_id = ? AND student_id = ?");
        $check_link->execute([$guardian_id, $student->id]);
        
        if ($check_link->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'This student is already linked to your account.']);
            exit;
        }

        // 3. Insert Link
        $insert = $conn->prepare("INSERT INTO guardian_wards (guardian_id, student_id) VALUES (?, ?)");
        $insert->execute([$guardian_id, $student->id]);

        if (function_exists('recordActivity')) {
            recordActivity($conn, "WARD_LINKED", "Guardian {$_SESSION['username']} linked student $student_reg_no");
        }

        echo json_encode(['status' => 'success', 'message' => htmlspecialchars($student->first_name . ' ' . $student->surname) . ' has been successfully linked to your profile!', 'name' => htmlspecialchars($student->first_name . ' ' . $student->surname)]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'unlink_child') {
    $ward_link_id = intval($_POST['link_id'] ?? 0);

    if ($ward_link_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid link record.']);
        exit;
    }

    try {
        $del = $conn->prepare("DELETE FROM guardian_wards WHERE id = ? AND guardian_id = ?");
        $del->execute([$ward_link_id, $guardian_id]);

        echo json_encode(['status' => 'success', 'message' => 'Student unlinked successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
