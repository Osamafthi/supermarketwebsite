<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/init.php';

// Initialize database connection
try {
    $database = new Database();
    $db = $database->connect();
    $product = new Product($db);
    
    // Get product ID from URL
    $productId = $_GET['id'] ?? null;
    if(!$productId || !is_numeric($productId)) {
        header("Location: /404.php");
        exit;
    }
    
    // Get product info
    $product->setId($productId);
    if(!$product->read_single()) {
        header("Location: /404.php");
        exit;
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Set page title for header
$pageTitle = htmlspecialchars($product->getName()) . ' - SuperMarket';
// Include header
include '../includes/header.php';
?>
<html>
<head>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
</head>
<body>
<main>
    <div id="product-container">
        <div class="product-detail">
            <div class="product-gallery">
                <img src="../uploads/<?php echo htmlspecialchars($product->getImage() ?? 'default-product.jpg'); ?>"
                     alt="<?php echo htmlspecialchars($product->getName()); ?>"
                     onerror="this.onerror=null; this.src='../images/default-product.jpg'">
            </div>
            
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product->getName()); ?></h1>
                <div class="price">$<?php echo number_format($product->getPrice(), 2); ?></div>
                <div class="product-meta">
                    <span class="stock-status <?php echo $product->getStockQuantity() > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $product->getStockQuantity() > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                </div>
                
                <div class="product-description">
                    <?php
                    $description = $product->getDescription();
                    echo $description ? nl2br(htmlspecialchars($description)) : 'No description available';
                    ?>
                </div>
                
                <?php if ($product->getStockQuantity() > 0): ?>
                    <div class="add-to-cart">
                        <div class="quantity-selector">
                            <button class="quantity-btn minus">-</button>
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product->getStockQuantity(); ?>">
                            <button class="quantity-btn plus">+</button>
                        </div>
                        <button class="btn add-to-cart-btn" data-product-id="<?php echo $product->getId(); ?>">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </div>
                <?php else: ?>
                    <button class="btn notify-me-btn">Notify When Available</button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recommended Products Section -->
        <div class="recommendations-section">
            <h2 class="recommendations-title">Recommended Products</h2>
            <div class="recommendations-container">
                <button class="scroll-button prev" onclick="scrollRecommendations('left')">‹</button>
                <button class="scroll-button next" onclick="scrollRecommendations('right')">›</button>
                
                <div class="recommendations-loading" id="recommendations-loading">
                    Loading recommendations...
                </div>
                
                <div class="recommendations-error" id="recommendations-error" style="display: none;">
                    Unable to load recommendations. Please try again later.
                </div>
                
                <div class="recommendations-scroll" id="recommendations-scroll" style="display: none;">
                    <!-- Recommendations will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity selector functionality
    const quantityInput = document.querySelector('input[name="quantity"]');
    const maxQuantity = <?php echo $product->getStockQuantity(); ?>;
    
    document.querySelector('.quantity-btn.minus')?.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value > 1) {
            quantityInput.value = value - 1;
        }
    });
    
    document.querySelector('.quantity-btn.plus')?.addEventListener('click', function() {
        let value = parseInt(quantityInput.value);
        if (value < maxQuantity) {
            quantityInput.value = value + 1;
        }
    });
    
    // Prevent manual entry of invalid values
    quantityInput.addEventListener('change', function() {
        let value = parseInt(this.value);
        if (isNaN(value)) {
            this.value = 1;
        } else if (value < 1) {
            this.value = 1;
        } else if (value > maxQuantity) {
            this.value = maxQuantity;
        }
    });
    
    // Add to cart functionality
    document.querySelector('.add-to-cart-btn')?.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        const quantity = parseInt(quantityInput.value);
        addToCart(productId, quantity);
    });
    
    // Load recommendations
    loadRecommendations();
});

function addToCart(productId, quantity = 1) {
    fetch('../api/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Update cart count in header
            document.querySelectorAll('.cart-count').forEach(el => {
                el.textContent = data.cart_count;
            });
            // Show success message
        } else {
            alert('Error: ' + (data.error || 'Could not add to cart'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding to cart');
    });
}

function loadRecommendations() {
    const productId = <?php echo $product->getId(); ?>;
    const loadingEl = document.getElementById('recommendations-loading');
    const errorEl = document.getElementById('recommendations-error');
    const scrollEl = document.getElementById('recommendations-scroll');
    
    fetch(`../api/get_recommended_products.php?product_id=${productId}&limit=8`)
        .then(response => response.json())
        .then(data => {
            loadingEl.style.display = 'none';
            
            if (data.success && data.data.recommendations.length > 0) {
                renderRecommendations(data.data.recommendations);
                scrollEl.style.display = 'flex';
                updateScrollButtons();
            } else {
                errorEl.style.display = 'block';
                errorEl.textContent = 'No recommendations available at this time.';
            }
        })
        .catch(error => {
            console.error('Error loading recommendations:', error);
            loadingEl.style.display = 'none';
            errorEl.style.display = 'block';
        });
}

function renderRecommendations(recommendations) {
    const scrollEl = document.getElementById('recommendations-scroll');
    
    scrollEl.innerHTML = recommendations.map(product => `
        <a href="product.php?id=${product.id}" class="recommendation-card">
            <img src="../uploads/${product.image}" 
                 alt="${product.name}"
                 class="recommendation-image"
                 onerror="this.onerror=null; this.src='../images/default-product.jpg'">
            <div class="recommendation-name">${product.name}</div>
            <div class="recommendation-price">$${product.price.toFixed(2)}</div>
            <div class="recommendation-category">${product.category_name}</div>
            <span class="recommendation-stock ${product.in_stock ? 'in-stock' : 'out-of-stock'}">
                ${product.in_stock ? 'In Stock' : 'Out of Stock'}
            </span>
        </a>
    `).join('');
}

function scrollRecommendations(direction) {
    const scrollEl = document.getElementById('recommendations-scroll');
    const scrollAmount = 220; // Card width + gap
    
    if (direction === 'left') {
        scrollEl.scrollLeft -= scrollAmount;
    } else {
        scrollEl.scrollLeft += scrollAmount;
    }
    
    setTimeout(updateScrollButtons, 100);
}

function updateScrollButtons() {
    const scrollEl = document.getElementById('recommendations-scroll');
    const prevBtn = document.querySelector('.scroll-button.prev');
    const nextBtn = document.querySelector('.scroll-button.next');
    
    if (scrollEl) {
        prevBtn.disabled = scrollEl.scrollLeft === 0;
        nextBtn.disabled = scrollEl.scrollLeft >= scrollEl.scrollWidth - scrollEl.clientWidth;
    }
}

// Update scroll buttons on scroll
document.getElementById('recommendations-scroll')?.addEventListener('scroll', updateScrollButtons);
</script>
</body>
<footer>
    <?php include '../includes/footer.php'?>
</footer>
</html>