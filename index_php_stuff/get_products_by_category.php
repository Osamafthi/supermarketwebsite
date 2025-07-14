<?php
require_once '../includes/init.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    $categoryId = $_GET['category_id'] ?? null;
    
    if (!$categoryId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Category ID is required'
        ]);
        exit;
    }
    
    $database = new Database();
    $db = $database->connect();
    $product = new Product($db);
    
    // Get the statement and fetch results
    $stmt = $product->readByCategory($categoryId);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert price to float
    foreach ($products as &$product) {
        $product['price'] = (float)$product['price'];
    }
    unset($product); // Break the reference
    
    echo json_encode([
        'success' => true,
        'data' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_products_by_category.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}