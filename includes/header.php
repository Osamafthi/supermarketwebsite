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
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/includes/header.css?v=<?php echo time(); ?>">
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

  // Header shrinking effect for mobile devices
document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.header');
    let lastScrollTop = 0;
    let scrollTimeout;
    
    // Check if device is mobile
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Throttle scroll events for better performance
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }
    
    // Handle scroll events
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (isMobile()) {
            // Add shrinking effect on mobile
            if (scrollTop > 50) {
                header.classList.add('mobile-scrolled');
            } else {
                header.classList.remove('mobile-scrolled');
            }
        } else {
            // Remove mobile classes on desktop
            header.classList.remove('mobile-scrolled');
        }
        
        // Add general scrolled class for all devices
        if (scrollTop > 10) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    }
    
    // Add scroll event listener with throttling
    window.addEventListener('scroll', throttle(handleScroll, 16));
    
    // Handle resize events
    window.addEventListener('resize', throttle(function() {
        if (!isMobile()) {
            header.classList.remove('mobile-scrolled');
        }
    }, 250));
    
    // Initialize on page load
    handleScroll();
    
    // Add smooth search functionality
    const searchBar = document.getElementById('mainSearch');
    if (searchBar) {
        searchBar.addEventListener('input', function(e) {
            // Add your search logic here
            console.log('Searching for:', e.target.value);
        });
        
        // Handle search form submission
        const searchForm = document.querySelector('.search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Add your search submission logic here
                console.log('Search submitted:', searchBar.value);
            });
        }
    }
    
    // Add cart count animation
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        // Function to update cart count with animation
        function updateCartCount(newCount) {
            cartCount.style.transform = 'scale(1.3)';
            cartCount.textContent = newCount;
            
            setTimeout(() => {
                cartCount.style.transform = 'scale(1)';
            }, 200);
        }
        
        // Example usage: updateCartCount(5);
    }
    
    // Add loading states for navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add loading state
            this.style.opacity = '0.7';
            
            // Remove loading state after a short delay
            setTimeout(() => {
                this.style.opacity = '1';
            }, 500);
        });
    });
    
    // Add keyboard navigation support
    document.addEventListener('keydown', function(e) {
        // Focus search bar with Ctrl/Cmd + K
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchBar) {
                searchBar.focus();
            }
        }
    });
    
    // Add touch gesture support for mobile
    let touchStartY = 0;
    let touchStartTime = 0;
    
    if (isMobile()) {
        document.addEventListener('touchstart', function(e) {
            touchStartY = e.touches[0].clientY;
            touchStartTime = Date.now();
        });
        
        document.addEventListener('touchmove', function(e) {
            const touchY = e.touches[0].clientY;
            const deltaY = touchY - touchStartY;
            const deltaTime = Date.now() - touchStartTime;
            
            // If scrolling up quickly, ensure header is visible
            if (deltaY > 50 && deltaTime < 300) {
                header.classList.remove('mobile-scrolled');
            }
        });
    }
});
    </script>