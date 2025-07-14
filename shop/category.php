<?php
require_once '../includes/init.php';

// Get category ID
$categoryId = $_GET['id'] ?? null;
$categoryName = 'Category Products'; // Default

// Get category name if ID is provided
if ($categoryId) {
    try {
        $database = new Database();
        $db = $database->connect();
        $category = new Category($db);
        
        // Get single category by ID
        $stmt = $category->readSingle($categoryId); // Assuming you have this method
        if ($stmt->rowCount() > 0) {
            $categoryData = $stmt->fetch(PDO::FETCH_ASSOC);
            $categoryName = $categoryData['name'];
        }
    } catch (Exception $e) {
        error_log("Error getting category: " . $e->getMessage());
    }
}
include '../includes/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($categoryName); ?> - Products</title>
    <link rel="stylesheet" href="styling/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div id="category-products">
        <h1 id="category-title"><?php echo htmlspecialchars($categoryName); ?></h1>
        <div class="products-grid" id="products-container">
            <!-- Will be populated by AJAX -->
            <div class="loading">Loading products...</div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryId = <?php echo json_encode($categoryId); ?>;
        
        // Load products by category
        if(categoryId) {
            fetch(`../index_php_stuff/get_products_by_category.php?category_id=${categoryId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const container = document.getElementById('products-container');
                    container.innerHTML = '';
                    
                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(product => {
                            const productCard = document.createElement('div');
                            productCard.className = 'product-card';
                            productCard.innerHTML = `
                            <div class="product-clickable-area" data-product-id="${product.id}">
                                <img src="../uploads/${product.image}" alt="${product.name}">
                                <h3>${product.name}</h3>
                                <div class="price">$${product.price.toFixed(2)}</div>
                                </div>
                               
                                <button class="btn add-to-cart-btn" data-product-id="${product.id}">Add to Cart</button>
                            `;
                            container.appendChild(productCard);
                        });
                    } else {
                        container.innerHTML = '<p>No products found in this category.</p>';
                    }
                    document.querySelectorAll('.product-clickable-area').forEach(area => {
                area.addEventListener('click', function(e) {
                    // Don't navigate if clicking on something inside that might have its own handler
                    if (e.target.closest('.add-to-cart-btn')) {
                        return;
                    }
                    const productId = this.getAttribute('data-product-id');
                    window.location.href = `product.php?id=${productId}`;
                });
            });
            
            // Add event listeners to add-to-cart buttons for featured products
            document.querySelectorAll(' .add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent the product click handler from firing
                    const productId = this.getAttribute('data-product-id');
                    addToCart(productId);
                });
            });
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('products-container').innerHTML =
                        '<p>Error loading products. Please try again.</p>';
                });
        } else {
            document.getElementById('products-container').innerHTML =
                '<p>No category specified.</p>';
        }
    });

    function loadCartCount() {
  fetch('../api/get_cart_count.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        updateCartCount(data.count);
      }
    })
    .catch(error => {
      console.error('Error loading cart count:', error);
    });
}


function updateCartCount(count) {
  const cartCountElement = document.querySelector('.cart-count');
  if (cartCountElement) {
    cartCountElement.textContent = count;
  }
}



function addToCart(productId,Quantity=1) {
  console.log('Attempting to add product:', productId);
  
  fetch('../api/add_to_cart.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ product_id: productId , quantity: Quantity})
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .then(data => {
    console.log('Add to cart response:', data);
    if (data.success) {
      updateCartCount(data.cart_count);
      // Better notification than alert
      showNotification('Product added to cart!');
    } else {
      throw new Error(data.error || 'Failed to add to cart');
    }
  })
  .catch(error => {
    console.error('Error adding to cart:', error);
    showNotification('Error: ' + error.message, 'error');
  });
}
    </script>

</body>
<footer>

<?php
include '../includes/footer.php';
?>
</footer>
</html>