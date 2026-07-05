<?php
// ============================================================
// SmartServe – Customer Order Placement API (JSON POST)
// File: order_api.php
// ============================================================

session_start();
header('Content-Type: application/json');

require_once 'db_connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Read the JSON request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    // Fallback to standard POST parameters if not JSON
    $data = $_POST;
}

$tableNumber = intval($data['table_number'] ?? 0);
$customerToken = $data['customer_token'] ?? 'Staff Order';
$cartItems = $data['items'] ?? [];

if ($tableNumber <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid table number. Please scan a valid QR code or select a table.']);
    exit;
}

if (empty($cartItems) || !is_array($cartItems)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Your cart is empty. Please add items to place an order.']);
    exit;
}

try {
    // Start database transaction
    $pdo->beginTransaction();

    // 0. Get or create an active session for this table
    $sessionStmt = $pdo->prepare("SELECT session_id FROM table_sessions WHERE table_number = ? AND status = 'active' LIMIT 1");
    $sessionStmt->execute([$tableNumber]);
    $sessionRow = $sessionStmt->fetch();

    if ($sessionRow) {
        $sessionId = intval($sessionRow['session_id']);
    } else {
        $createSession = $pdo->prepare("INSERT INTO table_sessions (table_number, status) VALUES (?, 'active')");
        $createSession->execute([$tableNumber]);
        $sessionId = $pdo->lastInsertId();
    }

    // 1. Insert into orders table with session_id and initial total_amount as 0.00
    $stmtOrder = $pdo->prepare("INSERT INTO orders (table_number, session_id, customer_token, total_amount, status) VALUES (?, ?, ?, 0.00, 'pending')");
    $stmtOrder->execute([$tableNumber, $sessionId, $customerToken]);
    $orderId = $pdo->lastInsertId();

    $totalAmount = 0.00;

    // 2. Loop through cart items, lookup real prices from the DB, and insert into order_items
    $stmtItemLookup = $pdo->prepare("SELECT price, is_available FROM menu_items WHERE item_id = ?");
    $stmtInsertOrderItem = $pdo->prepare("INSERT INTO order_items (order_id, item_id, quantity, subtotal) VALUES (?, ?, ?, ?)");

    foreach ($cartItems as $cartItem) {
        $itemId = intval($cartItem['id'] ?? 0);
        $quantity = intval($cartItem['qty'] ?? 0);

        if ($itemId <= 0 || $quantity <= 0) {
            throw new Exception("Invalid item ID or quantity in cart.");
        }

        // Retrieve actual item price and availability from DB
        $stmtItemLookup->execute([$itemId]);
        $dbItem = $stmtItemLookup->fetch();

        if (!$dbItem) {
            throw new Exception("Item with ID $itemId was not found in our menu.");
        }

        if (intval($dbItem['is_available']) === 0) {
            throw new Exception("One or more items in your cart are currently unavailable.");
        }

        $price = floatval($dbItem['price']);
        $subtotal = $price * $quantity;
        $totalAmount += $subtotal;

        // Insert individual item record
        $stmtInsertOrderItem->execute([$orderId, $itemId, $quantity, $subtotal]);
    }

    // 3. Update the total_amount in orders table including 10% tax (matching front-end presentation)
    $tax = $totalAmount * 0.10;
    $finalTotal = $totalAmount + $tax;

    $stmtUpdateOrder = $pdo->prepare("UPDATE orders SET total_amount = ? WHERE order_id = ?");
    $stmtUpdateOrder->execute([$finalTotal, $orderId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $orderId,
        'session_id' => $sessionId,
        'total_amount' => $finalTotal
    ]);

} catch (Exception $e) {
    // Rollback transaction on failure to keep DB clean
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to place order: ' . $e->getMessage()
    ]);
}
?>
