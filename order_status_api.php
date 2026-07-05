<?php
// ============================================================
// SmartServe – Public Order Status API
// File: order_status_api.php
// ============================================================

header('Content-Type: application/json');
// Prevent caching of order status checks
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: 0');
header('Pragma: no-cache');

require_once 'db_connect.php';

$orderId = intval($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT order_id, table_number, total_amount, status, order_date FROM orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if ($order) {
        echo json_encode([
            'success' => true,
            'order_id' => intval($order['order_id']),
            'table_number' => intval($order['table_number']),
            'status' => $order['status'],
            'order_date' => $order['order_date']
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Order not found.'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
