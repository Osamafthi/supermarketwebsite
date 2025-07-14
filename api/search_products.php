<?php
require_once '../includes/init.php';
header('Content-Type: application/json');

$searchTerm = $_GET['term'] ?? '';
$searchTerm = trim($searchTerm);

if (empty($searchTerm)) {
    echo json_encode(['success' => false, 'error' => 'Search term is required']);
    exit();
}

try {
    $database = new Database();
    $db = $database->connect();
    $product = new Product($db);

    $results = $product->search($searchTerm);
    $products = [];

    while ($row = $results->fetch(PDO::FETCH_ASSOC)) {
        $products[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'stock_quantity' => (int)$row['stock_quantity'],
            'image' => $row['image'],
            'category_name' => $row['category_name'] ?? '',
            'status' => $row['status'] ?? 'active',
            'description' => $row['description'] ?? '',
            'category_id' => (int)($row['category_id'] ?? 0)
        ];
    }

    echo json_encode(['success' => true, 'products' => $products]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
