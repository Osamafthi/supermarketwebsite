<?php
// checkout/shipping.php
require_once '../includes/init.php';
if(!User::isLoggedIn()){
    header("Location: ../account/login.php");
    exit();
  }
// Check if cart is empty
if ($cart->getCartCount() == 0) {
    header("Location: ../cart/cart.php");
    exit();
}

// Get cart items and total
$cartItems = $cart->getCartItems();
$cartTotal = $cart->getCartTotal();
$tax = $cartTotal * 0.1; // 10% tax
$grandTotal = $cartTotal + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate shipping information
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
  
    
    // Validation
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";

    if (empty($errors)) {
        // Store shipping info in session
        $_SESSION['shipping_info'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'zip_code' => $zipCode,
            'country' => $country
        ];
        
        // Redirect to payment page
        header("Location: payment.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Information - Supermarket</title>
    <?php include '../includes/header.php';?>
    <link rel="stylesheet" href="checkout_style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div class="checkout-header">
            <div class="checkout-steps">
                <div class="step active">
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
        
        <div class="checkout-content">
            <div class="shipping-form">
                <h2>Shipping Information</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Street Address *</label>
                        <input type="text" id="address" name="address" 
                               value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                        </div>
                     
                    </div>
                    
                  
                    <div class="form-actions">
                        <a href="../cart/cart.php" class="btn btn-secondary">Back to Cart</a>
                        <button type="submit" class="btn btn-primary">Continue to Payment</button>
                    </div>
                </form>
            </div>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
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
</body>
<footer>
  <?php
  include  '../includes/footer.php';
  ?>
</footer>
</html>