<?php
$directory = new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
$files = new RegexIterator($iterator, '/^.+\.(php|js|html)$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach($files as $file) {
    $path = $file[0];
    
    // Skip this script itself and vendor/node_modules if present
    if (strpos($path, 'replace_names.php') !== false) continue;
    if (strpos($path, '\\vendor\\') !== false) continue;
    if (strpos($path, '\\node_modules\\') !== false) continue;

    $originalContent = file_get_contents($path);
    if ($originalContent === false) continue;

    // Perform replacements securely
    $newContent = $originalContent;
    $newContent = str_replace('last_name', 'surname', $newContent);
    $newContent = str_replace('Last_name', 'Surname', $newContent);
    $newContent = str_replace('Last Name', 'Surname', $newContent);
    $newContent = str_replace('last name', 'surname', $newContent);
    $newContent = str_replace('LAST_NAME', 'SURNAME', $newContent);
    $newContent = str_replace('Last name', 'Surname', $newContent);

    if ($originalContent !== $newContent) {
        file_put_contents($path, $newContent);
        $count++;
        echo "Updated: $path\n";
    }
}
echo "\nTotal files updated: $count\n";
