<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';

$response = [
      "status" => "error",
      "message" => ""
];

if (!in_array($_SESSION['role'], ['admin', 'super'])) {
      echo json_encode(["status" => "error", "message" => "Unauthorized"]);
      exit;
}



// Collect & trim inputs
$first_name = trim($_POST["first_name"] ?? '');
$surname = trim($_POST["surname"] ?? ''); // Using surname internally for backwards compatibility where hardcoded, or replace fully below
$other_name = trim($_POST["other_name"] ?? '');
$user_id = trim($_POST["user_id"] ?? '');
$class_name = $_POST["class"] ?? '';
$password = $_POST["password"] ?? '';
$confirm_password = $_POST["confirm_password"] ?? '';
$user_role = trim($_POST["user_role"] ?? '');

$gender = trim($_POST["gender"] ?? '');
$home_address = trim($_POST["home_address"] ?? '');
$parent_name = trim($_POST["parent_name"] ?? '');
$parent_phone = trim($_POST["parent_phone"] ?? '');
$parent_email = trim($_POST["parent_email"] ?? '');
$state_of_origin = trim($_POST["state_of_origin"] ?? '');
$admission_date = trim($_POST["admission_date"] ?? '');
$date_of_birth = trim($_POST["date_of_birth"] ?? '');

// Convert empty dates to null
if(empty($admission_date)) $admission_date = null;
if(empty($date_of_birth)) $date_of_birth = null;

// Validate required fields
if (
      empty($first_name) ||
      empty($surname) ||
      empty($user_id) ||
      empty($password) ||
      empty($confirm_password) ||
      empty($user_role)
) {
      $response["message"] = "Essential fields are required";
      echo json_encode($response);
      exit;
}

if ($user_role === 'student' && empty($class_name)) {
      $response["message"] = "Class is required for students";
      echo json_encode($response);
      exit;
}

// Check password match
if ($password !== $confirm_password) {
      $response["message"] = "Passwords do not match";
      echo json_encode($response);
      exit;
}

// Check if user already exists
$user_check = $conn->prepare("SELECT id FROM users WHERE user_id = :user_id");
$user_check->execute([':user_id' => $user_id]);

if ($user_check->fetch()) {
      $response["message"] = "User with User ID $user_id already exists";
      echo json_encode($response);
      exit;
}

// Handle Profile Photo Upload
$profile_photo_path = null;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
      $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
      $file_name = $_FILES['profile_photo']['name'];
      $file_tmp = $_FILES['profile_photo']['tmp_name'];
      $file_size = $_FILES['profile_photo']['size'];
      $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

      if (!in_array($ext, $allowed_ext)) {
            $response["message"] = "Invalid image format. Only JPG, PNG, WEBP allowed.";
            echo json_encode($response);
            exit;
      }
      
      if ($file_size > (2 * 1024 * 1024)) {
            $response["message"] = "Image size exceeds 2MB limit.";
            echo json_encode($response);
            exit;
      }

      $new_name = uniqid('profile_') . time() . '.' . $ext;
      $dest_dir = '../../uploads/profile_photos/';
      
      if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0777, true);
      }
      
      if (move_uploaded_file($file_tmp, $dest_dir . $new_name)) {
            $profile_photo_path = $new_name; // just store the specific filename, navbar.php handles prefix
      }
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("
INSERT INTO users (first_name, surname, other_name, user_id, class, password, role, gender, home_address, parent_name, parent_phone, parent_email, state_of_origin, admission_date, date_of_birth, profile_photo) 
VALUES (:first_name, :surname, :other_name, :user_id, :class, :password, :user_role, :gender, :home_address, :parent_name, :parent_phone, :parent_email, :state_of_origin, :admission_date, :date_of_birth, :profile_photo)
");

$stmt->execute([
      ':first_name' => $first_name,
      ':surname' => $surname,
      ':other_name' => $other_name,
      ':user_id' => $user_id,
      ':class' => $class_name,
      ':password' => $hashed_password,
      ':user_role' => $user_role,
      ':gender' => $gender,
      ':home_address' => $home_address,
      ':parent_name' => $parent_name,
      ':parent_phone' => $parent_phone,
      ':parent_email' => $parent_email,
      ':state_of_origin' => $state_of_origin,
      ':admission_date' => $admission_date,
      ':date_of_birth' => $date_of_birth,
      ':profile_photo' => $profile_photo_path
]);

recordActivity($conn, 'USER_REGISTER', "Admin created new $user_role: $first_name $surname ($user_id)");

// TRIGGER: Welcome Email Notifications
require_once '../../connections/mailer.php';
$check_config = $conn->query("SELECT school_name, notify_welcome_email FROM school_config LIMIT 1");
$config = $check_config->fetch(PDO::FETCH_ASSOC);

if ($config && ($config['notify_welcome_email'] ?? 0) == 1 && filter_var($user_id, FILTER_VALIDATE_EMAIL)) {
    $school_name = htmlspecialchars($config['school_name'] ?? 'The School');
    $role_label = ucfirst($user_role);
    $subject = "Welcome to $school_name ($role_label Portal)";
    
    $messageHTML = "
    <div style='font-family: Arial, sans-serif; background-color: #f6f9fc; padding: 30px;'>
        <div style='max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
            <h2 style='color: #16a34a; margin-bottom: 15px'>Welcome, " . htmlspecialchars($first_name) . "!</h2>
            <p style='color: #4b5563; font-size: 16px; line-height: 1.6;'>
                Your <strong>$role_label</strong> account for <strong>$school_name</strong> has been created successfully sequence by the system administrator.
            </p>
            <div style='background-color: #f3f4f6; border-left: 4px solid #16a34a; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                <p style='margin: 0 0 10px 0; color: #374151;'><strong>Your Login Credentials:</strong></p>
                <p style='margin: 0 0 5px 0; color: #4b5563;'><strong>User ID / Email:</strong> " . htmlspecialchars($user_id) . "</p>
                <p style='margin: 0; color: #4b5563;'><strong>Password:</strong> " . htmlspecialchars($password) . "</p>
            </div>
            <p style='color: #ef4444; font-size: 13px; font-weight: bold;'>Please change your password immediately after logging in for the first time.</p>
            <div style='margin-top: 25px; text-align: center'>
                <a href='" . APP_URL . "auth/login.php' style='display: inline-block; background-color: #16a34a; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>Login to Portal</a>
            </div>
        </div>
    </div>";

    $messageAlt = "Welcome, $first_name! Your $role_label account for $school_name has been created.\n\nLogin Credentials:\nUser ID: $user_id\nPassword: $password\n\nPlease login at: " . APP_URL . "auth/login.php and change your password immediately.";
    
    send_school_email($conn, $user_id, $subject, $messageHTML, $messageAlt);
}

$response["status"] = "success";
$response["message"] = "User registered successfully";
echo json_encode($response);
exit;
