<?php


require_once '../includes/init.php';
$database = new Database();
$db = $database->connect();
$user = new User($db);
$error = null;



    try {
        // Login and get user data
      
        $userData = $user->login($_POST['email'], $_POST['password']);
      
        // Store user information in session
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['user_name'] = $userData['name'];
        $_SESSION['user_role'] = $userData['role'] ?? 'customer';
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // If "Remember me" was checked
        if (isset($_POST['remember'])) {
            $remember_token = bin2hex(random_bytes(32));
            
            // Store token in database linked to user
            $user->setRememberToken($userData['id'], $remember_token);
            
            // Set cookie
            setcookie('remember_token', $remember_token, time() + (86400 * 30), "/", "", false, true);
        }
        
        // Merge any guest cart with user cart
        if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {
            $cart = new Cart($db);
            $cart->mergeGuestCart($_SESSION['guest_cart'], $userData['id']);
            unset($_SESSION['guest_cart']); // Clear guest cart
        }
        
        // Redirect to homepage or previous page
        $redirect_url = $_SESSION['redirect_after_login'] ?? '../index.php';
        unset($_SESSION['redirect_after_login']);
        
        header("Location: ../index.php " );
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        // You might want to display this error on the login page
        $_SESSION['login_error'] = $error;
        header("Location: ../account/login.php");
        exit();
    }
