<?php
require __DIR__ . '/../connections/db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}auth/login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);

/** @var stdClass|false $user */
$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    session_destroy();
    header("Location: {$base}auth/login.php");
    exit();
}

// Maintenance Mode Enforcement 
if (!in_array($user->role, ['super', 'admin'])) {
    $maintenance_check = $conn->query("SELECT maintenance_mode FROM school_config LIMIT 1");
    if ($maintenance_check && $maintenance_check->fetchColumn() == 1) {
        header("Location: {$base}maintenance.php");
        exit();
    }
}

// Make user available to pages
$_SESSION['role'] = $user->role;
$_SESSION['first_name'] = $user->first_name;
$_SESSION['surname'] = $user->surname;
$_SESSION['username'] = $user->user_id;

// Also refresh class if it's a student
if ($user->role === 'student' && !empty($user->class)) {
    $_SESSION['class'] = $user->class;
} elseif ($user->role === 'staff') {
    $class_stmt = $conn->prepare("SELECT class FROM class WHERE teacher_id = :id LIMIT 1");
    $class_stmt->execute([':id' => $user->id]);
    $_SESSION['class'] = $class_stmt->fetchColumn() ?: 'Unassigned';
}

