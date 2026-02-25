<?php
session_start();
require __DIR__ . '/../connections/db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: /school_app/auth/login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);

$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    session_destroy();
    header("Location: /school_app/auth/login.php");
    exit();
}

// Make user available to pages
$_SESSION['role'] = $user->role;
$_SESSION['first_name'] = $user->first_name;
$_SESSION['last_name'] = $user->last_name;
$_SESSION['username'] = $user->user_id;

// Also refresh class if it's a student
if ($user->role === 'student' && !empty($user->class)) {
    $_SESSION['class'] = $user->class;
} elseif ($user->role === 'staff') {
    $class_stmt = $conn->prepare("SELECT class FROM class WHERE teacher_id = :id LIMIT 1");
    $class_stmt->execute([':id' => $user->id]);
    $_SESSION['class'] = $class_stmt->fetchColumn() ?: 'Unassigned';
}
