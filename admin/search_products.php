<?php
header('Content-Type: application/json');
require_once '../includes/init.php';

$term = $_GET['term'] ?? '';
$categoryId = $_GET['category_id'] ?? null;
$status = $_GET['status'] ?? null;

try {
    $database = new Database();
    $db = $database->connect();
    
    $product = new Product($db);
   
    
    if (!empty($term)) {
        $results = $product->search($term);
    } elseif (!empty($categoryId)) {
        $results = $product->readByCategory($categoryId);
    } elseif (!empty($status)) {
        $results = $product->readByStatus($status);
    } else {
        $results = $product->readAll();
    }
 
    
    // Format the results consistently
    $formattedResults = [];
    while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
        $formattedResults[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'stock_quantity' => (int)$row['stock_quantity'],
            'image' => $row['image'],
            'category_name' => $row['category_name'] ?? '',
            'status' => $row['status'] ?? 'active',
            'description' => $row['description'] ?? '',
            'category_id' => (int)($row['category_id'] ?? 0),
            'isFeatured' => $row['is_featured'] ?? '1'
        ];
    }
    
    echo json_encode($formattedResults);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}