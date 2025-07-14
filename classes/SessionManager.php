<?php
// File: includes/SessionManager.php
require_once 'Database.php';
require_once 'User.php';

class SessionManager {
    private $db;
    private $user;
    
    public function __construct($database) {
        // Get the PDO connection from the Database object
        $this->db = $database->connect();
        $this->user = new User($this->db); // Pass PDO connection, not Database object
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check for remember me auto-login
        $this->checkRememberMe();
    }
    
    private function checkRememberMe() {
        // If user is not logged in but has remember token
        if (!User::isLoggedIn() && isset($_COOKIE['remember_token'])) {
            $userData = $this->user->loginWithRememberToken($_COOKIE['remember_token']);
            
            if ($userData) {
                // Set session data
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['user_email'] = $userData['email'];
                $_SESSION['user_name'] = $userData['name'];
                $_SESSION['user_role'] = $userData['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
            } else {
                // Invalid or expired token, clear cookie
                setcookie('remember_token', '', time() - 3600, "/");
            }
        }
    }
    
    public function extendSession() {
        if (User::isLoggedIn()) {
            $_SESSION['last_activity'] = time();
        }
    }
    
    public function isSessionExpired($timeout = 7200) { // 2 hours default
        if (User::isLoggedIn() && isset($_SESSION['last_activity'])) {
            return (time() - $_SESSION['last_activity']) > $timeout;
        }
        return false;
    }
}