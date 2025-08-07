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
    <link rel="stylesheet" href="checkout_style.css?v=<?php echo time(); ?>">
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
                    <p><?php echo htmlspecialchars($shippingInfo['city'] ); ?></p>
                    
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