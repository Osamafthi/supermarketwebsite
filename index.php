<?php error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SuperMarket - Home</title>
  <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
  <?php include "includes/header.php"; ?>
</head>
<body>


  <main class="container">
    <!-- Hero Section -->
    <section class="hero">
      <img src="images/default-product.jpg" alt="Weekly Specials" class="hero-image">
    </section>
   
    <!-- Categories Grid -->

    <section class="categories">
    
      <h2>Shop by Category</h2>
      <div class="grid grid-4" id="categoriesContainer">
        <!-- Will be loaded by AJAX -->
        <div class="loading">Loading categories...</div>
      </div>
      
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
      <h2>Featured Products</h2>
      <div class="grid grid-4" id="featuredProductsContainer">
        <!-- Will be loaded by AJAX -->
        <div class="loading">Loading products...</div>
      </div>
    </section>
    <!-- Regular Products (paginated) -->
<section class="all-products">
  <h2>Our Products</h2>
  <div class="grid grid-4" id="productsContainer">
    <!-- Will be loaded by loadProducts() -->
    <div class="loading">Loading products...</div>
  </div>
  <div class="pagination" id="paginationContainer"></div>
</section>
  </main>



  <script>


document.addEventListener('DOMContentLoaded', function() {
  // Load initial cart count
  loadCartCount();
  // Load categories and products
  loadCategories();
  loadFeaturedProducts();
  loadProducts(currentPage);
  // Setup search functionality
 
  // Search functionality for index.php

  const searchInput = document.getElementById('mainSearch');
  const searchForm = document.querySelector('.search-form');
    let searchTimeout;

    // Handle search input with debouncing for suggestions (optional)
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Optional: Add live suggestions here if needed
        // For now, we'll just handle the form submission
    });

    // Handle form submission and Enter key
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
  
    // Add a search button if you want
    function addSearchButton() {
        if (!searchForm.querySelector('.search-btn')) {
            const searchButton = document.createElement('button');
            searchButton.type = 'button';
            searchButton.className = 'search-btn';
            searchButton.innerHTML = '🔍';
            searchButton.onclick = performSearch;
            searchForm.appendChild(searchButton);
        }
    }

    // Call this if you want a search button
    // addSearchButton();

    function performSearch() {
        const searchTerm = searchInput.value.trim();
        
        if (searchTerm.length === 0) {
            alert('Please enter a search term');
            return;
        }

        // Redirect to search page with the search term
        window.location.href = `shop/search.php?term=${encodeURIComponent(searchTerm)}`;
    }

    // Optional: Add click handler for search button if it exists
    const searchButton = searchForm.querySelector('.search-btn');
    if (searchButton) {
        searchButton.addEventListener('click', performSearch);
    }

  });
// Add this function to load the initial cart count
function loadCartCount() {
  fetch('api/get_cart_count.php')
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
  
  fetch('api/add_to_cart.php', {
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
function loadCategories() {
    fetch('index_php_stuff/get_categories.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const container = document.getElementById('categoriesContainer');
            container.innerHTML = '';
            
            // Get only the first 5 categories
            const firstFiveCategories = data.slice(0, 5);
            
            firstFiveCategories.forEach(category => {
                const categoryCard = document.createElement('div');
                categoryCard.className = 'category-card';
                
                // Handle image path with fallback
                const imagePath = category.image 
                    ? `uploads/categories/${category.image}`
                    : 'images/default-category.jpg';
                
                categoryCard.innerHTML = `
                    <a href="shop/category.php?id=${category.id}">
                        <img src="${imagePath}" 
                             alt="${category.name}"
                             onerror="this.onerror=null; this.src='images/default-category.jpg'">
                        <h3>${category.name}</h3>
                    </a>
                `;
                container.appendChild(categoryCard);
            });
        })
        .catch(error => {
            console.error('Error loading categories:', error);
            document.getElementById('categoriesContainer').innerHTML = 
                '<p class="error">Error loading categories. Please try again.</p>';
        });
}
function loadFeaturedProducts() {
    fetch('index_php_stuff/get_featured_products.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('featuredProductsContainer');
            
            if (!data.success || !data.data || data.data.length === 0) {
                container.innerHTML = `
                    <div class="no-featured">
                        <p>Check out our weekly specials!</p>
                        <a href="products.html" class="btn">View All Products</a>
                    </div>
                `;
                return;
            }
            
            // Clear the container first
            container.innerHTML = '';
            
            data.data.forEach(product => {
                const productCard = document.createElement('article');
                productCard.className = 'product-card featured';
                
                const imagePath = product.image 
                    ? `uploads/${product.image}`
                    : 'images/default-product.jpg';
                
                productCard.innerHTML = `
                    <div class="featured-badge">🌟 Featured</div>
                    <div class="product-clickable-area" data-product-id="${product.id}">
                        <img src="${imagePath}" 
                             alt="${product.name}"
                             onerror="this.src='images/default-product.jpg'">
                        <h3>${product.name}</h3>
                        <p class="price">$${product.price.toFixed(2)}</p>
                    </div>
                    <button class="btn add-to-cart-btn" data-product-id="${product.id}">Add to Cart</button>
                `;
                container.appendChild(productCard);
            });
            
            // Add event listeners to clickable areas for featured products
            document.querySelectorAll('#featuredProductsContainer .product-clickable-area').forEach(area => {
                area.addEventListener('click', function(e) {
                    // Don't navigate if clicking on something inside that might have its own handler
                    if (e.target.closest('.add-to-cart-btn')) {
                        return;
                    }
                    const productId = this.getAttribute('data-product-id');
                    window.location.href = `shop/product.php?id=${productId}`;
                });
            });
            
            // Add event listeners to add-to-cart buttons for featured products
            document.querySelectorAll('#featuredProductsContainer .add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent the product click handler from firing
                    const productId = this.getAttribute('data-product-id');
                    addToCart(productId);
                });
            });
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('featuredProductsContainer').innerHTML = `
                <div class="error">
                    <p>Featured products will be back soon!</p>
                    <a href="products.html" class="btn">Browse Products</a>
                </div>
            `;
        });
}

