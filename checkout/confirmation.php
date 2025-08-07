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
    <link rel="stylesheet" href="checkout_style.css?v=<?php echo time(); ?>">
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
                        <?php echo htmlspecialchars($shippingInfo['city']); ?><br>
                       
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