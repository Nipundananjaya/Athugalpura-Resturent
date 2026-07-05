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
        // Fetch raw tables to perform calculations in PHP (due to PDO shim JOIN & aggregation limitations)
        $allOrders = db_select('orders');
        $allSessions = db_select('table_sessions');
        $categories = db_select('categories');
        $menuItems = db_select('menu_items');
        $orderItems = db_select('order_items');

        $todayStr = date('Y-m-d');
        $revenue = 0.00;
        $activeOrders = 0;
        $liveOrdersList = [];

        // 1. Calculate Revenue (today's non-cancelled orders) and Active Orders
        foreach ($allOrders as $order) {
            $status = $order['status'];
            $orderDate = $order['order_date'];
            $orderDatePart = substr($orderDate, 0, 10);

            if ($orderDatePart === $todayStr && $status !== 'cancelled') {
                $revenue += floatval($order['total_amount'] ?? 0.00);
            }

            if (in_array($status, ['pending', 'preparing', 'ready'])) {
                $activeOrders++;
                $liveOrdersList[] = [
                    'order_id' => intval($order['order_id']),
                    'table_number' => intval($order['table_number']),
                    'total_amount' => floatval($order['total_amount'] ?? 0.00),
                    'status' => $status,
                    'order_date' => $orderDate
                ];
            }
        }

        // Sort live orders by date descending
        usort($liveOrdersList, function($a, $b) {
            return strcmp($b['order_date'], $a['order_date']);
        });

        // 2. Calculate Items Served Today (quantities from served orders today)
        $todayServedOrderIds = [];
        foreach ($allOrders as $order) {
            $orderDatePart = substr($order['order_date'], 0, 10);
            if ($orderDatePart === $todayStr && $order['status'] === 'served') {
                $todayServedOrderIds[] = intval($order['order_id']);
            }
        }

        $itemsServed = 0;
        if (!empty($todayServedOrderIds)) {
            foreach ($orderItems as $item) {
                if (in_array(intval($item['order_id']), $todayServedOrderIds)) {
                    $itemsServed += intval($item['quantity'] ?? 0);
                }
            }
        }

        // 3. Calculate Occupied and Empty Tables (1 to 20 grid)
        $occupiedTablesSet = [];
        foreach ($allSessions as $session) {
            if ($session['status'] === 'active') {
                $occupiedTablesSet[intval($session['table_number'])] = true;
            }
        }
        $occupiedTablesCount = count($occupiedTablesSet);
        $emptyTablesCount = max(0, 20 - $occupiedTablesCount);

        // Helper Maps for category and items
        $catMap = [];
        foreach ($categories as $cat) {
            $catMap[intval($cat['category_id'])] = $cat['category_name'];
        }

        $itemToCatMap = [];
        $itemNamesMap = [];
        foreach ($menuItems as $mi) {
            $itemId = intval($mi['item_id']);
            $itemToCatMap[$itemId] = intval($mi['category_id']);
            $itemNamesMap[$itemId] = $mi['item_name'];
        }

        $nonCancelledOrderIdsMap = [];
        foreach ($allOrders as $order) {
            if ($order['status'] !== 'cancelled') {
                $nonCancelledOrderIdsMap[intval($order['order_id'])] = true;
            }
        }

        // 4. Calculate Popular Categories Chart Data
        $catQuantities = [];
        foreach ($orderItems as $oi) {
            $orderId = intval($oi['order_id']);
            if (isset($nonCancelledOrderIdsMap[$orderId])) {
                $itemId = intval($oi['item_id']);
                $catId = $itemToCatMap[$itemId] ?? 0;
                if ($catId > 0 && isset($catMap[$catId])) {
                    $catName = $catMap[$catId];
                    $catQuantities[$catName] = ($catQuantities[$catName] ?? 0) + intval($oi['quantity']);
                }
            }
        }
        arsort($catQuantities);
        $topCategories = array_slice($catQuantities, 0, 5, true);
        $chartLabels = array_keys($topCategories);
        $chartData = array_values($topCategories);

        // 5. Calculate Popular Items (Most Sold) Chart Data
        $itemQuantities = [];
        foreach ($orderItems as $oi) {
            $orderId = intval($oi['order_id']);
            if (isset($nonCancelledOrderIdsMap[$orderId])) {
                $itemId = intval($oi['item_id']);
                if (isset($itemNamesMap[$itemId])) {
                    $itemName = $itemNamesMap[$itemId];
                    $itemQuantities[$itemName] = ($itemQuantities[$itemName] ?? 0) + intval($oi['quantity']);
                }
            }
        }
        arsort($itemQuantities);
        $topItems = array_slice($itemQuantities, 0, 5, true);
        $itemLabels = array_keys($topItems);
        $itemData = array_values($topItems);

        echo json_encode([
            'success' => true,
            'stats' => [
                'revenue' => $revenue,
                'active_orders' => $activeOrders,
                'items_served' => $itemsServed,
                'occupied_tables' => $occupiedTablesCount,
                'empty_tables' => $emptyTablesCount
            ],
            'live_orders' => $liveOrdersList,
            'chart' => [
                'labels' => $chartLabels,
                'data' => $chartData
            ],
            'items_chart' => [
                'labels' => $itemLabels,
                'data' => $itemData
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
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
