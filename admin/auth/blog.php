<?php
session_start();
header('Content-Type: application/json');
require '../../connections/db.php';
require_once '../../connections/functions.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super'])) {
      echo json_encode(["status" => "error", "message" => "Unauthorized"]);
      exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode([
            'status' => 'error',
            'message' => 'Invalid request'
      ]);
      exit;
}

/* Validate fields */
if (
      empty($_POST['blog_title']) ||
      empty($_POST['blog_category']) ||
      empty($_POST['blog_message'])
) {
      echo json_encode([
            'status' => 'error',
            'message' => 'All fields (except image) are required'
      ]);
      exit;
}

$blog_title = trim($_POST['blog_title']);
$blog_category = trim($_POST['blog_category']);
$blog_message = $_POST['blog_message'];
$newName = null;

/* File upload handling (only if provided) */
if (isset($_FILES['blog_image']) && !empty($_FILES['blog_image']['name'])) {
      $uploadDir = '../../uploads/blogs/';
      if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
      }

      $fileName = $_FILES['blog_image']['name'];
      $tmpName = $_FILES['blog_image']['tmp_name'];
      $fileSize = $_FILES['blog_image']['size'];
      $fileError = $_FILES['blog_image']['error'];

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
      $newName = uniqid('blog_', true) . '.' . $ext;
      $destination = $uploadDir . $newName;

      if (!move_uploaded_file($tmpName, $destination)) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
            exit;
      }
}

/* Save to database */
$stmt = $conn->prepare("INSERT INTO blog (blog_title, blog_category, blog_message, blog_image) VALUES (?, ?, ?, ?)");
$stmt->execute([$blog_title, $blog_category, $blog_message, $newName]);

recordActivity($conn, 'BLOG_PUBLISH', "Admin published a new blog post: '$blog_title'");

echo json_encode([
      'status' => 'success',
      'message' => 'Blog published successfully'
]);
