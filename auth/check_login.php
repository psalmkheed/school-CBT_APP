<?php
// auth/check_login.php — Checks if a user has an active login session
require '../connections/db.php';
header('Content-Type: application/json');

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    echo json_encode([
        'loggedIn' => true,
        'role'     => $_SESSION['role']
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