// Global variables for pagination
let currentPage = 1;
const productsPerPage = 12;

function loadProducts(page = 1, categoryId = null, searchTerm = null) {
    currentPage = page;
    
    let url = `index_php_stuff/get_products.php?page=${page}&per_page=${productsPerPage}`;
    if (categoryId) url += `&category_id=${categoryId}`;
    if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to load products');
            }
            
            renderProducts(data.data.products);
            renderPagination(data.data.pagination);
        })
        .catch(error => {
            console.error('Error loading products:', error);
            document.getElementById('productsContainer').innerHTML = 
                `<p class="error">Error loading products: ${error.message}</p>`;
        });


}

function renderProducts(products) {
    const container = document.getElementById('productsContainer');
    container.innerHTML = '';
    
    if (!products || products.length === 0) {
        container.innerHTML = '<p>No products found.</p>';
        return;
    }
    
    products.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        
        const imagePath = product.image 
            ? `uploads/${product.image}`
            : 'images/default-product.jpg';
        
        productCard.innerHTML = `
            <div class="product-clickable-area" data-product-id="${product.id}">
                <img src="${imagePath}" alt="${product.name}" 
                     onerror="this.onerror=null; this.src='images/default-product.jpg'">
                <h3>${product.name}</h3>
                <p class="price">$${(product.price || 0).toFixed(2)}</p>
            </div>
            <button class="btn add-to-cart-btn" data-product-id="${product.id}">Add to Cart</button>
        `;
        container.appendChild(productCard);
    });
    
    // Add event listeners to clickable areas
    document.querySelectorAll('.product-clickable-area').forEach(area => {
        area.addEventListener('click', function(e) {
            // Don't navigate if clicking on something inside that might have its own handler
            if (e.target.closest('.add-to-cart-btn')) {
                return;
            }
            const productId = this.getAttribute('data-product-id');
            window.location.href = `shop/product.php?id=${productId}`;
        });
    });
    
    // Add event listeners to add-to-cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent the product click handler from firing
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });
}

function renderPagination(pagination) {
    const paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer) return;
    
    paginationContainer.innerHTML = '';
    
    const totalPages = pagination.total_pages;
    if (totalPages <= 1) return;
    
    // Previous button
    const prevButton = document.createElement('button');
    prevButton.textContent = 'Previous';
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => {
        if (currentPage > 1) loadProducts(currentPage - 1);
    });
    paginationContainer.appendChild(prevButton);
    
    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const pageButton = document.createElement('button');
        pageButton.textContent = i;
        pageButton.className = currentPage === i ? 'active' : '';
        pageButton.addEventListener('click', () => loadProducts(i));
        paginationContainer.appendChild(pageButton);
    }
    
    // Next button
    const nextButton = document.createElement('button');
    nextButton.textContent = 'Next';
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => {
        if (currentPage < totalPages) loadProducts(currentPage + 1);
    });
    paginationContainer.appendChild(nextButton);
    
    // Page info
    const pageInfo = document.createElement('span');
    pageInfo.className = 'page-info';
    pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    paginationContainer.appendChild(pageInfo);
}
  function setupSearch() {
    const searchInput = document.getElementById('mainSearch');
    searchInput.addEventListener('input', function() {
      const term = this.value.trim();
      
      if (term.length >= 2) {
        // Show search results in a modal or redirect to search page
        window.location.href = `products.php?search=${encodeURIComponent(term)}`;
      }
    });
  }
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart-btn')) {
        const productId = e.target.dataset.productId;
        addToCart(productId);
    }
});
 
  </script>
</body>
<?php 
     
     include "includes/footer.php";
     ?>
</html>