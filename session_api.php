<?php
// ============================================================
// SmartServe – Table Session API
// File: session_api.php
// Handles: get/create session, close session, bill generation
// ============================================================

header('Content-Type: application/json');
require_once 'db_connect.php';

$action = $_REQUEST['action'] ?? '';

// ─────────────────────────────────────────────────────────────
// ACTION: get_or_create
// Called by order_api.php (and customer/waiter) to find or open
// an active session for a given table.
// Accepts: table_number (GET or POST)
// Returns: session_id
// ─────────────────────────────────────────────────────────────
if ($action === 'get_or_create') {
    $tableNumber = intval($_REQUEST['table_number'] ?? 0);

    if ($tableNumber <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid table number.']);
        exit;
    }

    try {
        // Try to find an active session for this table
        $stmt = $pdo->prepare("SELECT session_id FROM table_sessions WHERE table_number = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$tableNumber]);
        $row = $stmt->fetch();

        if ($row) {
            // Re-use existing session
            echo json_encode(['success' => true, 'session_id' => intval($row['session_id']), 'is_new' => false]);
        } else {
            // Create new session
            $insertStmt = $pdo->prepare("INSERT INTO table_sessions (table_number, status) VALUES (?, 'active')");
            $insertStmt->execute([$tableNumber]);
            $newSessionId = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'session_id' => intval($newSessionId), 'is_new' => true]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// ACTION: get_table_bill
// Returns aggregated bill for ALL orders in a session.
// Accepts: session_id  OR  table_number (finds active session)
// Returns: session info, all orders, per-order totals, grand total
// ─────────────────────────────────────────────────────────────
if ($action === 'get_table_bill') {
    session_start();
    // Only waiter/admin can generate bills
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['waiter', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $sessionId   = intval($_REQUEST['session_id'] ?? 0);
    $tableNumber = intval($_REQUEST['table_number'] ?? 0);

    try {
        // Resolve session_id from table_number if not provided
        if ($sessionId <= 0 && $tableNumber > 0) {
            $stmt = $pdo->prepare("SELECT session_id FROM table_sessions WHERE table_number = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$tableNumber]);
            $row = $stmt->fetch();
            if ($row) {
                $sessionId = intval($row['session_id']);
            }
        }

        if ($sessionId <= 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No active session found for this table.']);
            exit;
        }

        // Fetch session info
        $sesStmt = $pdo->prepare("SELECT * FROM table_sessions WHERE session_id = ?");
        $sesStmt->execute([$sessionId]);
        $session = $sesStmt->fetch();

        if (!$session) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Session not found.']);
            exit;
        }

        // Fetch all orders + items in this session
        $query = "SELECT o.order_id, o.table_number, o.customer_token, o.total_amount, o.status,
                         o.order_date, o.bill_requested,
                         oi.order_item_id, oi.item_id, oi.quantity, oi.subtotal,
                         m.item_name, m.price
                  FROM orders o
                  LEFT JOIN order_items oi ON o.order_id = oi.order_id
                  LEFT JOIN menu_items m ON oi.item_id = m.item_id
                  WHERE o.session_id = ?
                    AND o.status NOT IN ('cancelled')
                  ORDER BY o.order_id ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll();

        // Group rows into orders
        $orders = [];
        foreach ($rows as $row) {
            $oid = $row['order_id'];
            if (!isset($orders[$oid])) {
                $orders[$oid] = [
                    'order_id'       => intval($oid),
                    'customer_token' => $row['customer_token'],
                    'total_amount'   => floatval($row['total_amount']),
                    'status'         => $row['status'],
                    'order_date'     => $row['order_date'],
                    'bill_requested' => intval($row['bill_requested']),
                    'items'          => []
                ];
            }
            if ($row['item_name']) {
                $orders[$oid]['items'][] = [
                    'item_name' => $row['item_name'],
                    'quantity'  => intval($row['quantity']),
                    'price'     => floatval($row['price']),
                    'subtotal'  => floatval($row['subtotal'])
                ];
            }
        }

        $ordersArr  = array_values($orders);
        $grandTotal = array_sum(array_column($ordersArr, 'total_amount'));

        // Check if bill has been requested by any customer at this table
        $billRequested = false;
        foreach ($ordersArr as $o) {
            if ($o['bill_requested']) {
                $billRequested = true;
                break;
            }
        }

        echo json_encode([
            'success'        => true,
            'session_id'     => intval($sessionId),
            'table_number'   => intval($session['table_number']),
            'opened_at'      => $session['opened_at'],
            'orders'         => $ordersArr,
            'grand_total'    => $grandTotal,
            'order_count'    => count($ordersArr),
            'bill_requested' => $billRequested
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// ACTION: close_session
// Called by waiter after billing to mark table as free.
// Accepts: session_id
// ─────────────────────────────────────────────────────────────
if ($action === 'close_session') {
    session_start();
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['waiter', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $sessionId = intval($input['session_id'] ?? 0);

    if ($sessionId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid session_id.']);
        exit;
    }

    try {
        // Mark all non-cancelled orders in this session as served
        $pdo->prepare("UPDATE orders SET status = 'served' WHERE session_id = ? AND status NOT IN ('served','cancelled')")
            ->execute([$sessionId]);

        // Close the session
        $pdo->prepare("UPDATE table_sessions SET status = 'closed', closed_at = NOW() WHERE session_id = ?")
            ->execute([$sessionId]);

        echo json_encode(['success' => true, 'message' => 'Table session closed. Table is now free.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// ACTION: request_bill
// Called by customer from their phone to notify waiter.
// Accepts: order_id  (marks bill_requested = 1)
// No auth needed (customer-facing)
// ─────────────────────────────────────────────────────────────
if ($action === 'request_bill') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $orderId = intval($input['order_id'] ?? 0);

    if ($orderId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid order_id.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET bill_requested = 1 WHERE order_id = ?");
        $stmt->execute([$orderId]);
        echo json_encode(['success' => true, 'message' => 'Bill request sent to waiter!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────
// ACTION: get_active_sessions
// Returns list of all currently active table sessions (for waiter dashboard).
// ─────────────────────────────────────────────────────────────
if ($action === 'get_active_sessions') {
    session_start();
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['waiter', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    try {
        $query = "SELECT ts.session_id, ts.table_number, ts.opened_at,
                         COUNT(DISTINCT o.order_id) AS order_count,
                         SUM(o.total_amount) AS total_amount,
                         MAX(o.bill_requested) AS bill_requested,
                         GROUP_CONCAT(DISTINCT o.status ORDER BY o.order_id SEPARATOR ',') AS statuses
                  FROM table_sessions ts
                  LEFT JOIN orders o ON ts.session_id = o.session_id AND o.status NOT IN ('cancelled')
                  WHERE ts.status = 'active'
                  GROUP BY ts.session_id
                  ORDER BY ts.opened_at ASC";
        $stmt = $pdo->query($query);
        $sessions = $stmt->fetchAll();

        // Compute dominant status for each session
        $result = [];
        foreach ($sessions as $s) {
            $statusList = explode(',', $s['statuses'] ?? '');
            $dominant = 'idle';
            if (in_array('pending', $statusList)) $dominant = 'pending';
            if (in_array('preparing', $statusList)) $dominant = 'preparing';
            if (in_array('ready', $statusList)) $dominant = 'ready';

            $result[] = [
                'session_id'    => intval($s['session_id']),
                'table_number'  => intval($s['table_number']),
                'opened_at'     => $s['opened_at'],
                'order_count'   => intval($s['order_count']),
                'total_amount'  => floatval($s['total_amount']),
                'bill_requested'=> intval($s['bill_requested']),
                'dominant_status'=> $dominant
            ];
        }

        echo json_encode(['success' => true, 'data' => $result]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
