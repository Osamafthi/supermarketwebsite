<?php
require_once '../includes/init.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();
    
    $product = new Product($db);
    $products = $product->getFeaturedProducts();



    // After getting the products but before json_encode
foreach ($products as &$product) {
    $product['price'] = (float)$product['price'];
}
unset($product); // Break the reference
    // Debug output
    if (empty($products)) {
        error_log("No featured products found");
    }
    
    echo json_encode([
        'success' => true,
        'data' => $products
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_featured_products.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTrace() // Only for debugging, remove in production
    ]);
}