<?php
// shop/search.php
require_once '../includes/init.php';


// Include header
include '../includes/header.php';
?>

<div class="container">
    <div class="search-page">
        <!-- Search Header -->
        <div class="search-header">
            <h1>Search Results</h1>
            
            <!-- Search Form -->
            <div class="search-form-container">
                <form method="GET" action="search.php" class="search-form-page">
                    <input type="search" 
                           name="term" 
                           
                           placeholder="Search products..." 
                           class="search-input">
                    <button type="submit" class="search-button">Search</button>
                </form>
            </div>
            <div id="product-results" class="products-grid"></div>
        </div>

        <!-- Search Results -->
        <div class="search-results">
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            
            <?php elseif (empty($searchTerm)): ?>
                <div class="no-search-term">
                    <div class="search-prompt">
                        <h3>Search Our Products</h3>
                        <p>Enter a product name or description to find what you're looking for.</p>
                    </div>
                </div>
            
            <?php elseif ($totalResults === 0): ?>
                <div class="no-results">
                    <div class="no-results-content">
                        <div class="no-results-icon">🔍</div>
                        <h3>No products found</h3>
                        <p>We couldn't find any products matching your search.</p>
                        <div class="search-suggestions">
                            <h4>Try:</h4>
                            <ul>
                                <li>Checking your spelling</li>
                                <li>Using different keywords</li>
                                <li>Using more general terms</li>
                            </ul>
                        </div>
                        <a href="../index.php" class="back-home-btn">Browse All Products</a>
                    </div>
                </div>
            
            <?php else: ?>

                <div id="product-results" class="products-grid"></div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('term');
    
    const resultsContainer = document.getElementById('product-results');

    if (!searchTerm) {
        resultsContainer.innerHTML = '<p>Please enter a search term.</p>';
        return;
    }

    fetch(`../api/search_products.php?term=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                resultsContainer.innerHTML = `<p> tek${data.error}</p>`;
                return;
            }

            if (data.products.length === 0) {
                resultsContainer.innerHTML = '<p>No products found.</p>';
                return;
            }

            resultsContainer.innerHTML = ''; // clear any existing content

            data.products.forEach(product => {
                const productCard = document.createElement('div');
                productCard.className = 'product-card';

                productCard.innerHTML = `
                    <div class="product-image">
                        <img src="../uploads/${product.image}" 
                             alt="${product.name}" 
                             onerror="this.src='../images/default-product.jpg'">
                    </div>

                    <div class="product-info">
                        <h3 class="product-name">${product.name}</h3>
                        ${product.category_name ? `<p class="product-category">${product.category_name}</p>` : ''}
                        <p class="product-price">$${product.price.toFixed(2)}</p>
                        <p class="product-description">${product.description.substring(0, 100)}${product.description.length > 100 ? '...' : ''}</p>
                        <div class="product-stock">
                            ${product.stock_quantity > 0 
                                ? `<span class="in-stock">In Stock (${product.stock_quantity})</span>` 
                                : `<span class="out-of-stock">Out of Stock</span>`}
                        </div>
                    </div>

                    <div class="product-actions">
                        <a href="product.php?id=${product.id}" class="view-product-btn">View Product</a>
                        ${product.stock_quantity > 0 
                            ? `<button class="add-to-cart-btn" data-product-id="${product.id}" data-max="${product.stock_quantity}">Add to Cart</button>`
                            : ''}
                    </div>
                `;

                resultsContainer.appendChild(productCard);
            });

            // Add event listeners for Add to Cart buttons
            document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const max = parseInt(this.dataset.max);
                    const quantity = 1;

                    if (quantity > max) {
                        alert("Not enough stock.");
                        return;
                    }

                    fetch('../api/add_to_cart.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: quantity
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('Product added to cart!');
                            document.querySelector('.cart-count').innerText = result.cart_count;
                        } else {
                            alert(result.error);
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        alert('Error adding to cart.');
                    });
                });
            });
        })
        .catch(error => {
            console.error(error);
            resultsContainer.innerHTML = '<p>Failed to fetch products.</p>';
        });
});
</script>

<style>
.search-page {
    padding: 20px 0;
}

.search-header {
    text-align: center;
    margin-bottom: 30px;
}

.search-form-container {
    max-width: 600px;
    margin: 20px auto;
}

.search-form-page {
    display: flex;
    gap: 10px;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 25px;
    background: white;
}

.search-input {
    flex: 1;
    border: none;
    outline: none;
    padding: 10px 15px;
    font-size: 16px;
}

.search-button {
    background: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 16px;
}

.search-button:hover {
    background: #0056b3;
}

.search-info {
    margin: 20px 0;
    color: #666;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.product-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 4px;
}

.product-info {
    padding: 15px 0;
}

.product-name {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 5px;
}

.product-category {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.product-price {
    font-size: 20px;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 10px;
}

.product-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.product-stock {
    margin-bottom: 15px;
}

.in-stock {
    color: green;
    font-weight: bold;
}

.out-of-stock {
    color: red;
    font-weight: bold;
}

.product-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.view-product-btn {
    background: #28a745;
    color: white;
    text-decoration: none;
    padding: 10px;
    text-align: center;
    border-radius: 4px;
    transition: background 0.2s;
}

.view-product-btn:hover {
    background: #218838;
}

.add-to-cart-form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.quantity-input {
    width: 60px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.add-to-cart-btn {
    flex: 1;
    background: #007bff;
    color: white;
    border: none;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
}

.add-to-cart-btn:hover {
    background: #0056b3;
}

.no-results, .no-search-term {
    text-align: center;
    padding: 40px 20px;
}

.no-results-content, .search-prompt {
    max-width: 400px;
    margin: 0 auto;
}

.no-results-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.search-suggestions {
    text-align: left;
    margin: 20px 0;
}

.search-suggestions ul {
    list-style: none;
    padding: 0;
}

.search-suggestions li {
    padding: 5px 0;
}

.search-suggestions li:before {
    content: "• ";
    color: #007bff;
    font-weight: bold;
}

.back-home-btn {
    background: #007bff;
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 20px;
}

.back-home-btn:hover {
    background: #0056b3;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 4px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .search-form-page {
        flex-direction: column;
    }
    
    .search-input, .search-button {
        width: 100%;
    }
}
</style>

<?php
// Include footer
include '../includes/footer.php';
?>