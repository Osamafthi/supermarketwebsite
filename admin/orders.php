<?php 
require_once '../includes/init.php';
if (!User::isAdmin()) {
    header('Location: http://localhost/deepseek_noor_3la_noor/index.php');
    exit();
}?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">
    <?php include '../includes/header.php'; ?>
</head>
<body>
    <div class="container">
        <h1>Orders Management</h1>
        
        <!-- Loading indicator -->
        <div id="loading" style="display: none; text-align: center; padding: 20px;">
            <p>Loading orders...</p>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form id="ordersFilterForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Order Status</label>
                        <select name="status" id="status">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="payment">Payment Status</label>
                        <select name="payment" id="payment">
                            <option value="all">All Payment</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" placeholder="Order number, customer name, email...">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats Cards (Optional) -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p id="total_orders">0</p>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <p id="pending_orders">0</p>
            </div>
            <div class="stat-card">
                <h3>Processing Orders</h3>
                <p id="processing_orders">0</p>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <p id="total_revenue">$0.00</p>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="orders-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Orders will be populated by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <!-- Pagination buttons will be populated by JavaScript -->
        </div>
    </div>

    <script src="js/orders.js"></script>
</body>
<?php include '../includes/footer.php';?>
</html>