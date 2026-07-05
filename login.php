<?php
// ============================================================
// SmartServe – Login Handler (AJAX Endpoint)
// File: login.php
// ============================================================

// Start session to store user data after login
session_start();

// Set response type to JSON
header('Content-Type: application/json');
// Prevent caching of login responses
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: 0');
header('Pragma: no-cache');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Include database connection
require_once 'db_connect.php';

// Get the POST data (supports both form data and JSON body)
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    $role     = trim($input['role'] ?? '');
} else {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? '');
}

// Validate inputs
if (empty($username) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Validate role value
$allowed_roles = ['admin', 'waiter', 'kitchen'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected.']);
    exit;
}

try {
    // Use Prepared Statement to prevent SQL Injection
    $stmt = $pdo->prepare("SELECT user_id, username, password, role FROM users WHERE username = :username AND role = :role LIMIT 1");
    $stmt->execute([
        ':username' => $username,
        ':role'     => $role
    ]);

    $user = $stmt->fetch();

    if ($user) {
        // Check password
        // Since the admin password was stored as plain text ("admin123"),
        // we check both plain text and hashed passwords for compatibility
        $password_match = false;

        // First try: direct plain-text comparison (for initial admin user)
        if ($password === $user['password']) {
            $password_match = true;
        }
        // Second try: bcrypt hash comparison (for future hashed passwords)
        elseif (password_verify($password, $user['password'])) {
            $password_match = true;
        }

        if ($password_match) {
            // Login successful – store in session
            // Regenerate session id to prevent fixation
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Determine redirect URL based on role
            $redirect = '';
            switch ($user['role']) {
                case 'admin':
                    $redirect = 'admin.html';
                    break;
                case 'waiter':
                    $redirect = 'waiter.html';
                    break;
                case 'kitchen':
                    $redirect = 'kitchen.html';
                    break;
            }

            // Add cache-busting query param to ensure browser fetches latest dashboard
            $sep = (strpos($redirect, '?') !== false) ? '&' : '?';
            $redirect .= $sep . '_cb=' . time();

            echo json_encode([
                'success'  => true,
                'message'  => 'Login successful! Welcome, ' . $user['username'] . '.',
                'role'     => $user['role'],
                'redirect' => $redirect
            ]);
        } else {
            // Wrong password
            echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
        }
    } else {
        // No user found with that username + role
        echo json_encode(['success' => false, 'message' => 'No ' . $role . ' account found with that username.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
?>
