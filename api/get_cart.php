<?php


require_once '../includes/init.php';
header('Content-Type: application/json');

try {
    // Use the global cart object from init.php
    global $cart;
    $items = $cart->getCartItems();
    
    $subtotal = 0;
    $processedItems = [];

    
    
    foreach ($items as $item) {
        // Ensure price is treated as a float
        $price = (float)$item['price'];
        $quantity = (int)$item['quantity'];
        $total = $price * $quantity;
           
        $processedItems[] = [
            'id' => $item['id'] ?? null,
            'product_id' => $item['product_id'] ?? $item['id'],
            'name' => $item['name'],
            'price' => $price,
            'quantity' => $quantity,
            'total' => $total,
            'image_url' => $item['image_url'] ?? $item['image'] ?? null,
            'stock_quantity' => $item['stock_quantity'] ?? 0
        ];
        
        $subtotal += $total;
    }
    
    $tax = $subtotal * 0.1; // 10% tax example
    $total = $subtotal + $tax;
    $itemCount = array_sum(array_column($processedItems, 'quantity'));

    echo json_encode([
        'success' => true,
        'data' => [
            'items' => $processedItems,
            'summary' => [
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'total' => round($total, 2),
                'item_count' => $itemCount
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
