<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'Database.php';


class Cart {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function addItem($productId, $quantity = 1) {
        if (User::isLoggedIn()) {
            // Logged in user - save to database
            $this->addToUserCart(User::getCurrentUserId(), $productId, $quantity);
        } else {
            // Guest user - save to session
            $this->addToGuestCart($productId, $quantity);
        }
    }
    
    private function addToUserCart($userId, $productId, $quantity) {
        // Check if item already exists in cart
        $stmt = $this->db->prepare("
            SELECT id, quantity FROM cart_items 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            $stmt = $this->db->prepare("
                UPDATE cart_items SET quantity = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$newQuantity, $existing['id']]);
        } else {
            // Insert new item
            $stmt = $this->db->prepare("
                INSERT INTO cart_items (user_id, product_id, quantity, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $productId, $quantity]);
        }
    }
    
    private function addToGuestCart($productId, $quantity) {
        if (!isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
        }
        
        if (isset($_SESSION['guest_cart'][$productId])) {
            $_SESSION['guest_cart'][$productId] += $quantity;
        } else {
            $_SESSION['guest_cart'][$productId] = $quantity;
        }
    }
    
    public function getCartItems() {
        if (User::isLoggedIn()) {
            return $this->getUserCartItems(User::getCurrentUserId());
        } else {
            return $this->getGuestCartItems();
        }
    }
    
    private function getUserCartItems($userId) {
        $stmt = $this->db->prepare("
            SELECT ci.id, ci.product_id, ci.quantity, p.name, p.price, p.image as image_url, p.stock_quantity
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?
            ORDER BY ci.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getGuestCartItems() {
        if (!isset($_SESSION['guest_cart']) || empty($_SESSION['guest_cart'])) {
            return [];
        }
        
        $productIds = array_keys($_SESSION['guest_cart']);
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        
        $stmt = $this->db->prepare("
            SELECT id, name, price, image, stock_quantity
            FROM products 
            WHERE id IN ($placeholders)
        ");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add quantities from session
        foreach ($products as &$product) {
            $product['quantity'] = $_SESSION['guest_cart'][$product['id']];
            $product['product_id'] = $product['id']; // Add for consistency
        }
        
        return $products;
    }
    
    public function mergeGuestCart($guestCart, $userId) {
        foreach ($guestCart as $productId => $quantity) {
            $this->addToUserCart($userId, $productId, $quantity);
        }
    }
    
    public function removeItem($productId) {
        if (User::isLoggedIn()) {
            $stmt = $this->db->prepare("
                DELETE FROM cart_items 
                WHERE user_id = ? AND product_id = ?
            ");
         
           



            $stmt->execute([User::getCurrentUserId(), $productId]);
        } else {
            unset($_SESSION['guest_cart'][$productId]);
        }
    }
    
    public function clearCart() {
        if (User::isLoggedIn()) {
            $stmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([User::getCurrentUserId()]);
        } else {
            $_SESSION['guest_cart'] = [];
        }
    }
    
    public function getCartTotal() {
        $items = $this->getCartItems();
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return $total;
    }
    
    public function getCartCount() {
        if (User::isLoggedIn()) {
            $stmt = $this->db->prepare("
                SELECT SUM(quantity) as total 
                FROM cart_items 
                WHERE user_id = ?
            ");
            $stmt->execute([User::getCurrentUserId()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } else {
            return array_sum($_SESSION['guest_cart'] ?? []);
        }
    }
}
