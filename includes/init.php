<?php
// File: includes/init.php - Include this at the top of every page
require_once 'autoload.php';

// Initialize session and database

$database = new Database();
$db = $database->connect();
$sessionManager = new SessionManager($database);

// Extend session activity
$sessionManager->extendSession();

// Check for session timeout
if ($sessionManager->isSessionExpired()) {
    $user = new User($db);
    $user->logout();
    $_SESSION['session_expired'] = true;
    header("Location: ../account/login.php");
    exit();
}
// Add this to your init.php
define('BASE_PATH', '/deepseek_noor_3la_noor/');
// Initialize cart
$cart = new Cart($db);

// Helper functions for templates
function isLoggedIn() {
    return User::isLoggedIn();
}

function getCurrentUser() {
    if (User::isLoggedIn()) {
        return [
            'id' => User::getCurrentUserId(),
            'email' => User::getCurrentUserEmail(),
            'name' => User::getCurrentUserName()
        ];
    }
    return null;
}
// Redirect non-admin users trying to access admin pages
function adminOnly() {
    if (!User::isAdmin()) {
        header('Location: /login.php');
        exit();
    }
}
function getCartCount() {
    global $cart;
    return $cart->getCartCount();
}

function requireLogin() {
    User::requireLogin();
}

// Make these available globally
$GLOBALS['cart'] = $cart;
$GLOBALS['db'] = $db;
