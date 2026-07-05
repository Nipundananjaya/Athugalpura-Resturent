<?php
$host = 'localhost';
$dbname = 'qr_restaurant_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$tablesQuery = $pdo->query("SHOW TABLES");
$tables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);

$sql = "";

foreach ($tables as $table) {
    // Schema
    $createTableQuery = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $mysqlCreate = $createTableQuery['Create Table'];
    
    // Very basic MySQL to Postgres schema conversion
    $pgCreate = preg_replace('/int\(\d+\)/i', 'INTEGER', $mysqlCreate);
    $pgCreate = preg_replace('/tinyint\(1\)/i', 'BOOLEAN', $pgCreate);
    $pgCreate = preg_replace('/varchar\((\d+)\)/i', 'VARCHAR($1)', $pgCreate);
    $pgCreate = preg_replace('/datetime/i', 'TIMESTAMP', $pgCreate);
    $pgCreate = preg_replace('/AUTO_INCREMENT/i', '', $pgCreate); // Will handle primary keys manually if needed, or use SERIAL
    $pgCreate = preg_replace('/ENGINE=InnoDB.*$/i', ';', $pgCreate);
    
    // Remove backticks
    $pgCreate = str_replace('`', '"', $pgCreate);
    
    // Data
    $dataQuery = $pdo->query("SELECT * FROM `$table`");
    $rows = $dataQuery->fetchAll(PDO::FETCH_ASSOC);
    
    $inserts = "";
    foreach ($rows as $row) {
        $cols = array_keys($row);
        $vals = array_values($row);
        
        $escapedVals = array_map(function($val) use ($pdo) {
            if ($val === null) return 'NULL';
            return $pdo->quote($val);
        }, $vals);
        
        $colStr = '"' . implode('", "', $cols) . '"';
        $valStr = implode(", ", $escapedVals);
        $inserts .= "INSERT INTO \"$table\" ($colStr) VALUES ($valStr);\n";
    }
    
    // Write out MySQL structure as comment, then inserts
    $sql .= "-- TABLE: $table\n";
    $sql .= $inserts . "\n";
}

file_put_contents('postgres_inserts.sql', $sql);
echo "Data inserts exported to postgres_inserts.sql\n";
?>
