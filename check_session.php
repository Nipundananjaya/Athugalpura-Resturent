<?php
// ============================================================
// SmartServe – Session Checker (AJAX Endpoint)
// File: check_session.php
// ============================================================

session_start();
header('Content-Type: application/json');
// Prevent caching so session checks always return fresh data
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: 0');
header('Pragma: no-cache');

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    echo json_encode([
        'logged_in' => true,
        'username'  => $_SESSION['username'],
        'role'      => $_SESSION['role']
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
?>
