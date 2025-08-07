<?php
// File: account/account.php
require_once '../includes/init.php';

// Require login to access this page
requireLogin();

$user = getCurrentUser();
$user_id = $user['id'];

// Handle different sections
$section = $_GET['section'] ?? 'dashboard';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['action']) {
            case 'update_profile':
                $full_name = $_POST['full_name'];
                $email = $_POST['email'];
                $phone = $_POST['phone'] ?? null;
                $address = $_POST['address'] ?? null;
                
                $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $phone, $address, $user_id]);
                
                $_SESSION['success_message'] = "Profile updated successfully!";
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords don't match");
                }
                
                if (strlen($new_password) < 8) {
                    throw new Exception("Password must be at least 8 characters");
                }
                
                // Verify current password
                $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($current_password, $user_data['password'])) {
                    throw new Exception("Current password is incorrect");
                }
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $_SESSION['success_message'] = "Password changed successfully!";
                break;
                
            case 'cancel_order':
                $order_id = $_POST['order_id'];
                
                // Check if order can be cancelled
                $stmt = $db->prepare("SELECT order_status FROM orders WHERE id = ? AND user_id = ?");
                $stmt->execute([$order_id, $user_id]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order && in_array($order['order_status'], ['pending', 'processing'])) {
                    $stmt = $db->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
                    $stmt->execute([$order_id]);
                    $_SESSION['success_message'] = "Order cancelled successfully!";
                } else {
                    throw new Exception("Order cannot be cancelled");
                }
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header("Location: account.php?section=" . $section);
    exit();
}

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Get order statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        COALESCE(SUM(total_amount), 0) as total_spent
    FROM orders 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent orders
