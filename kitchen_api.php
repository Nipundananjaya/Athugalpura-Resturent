<?php
// ============================================================
// SmartServe – Kitchen API
// File: kitchen_api.php
// ============================================================

session_start();
header('Content-Type: application/json');

// Include database connection
require_once 'db_connect.php';

// Authentication Check: Only allow logged-in kitchen staff or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['kitchen', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

// 1. Fetch active orders ('pending', 'preparing', 'ready')
if ($action === 'fetch_active') {
    try {
        $query = "SELECT o.order_id, o.table_number, o.session_id, o.total_amount, o.status, o.order_date,
                         oi.order_item_id, oi.item_id, oi.quantity, oi.subtotal,
                         m.item_name, m.image_url as image_path
                  FROM orders o
                  JOIN order_items oi ON o.order_id = oi.order_id
                  JOIN menu_items m ON oi.item_id = m.item_id
                  WHERE o.status IN ('pending', 'preparing', 'ready')
                  ORDER BY o.table_number ASC, o.order_date ASC, o.order_id ASC";
        
        $stmt = $pdo->query($query);
        $rows = $stmt->fetchAll();
        
        $orders = [];
        foreach ($rows as $row) {
            $orderId = $row['order_id'];
            if (!isset($orders[$orderId])) {
                $orders[$orderId] = [
                    'order_id' => intval($row['order_id']),
                    'table_number' => intval($row['table_number']),
                    'session_id' => $row['session_id'] ? intval($row['session_id']) : null,
                    'total_amount' => floatval($row['total_amount']),
                    'status' => $row['status'],
                    'order_date' => $row['order_date'],
                    'items' => []
                ];
            }
            $orders[$orderId]['items'][] = [
                'item_name' => $row['item_name'],
                'quantity' => intval($row['quantity']),
                'image_path' => $row['image_path']
            ];
        }

        // Group orders by table_number + session for the kitchen display
        $tableGroups = [];
        foreach ($orders as $order) {
            $groupKey = $order['table_number'] . '_' . ($order['session_id'] ?? 'legacy');
            if (!isset($tableGroups[$groupKey])) {
                $tableGroups[$groupKey] = [
                    'table_number' => $order['table_number'],
                    'session_id' => $order['session_id'],
                    'orders' => []
                ];
            }
            $tableGroups[$groupKey]['orders'][] = $order;
        }
        
        echo json_encode([
            'success' => true,
            'data' => array_values($orders),
            'table_groups' => array_values($tableGroups)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. Update order status
if ($action === 'update_status') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $orderId = intval($input['order_id'] ?? 0);
    $newStatus = trim($input['status'] ?? '');
    
    if ($orderId <= 0 || empty($newStatus)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order ID or status.']);
        exit;
    }
    
    $allowedStatuses = ['pending', 'preparing', 'ready', 'served', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
        exit;
    }
    
    try {
        // Update query
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Order #$orderId status updated to $newStatus successfully."
            ]);
        } else {
            // Might be that order was already in this status, check if order exists
            $checkStmt = $pdo->prepare("SELECT order_id FROM orders WHERE order_id = ?");
            $checkStmt->execute([$orderId]);
            if ($checkStmt->fetch()) {
                echo json_encode([
                    'success' => true,
                    'message' => "Order #$orderId status remains unchanged."
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => "Order #$orderId not found."
                ]);
            }
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Default response for invalid action
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
