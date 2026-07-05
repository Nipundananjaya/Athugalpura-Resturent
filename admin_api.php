<?php
// ============================================================
// SmartServe – Admin API for Real-Time Dashboard Stats
// File: admin_api.php
// ============================================================

session_start();
header('Content-Type: application/json');

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: 0');
header('Pragma: no-cache');

require_once 'db_connect.php';

// Security Check: Only allow logged-in Admin users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'dashboard_data') {
    try {
        // 1. Fetch Today's Revenue (orders placed today that are NOT cancelled)
        $stmtRev = $pdo->query("SELECT SUM(total_amount) AS revenue 
                                FROM orders 
                                WHERE DATE(order_date) = CURDATE() AND status != 'cancelled'");
        $revRow = $stmtRev->fetch();
        $revenue = floatval($revRow['revenue'] ?? 0.00);

        // 2. Fetch Active Orders Count (pending, preparing, ready)
        $stmtAct = $pdo->query("SELECT COUNT(*) AS active_count 
                                FROM orders 
                                WHERE status IN ('pending', 'preparing', 'ready')");
        $actRow = $stmtAct->fetch();
        $activeOrders = intval($actRow['active_count'] ?? 0);

        // 3. Fetch Items Served Today (sum of quantities from served orders placed today)
        $stmtServed = $pdo->query("SELECT SUM(oi.quantity) AS items_served 
                                   FROM order_items oi
                                   JOIN orders o ON oi.order_id = o.order_id
                                   WHERE DATE(o.order_date) = CURDATE() AND o.status = 'served'");
        $servedRow = $stmtServed->fetch();
        $itemsServed = intval($servedRow['items_served'] ?? 0);

        // 4. Fetch Live Orders List (pending, preparing, ready)
        $stmtLive = $pdo->query("SELECT order_id, table_number, total_amount, status, order_date 
                                 FROM orders 
                                 WHERE status IN ('pending', 'preparing', 'ready')
                                 ORDER BY order_date DESC");
        $liveOrders = $stmtLive->fetchAll();

        // Format live orders data
        $liveOrdersList = [];
        foreach ($liveOrders as $order) {
            $liveOrdersList[] = [
                'order_id' => intval($order['order_id']),
                'table_number' => intval($order['table_number']),
                'total_amount' => floatval($order['total_amount']),
                'status' => $order['status'],
                'order_date' => $order['order_date']
            ];
        }

        // 5. Fetch Popular Categories Chart Data (sold quantities per category)
        $stmtCats = $pdo->query("SELECT c.category_name AS name, SUM(oi.quantity) AS total_qty
                                 FROM order_items oi
                                 JOIN menu_items m ON oi.item_id = m.item_id
                                 JOIN categories c ON m.category_id = c.category_id
                                 JOIN orders o ON oi.order_id = o.order_id
                                 WHERE o.status != 'cancelled'
                                 GROUP BY c.category_id, c.category_name
                                 ORDER BY total_qty DESC
                                 LIMIT 5");
        $categoriesData = $stmtCats->fetchAll();

        $chartLabels = [];
        $chartData = [];
        foreach ($categoriesData as $cat) {
            $chartLabels[] = $cat['name'];
            $chartData[] = intval($cat['total_qty']);
        }

        echo json_encode([
            'success' => true,
            'stats' => [
                'revenue' => $revenue,
                'active_orders' => $activeOrders,
                'items_served' => $itemsServed
            ],
            'live_orders' => $liveOrdersList,
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartData
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// Action: fetch_orders
// Returns paginated orders list with item summary, supports search & status filter
// ============================================================
if ($action === 'fetch_orders') {
    try {
        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        // Build query with GROUP_CONCAT for items summary
        $sql = "SELECT 
                    o.order_id,
                    o.table_number,
                    o.customer_token,
                    o.total_amount,
                    o.status,
                    o.order_date,
                    GROUP_CONCAT(CONCAT(oi.quantity, 'x ', mi.item_name) ORDER BY mi.item_name SEPARATOR ', ') AS items_summary
                FROM orders o
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                LEFT JOIN menu_items mi ON oi.item_id = mi.item_id
                WHERE 1=1";

        $params = [];

        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $statusFilter;
        }

        if ($search !== '') {
            $sql .= " AND (o.order_id LIKE ? OR o.table_number LIKE ? OR o.customer_token LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= " GROUP BY o.order_id ORDER BY o.order_date DESC LIMIT 200";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        // Build KPI counts (today's orders)
        $kpiSql = "SELECT
            COUNT(CASE WHEN DATE(order_date) = CURDATE() AND status != 'cancelled' THEN 1 END) AS total_today,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_count,
            COUNT(CASE WHEN status = 'preparing' THEN 1 END) AS preparing_count,
            COUNT(CASE WHEN status IN ('ready','served') THEN 1 END) AS completed_count
        FROM orders";
        $kpiRow = $pdo->query($kpiSql)->fetch();

        $ordersList = [];
        foreach ($orders as $row) {
            $ordersList[] = [
                'order_id'       => intval($row['order_id']),
                'table_number'   => intval($row['table_number']),
                'customer_token' => $row['customer_token'] ?? '—',
                'items_summary'  => $row['items_summary'] ?? 'No items',
                'total_amount'   => floatval($row['total_amount']),
                'status'         => $row['status'],
                'order_date'     => $row['order_date'],
            ];
        }

        echo json_encode([
            'success' => true,
            'orders'  => $ordersList,
            'kpi'     => [
                'total_today'     => intval($kpiRow['total_today'] ?? 0),
                'pending_count'   => intval($kpiRow['pending_count'] ?? 0),
                'preparing_count' => intval($kpiRow['preparing_count'] ?? 0),
                'completed_count' => intval($kpiRow['completed_count'] ?? 0),
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// Action: order_details
// Returns full itemized breakdown for a specific order_id
// ============================================================
if ($action === 'order_details') {
    $orderId = intval($_GET['order_id'] ?? 0);
    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
        exit;
    }
    try {
        // Fetch order header
        $stmtO = $pdo->prepare("SELECT order_id, table_number, customer_token, total_amount, status, order_date FROM orders WHERE order_id = ?");
        $stmtO->execute([$orderId]);
        $order = $stmtO->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }

        // Fetch itemized lines
        $stmtI = $pdo->prepare("SELECT mi.item_name, mi.price AS unit_price, oi.quantity, oi.subtotal
                                 FROM order_items oi
                                 JOIN menu_items mi ON oi.item_id = mi.item_id
                                 WHERE oi.order_id = ?");
        $stmtI->execute([$orderId]);
        $items = $stmtI->fetchAll();

        $itemsList = [];
        $subtotalRaw = 0;
        foreach ($items as $item) {
            $itemsList[] = [
                'item_name'  => $item['item_name'],
                'unit_price' => floatval($item['unit_price']),
                'quantity'   => intval($item['quantity']),
                'subtotal'   => floatval($item['subtotal']),
            ];
            $subtotalRaw += floatval($item['subtotal']);
        }

        $finalTotal = floatval($order['total_amount']);
        $tax = $finalTotal - $subtotalRaw;

        echo json_encode([
            'success'        => true,
            'order_id'       => intval($order['order_id']),
            'table_number'   => intval($order['table_number']),
            'customer_token' => $order['customer_token'] ?? '—',
            'status'         => $order['status'],
            'order_date'     => $order['order_date'],
            'items'          => $itemsList,
            'subtotal'       => round($subtotalRaw, 2),
            'tax'            => round($tax, 2),
            'total'          => $finalTotal,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// Action: cancel_order
// Voids/cancels an order by setting its status to 'cancelled'
// ============================================================
if ($action === 'cancel_order') {
    $input = file_get_contents('php://input');
    $data  = json_decode($input, true) ?: $_POST;
    $orderId = intval($data['order_id'] ?? 0);

    if ($orderId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
        exit;
    }
    try {
        // Only allow cancelling if not already served or cancelled
        $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmtCheck->execute([$orderId]);
        $existing = $stmtCheck->fetch();

        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }
        if (in_array($existing['status'], ['served', 'cancelled'])) {
            echo json_encode(['success' => false, 'message' => 'Cannot cancel a ' . $existing['status'] . ' order.']);
            exit;
        }

        $stmtCancel = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
        $stmtCancel->execute([$orderId]);

        echo json_encode(['success' => true, 'message' => 'Order #' . $orderId . ' has been cancelled.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================================
// Action: fetch_reports
// Returns analytics data for the reporting dashboard
// ============================================================
if ($action === 'fetch_reports') {
    try {
        $startDate = $_GET['start_date'] ?? date('Y-m-d');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        // ensure format Y-m-d
        $start = date('Y-m-d', strtotime($startDate)) . ' 00:00:00';
        $end = date('Y-m-d', strtotime($endDate)) . ' 23:59:59';

        // 1. Revenue & Sales Report
        $stmtRev = $pdo->prepare("
            SELECT DATE(order_date) as report_date, SUM(total_amount) as revenue 
            FROM orders 
            WHERE order_date BETWEEN ? AND ? AND status != 'cancelled'
            GROUP BY DATE(order_date)
            ORDER BY DATE(order_date) ASC
        ");
        $stmtRev->execute([$start, $end]);
        $revenueData = $stmtRev->fetchAll();

        // Overall totals for Revenue
        $stmtRevTotal = $pdo->prepare("
            SELECT SUM(total_amount) as gross_revenue, COUNT(*) as order_count 
            FROM orders 
            WHERE order_date BETWEEN ? AND ? AND status != 'cancelled'
        ");
        $stmtRevTotal->execute([$start, $end]);
        $revTotal = $stmtRevTotal->fetch();
        $grossRev = floatval($revTotal['gross_revenue'] ?? 0);
        $orderCountForAov = intval($revTotal['order_count'] ?? 0);
        $aov = $orderCountForAov > 0 ? $grossRev / $orderCountForAov : 0;

        // 2. Order Volume & Analytics
        $stmtVol = $pdo->prepare("
            SELECT HOUR(order_date) as hour_of_day, COUNT(*) as count 
            FROM orders 
            WHERE order_date BETWEEN ? AND ? AND status != 'cancelled'
            GROUP BY HOUR(order_date)
            ORDER BY HOUR(order_date) ASC
        ");
        $stmtVol->execute([$start, $end]);
        $volumeData = $stmtVol->fetchAll();

        $stmtVolTotal = $pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
            FROM orders
            WHERE order_date BETWEEN ? AND ?
        ");
        $stmtVolTotal->execute([$start, $end]);
        $volTotal = $stmtVolTotal->fetch();

        // 3. Menu Item Performance
        $stmtItems = $pdo->prepare("
            SELECT mi.item_name, c.category_name, SUM(oi.quantity) as sold_qty, SUM(oi.subtotal) as item_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            JOIN menu_items mi ON oi.item_id = mi.item_id
            JOIN categories c ON mi.category_id = c.category_id
            WHERE o.order_date BETWEEN ? AND ? AND o.status != 'cancelled'
            GROUP BY mi.item_id, mi.item_name, c.category_name
            ORDER BY sold_qty DESC
            LIMIT 10
        ");
        $stmtItems->execute([$start, $end]);
        $itemsData = $stmtItems->fetchAll();

        $stmtCat = $pdo->prepare("
            SELECT c.category_name, SUM(oi.subtotal) as cat_revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            JOIN menu_items mi ON oi.item_id = mi.item_id
            JOIN categories c ON mi.category_id = c.category_id
            WHERE o.order_date BETWEEN ? AND ? AND o.status != 'cancelled'
            GROUP BY c.category_id, c.category_name
        ");
        $stmtCat->execute([$start, $end]);
        $catData = $stmtCat->fetchAll();

        // 4. Table & Customer Traffic
        $stmtTables = $pdo->prepare("
            SELECT table_number, 
                   COUNT(DISTINCT customer_token) as unique_customers,
                   COUNT(*) as total_orders
            FROM orders
            WHERE order_date BETWEEN ? AND ? AND status != 'cancelled'
            GROUP BY table_number
            ORDER BY total_orders DESC
            LIMIT 15
        ");
        $stmtTables->execute([$start, $end]);
        $tablesData = $stmtTables->fetchAll();

        // Aggregate Single vs Group customers
        $stmtGroup = $pdo->prepare("
            SELECT DATE(order_date) as d, table_number, COUNT(DISTINCT customer_token) as tokens
            FROM orders
            WHERE order_date BETWEEN ? AND ? AND status != 'cancelled'
            GROUP BY DATE(order_date), table_number
        ");
        $stmtGroup->execute([$start, $end]);
        $groupTokens = $stmtGroup->fetchAll();

        $singleCount = 0;
        $groupCount = 0;
        foreach ($groupTokens as $row) {
            if ($row['tokens'] > 1) {
                $groupCount++;
            } else {
                $singleCount++;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'revenue_sales' => [
                    'timeline' => $revenueData,
                    'gross_revenue' => $grossRev,
                    'net_profit' => $grossRev * 0.85, 
                    'aov' => $aov
                ],
                'order_volume' => [
                    'timeline' => $volumeData,
                    'total_orders' => intval($volTotal['total_orders']),
                    'cancelled_orders' => intval($volTotal['cancelled_orders'])
                ],
                'menu_performance' => [
                    'top_items' => $itemsData,
                    'category_revenue' => $catData
                ],
                'table_traffic' => [
                    'table_stats' => $tablesData,
                    'single_count' => $singleCount,
                    'group_count' => $groupCount
                ]
            ]
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>
