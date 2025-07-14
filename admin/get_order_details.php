<?php
// File: admin/get_order_details.php
require_once '../includes/init.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is admin
if (!User::isLoggedIn()) {
    http_response_code(403);
    exit('Unauthorized - Not logged in');
}

if (!User::isAdmin()) {
    http_response_code(403);
    exit('Unauthorized - Not admin');
}

$orderId = $_GET['id'] ?? 0;

if (!$orderId) {
    http_response_code(400);
    exit('Order ID required');
}

try {
    // Get order details
    $stmt = $db->prepare("
        SELECT 
            o.*,
            u.full_name as customer_name,
            u.email as customer_email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        exit('Order not found');
    }

    // Get order items
    $stmt = $db->prepare("
        SELECT 
            oi.*,
            p.name as product_name,
            p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get order tracking history
    $stmt = $db->prepare("
        SELECT *
        FROM order_tracking
        WHERE order_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$orderId]);
    $trackingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Log the actual error for debugging
    error_log("Order details error: " . $e->getMessage());
    http_response_code(500);
    exit('Error loading order details: ' . $e->getMessage());
}
?>

<div class="order-details">
    <div class="order-header">
        <h3>Order #<?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></h3>
        <div class="order-meta">
            <span class="status-badge status-<?php echo $order['order_status']; ?>">
                <?php echo ucfirst($order['order_status']); ?>
            </span>
            <span class="status-badge payment-<?php echo $order['payment_status']; ?>">
                Payment: <?php echo ucfirst($order['payment_status']); ?>
            </span>
        </div>
    </div>

    <div class="order-info-grid">
        <div class="info-section">
            <h4>Customer Information</h4>
            <div class="info-item">
                <strong>Name:</strong> <?php echo htmlspecialchars(($order['shipping_first_name'] ?? '') . ' ' . ($order['shipping_last_name'] ?? '')); ?>
            </div>
            <div class="info-item">
                <strong>Email:</strong> <?php echo htmlspecialchars($order['shipping_email'] ?? ''); ?>
            </div>
            <div class="info-item">
                <strong>Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone'] ?? ''); ?>
            </div>
            <?php if (!empty($order['customer_name'])): ?>
            <div class="info-item">
                <strong>Account:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="info-section">
            <h4>Shipping Address</h4>
            <div class="address">
                <?php echo htmlspecialchars($order['shipping_address'] ?? ''); ?><br>
                <?php echo htmlspecialchars(($order['shipping_city'] ?? '') . ', ' . ($order['shipping_state'] ?? '') . ' ' . ($order['shipping_zip'] ?? '')); ?><br>
                <?php echo htmlspecialchars($order['shipping_country'] ?? ''); ?>
            </div>
        </div>

        <div class="info-section">
            <h4>Order Summary</h4>
            <div class="info-item">
                <strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?>
            </div>
            <div class="info-item">
                <strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?>
            </div>
            <div class="info-item">
                <strong>Subtotal:</strong> $<?php echo number_format(($order['total_amount'] ?? 0) - ($order['tax_amount'] ?? 0), 2); ?>
            </div>
            <div class="info-item">
                <strong>Tax:</strong> $<?php echo number_format($order['tax_amount'] ?? 0, 2); ?>
            </div>
            <div class="info-item">
                <strong>Total:</strong> <strong>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></strong>
            </div>
        </div>
    </div>

    <div class="order-items-section">
        <h4>Order Items</h4>
        <div class="items-list">
            <?php if (!empty($orderItems)): ?>
                <?php foreach ($orderItems as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <div class="item-image">
                                <img src="../uploads/<?php echo $item['image'] ?? 'placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?>">
                            </div>
                            <div class="item-content">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></div>
                                <div class="item-details">
                                    Quantity: <?php echo $item['quantity'] ?? 0; ?> × $<?php echo number_format($item['price'] ?? 0, 2); ?>
                                </div>
                            </div>
                        </div>
                        <div class="item-total">
                            $<?php echo number_format($item['total'] ?? 0, 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-name">No items found</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($trackingHistory)): ?>
    <div class="tracking-section">
        <h4>Order History</h4>
        <div class="tracking-timeline">
            <?php foreach ($trackingHistory as $track): ?>
                <div class="tracking-item">
                    <div class="tracking-date">
                        <?php echo date('M j, Y g:i A', strtotime($track['created_at'])); ?>
                    </div>
                    <div class="tracking-status">
                        <strong><?php echo ucfirst($track['status']); ?></strong>
                    </div>
                    <div class="tracking-message">
                        <?php echo htmlspecialchars($track['message']); ?>
                    </div>
                    <?php if (!empty($track['tracking_number'])): ?>
                    <div class="tracking-number">
                        Tracking: <?php echo htmlspecialchars($track['tracking_number']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($order['notes'])): ?>
    <div class="notes-section">
        <h4>Notes</h4>
        <div class="notes-content">
            <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .order-details {
        max-width: 600px;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eee;
    }

    .order-meta {
        display: flex;
        gap: 10px;
    }

    .order-info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .info-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
    }

    .info-section h4 {
        margin-bottom: 10px;
        color: #333;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .info-item {
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
    }

    .info-item strong {
        color: #555;
        min-width: 100px;
    }

    .address {
        line-height: 1.5;
        color: #666;
    }

    .order-items-section {
        margin-bottom: 20px;
    }

    .order-items-section h4 {
        margin-bottom: 15px;
        color: #333;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .items-list {
        background: #f8f9fa;
        border-radius: 5px;
        overflow: hidden;
    }

    .order-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    .order-item:last-child {
        border-bottom: none;
    }

    .item-info {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
    }

    .item-image {
        flex-shrink: 0;
    }

    .item-image img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        background-color: #f9f9f9;
    }

    .item-content {
        flex: 1;
    }

    .item-name {
        font-weight: 500;
        margin-bottom: 5px;
        color: #333;
    }

    .item-details {
        color: #666;
        font-size: 14px;
    }

    .item-total {
        font-weight: 600;
        color: #333;
        margin-left: 15px;
    }

    .tracking-section {
        margin-bottom: 20px;
    }

    .tracking-section h4 {
        margin-bottom: 15px;
        color: #333;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .tracking-timeline {
        background: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
    }

    .tracking-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .tracking-item:last-child {
        border-bottom: none;
    }

    .tracking-date {
        color: #666;
        font-size: 12px;
        margin-bottom: 5px;
    }

    .tracking-status {
        margin-bottom: 5px;
    }

    .tracking-message {
        color: #555;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .tracking-number {
        color: #007bff;
        font-size: 12px;
        font-weight: 500;
    }

    .notes-section {
        margin-bottom: 20px;
    }

    .notes-section h4 {
        margin-bottom: 10px;
        color: #333;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    .notes-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        color: #555;
        line-height: 1.5;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-pending { background-color: #fff3cd; color: #856404; }
    .status-processing { background-color: #d4edda; color: #155724; }
    .status-shipped { background-color: #cce5ff; color: #004085; }
    .status-delivered { background-color: #d1ecf1; color: #0c5460; }
    .status-cancelled { background-color: #f8d7da; color: #721c24; }

    .payment-pending { background-color: #fff3cd; color: #856404; }
    .payment-completed { background-color: #d4edda; color: #155724; }
    .payment-failed { background-color: #f8d7da; color: #721c24; }

    @media (max-width: 768px) {
        .order-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .order-meta {
            flex-direction: column;
            gap: 5px;
        }
        
        .info-item {
            flex-direction: column;
            gap: 5px;
        }
        
        .order-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .item-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            width: 100%;
        }

        .item-image {
            align-self: center;
        }

        .item-image img {
            width: 80px;
            height: 80px;
        }

        .item-content {
            text-align: center;
            width: 100%;
        }

        .item-total {
            margin-left: 0;
            align-self: center;
            font-size: 16px;
        }
    }
</style>