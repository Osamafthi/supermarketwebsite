<?php
// File: admin/orders_data_controller.php
require_once '../includes/init.php';

header('Content-Type: application/json');

if (!User::isLoggedIn() || !User::isAdmin()) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Get filter parameters
    $status_filter = $_GET['status'] ?? 'all';
    $payment_filter = $_GET['payment'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Build WHERE conditions and parameters
    $where_conditions = [];
    $params = [];

    if ($status_filter !== 'all') {
        $where_conditions[] = "o.order_status = ?";
        $params[] = $status_filter;
    }

    if ($payment_filter !== 'all') {
        $where_conditions[] = "o.payment_status = ?";
        $params[] = $payment_filter;
    }

    if ($search) {
        $where_conditions[] = "(o.order_number LIKE ? OR o.shipping_first_name LIKE ? OR o.shipping_last_name LIKE ? OR o.shipping_email LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM orders o $where_clause";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_orders / $limit);

    // Get orders with pagination
    $orders_sql = "SELECT o.*, u.full_name as customer_name, u.email as customer_email, COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $where_clause
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($orders_sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get stats for today's orders
    $stats_stmt = $db->prepare("SELECT
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
        SUM(total_amount) as total_revenue
        FROM orders
        WHERE DATE(created_at) = CURDATE()");
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    $stats = array_map(fn($v) => $v === NULL ? 0 : $v, $stats);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'stats' => $stats,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_orders' => $total_orders,
            'limit' => $limit
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>