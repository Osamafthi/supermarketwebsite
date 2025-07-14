<?php
// checkout/payment.php
require_once '../includes/init.php';

// Check if cart is empty
if ($cart->getCartCount() == 0) {
    header("Location: ../cart/cart.php");
    exit();
}

// Check if shipping info exists
if (!isset($_SESSION['shipping_info'])) {
    header("Location: shipping.php");
    exit();
}

// Get cart items and total
$cartItems = $cart->getCartItems();
$cartTotal = $cart->getCartTotal();
$tax = $cartTotal * 0.1; // 10% tax
$grandTotal = $cartTotal + $tax;
$shippingInfo = $_SESSION['shipping_info'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate payment information
    $paymentMethod = $_POST['payment_method'] ?? '';
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $cardName = trim($_POST['card_name'] ?? '');
    $expiryMonth = $_POST['expiry_month'] ?? '';
    $expiryYear = $_POST['expiry_year'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Validation
    if (empty($paymentMethod)) $errors[] = "Payment method is required";
    
    if ($paymentMethod === 'card') {
        if (empty($cardNumber) || !preg_match('/^\d{16}$/', $cardNumber)) {
            $errors[] = "Valid 16-digit card number is required";
        }
        if (empty($cardName)) $errors[] = "Cardholder name is required";
        if (empty($expiryMonth) || !in_array($expiryMonth, range(1, 12))) {
            $errors[] = "Valid expiry month is required";
        }
        if (empty($expiryYear) || $expiryYear < date('Y')) {
            $errors[] = "Valid expiry year is required";
        }
        if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = "Valid CVV is required";
        }
    }
    
    if (empty($errors)) {
        // Store payment info in session (in real app, process payment here)
        $_SESSION['payment_info'] = [
            'payment_method' => $paymentMethod,
            'card_last_four' => $paymentMethod === 'card' ? substr($cardNumber, -4) : null,
            'card_name' => $cardName,
            'total' => $grandTotal
        ];
        
        // In a real application, you would process the payment here
        // For demo purposes, we'll assume payment is successful
        $_SESSION['payment_success'] = true;
        
        // Redirect to confirmation page
        header("Location: confirmation.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Supermarket</title>
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
            max-width: 1200px;
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
            color: #666;
            font-weight: 500;
        }
        
        .step.active {
            color: #27ae60;
        }
        
        .step.completed {
            color: #27ae60;
        }
        
        .step-number {
            background: #ddd;
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
        
        .step.active .step-number,
        .step.completed .step-number {
            background: #27ae60;
        }
        
        .step-divider {
            width: 50px;
            height: 2px;
            background: #ddd;
            margin: 0 20px;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .payment-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-option {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .payment-option:hover {
            border-color: #27ae60;
        }
        
        .payment-option.selected {
            border-color: #27ae60;
            background-color: #f0f8f0;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-option i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #27ae60;
        }
        
        .card-details {
            display: none;
            margin-top: 20px;
        }
        
        .card-details.active {
            display: block;
        }
        
        .error-messages {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .shipping-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .shipping-info h4 {
            margin-bottom: 10px;
            color: #27ae60;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            color: #27ae60;
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
            text-align: center;
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .secure-notice {
            background: #e8f5e8;
            border: 1px solid #27ae60;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .secure-notice::before {
            content: "🔒";
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkout-header">
            <div class="checkout-steps">
                <div class="step completed">
                    <div class="step-number">1</div>
                    <span>Shipping</span>
                </div>
                <div class="step-divider"></div>
                <div class="step active">
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
        
        <div class="checkout-content">
            <div class="payment-form">
                <h2>Payment Information</h2>
                
                <div class="secure-notice">
                    <span>Your payment information is secure and encrypted</span>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="paymentForm">
                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="payment-methods">
                            <div class="payment-option" data-method="card">
                                <input type="radio" name="payment_method" value="card" id="card" required>
                                <div>💳</div>
                                <div>Credit/Debit Card</div>
                            </div>
                            <div class="payment-option" data-method="paypal">
                                <input type="radio" name="payment_method" value="paypal" id="paypal">
                                <div>🅿️</div>
                                <div>PayPal</div>
                            </div>
                            <div class="payment-option" data-method="cash">
                                <input type="radio" name="payment_method" value="cash" id="cash">
                                <div>💵</div>
                                <div>Cash on Delivery</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-details" id="cardDetails">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" 
                                   placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                        
                        <div class="form-group">
                            <label for="card_name">Cardholder Name</label>
                            <input type="text" id="card_name" name="card_name" 
                                   placeholder="John Doe">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry_month">Expiry Month</label>
                                <select id="expiry_month" name="expiry_month">
                                    <option value="">Month</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="expiry_year">Expiry Year</label>
                                <select id="expiry_year" name="expiry_year">
                                    <option value="">Year</option>
                                    <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="shipping.php" class="btn btn-secondary">Back to Shipping</a>
                        <button type="submit" class="btn btn-primary">Complete Order</button>
                    </div>
                </form>
            </div>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                
                <div class="shipping-info">
                    <h4>Shipping Address</h4>
                    <p><?php echo htmlspecialchars($shippingInfo['first_name'] . ' ' . $shippingInfo['last_name']); ?></p>
                    <p><?php echo htmlspecialchars($shippingInfo['address']); ?></p>
                    <p><?php echo htmlspecialchars($shippingInfo['city'] . ', ' . $shippingInfo['state'] . ' ' . $shippingInfo['zip_code']); ?></p>
                    <p><?php echo htmlspecialchars($shippingInfo['country']); ?></p>
                </div>
                
                <div class="summary-item">
                    <span>Items (<?php echo $cart->getCartCount(); ?>)</span>
                    <span>$<?php echo number_format($cartTotal, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Tax</span>
                    <span>$<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-item">
                    <span>Total</span>
                    <span>$<?php echo number_format($grandTotal, 2); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Handle payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Show/hide card details
                const cardDetails = document.getElementById('cardDetails');
                if (this.dataset.method === 'card') {
                    cardDetails.classList.add('active');
                    // Make card fields required
                    cardDetails.querySelectorAll('input, select').forEach(field => {
                        field.required = true;
                    });
                } else {
                    cardDetails.classList.remove('active');
                    // Remove required attribute from card fields
                    cardDetails.querySelectorAll('input, select').forEach(field => {
                        field.required = false;
                    });
                }
            });
        });
        
        // Format card number input
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
        
        // Validate CVV input
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/gi, '');
        });
        
        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return;
            }
            
            if (paymentMethod.value === 'card') {
                const cardNumber = document.getElementById('card_number').value.replace(/\s+/g, '');
                const cardName = document.getElementById('card_name').value.trim();
                const expiryMonth = document.getElementById('expiry_month').value;
                const expiryYear = document.getElementById('expiry_year').value;
                const cvv = document.getElementById('cvv').value;
                
                if (!cardNumber || cardNumber.length !== 16) {
                    e.preventDefault();
                    alert('Please enter a valid 16-digit card number');
                    return;
                }
                
                if (!cardName) {
                    e.preventDefault();
                    alert('Please enter the cardholder name');
                    return;
                }
                
                if (!expiryMonth || !expiryYear) {
                    e.preventDefault();
                    alert('Please select expiry month and year');
                    return;
                }
                
                if (!cvv || cvv.length < 3) {
                    e.preventDefault();
                    alert('Please enter a valid CVV');
                    return;
                }
            }
        });
    </script>
</body>
<footer>
  <?php
  include  '../includes/footer.php';
  ?>
</footer>
</html>