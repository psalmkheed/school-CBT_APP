<?php
require __DIR__ . '/../connections/db.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
      echo json_encode([
            'status' => 'error',
            'message' => 'Invalid request'
      ]);

      exit;
}

if(
      empty($_FILES['school_logo']['name']) ||
      empty($_POST['school_name']) ||
      empty($_POST['school_tagline']) ||
      empty($_POST['school_primary_color']) ||
      empty($_POST['school_secondary_color'])
){
      echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required'
      ]);
      exit;
}

$school_name = trim($_POST['school_name']);
$school_tagline = trim($_POST['school_tagline']);
$school_primary_color = trim($_POST['school_primary_color']);
$school_secondary_color = trim($_POST['school_secondary_color']);

/* File upload handling */
$uploadDir = '../uploads/school_logo/';
if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
}

$fileName = $_FILES['school_logo']['name'];
$tmpName = $_FILES['school_logo']['tmp_name'];
$fileSize = $_FILES['school_logo']['size'];
$fileError = $_FILES['school_logo']['error'];

$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid image type']);
      exit;
}

if ($fileSize > 2 * 1024 * 1024) {
      echo json_encode(['status' => 'error', 'message' => 'Image must be less than 2MB']);
      exit;
}

if ($fileError !== 0) {
      echo json_encode(['status' => 'error', 'message' => 'Upload error']);
      exit;
}

/* Rename file to avoid duplicates */
$newName = uniqid('school_logo', true) . '.' . $ext;
$destination = $uploadDir . $newName;

if (!move_uploaded_file($tmpName, $destination)) {
      echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
      exit;
}

/* Save to database */
$stmt = $conn->prepare("INSERT INTO school_config (school_logo, school_name, school_tagline, school_primary, school_secondary) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
      $newName, 
      $school_name, 
      $school_tagline, 
      $school_primary_color, 
      $school_secondary_color
     ]);

echo json_encode([
      'status' => 'success',
      'message' => 'School configured successfully'
]);
