<?php
header('Content-Type: text/plain');
require_once 'db_connect.php';

echo "Testing Supabase REST API connection...\n\n";

// Test 1: Read users
$url = SUPABASE_URL . '/rest/v1/users?select=username,role&limit=10';
$headers = [
    'apikey: ' . SUPABASE_KEY,
    'Authorization: Bearer ' . SUPABASE_KEY,
];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Response: $response\n\n";

// Test 2: Use $pdo shim
echo "Testing PDO shim...\n";
$stmt = $pdo->prepare("SELECT user_id, username, role FROM users WHERE username = :username AND role = :role LIMIT 1");
$stmt->execute([':username' => 'nipun', ':role' => 'admin']);
$user = $stmt->fetch();
var_dump($user);
?>
