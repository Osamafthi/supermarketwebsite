<?php
require_once '../includes/init.php';
header('Content-Type: application/json');

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    $productId = isset($input['product_id']) ? (int)$input['product_id'] : null;
    $quantity = isset($input['quantity']) ? (int)$input['quantity'] : null;
    
    // Support both product_id and item_id for backward compatibility
    if (!$productId && isset($input['item_id'])) {
        $productId = (int)$input['item_id'];
    }

    if ($productId === null || $quantity === null) {
        throw new Exception('Both product_id and quantity are required');
    }

    global $cart;

    if ($quantity <= 0) {
        // Remove item from cart
        $cart->removeItem($productId);
        $message = 'Item removed from cart';
    } else {
        // Check stock availability first
        $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock available');
        }

        // Update quantity by removing old and adding new
        $cart->removeItem($productId);
        $cart->addItem($productId, $quantity);
        $message = 'Cart updated successfully';
    }

    // Get updated cart count
    $cartCount = $cart->getCartCount();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cartCount
        
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'input_received' => $input ?? null
    ]);
}
