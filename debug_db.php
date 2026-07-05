<?php
// Debug script – check users table structure and data
// DELETE THIS FILE after debugging!

header('Content-Type: text/html; charset=utf-8');
require_once 'db_connect.php';

echo "<h2>🔍 Database Debug Info</h2>";

// 1. Check table structure
echo "<h3>📋 Users Table Columns:</h3>";
try {
    $cols = $pdo->query("DESCRIBE users")->fetchAll();
    echo "<table border='1' cellpadding='8'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($cols as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// 2. Show all users
echo "<h3>👥 All Users in Database:</h3>";
try {
    $users = $pdo->query("SELECT * FROM users")->fetchAll();
    if (count($users) == 0) {
        echo "<p style='color:red'>⚠️ No users found in the table!</p>";
    } else {
        echo "<table border='1' cellpadding='8'><tr>";
        foreach (array_keys($users[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $val) {
                echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr><p><em>Delete this file after debugging!</em></p>";
?>
