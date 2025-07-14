<?php
require_once 'init.php';
$admin='admin';


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'SuperMarket'; ?></title>
    <link rel="stylesheet" href="../style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <nav class="navbar container">
            <div class="logo">
                <a href="<?php echo BASE_PATH; ?>index.php">SuperMarket</a>
            </div>
            <div class="search-form">
    <input type="search" id="mainSearch" placeholder="Search products..." class="search-bar">
    <button type="submit" class="search-btn">🔍</button></div>
            <div class="nav-links">
                <a href="<?php echo BASE_PATH; ?>index.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
               
                <div class="cart-icon">
                <a href="<?php echo BASE_PATH; ?>cart/cart.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">
                            <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
                        </span>
                    </a>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
    <a href="<?php echo BASE_PATH; ?>/account/account.php" class="nav-link"><i class="fas fa-user"></i> Account</a>
    <a href="<?php echo BASE_PATH; ?>account/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
   
    
<?php else: ?>
    <a href="<?php echo BASE_PATH; ?>account/login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a>
<?php endif; ?>

<?php if (isset($_SESSION['user_id'])&&$_SESSION['user_role']===$admin): ?>
    <a href="<?php echo BASE_PATH;?>admin/products.php" class="nav-link"><i class="fas fa-admin-products-alt"></i> admin_products</a>
    <a href="<?php echo BASE_PATH;?>admin/index.php" class="nav-link"><i class="fas fa-admin-products-alt"></i> admin_index</a>
    <a href="<?php echo BASE_PATH;?>admin/orders.php" class="nav-link"><i class="fas fa-admin-products-alt"></i> admin_orders</a>
    <a href="<?php echo BASE_PATH;?>admin/create_user.php" class="nav-link"><i class="fas fa-admin-products-alt"></i> admin_create_user</a>
    <?php endif; ?>

            </div>
        </nav>
    </header>

    <main class="container">


    <script>
        document.addEventListener('DOMContentLoaded', function() {
  

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
        window.location.href = `<?php echo BASE_PATH; ?>shop/search.php?term=${encodeURIComponent(searchTerm)}`;
    }

    // Optional: Add click handler for search button if it exists
    const searchButton = searchForm.querySelector('.search-btn');
    if (searchButton) {
        searchButton.addEventListener('click', performSearch);
    }

  });
    </script>