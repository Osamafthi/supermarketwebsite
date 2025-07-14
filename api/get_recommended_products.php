<?php
// File: api/get_recommendations.php
require_once '../includes/init.php';

// Set headers before any output
header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->connect();
    $product = new Product($db);
    
    // Get product ID from GET parameters
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
    $limit = isset($_GET['limit']) ? min(20, max(1, (int)$_GET['limit'])) : 8;
    
    if (!$productId) {
        throw new Exception('Product ID is required');
    }
    
    // Verify product exists
    $product->setId($productId);
    if (!$product->read_single()) {
        throw new Exception('Product not found');
    }
    
    // Get recommended products
    $recommendations = $product->getRecommendedProducts($productId, $limit);
    
    // Format the response
    $formattedRecommendations = array_map(function($item) {
        return [
            'id' => (int)$item['id'],
            'name' => htmlspecialchars($item['name']),
            'price' => (float)$item['price'],
            'image' => $item['image'] ?? 'default-product.jpg',
            'category_name' => htmlspecialchars($item['category_name'] ?? ''),
            'stock_quantity' => (int)$item['stock_quantity'],
            'in_stock' => (int)$item['stock_quantity'] > 0
        ];
    }, $recommendations);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'recommendations' => $formattedRecommendations,
            'count' => count($formattedRecommendations)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
exit;
