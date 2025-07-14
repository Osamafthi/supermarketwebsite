<?php
require_once '../includes/init.php';
header('Content-Type: application/json');

try {
    // Use the global cart object from init.php
    global $cart;
    $count = $cart->getCartCount();

    echo json_encode([
        'success' => true,
        'count' => $count
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'count' => 0
    ]);
}
