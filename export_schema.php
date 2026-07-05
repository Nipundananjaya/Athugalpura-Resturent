<?php
$pdo = new PDO("mysql:host=localhost;dbname=qr_restaurant_db;charset=utf8mb4", 'root', '');
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$output = "";
foreach($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC)['Create Table'];
    $output .= $create . "\n\n";
}
file_put_contents('mysql_schema.txt', $output);
echo "Schema exported to mysql_schema.txt";
?>
