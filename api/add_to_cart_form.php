<?php
require_once '../includes/init.php';

// Get product ID from POST
$productId = $_POST['product_id'] ?? null;

try {
    if (!$productId) {
        throw new Exception('Product ID is required');
    }

    $database = new Database();
    $db = $database->connect();
    
    // Check if product exists
    $product = new Product($db);
    if (!$product->readOne($productId)) {
        throw new Exception('Product not found');
    }

    // Use the consistent session ID
    $sessionId = session_id();
    
    // Check if product already in cart
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE session_id = ? AND product_id = ?");
    $stmt->execute([$sessionId, $productId]);
    $existingItem = $stmt->fetch();

    if ($existingItem) {
        // Update quantity
        $stmt = $db->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
        $stmt->execute([$existingItem['id']]);
    } else {
        // Add new item
        $stmt = $db->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$sessionId, $productId]);
    }

    // Return success response
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?cart_success=1');
    exit;

} catch (Exception $e) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '?cart_error=' . urlencode($e->getMessage()));
    exit;
}