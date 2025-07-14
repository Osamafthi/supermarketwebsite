<?php
// Absolute first line - no spaces before!
require_once '../includes/init.php';

// Set headers before any output
header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();
    $product = new Product($db);
    
    // Get parameters with proper sanitization
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
    
    // Get paginated products
    $result = $product->getPaginatedProducts($page, $perPage);
    
    // Ensure all prices are floats
    array_walk($result['products'], function(&$product) {
        $product['price'] = (float)$product['price'];
    });
    
    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $result['products'],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $result['total'],
                'total_pages' => ceil($result['total'] / $perPage)
            ]
        ]
    ]);
    exit; // Prevent any additional output
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}