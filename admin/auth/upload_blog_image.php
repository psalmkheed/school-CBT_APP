<?php
session_start();
header('Content-Type: application/json');

$uploadDir = '../../uploads/blogs/';
if (!is_dir($uploadDir))
      mkdir($uploadDir, 0777, true);

if (isset($_FILES['upload'])) {
      $file = $_FILES['upload'];
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

      if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => 'Invalid file type']]);
            exit;
      }

      if ($file['size'] > 2 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => ['message' => 'File too large']]);
            exit;
      }

      $newName = uniqid('blog_img_', true) . '.' . $ext;
      $destination = $uploadDir . $newName;

      if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode(['url' => '/school_app/uploads/blogs/' . $newName]);
      } else {
            http_response_code(500);
            echo json_encode(['error' => ['message' => 'Upload failed']]);
      }
} else {
      http_response_code(400);
      echo json_encode(['error' => ['message' => 'No file uploaded']]);
}