<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require 'db_connect.php';

$action = $_REQUEST['action'] ?? '';

// Fetch all staff members
if ($action === 'fetch') {
    try {
        $stmt = $pdo->query("SELECT staff_id, full_name, id_number, phone_number, role FROM staff ORDER BY staff_id DESC");
        $staff = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $staff]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Register Staff
if ($action === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if (empty($full_name) || empty($id_number) || empty($phone_number) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    $allowed_roles = ['admin', 'waiter', 'kitchen'];
    if (!in_array($role, $allowed_roles)) {
        echo json_encode(['success' => false, 'message' => 'Invalid role.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check if ID number already exists
        $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE id_number = ?");
        $stmt->execute([$id_number]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'NIC / ID Number already exists.']);
            exit;
        }
        
        // Check if username (full_name) already exists
        $stmtUser = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmtUser->execute([$full_name]);
        if ($stmtUser->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'User with this name already exists. Please use a unique name.']);
            exit;
        }

        // Insert into staff table
        $stmt = $pdo->prepare("INSERT INTO staff (full_name, id_number, phone_number, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $id_number, $phone_number, $role]);
        $staff_id = $pdo->lastInsertId();

        // Insert into users table
        $hashed_password = password_hash($id_number, PASSWORD_BCRYPT);
        $stmtUser = $pdo->prepare("INSERT INTO users (username, password, role, staff_id) VALUES (?, ?, ?, ?)");
        $stmtUser->execute([$full_name, $hashed_password, $role, $staff_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Staff registered successfully! Username is their Name, Password is the NIC.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
