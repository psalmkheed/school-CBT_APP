<?php
session_start();
header('Content-Type: application/json');
require '../connections/db.php';

if(isset($_SESSION['user_id'])) {
    $response["status"] = "success";
    $response["role"] = strtolower(trim($_SESSION['role'])); 
    echo json_encode($response);
    exit;
}

$response = [
    "status" => "error",
    "message" => ""
];

$user_id = trim($_POST['user_id'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($user_id) || empty($password)) {
    $response["message"] = "All fields are required";
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    $response["message"] = "Account does not exist";
    echo json_encode($response);
    exit;
}


if (!password_verify($password, $user->password)) {
    $response["message"] = "Invalid password";
    echo json_encode($response);
    exit;
}

if ($user->status == 0) {
    $response["message"] = "Your account has been deactivated. Please contact the administrator for assistance.";
    echo json_encode($response);
    exit;
}

    $_SESSION['user_id'] = $user->id;
    $_SESSION['username'] = $user->user_id;
    $_SESSION['first_name'] = $user->first_name;
    $_SESSION['last_name'] = $user->last_name;
    $_SESSION['role'] = $user->role;
    $_SESSION['show_welcome'] = true;

    // Fetch Class Assignment based on Role
    if ($user->role === 'student') {
        $_SESSION['class'] = $user->class;
    } elseif ($user->role === 'staff') {
        $class_stmt = $conn->prepare("SELECT class FROM class WHERE teacher_id = :id LIMIT 1");
        $class_stmt->execute([':id' => $user->id]);
        $_SESSION['class'] = $class_stmt->fetchColumn() ?: 'Unassigned';
    }
    session_write_close();

    $response["status"] = "success";
    $response["role"] = strtolower(trim($user->role));

    echo json_encode($response);

    exit;

