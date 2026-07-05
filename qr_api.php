<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'fetch') {
    try {
        $stmt = $pdo->query("SELECT * FROM tables_qr ORDER BY table_number ASC");
        $qrs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $qrs]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'save') {
    $tableNum = $_POST['table_number'] ?? '';
    $qrLink = $_POST['qr_link'] ?? '';
    $imageData = $_POST['image_data'] ?? ''; // base64 encoded image

    if (empty($tableNum) || empty($qrLink) || empty($imageData)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        // Use absolute path for folder creation to prevent CWD issues
        $dir = __DIR__ . '/uploads/qrcodes';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Process base64 image
        $image_parts = explode(";base64,", $imageData);
        if (count($image_parts) == 2) {
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            
            $fileName = 'table_' . $tableNum . '.png';
            $absolutePath = $dir . '/' . $fileName;
            $relativeUrl = 'uploads/qrcodes/' . $fileName; // relative URL for DB
            
            file_put_contents($absolutePath, $image_base64);
            
            // Check if table number already exists
            $checkStmt = $pdo->prepare("SELECT id FROM tables_qr WHERE table_number = ?");
            $checkStmt->execute([$tableNum]);
            if ($checkStmt->rowCount() > 0) {
                // Update
                $updateStmt = $pdo->prepare("UPDATE tables_qr SET qr_link = ?, qr_image_path = ? WHERE table_number = ?");
                $updateStmt->execute([$qrLink, $relativeUrl, $tableNum]);
            } else {
                // Insert
                $insertStmt = $pdo->prepare("INSERT INTO tables_qr (table_number, qr_link, qr_image_path) VALUES (?, ?, ?)");
                $insertStmt->execute([$tableNum, $qrLink, $relativeUrl]);
            }
            
            echo json_encode(['success' => true, 'message' => 'QR Code saved successfully', 'image_path' => $relativeUrl]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid image data']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID is required']);
        exit;
    }

    try {
        // Get image path first to delete file
        $stmt = $pdo->prepare("SELECT qr_image_path FROM tables_qr WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if ($row && file_exists($row['qr_image_path'])) {
            unlink($row['qr_image_path']);
        }

        $delStmt = $pdo->prepare("DELETE FROM tables_qr WHERE id = ?");
        $delStmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'QR Code deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
