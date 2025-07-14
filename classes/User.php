<?php
require_once 'Database.php';

class User {
    private $conn;
    
    // User properties
    private $id;
    private $full_name;
    private $email;
    private $password;
    private $phone;
    private $address;
    private $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Getters and Setters
    public function getId() { return $this->id; }
    public function getFullName() { return $this->full_name; }
    // ... other getters ...


    
    
    public function register($full_name, $email, $password, $phone = null, $address = null, $role = 'customer') {
        // Validate input
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }
    
        // Check if email exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email already exists");
        }
    
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        $is_verified = 0; // 0 = not verified, 1 = verified
    
        // Insert new user with verification fields
        $stmt = $this->conn->prepare("
            INSERT INTO users 
            (full_name, email, password, phone, address, role, verification_token, is_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $success = $stmt->execute([
            $full_name, 
            $email, 
            $hashed_password, 
            $phone, 
            $address, 
            $role, 
            $verification_token,
            $is_verified
        ]);
        
       // In your register method, after calling sendVerificationEmail:
if ($success) {
    $this->sendVerificationEmail($email, $verification_token);
    
    // TEMPORARY DEBUG - Remove in production
    echo "<script>console.log('Verification email should have been sent to $email');</script>";
    error_log("Attempted to send verification email to $email with token: $verification_token");
    
    return true;
}
        
        return false;
    }
    
  
    private function sendVerificationEmail($email, $token) {
        // Load PHPMailer manually
        require_once __DIR__ . '/../includes/PHPMailer-6.10.0/src/PHPMailer.php';
        require_once __DIR__ . '/../includes/PHPMailer-6.10.0/src/SMTP.php';
        require_once __DIR__ . '/../includes/PHPMailer-6.10.0/src/Exception.php';
    
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
        try {
            // Server settings (Gmail example)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'fathiosama2017@gmail.com'; // Your Gmail
            $mail->Password   = 'fldc mkwl hfgo yakj'; // Gmail App Password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
    
            // Recipients
            $mail->setFrom('fathiosama2017@gmail.com', 'SuperMarket');
            $mail->addAddress($email);
    
            // Content
            $verification_url = "http://localhost/deepseek_noor_3la_noor/api/verify.php?token=" . $token;
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email';
            $mail->Body    = "Click <a href='$verification_url'>here</a> to verify your email.";
            $mail->AltBody = "Or paste this link in your browser: $verification_url";
    
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
            return false;
        }
    }

    // Get current user
    public static function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    
    public function login($email, $password) {
        // Check if role column exists, if not select without it
        $stmt = $this->conn->prepare("SELECT id, email, full_name, password, role, created_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password");
        }
        
        // Set default role if not present
        if (!isset($user['role']) || $user['role'] === null) {
            $user['role'] = 'customer';
        }
        
        // Update last login
        $this->updateLastLogin($user['id']);
        
        // Return user data (without password)
        unset($user['password']);
        return $user;
    }
    
    public function setRememberToken($userId, $token) {
        $hashedToken = hash('sha256', $token);
        $stmt = $this->conn->prepare("UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?");
        $stmt->execute([$hashedToken, date('Y-m-d H:i:s', time() + (86400 * 30)), $userId]);
    }
    
    public function loginWithRememberToken($token) {
        $hashedToken = hash('sha256', $token);
        $stmt = $this->conn->prepare("
            SELECT id, email, name, role 
            FROM users 
            WHERE remember_token = ? 
            AND remember_token_expires > NOW()
        ");
        $stmt->execute([$hashedToken]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Set default role if not present
            if (!isset($user['role']) || $user['role'] === null) {
                $user['role'] = 'customer';
            }
            
            $this->updateLastLogin($user['id']);
            return $user;
        }
        
        return false;
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: ../account/login.php");
            exit();
        }
    }
    
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getCurrentUserEmail() {
        return $_SESSION['user_email'] ?? null;
    }
    
    public static function getCurrentUserName() {
        return $_SESSION['user_name'] ?? null;
    }
    
    public function logout() {
        // Clear remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, "/");
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session for flash messages
        session_start();
    }
    public function initiatePasswordReset($email) {
        // Implementation would involve:
        // 1. Generating a reset token
        // 2. Storing it in database with expiry
        // 3. Sending email with reset link
    }
    
    // Complete password reset
    public function completePasswordReset($token, $new_password) {
        // Implementation would involve:
        // 1. Verifying token is valid and not expired
        // 2. Updating password
        // 3. Invalidating token
    }

    public static function isAdmin() {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        // Method 1: Check if user has admin role in database
        // Assuming you have a 'role' column in users table
        $db = $GLOBALS['db'];
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([self::getCurrentUserId()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user && $user['role'] === 'admin';
        
        // Method 2: Alternative - check by email (if you have specific admin emails)
        // $adminEmails = ['admin@yourstore.com', 'manager@yourstore.com'];
        // return in_array(self::getCurrentUserEmail(), $adminEmails);
        
        // Method 3: Alternative - check by user ID (if you have specific admin IDs)
        // $adminIds = [1, 2, 3]; // Your admin user IDs
        // return in_array(self::getCurrentUserId(), $adminIds);
    }

    
    
}