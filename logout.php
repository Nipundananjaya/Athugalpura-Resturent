<?php
// ============================================================
// SmartServe – Logout Handler
// File: logout.php
// ============================================================

session_start();

// Destroy all session data
$_SESSION = [];
session_destroy();

// Redirect back to login page
header('Location: index.html');
exit;
?>
