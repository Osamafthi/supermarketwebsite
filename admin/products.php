<?php
require_once '../includes/init.php';
if (!User::isAdmin()) {
 
  header('Location: http://localhost/deepseek_noor_3la_noor/index.php');
  exit();
}
$database = new Database();
$db = $database->connect();

$product = new Product($db);

// Get filters from URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$category_filter = isset($_GET['category']) ? $_GET['category'] : null;

// Fetch products based on filters
if ($category_filter) {
    $products = $product->readByCategory($category_filter);
} elseif ($status_filter) {
    $products = $product->readAll($status_filter);
} else {
    $products = $product->readAll();
}

// Fetch all categories for the dropdown
$category_sql = "SELECT * FROM categories";
$categories = $db->query($category_sql)->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Product Management</title>
  <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
<?php 
include "../includes/header.php";
?>
</head>
<body>
  <div class="admin-container">
    <!-- Admin Header -->
    
    <header class="admin-header">
  
      <h1>Product Management</h1>
      <div class="admin-actions">
      <input type="search" id="productSearch" placeholder="Search products..." class="admin-search">
       
        <div class="status-filter">
        <select onchange="filterProducts(this.value)">
           <option value="all" <?php echo (!isset($_GET['status'])) ? 'selected' : ''; ?>>All Statuses</option>
           <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
           <option value="archived" <?php echo (isset($_GET['status']) && $_GET['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
       </select>

       </div>
       <div class="category-filter">
        <select onchange="filterByCategory(this.value)">
          <option value="">All Categories</option>
          <?php foreach ($categories as $category): ?>
          <option value="<?php echo $category['id']; ?>" 
          <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($category['name']); ?>
          </option>
          <?php endforeach; ?>
       </select>

       </div>

       <button class="btn-clear-filters" onclick="clearFilters()">Clear Filters</button>

        <button class="btn btn-primary" onclick="openProductModal()">
          + New Product
        </button>
      </div>
    </header>
      
 

     <div class="form-group">
       <label>Category</label>
       <div class="category-select-container">
       <select name="category_id" id="categorySelect">
         <?php foreach ($categories as $category): ?>
           <option value="<?php echo $category['id']; ?>">
           <?php echo htmlspecialchars($category['name']); ?>
          </option>
        <?php endforeach; ?>
       </select>
       <button type="button" class="btn btn-secondary" onclick="openCategoryModal()">
         + Add Category
       </button>
  </div>
</div>
<!-- Add this inside the .admin-actions div -->



    <!-- Category Filter Buttons -->



    <!-- Products Table -->
    <table class="data-table">
      <thead>
        <tr>
          <th><input type="checkbox"></th>
          <th>Product</th>
          <th>SKU</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Category</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="productsTableBody">
  <!-- This will be populated by JavaScript -->
  <tr>
    <td colspan="8" class="loading-message">Loading products...</td>
  </tr>
</tbody>
    </table>

    <!-- Table Footer -->
    <div class="table-footer">
      <div class="bulk-actions">
        <select>
          <option>Bulk Actions</option>
          <option>Update Categories</option>
          <option>Adjust Prices</option>
          <option>Archive Products</option>
        </select>
        <button class="btn-apply">Apply</button>
      </div>
      <div class="pagination">
        <span>Showing 1-10 of 150 products</span>
        <div class="page-controls">
          <button class="btn-prev">←</button>
          <button class="btn-next">→</button>
        </div>
      </div>
    </div>

    <!-- Product Modal -->
    <div class="modal" id="productModal">
      <div class="modal-content">
        <span class="close" onclick="closeProductModal()">&times;</span>
        <h2>New Product</h2>
        <form class="product-form" action="add_product.php"  method="POST" enctype="multipart/form-data">

          <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name"required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Price ($)</label>
              <input type="number" name="price" step="0.01" required>
            </div>
            <div class="form-group">
              <label>Stock Quantity</label>
              <input type="number" name="quantity" min="0" required>
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" required>
            <label>Category</label>
          
            <select name="category_id" id="categorySelect">
              <?php foreach ($categories as $category): ?>
              <option value="<?php echo $category['id']; ?>">
              <?php echo htmlspecialchars($category['name']); ?>
              </option>
             <?php endforeach; ?>
             </select>
          </div>
          <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" accept="image/*">
          </div>
          <button type="submit" class="btn btn-primary">Save Product</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Product Modal --><div class="modal" id="editProductModal">
  <div class="modal-content">
    <span class="close" onclick="closeEditProductModal()">&times;</span>
    <h2>Edit Product</h2>
    <form class="product-form" action="edit_product.php" method="POST" enctype="multipart/form-data" id="editProductForm">
      <!-- Put these back in your edit form -->
<input type="hidden" name="id" id="edit-id">
<input type="hidden" name="csrf_token" id="edit-csrf" value="<?php echo generateCsrfToken(); ?>">

      <div class="form-group">
        <label>Product Name</label>
        <input type="text" name="name" id="edit-name" required>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Price ($)</label>
          <input type="number" name="price" id="edit-price" step="0.01" required>
        </div>
        <div class="form-group">
          <label>Stock Quantity</label>
          <input type="number" name="quantity" id="edit-quantity" min="0" required>
        </div>
      </div>
      
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="edit-description" required></textarea>
      </div>
      
      <div class="form-group">
        <label>Category</label>
        <select name="category_id" id="edit-category_id">
          <?php foreach ($categories as $category): ?>
          <option value="<?php echo $category['id']; ?>">
            <?php echo htmlspecialchars($category['name']); ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label>Current Image</label>
        <div id="current-image-container" style="margin-bottom: 10px;">
          <!-- Will be populated by JavaScript -->
        </div>
        
        <label>Change Image (optional)</label>
        <input type="file" name="image" id="edit-image" accept="image/*">
        
        <div class="image-options" style="margin-top: 10px;">
          <label>
            <input type="checkbox" name="remove_image" id="remove-image">
            Remove current image
          </label>
        </div>
      </div>
      
      <button type="submit" class="btn btn-primary">Update Product</button>
    </form>
  </div>
</div>

<div class="modal" id="categoryModal">
  <div class="modal-content">
    <span class="close" onclick="closeCategoryModal()">&times;</span>
    <h2>Add New Category</h2>
    <form class="category-form" action="add_category.php" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Category Name</label>
        <input type="text" name="name" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="3"></textarea>
      </div>
      <div class="form-group">
        <label>Category Image</label>
        <input type="file" name="image" accept="image/*">
      </div>
      <button type="submit" class="btn btn-primary">Save Category</button>
    </form>
  </div>
</div>
<?php include "../includes/footer.php"; ?>
<?php function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}?>
  <script>
    function openProductModal() {
      document.getElementById('productModal').style.display = 'block';
    }
    function closeProductModal() {
      document.getElementById('productModal').style.display = 'none';
    }


  // Update openEditProductModal to show current image
function openEditProductModal(button) {
    const row = button.closest('tr');
    const id = button.getAttribute("data-id");
    const name = button.getAttribute("data-name");
    const price = button.getAttribute("data-price");
    const quantity = button.getAttribute("data-quantity");
    const description = button.getAttribute("data-description");
    const category = button.getAttribute("data-category");
    const imageSrc = row.querySelector('.product-thumb').getAttribute('src');

    document.getElementById("edit-id").value = id;
    document.getElementById("edit-name").value = name;
    document.getElementById("edit-price").value = price;
    document.getElementById("edit-quantity").value = quantity;
    document.getElementById("edit-description").value = description;
    document.getElementById("edit-category_id").value = category;
    
    // Display current image
    const imageContainer = document.getElementById("current-image-container");
    if (imageSrc.includes('placeholder.jpg')) {
        imageContainer.innerHTML = '<p>No current image</p>';
    } else {
        imageContainer.innerHTML = `<img src="${imageSrc}" style="max-width: 200px; max-height: 200px;">`;
    }

    document.getElementById("editProductModal").style.display = "block";
}

function closeEditProductModal() {
  document.getElementById("editProductModal").style.display = "none";
}
function filterProducts(status) {
    showLoading(true);
    
    // Clear any existing search term
    const searchInput = document.getElementById('productSearch');
    if (searchInput) searchInput.value = '';
    
    // Update URL without reloading
    const urlParams = new URLSearchParams(window.location.search);
    if (status && status !== 'all') {
        urlParams.set('status', status);
    } else {
        urlParams.delete('status');
    }
    urlParams.delete('search'); // Remove search when filtering by status
    history.replaceState(null, '', '?' + urlParams.toString());
    
    // Fetch products by status
    if (status && status !== 'all') {
        fetch(`search_products.php?status=${status}`)
            .then(response => response.json())
            .then(products => {
                renderProducts(products);
                showLoading(false);
            })
            .catch(error => {
                console.error('Status filter failed:', error);
                showError('Failed to filter by status');
                showLoading(false);
            });
    } else {
        // If "All Statuses" was selected
        loadAllProducts();
    }
}
function filterByCategory(categoryId) {
    showLoading(true);
    
    // Clear any existing search term
    const searchInput = document.getElementById('productSearch');
    if (searchInput) searchInput.value = '';
    
    // Update URL without reloading
    const urlParams = new URLSearchParams(window.location.search);
    if (categoryId) {
        urlParams.set('category', categoryId);
    } else {
        urlParams.delete('category');
    }
    urlParams.delete('search'); // Remove search when filtering by category
    history.replaceState(null, '', '?' + urlParams.toString());
    
    // Fetch products by category
    if (categoryId) {
        fetch(`search_products.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(products => {
                renderProducts(products);
                showLoading(false);
            })
            .catch(error => {
                console.error('Category filter failed:', error);
                showError('Failed to filter by category');
                showLoading(false);
            });
    } else {
        // If "All Categories" was selected
        loadAllProducts();
    }
}


function clearFilters() {
  window.location.href = 'products.php';
}

function openCategoryModal() {
  document.getElementById('categoryModal').style.display = 'block';
}

function closeCategoryModal() {
  document.getElementById('categoryModal').style.display = 'none';
}

// Handle category form submission with AJAX
document.querySelector('.category-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const form = this;
  const formData = new FormData(form);
  
  fetch(form.action, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.id) {
      // Add the new category to the select dropdown
      const select = document.getElementById('categorySelect');
      const option = document.createElement('option');
      option.value = data.id;
      option.textContent = data.name;
      option.selected = true;
      select.appendChild(option);
      
      // Close the modal and reset the form
      closeCategoryModal();
      form.reset();
    }
  })
  .catch(error => {
    console.error('Error:', error);
  });
});

// Real-time search function

  </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search functionality
    initSearch();
    
    // Check for initial filters
    const urlParams = new URLSearchParams(window.location.search);
    const initialCategory = urlParams.get('category');
    const initialStatus = urlParams.get('status');
    const initialSearch = urlParams.get('search');
    
    if (initialCategory) {
        filterByCategory(initialCategory);
    } else if (initialStatus) {
        filterProducts(initialStatus);
    } else if (initialSearch) {
        const searchInput = document.getElementById('productSearch');
        if (searchInput) searchInput.value = initialSearch;
        searchProducts(initialSearch);
    } else {
        loadAllProducts();
    }

    
    
});
const editForm = document.getElementById('editProductForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    closeEditProductModal();
                    loadAllProducts();
                } else {
                    throw new Error('Network response was not ok');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating product');
            });
        });
    }

function initSearch() {
    const searchInput = document.getElementById('productSearch') || document.querySelector('.admin-search');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const term = this.value.trim();
           
            if (term.length >= 2) {
                searchProducts(term);
            } else {
                loadAllProducts();
            }
        });
    }
}

function searchProducts(term) {
 
    showLoading(true);
    fetch(`search_products.php?term=${encodeURIComponent(term)}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(products => {
            // Handle both array and object responses
            const productsArray = Array.isArray(products) ? products : (products.data || []);
            renderProducts(productsArray);
            showLoading(false);
        })
        .catch(error => {
            console.error('Search failed:', error);
            showError('Search failed. Please try again.');
            showLoading(false);
        });
}

function loadAllProducts() {
    showLoading(true);
    
    const urlParams = new URLSearchParams(window.location.search);
    const categoryId = urlParams.get('category');
    const status = urlParams.get('status');
    
    let fetchUrl = 'search_products.php?';
    if (categoryId) {
        fetchUrl += `category_id=${categoryId}`;
    }
    
    fetch(fetchUrl)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            // Handle both array and object responses safely
            const products = Array.isArray(data) ? data : (data.data || []);
            renderProducts(products);
            showLoading(false);
        })
        .catch(error => {
            console.error('Failed to load products:', error);
            showError('Failed to load products. Please refresh the page.');
            showLoading(false);
        });
}
function showError(message) {
    const errorElement = document.createElement('div');
    errorElement.className = 'search-error';
    errorElement.innerHTML = `
        <p>${message}</p>
        <button onclick="this.parentElement.remove()">Dismiss</button>
    `;
    document.querySelector('.admin-container').prepend(errorElement);
}

function renderProducts(products) {
    const tableBody = document.getElementById('productsTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = ''; // Clear existing content
    
    if (!products || products.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="9">No products found</td></tr>';
        return;
    }
    
    products.forEach(product => {
        const price = parseFloat(product.price) || 0;
        const isFeatured = product.is_featured == 1;
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="checkbox"></td>
            <td class="product-info">
                <img src="../uploads/${product.image || 'placeholder.jpg'}" 
                     alt="${product.name}" class="product-thumb">
                <span>${product.name}</span>
            </td>
            <td>PROD-${product.id}</td>
            <td>$${price.toFixed(2)}</td>
            <td><input type="number" value="${product.stock_quantity}" min="0" class="stock-input"></td>
            <td>${product.category_name}</td>
            <td>
                <span class="status-badge ${product.status === 'active' ? 'active' : 'archived'}">
                    ${product.status.charAt(0).toUpperCase() + product.status.slice(1)}
                </span>
            </td>
            <td>
                <span class="featured-badge ${isFeatured ? 'featured' : 'not-featured'}">
                    ${isFeatured ? 'Featured' : 'Regular'}
                </span>
            </td>
            <td>
                <button class="btn-edit" 
                    data-id="${product.id}"
                    data-name="${product.name}"
                    data-price="${price}"
                    data-quantity="${product.stock_quantity}"
                    data-description="${product.description}"
                    data-category="${product.category_id}"
                    onclick="openEditProductModal(this)">Edit</button>
                
                ${product.status === 'active' 
                    ? `<button class="btn-archive" onclick="archiveProduct(${product.id})">Archive</button>`
                    : `<button class="btn-activate" onclick="activateProduct(${product.id})">Activate</button>`}

                   
                    ${product.isFeatured === 1 
                       ? `<button class="btn-unfeature" onclick="unfeatureProduct(${product.id})">Remove Feature</button>`
                       : `<button class="btn-feature" onclick="featureProduct(${product.id})">Set as Featured</button>`}
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Add these new functions to your admin JS
function featureProduct(productId) {
    if (confirm('Set this product as featured?')) {
        fetch(`set_featured.php?id=${productId}`)
            .then(response => {
                if (response.ok) {
                    // Replace the button
                  loadAllProducts();
                    
                  
                
                }
            });
    }
}

function unfeatureProduct(productId) {
    if (confirm('Remove this product from featured items?')) {
        fetch(`unset_featured.php?id=${productId}`)
            .then(response => {
                if (response.ok) {
                    // Replace button
                   loadAllProducts();
                
                }
            });
    }
}

// Helper function to update status badge
function updateFeaturedBadge(buttonElement, isFeatured) {
    const row = buttonElement.closest('tr');
    const badge = row.querySelector('.featured-badge');
    if (badge) {
        badge.textContent = isFeatured ? 'Featured' : 'Regular';
        badge.className = `featured-badge ${isFeatured ? 'featured' : 'not-featured'}`;
    }
}
function archiveProduct(productId) {
    if (confirm('Are you sure you want to archive this product?')) {
        fetch(`archieve_product.php?id=${productId}`)
            .then(response => {
                if (response.ok) {
                    loadAllProducts(); // Refresh the product list
                } else {
                    alert('Failed to archive product');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error archiving product');
            });
    }
}

function activateProduct(productId) {
    if (confirm('Are you sure you want to activate this product?')) {
        fetch(`activate_product.php?id=${productId}`)
            .then(response => {
                if (response.ok) {
                    loadAllProducts(); // Refresh the product list
                } else {
                    alert('Failed to activate product');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error activating product');
            });
    }
}
function showLoading(show) {
    const tableBody = document.getElementById('productsTableBody');
    if (!tableBody) return;
    
    if (show) {
        tableBody.innerHTML = '<tr><td colspan="8" class="loading-message">Loading...</td></tr>';
    }
}

function showError(message) {
    const errorElement = document.createElement('div');
    errorElement.className = 'search-error';
    errorElement.textContent = message;
    
    const container = document.querySelector('.admin-container') || document.body;
    container.prepend(errorElement);
    
    setTimeout(() => errorElement.remove(), 3000);
}
</script>

</body>
</html>