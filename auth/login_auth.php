<?php
// ⚠️ db.php MUST be required first — it calls session_start()
// session_start() must run before ANY output or headers
require '../connections/db.php';
header('Content-Type: application/json');

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

// Temporarily set session ID for logging failed password
$_SESSION['user_id'] = $user->id;

if (!password_verify($password, $user->password)) {
    recordActivity($conn, 'LOGIN_FAILED', "Failed login attempt for User ID: {$user->user_id}", 'warning');
    unset($_SESSION['user_id']); // Remove temp session
    $response["message"] = "Invalid password";
    echo json_encode($response);
    exit;
}

if ($user->status == 0) {
    recordActivity($conn, 'LOGIN_BLOCKED', "Deactivated user {$user->user_id} tried to log in.", 'warning');
    unset($_SESSION['user_id']); // Remove temp session
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

recordActivity($conn, 'LOGIN', "User logged in successfully via {$_SERVER['HTTP_USER_AGENT']}");
    session_write_close();

    $response["status"] = "success";
    $response["role"] = strtolower(trim($user->role));

    echo json_encode($response);

    exit;

