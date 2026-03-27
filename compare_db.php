<?php
$host = 'localhost';
$user = 'blaqdev';
$pass = 'codingscience';

try {
    $conn = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db_target = 'edu_app';
    $db_reference = 'edu_app_clean';

    function getDbSchema($conn, $dbName) {
        $stmt = $conn->prepare("
            SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = :db
            ORDER BY TABLE_NAME, ORDINAL_POSITION
        ");
        $stmt->execute(['db' => $dbName]);
        $schema = [];
        foreach ($stmt->fetchAll() as $row) {
            $table = $row['TABLE_NAME'];
            if (!isset($schema[$table])) {
                $schema[$table] = [];
            }
            $schema[$table][$row['COLUMN_NAME']] = $row;
        }
        return $schema;
    }

    $targetSchema = getDbSchema($conn, $db_target);
    $refSchema = getDbSchema($conn, $db_reference);

    $missingInTargetParams = [];
    $extraInTargetParams = [];
    $modifiedParams = [];

    // Compare Reference to Target (missing in Target)
    foreach ($refSchema as $table => $columns) {
        if (!isset($targetSchema[$table])) {
            $missingInTargetParams[] = "Missing TABLE in $db_target: $table";
            continue;
        }

        foreach ($columns as $colName => $refCol) {
            if (!isset($targetSchema[$table][$colName])) {
                $missingInTargetParams[] = "Missing COLUMN in $db_target.$table: $colName (" . $refCol['COLUMN_TYPE'] . ")";
            } else {
                $tgtCol = $targetSchema[$table][$colName];
                if ($tgtCol['COLUMN_TYPE'] !== $refCol['COLUMN_TYPE']) {
                    $modifiedParams[] = "Modified COLUMN TYPE in $db_target.$table: $colName (Ref: {$refCol['COLUMN_TYPE']} vs Target: {$tgtCol['COLUMN_TYPE']})";
                }
            }
        }
    }

    // Compare Target to Reference (extra in Target)
    foreach ($targetSchema as $table => $columns) {
        if (!isset($refSchema[$table])) {
            $extraInTargetParams[] = "Extra TABLE in $db_target: $table (not in $db_reference)";
            continue;
        }

        foreach ($columns as $colName => $tgtCol) {
            if (!isset($refSchema[$table][$colName])) {
                $extraInTargetParams[] = "Extra COLUMN in $db_target.$table: $colName (" . $tgtCol['COLUMN_TYPE'] . ")";
            }
        }
    }

    echo "=== SCHEMA COMPARISON RESULT ===\n";
    echo "\n[ MISSING in $db_target compared to $db_reference ]\n";
    if (empty($missingInTargetParams)) echo "None.\n";
    else echo implode("\n", $missingInTargetParams) . "\n";

    echo "\n[ EXTRA in $db_target compared to $db_reference ]\n";
    if (empty($extraInTargetParams)) echo "None.\n";
    else echo implode("\n", $extraInTargetParams) . "\n";

    echo "\n[ MODIFIED (Type changes) ]\n";
    if (empty($modifiedParams)) echo "None.\n";
    else echo implode("\n", $modifiedParams) . "\n";

} catch (PDOException $e) {
    die("Error connecting to DB: " . $e->getMessage() . "\n");
}
