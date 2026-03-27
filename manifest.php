<?php
require 'connections/db.php';
header('Content-Type: application/json');

$stmt = $conn->prepare('SELECT * FROM school_config LIMIT 1');
$stmt->execute();
$config = $stmt->fetch(PDO::FETCH_OBJ);

$school_name = $config->school_name ?? 'School Portal';
$short_name = explode(' ', $school_name)[0] ?? 'Portal';
$theme_color = $config->pwa_theme_color ?? ($config->school_primary ?? '#0084D1');
$bg_color = $config->pwa_bg_color ?? '#ffffff';
$display = $config->pwa_display ?? 'standalone';

// Handle icon logic
$pwa_icon = '';
if (!empty($config->pwa_icon)) {
    $pwa_icon = ltrim($config->pwa_icon, '/');
} else {
    // fallback to school_logo if missing
    $fallback = $config->school_logo ?? '';
    // Strip leading slash for relative asset embedding
    $pwa_icon = ltrim($fallback, '/');
}

$base_path = parse_url(APP_URL, PHP_URL_PATH) ?: '/';

$manifest = [
    "name" => $school_name,
    "short_name" => $short_name,
    "start_url" => rtrim($base_path, '/') . "/index.php",
    "scope" => rtrim($base_path, '/') . "/",
    "display" => $display,
    "background_color" => $bg_color,
    "theme_color" => $theme_color,
    "description" => $school_name . " Application Portal",
    "orientation" => "portrait",
    "icons" => [
        [
            "src" => rtrim($base_path, '/') . "/" . $pwa_icon,
            "sizes" => "192x192",
            "type" => "image/png",
            "purpose" => "any maskable"
        ],
        [
            "src" => rtrim($base_path, '/') . "/" . $pwa_icon,
            "sizes" => "512x512",
            "type" => "image/png",
            "purpose" => "any maskable"
        ]
    ]
];

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
