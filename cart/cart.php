<?php include '../includes/init.php';?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SuperMarket - Shopping Cart</title>
  <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
</head>
<body>
  <header class="header">
  <?php include "../includes/header.php";?>
  </header>

  <main class="container">
    <section class="cart-page">
    
      
      <!-- Cart Items -->
      <div class="cart-items" id="cartItemsContainer">
        <div class="loading">Loading your cart...</div>
      </div>

      <!-- Cart Summary -->
      <aside class="cart-summary" id="cartSummary">
        <h2>Order Summary</h2>
        <div class="summary-row">
          <span>Subtotal</span>
          <span id="subtotal">$0.00</span>
        </div>
        <div class="summary-row">
          <span>Estimated Tax</span>
          <span id="tax">$0.00</span>
        </div>
        <div class="summary-row total">
          <span>Total</span>
          <span id="total">$0.00</span>
        </div>
        <button class="btn checkout-btn" id="checkoutBtn">Proceed to Checkout</button>
        <a href="../index.php" class="continue-shopping">← Continue Shopping</a>
      </aside>
    </section>
  </main>

  <footer class="footer">
  <?php include "../includes/footer.php";?>
  </footer>

  <script>
 document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    updateCartCount();
    
    // Event delegation for cart controls
    document.addEventListener('click', function(e) {
        // Handle minus button
        if (e.target.classList.contains('minus-btn')) {
            const itemId = e.target.dataset.itemId;
            const input = e.target.nextElementSibling;
            const newQuantity = parseInt(input.value) - 1;
            if (newQuantity >= 1) {
                updateQuantity(itemId, newQuantity);
            }
        }
        
        // Handle plus button
        if (e.target.classList.contains('plus-btn')) {
            const itemId = e.target.dataset.itemId;
            const input = e.target.previousElementSibling;
            const newQuantity = parseInt(input.value) + 1;
            updateQuantity(itemId, newQuantity);
        }
        
        // Handle remove button
        if (e.target.classList.contains('remove-btn')) {
            const itemId = e.target.dataset.itemId;
            updateQuantity(itemId, 0);
        }
    });
    
    // Handle direct input changes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-input')) {
            const itemId = e.target.dataset.itemId;
            const newQuantity = parseInt(e.target.value);
            if (newQuantity >= 1) {
                updateQuantity(itemId, newQuantity);
            } else {
                e.target.value = 1; // Reset to minimum quantity
            }
        }
    });

    document.getElementById('checkoutBtn').addEventListener('click', function() {
    // First verify the cart isn't empty
    fetch('../api/get_cart.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.data.items.length === 0) {
                alert('Your cart is empty. Please add items before checkout.');
                return;
            }
            // Redirect to shipping page
            window.location.href = '../checkout/shipping.php';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error checking cart status. Please try again.');
        });
});
});
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}
  function loadCart() {
    fetch('../api/get_cart.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to load cart');
            }
            
            // Debug the received data
            console.log('Cart data:', data);
            
            // Ensure items is an array
            if (!Array.isArray(data.data.items)) {
                throw new Error('Invalid cart data format');
            }
            
            renderCartItems(data.data.items);
            renderCartSummary(data.data.summary);
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            document.getElementById('cartItemsContainer').innerHTML = `
                <div class="error">
                    <p>Error loading your cart</p>
                    <p><small>${error.message}</small></p>
                    <button onclick="loadCart()" class="btn">Retry</button>
                </div>
            `;
        });
}

function renderCartItems(items) {
    const container = document.getElementById('cartItemsContainer');
    
    if (!items || items.length === 0) {
        container.innerHTML = `
        <div class="empty-cart">
            <p>Your cart is empty</p>
            <a href="../index.php" class="btn">Start Shopping</a>
        </div>
        `;
        return;
    }
    
    container.innerHTML = items.map(item => {
        const price = parseFloat(item.price) || 0;
        const formattedPrice = price.toFixed(2);
       
        return `
        <article class="cart-item" data-item-id="${item.product_id}">
            <img src="../uploads/${item.image_url}" 
                 alt="${item.name}" 
                 class="cart-item-image"
                 onerror="this.src='../images/default-product.jpg'">
            <div class="cart-item-details">
                <h3>${item.name}</h3>
               
                <p class="price">$${formattedPrice}</p>
                <div class="quantity-controls">
                    <button class="quantity-btn minus-btn" data-item-id="${item.product_id}">−</button>
                    <input type="number" value="${item.quantity}" min="1" 
                           class="quantity-input"
                           data-item-id="${item.id}">
                    <button class="quantity-btn plus-btn" data-item-id="${item.product_id}">+</button>
                </div>
            </div>
            <button class="remove-btn" data-item-id="${item.product_id}">Remove</button>
           
        </article>
        `;
    }).join('');
}
  function renderCartSummary(summary) {
    document.getElementById('subtotal').textContent = `$${summary.subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${summary.tax.toFixed(2)}`;
    document.getElementById('total').textContent = `$${summary.total.toFixed(2)}`;
    document.querySelector('.cart-count').textContent = summary.item_count;
  }

  function updateQuantity(itemId, newQuantity) {
    fetch('../api/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: parseInt(itemId),  // Ensure this is a number
            quantity: parseInt(newQuantity)  // Ensure this is a number
        })
    })
    .then(response => {
        if (!response.ok) {
            // Get the actual error message from the response
            return response.json().then(err => {
                throw new Error(err.error || 'Failed to update cart');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            loadCart();
            updateCartCount();
            showNotification('Cart updated successfully');
        }
    })
    .catch(error => {
        console.error('Error updating cart:', error);
        showNotification('Error: ' + error.message, 'error');
    });
}

  function updateCartCount() {
    fetch('../api/get_cart.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.querySelector('.cart-count').textContent = data.data.summary.item_count;
        }
      });
  }
  </script>
</body>
</html>