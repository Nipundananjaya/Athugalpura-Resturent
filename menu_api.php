<?php
session_start();
header('Content-Type: application/json');

require 'db_connect.php';

$action = $_REQUEST['action'] ?? '';

// Fetch all menu items (available to public/guests and admins)
if ($action === 'fetch') {
    try {
        // Fetch all items to allow frontend to show 'Sold Out' status for unavailable ones
        $query = "SELECT m.item_id as id, m.item_name as name, c.category_name as category, 
                         m.price, m.description, m.image_url as image_path, m.is_available 
                  FROM menu_items m 
                  LEFT JOIN categories c ON m.category_id = c.category_id 
                  ORDER BY m.item_id DESC";
        $stmt = $pdo->query($query);
        $items = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $items]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Check authentication for all other administrative actions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Toggle availability
if ($action === 'toggle_availability') {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 1;

    try {
        $stmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE item_id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
    }
    exit;
}

// Delete item
if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE item_id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Item deleted successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete item: ' . $e->getMessage()]);
    }
    exit;
}

// Add or Edit item
if ($action === 'add' || $action === 'edit') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $category_name = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? 0;
    $description = $_POST['description'] ?? '';
    
    if (empty($name) || empty($category_name) || empty($price)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }

    try {
        // Find or create category
        $stmtCat = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $stmtCat->execute([$category_name]);
        $catRow = $stmtCat->fetch();
        
        if ($catRow) {
            $category_id = $catRow['category_id'];
        } else {
            // Insert new category
            $stmtInsertCat = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmtInsertCat->execute([$category_name]);
            $category_id = $pdo->lastInsertId();
        }

        $image_url = null;

        // Handle image upload if a file was provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileInfo = pathinfo($_FILES['image']['name']);
            $ext = strtolower($fileInfo['extension']);
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $fileName = uniqid('menu_') . '.' . $ext;
                $destination = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image_url = 'uploads/' . $fileName;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid image format.']);
                exit;
            }
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, item_name, description, price, image_url, is_available) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$category_id, $name, $description, $price, $image_url]);
            echo json_encode(['success' => true, 'message' => 'Item added successfully.']);
        } else {
            // Edit
            if ($image_url) {
                $stmt = $pdo->prepare("UPDATE menu_items SET item_name = ?, category_id = ?, price = ?, description = ?, image_url = ? WHERE item_id = ?");
                $stmt->execute([$name, $category_id, $price, $description, $image_url, $id]);
            } else {
                // Update without changing image
                $stmt = $pdo->prepare("UPDATE menu_items SET item_name = ?, category_id = ?, price = ?, description = ? WHERE item_id = ?");
                $stmt->execute([$name, $category_id, $price, $description, $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Item updated successfully.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
