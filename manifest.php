<?php
require 'connections/db.php';
header('Content-Type: application/json');

$stmt = $conn->prepare('SELECT * FROM school_config LIMIT 1');
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_OBJ);

$school_name = $config->school_name ?? 'School Portal';
$short_name = explode(' ', $school_name)[0];
$logo = $config->school_logo ?? '';
$primary_color = $config->school_primary ?? '#0084D1';

$manifest = [
    "name" => $school_name,
    "short_name" => $short_name,
    "start_url" => "/school_app/auth/login.php",
    "display" => "standalone",
    "display_override" => ["window-controls-overlay", "standalone", "minimal-ui"],
    "background_color" => "#ffffff",
    "theme_color" => $primary_color,
    "description" => "Complete School Management and CBT Portal",
    "orientation" => "portrait",
    "icons" => [
        [
            "src" => "/school_app/uploads/school_logo/" . $logo,
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "any maskable"
        ],
        [
            "src" => "/school_app/uploads/school_logo/" . $logo,
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "any maskable"
        ]
    ]
];

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
