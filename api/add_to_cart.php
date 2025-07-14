<?php
require_once '../includes/init.php';
header('Content-Type: application/json');


try {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = $input['product_id'] ?? null;
    $quantity = $input['quantity'] ?? 1;

    if (!$productId) {
        throw new Exception('Product ID is required');
    }

    // Check if product exists and get product info
    $stmt = $db->prepare("SELECT id, name, stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Product not found');
    }

    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        throw new Exception('Insufficient stock available');
    }

    // Use the global cart object from init.php
    global $cart;
    $cart->addItem($productId, $quantity);

    // Get updated cart count
    $cartCount = $cart->getCartCount();

    echo json_encode([
        'success' => true,
        'cart_count' => $cartCount,
        'message' => 'Product added to cart successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
