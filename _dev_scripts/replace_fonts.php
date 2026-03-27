<?php
$directory = new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
$files = new RegexIterator($iterator, '/^.+\.(php|js|html|css)$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach($files as $file) {
    $path = $file[0];
    
    // Skip this script itself and vendor/node_modules if present
    if (strpos($path, 'replace_fonts.php') !== false) continue;
    if (strpos($path, '\\vendor\\') !== false) continue;
    if (strpos($path, '\\node_modules\\') !== false) continue;

    $originalContent = file_get_contents($path);
    if ($originalContent === false) continue;

    // Perform replacements securely
    $newContent = str_replace('font-black', 'font-semibold', $originalContent);

    if ($originalContent !== $newContent) {
        file_put_contents($path, $newContent);
        $count++;
        echo "Updated: $path\n";
    }
}
echo "\nTotal files updated: $count\n";
