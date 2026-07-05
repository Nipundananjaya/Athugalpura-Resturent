<?php
$host     = 'aws-0-ap-southeast-2.pooler.supabase.com';
$dbname   = 'postgres';
$username = 'postgres.phheuvsnkllqxjkgoodh';
$password = 'Dananjaya400902#';

try {
    $pdo = new PDO("pgsql:host=$host;port=6543;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully to Pooler!\n";
} catch (PDOException $e) {
    echo "Pooler 6543 failed: " . $e->getMessage() . "\n";
}

try {
    $pdo = new PDO("pgsql:host=$host;port=5432;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully to Pooler on 5432!\n";
} catch (PDOException $e) {
    echo "Pooler 5432 failed: " . $e->getMessage() . "\n";
}

$directHost = 'db.phheuvsnkllqxjkgoodh.supabase.co';
try {
    $pdo = new PDO("pgsql:host=$directHost;port=5432;dbname=$dbname", "postgres", $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully to Direct DB!\n";
} catch (PDOException $e) {
    echo "Direct DB failed: " . $e->getMessage() . "\n";
}
?>
