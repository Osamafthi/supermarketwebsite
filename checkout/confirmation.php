<?php
// checkout/confirmation.php
require_once '../includes/init.php';

// Check if payment was successful
if (!isset($_SESSION['payment_success']) || !$_SESSION['payment_success']) {
    header("Location: ../cart/cart.php");
    exit();
}

// Check if required session data exists
if (!isset($_SESSION['shipping_info']) || !isset($_SESSION['payment_info'])) {
    header("Location: ../cart/cart.php");
    exit();
}

// Get order data
$cartItems = $cart->getCartItems();
$cartTotal = $cart->getCartTotal();
$tax = $cartTotal * 0.1;
$grandTotal = $cartTotal + $tax;
$shippingInfo = $_SESSION['shipping_info'];
$paymentInfo = $_SESSION['payment_info'];

// Generate order number
$orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// In a real application, you would:
// 1. Save the order to database
// 2. Send confirmation email
// 3. Update inventory
// 4. Process payment
// 5. Generate invoice

// For demo purposes, we'll simulate saving to database
try {
    // Start transaction
    $db->beginTransaction();
    
    // Insert order
    $stmt = $db->prepare("
        INSERT INTO orders (
            order_number, user_id, total_amount, tax_amount, 
            shipping_first_name, shipping_last_name, shipping_email, 
            shipping_phone, shipping_address, shipping_city, 
            payment_method, order_status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , 'pending', NOW())
    ");
    
    $stmt->execute([
        $orderNumber,
        User::isLoggedIn() ? User::getCurrentUserId() : null,
        $grandTotal,
        $tax,
        $shippingInfo['first_name'],
        $shippingInfo['last_name'],
        $shippingInfo['email'],
        $shippingInfo['phone'],
        $shippingInfo['address'],
        $shippingInfo['city'],
 
        $paymentInfo['payment_method']
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Insert order items
    foreach ($cartItems as $item) {
        $stmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, total)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $item['price'] * $item['quantity']
        ]);
    }
    
    // Commit transaction
    $db->commit();
    
    // Clear the cart
    $cart->clearCart();
    
    $orderSaved = true;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    $orderSaved = false;
    $orderError = $e->getMessage();
}

// Clear session data
unset($_SESSION['shipping_info']);
unset($_SESSION['payment_info']);
unset($_SESSION['payment_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Supermarket</title>
    <?php include '../includes/header.php';?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .checkout-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .checkout-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .step {
            display: flex;
            align-items: center;
            color: #27ae60;
            font-weight: 500;
        }
        
        .step-number {
            background: #27ae60;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .step-divider {
            width: 50px;
            height: 2px;
            background: #27ae60;
            margin: 0 20px;
        }
        
        .confirmation-content {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon {
            font-size: 64px;
            color: #27ae60;
            margin-bottom: 20px;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .details-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .details-section h3 {
            color: #27ae60;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .info-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .order-items {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .order-items h3 {
            color: #27ae60;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .item-row:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .item-details {
            color: #666;
            font-size: 14px;
        }
        
        .item-total {
            font-weight: 500;
            color: #27ae60;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        
        .summary-item.total {
            font-weight: bold;
            font-size: 18px;
            color: #27ae60;
            border-top: 2px solid #27ae60;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .actions {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
        }
        
        .btn-primary {
            background: #27ae60;
            color: white;
        }
        
        .btn-primary:hover {
            background: #219a52;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .next-steps {
            background: #e8f5e8;
            border: 1px solid #27ae60;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .next-steps h4 {
            color: #27ae60;
            margin-bottom: 10px;
        }
        
        .next-steps ul {
            list-style-type: none;
            padding-left: 0;
        }
        
        .next-steps li {
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }
        
        .next-steps li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #27ae60;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .item-row {
                flex-direction: column;
                text-align: left;
            }
            
            .item-total {
                margin-top: 10px;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkout-header">
            <div class="checkout-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <span>Shipping</span>
                </div>
                <div class="step-divider"></div>
                <div class="step">
                    <div class="step-number">2</div>
                    <span>Payment</span>
                </div>
                <div class="step-divider"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <span>Confirmation</span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($orderError)): ?>
            <div class="error-message">
                <h3>Order Processing Error</h3>
                <p>There was an error processing your order. Please contact customer support.</p>
                <p><small>Error: <?php echo htmlspecialchars($orderError); ?></small></p>
            </div>
        <?php endif; ?>
        
        <div class="confirmation-content">
            <div class="success-icon">✅</div>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order. We've received your payment and will process your order shortly.</p>
            <h2 style="color: #27ae60; margin-top: 20px;">Order #<?php echo htmlspecialchars($orderNumber); ?></h2>
            <p>Order Date: <?php echo date('F j, Y, g:i a'); ?></p>
        </div>
        
        <div class="order-details">
            <div class="details-section">
                <h3>Shipping Information</h3>
                <div class="info-item">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($shippingInfo['first_name'] . ' ' . $shippingInfo['last_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($shippingInfo['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($shippingInfo['phone']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Address:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($shippingInfo['address']); ?><br>
                        <?php echo htmlspecialchars($shippingInfo['city'] . ', ' . $shippingInfo['state'] . ' ' . $shippingInfo['zip_code']); ?><br>
                        <?php echo htmlspecialchars($shippingInfo['country']); ?>
                    </span>
                </div>
            </div>
            
            <div class="details-section">
                <h3>Payment Information</h3>
                <div class="info-item">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value">
                        <?php 
                        switch($paymentInfo['payment_method']) {
                            case 'card':
                                echo 'Credit/Debit Card';
                                if ($paymentInfo['card_last_four']) {
                                    echo ' ending in ' . $paymentInfo['card_last_four'];
                                }
                                break;
                            case 'paypal':
                                echo 'PayPal';
                                break;
                            case 'cash':
                                echo 'Cash on Delivery';
                                break;
                            default:
                                echo ucfirst($paymentInfo['payment_method']);
                        }
                        ?>
                    </span>
                </div>
                <?php if ($paymentInfo['payment_method'] === 'card' && $paymentInfo['card_name']): ?>
                <div class="info-item">
                    <span class="info-label">Cardholder:</span>
                    <span class="info-value"><?php echo htmlspecialchars($paymentInfo['card_name']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Amount Paid:</span>
                    <span class="info-value">$<?php echo number_format($paymentInfo['total'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="order-items">
            <h3>Order Items</h3>
            <?php foreach ($cartItems as $item): ?>
                <div class="item-row">
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-details">
                            Quantity: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?>
                        </div>
                    </div>
                    <div class="item-total">
                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="order-summary">
                <div class="summary-item">
                    <span>Subtotal:</span>
                    <span>$<?php echo number_format($cartTotal, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Tax:</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Shipping:</span>
                    <span>Free</span>
                </div>
                <div class="summary-item total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($grandTotal, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <h3>What's Next?</h3>
            <div class="next-steps">
                <h4>Your Order Status</h4>
                <ul>
                    <li>Order confirmation email sent</li>
                    <li>Payment processed successfully</li>
                    <li>Order is being prepared</li>
                    <li>You'll receive tracking information once shipped</li>
                </ul>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="../index.php" class="btn btn-primary">Continue Shopping</a>
                <?php if (User::isLoggedIn()): ?>
                    <a href="../account/account.php" class="btn btn-secondary">View My Orders</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-redirect after 30 seconds (optional)
        setTimeout(function() {
            if (confirm('Would you like to continue shopping?')) {
                window.location.href = '../index.php';
            }
        }, 30000);
    </script>
</body>
<footer>
    <?php 
    include '../includes/footer.php';
    ?>
</footer>
</html>