<?php
require '../../connections/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: {$base}auth/login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);

$user = $stmt->fetch(PDO::FETCH_OBJ);

if (!$user) {
    session_destroy();
    header("Location: {$base}auth/login.php");
    exit();
}

// Make user available to pages
$_SESSION['role'] = $user->role;