$stmt = $db->prepare("
    SELECT 
        o.*,
        COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order details for specific order
$order_details = null;
if ($section === 'order_details' && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    $stmt = $db->prepare("
        SELECT o.*
        FROM orders o
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order_details) {
        $stmt = $db->prepare("
            SELECT 
                oi.*,
                p.name as product_name,
                p.image
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Get user's coupons
$stmt = $db->prepare("
    SELECT 
        c.*,
        CASE 
            WHEN c.expires_at < NOW() THEN 'expired'
            WHEN c.usage_count >= c.usage_limit THEN 'used_up'
            ELSE 'active'
        END as status
    FROM coupons c
    LEFT JOIN user_coupons uc ON c.id = uc.coupon_id
    WHERE uc.user_id = ? OR c.is_public = 1
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$user_coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - SuperMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .account-sidebar {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }
        .account-sidebar .nav-link {
            color: #495057;
            padding: 12px 20px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .account-sidebar .nav-link:hover,
        .account-sidebar .nav-link.active {
            background: #007bff;
            color: white;
        }
        .account-content {
            padding: 30px;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .order-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d4edda; color: #155724; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .coupon-card {
            border: 2px dashed #28a745;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8fff9;
        }
        .coupon-expired {
            border-color: #dc3545;
            background: #fff5f5;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5em;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 account-sidebar">
                <div class="text-center mb-4">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user_data['full_name'], 0, 1)); ?>
                    </div>
                    <h5><?php echo htmlspecialchars($user_data['full_name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user_data['email']); ?></p>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'dashboard' ? 'active' : ''; ?>" 
                           href="account.php?section=dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'profile' ? 'active' : ''; ?>" 
                           href="account.php?section=profile">
                            <i class="fas fa-user me-2"></i> Profile Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'orders' ? 'active' : ''; ?>" 
                           href="account.php?section=orders">
                            <i class="fas fa-shopping-bag me-2"></i> Order History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'coupons' ? 'active' : ''; ?>" 
                           href="account.php?section=coupons">
                            <i class="fas fa-ticket-alt me-2"></i> Coupons
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'addresses' ? 'active' : ''; ?>" 
                           href="account.php?section=addresses">
                            <i class="fas fa-map-marker-alt me-2"></i> Addresses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'security' ? 'active' : ''; ?>" 
                           href="account.php?section=security">
                            <i class="fas fa-lock me-2"></i> Security
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="../includes/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 account-content">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($section === 'dashboard'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Dashboard</h2>
                        <span class="text-muted">Welcome back, <?php echo htmlspecialchars($user_data['full_name']); ?>!</span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h4><?php echo $order_stats['total_orders']; ?></h4>
                                <p class="mb-0">Total Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h4><?php echo $order_stats['pending_orders']; ?></h4>
                                <p class="mb-0">Pending Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h4><?php echo $order_stats['delivered_orders']; ?></h4>
                                <p class="mb-0">Delivered Orders</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h4>$<?php echo number_format($order_stats['total_spent'], 2); ?></h4>
                                <p class="mb-0">Total Spent</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h4>Recent Orders</h4>
                        <?php if (empty($recent_orders)): ?>
                            <p class="text-muted">No orders found.</p>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-2">
                                            <strong>#<?php echo $order['order_number']; ?></strong>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <?php echo $order['item_count']; ?> items
                                        </div>
                                        <div class="col-md-2">
                                            $<?php echo number_format($order['total_amount'], 2); ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        </div>
                                        <div class="col-md-2">
                                            <a href="account.php?section=order_details&order_id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif ($section === 'profile'): ?>
                    <h2>Profile Settings</h2>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Personal Information</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_profile">
                                        
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input type="text" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Account Info</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Member Since:</strong><br>
                                    <?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></p>
                                    
                                    <p><strong>Last Login:</strong><br>
                                    <?php echo $user_data['last_login'] ? date('F j, Y g:i A', strtotime($user_data['last_login'])) : 'Never'; ?></p>
                                    
                                    <p><strong>Account Type:</strong><br>
                                    <?php echo ucfirst($user_data['role']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'security'): ?>
                    <h2>Security Settings</h2>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="change_password">
                                        
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'orders'): ?>
                    <h2>Order History</h2>
                    
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                            <h4>No orders yet</h4>
                            <p class="text-muted">Start shopping to see your orders here!</p>
                            <a href="../index.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="order-card">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5>Order #<?php echo $order['order_number']; ?></h5>
                                        <p class="text-muted mb-2">
                                            Placed on <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                        </p>
                                        <p class="mb-2">
                                            <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                            <span class="ms-2"><?php echo $order['item_count']; ?> items</span>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <h5>$<?php echo number_format($order['total_amount'], 2); ?></h5>
                                        <div class="btn-group-vertical">
                                            <a href="account.php?section=order_details&order_id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">View Details</a>
                                            <?php if (in_array($order['order_status'], ['pending', 'processing'])): ?>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                                    <input type="hidden" name="action" value="cancel_order">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel Order</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                <?php elseif ($section === 'order_details' && $order_details): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Order Details</h2>
                        <a href="account.php?section=orders" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Order #<?php echo $order_details['order_number']; ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>Order Date:</strong><br>
                                            <?php echo date('F j, Y g:i A', strtotime($order_details['created_at'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Status:</strong><br>
                                            <span class="status-badge status-<?php echo $order_details['order_status']; ?>">
                                                <?php echo ucfirst($order_details['order_status']); ?>
                                            </span></p>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>Payment Status:</strong><br>
                                            <span class="status-badge status-<?php echo $order_details['payment_status']; ?>">
                                                <?php echo ucfirst($order_details['payment_status']); ?>
                                            </span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Total Amount:</strong><br>
                                            $<?php echo number_format($order_details['total_amount'], 2); ?></p>
                                        </div>
                                    </div>
                                    
                                    <h6>Order Items:</h6>
                                    <?php if (isset($order_items)): ?>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Product</th>
                                                        <th>Price</th>
                                                        <th>Quantity</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($order_items as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if ($item['image']): ?>
                                                                        <img src="../uploads/<?php echo $item['image']; ?>" 
                                                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                                             class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                                   
                                                                </div>
                                                            </td>
                                                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                            <td><?php echo $item['quantity']; ?></td>
                                                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Shipping Address</h5>
                                </div>
                                <div class="card-body">
                                    <address>
                                        <?php echo htmlspecialchars($order_details['shipping_first_name'] . ' ' . $order_details['shipping_last_name']); ?><br>
                                        <?php echo htmlspecialchars($order_details['shipping_address']); ?><br>
                                        <?php echo htmlspecialchars($order_details['shipping_city'] ); ?><br>
                                        <?php echo htmlspecialchars($order_details['shipping_phone']); ?>
                                    </address>
                                </div>
                            </div>
                            
                            <?php if (in_array($order_details['order_status'], ['shipped', 'delivered'])): ?>
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>Tracking Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Tracking Number:</strong><br>
                                        <?php echo $order_details['tracking_number'] ?? 'Not available'; ?></p>
                                        
                                        <p><strong>Estimated Delivery:</strong><br>
                                        <?php echo $order_details['estimated_delivery'] ? date('F j, Y', strtotime($order_details['estimated_delivery'])) : 'Not available'; ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php elseif ($section === 'coupons'): ?>
                    <h2>My Coupons</h2>
                    
                    <?php if (empty($user_coupons)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                            <h4>No coupons available</h4>
                            <p class="text-muted">Check back later for exclusive offers!</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($user_coupons as $coupon): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="coupon-card <?php echo $coupon['status'] === 'expired' ? 'coupon-expired' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5><?php echo htmlspecialchars($coupon['code']); ?></h5>
                                                <p class="text-muted mb-2"><?php echo htmlspecialchars($coupon['description']); ?></p>
                                                <p class="mb-1">
                                                    <strong>
                                                        <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                                            <?php echo $coupon['discount_value']; ?>% OFF
                                                        <?php else: ?>
                                                            $<?php echo number_format($coupon['discount_value'], 2); ?> OFF
                                                        <?php endif; ?>
                                                    </strong>
                                                </p>
                                                <small class="text-muted">
                                                    <?php if ($coupon['min_order_amount']): ?>
                                                        Min. order: $<?php echo number_format($coupon['min_order_amount'], 2); ?> | 
                                                    <?php endif; ?>
                                                    Expires: <?php echo date('M j, Y', strtotime($coupon['expires_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <?php if ($coupon['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($coupon['status'] === 'expired'): ?>
                                                    <span class="badge bg-danger">Expired</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Used</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($coupon['status'] === 'active'): ?>
                                            <div class="mt-3">
                                                <button class="btn btn-outline-primary btn-sm" 
                                                        onclick="copyCouponCode('<?php echo $coupon['code']; ?>')">
                                                    <i class="fas fa-copy"></i> Copy Code
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php elseif ($section === 'addresses'): ?>
                    <h2>Manage Addresses</h2>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Default Address</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($user_data['address']): ?>
                                        <address>
                                            <?php echo htmlspecialchars($user_data['full_name']); ?><br>
                                            <?php echo nl2br(htmlspecialchars($user_data['address'])); ?><br>
                                            <?php if ($user_data['phone']): ?>
                                                Phone: <?php echo htmlspecialchars($user_data['phone']); ?>
                                            <?php endif; ?>
                                        </address>
                                        <a href="account.php?section=profile" class="btn btn-outline-primary">Edit Address</a>
                                    <?php else: ?>
                                        <p class="text-muted">No default address set.</p>
                                        <a href="account.php?section=profile" class="btn btn-primary">Add Address</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Address Book</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Manage multiple addresses for faster checkout.</p>
                                    <button class="btn btn-outline-primary" disabled>
                                        <i class="fas fa-plus"></i> Add New Address
                                    </button>
                                    <small class="text-muted d-block mt-2">Feature coming soon</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="text-center py-5">
                        <h4>Section not found</h4>
                        <p class="text-muted">The requested section does not exist.</p>
                        <a href="account.php" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyCouponCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0';
                toast.setAttribute('role', 'alert');
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            Coupon code "${code}" copied to clipboard!
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                // Add to page
                const toastContainer = document.getElementById('toast-container') || (() => {
                    const container = document.createElement('div');
                    container.id = 'toast-container';
                    container.className = 'toast-container position-fixed top-0 end-0 p-3';
                    container.style.zIndex = '9999';
                    document.body.appendChild(container);
                    return container;
                })();
                
                toastContainer.appendChild(toast);
                
                // Show toast
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                // Remove from DOM after hiding
                toast.addEventListener('hidden.bs.toast', () => {
                    toast.remove();
                });
            });
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
<?php 
include '../includes/footer.php';
?>
</html>