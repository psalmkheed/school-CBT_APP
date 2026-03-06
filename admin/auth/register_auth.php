<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';

$response = [
      "status" => "error",
      "message" => ""
];

if ($_SESSION['role'] !== 'admin') {
      echo json_encode(["status" => "error", "message" => "Unauthorized"]);
      exit;
}



// Collect & trim inputs
$first_name = trim($_POST["first_name"] ?? '');
$last_name = trim($_POST["last_name"] ?? '');
$user_id = trim($_POST["user_id"] ?? '');
$class_name = $_POST["class"] ?? '';
$password = $_POST["password"] ?? '';
$confirm_password = $_POST["confirm_password"] ?? '';
$user_role = trim($_POST["user_role"] ?? '');

// Validate required fields
if (
      empty($first_name) ||
      empty($last_name) ||
      empty($user_id) ||
      empty($class_name) ||
      empty($password) ||
      empty($confirm_password) ||
      empty($user_role)
) {
      $response["message"] = "All fields are required";
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

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("
INSERT INTO users (first_name, last_name, user_id, class, password, role) VALUES (:first_name, :last_name, :user_id, :class, :password, :user_role)
");

$stmt->execute([
      ':first_name' => $first_name,
      ':last_name' => $last_name,
      ':user_id' => $user_id,
      ':class' => $class_name,
      ':password' => $hashed_password,
      ':user_role' => $user_role
]);

recordActivity($conn, 'USER_REGISTER', "Admin created new $user_role: $first_name $last_name ($user_id)");

$response["status"] = "success";
$response["message"] = "User registered successfully";
echo json_encode($response);
exit;
