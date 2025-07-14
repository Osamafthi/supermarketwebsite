<?php
require_once '../includes/init.php';
// Create a test file

$database = new Database();
$db = $database->connect();
$user = new User($db);

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Verify the token
        $stmt = $db->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            // Mark as verified
            $user_id = $stmt->fetchColumn();
            $update = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            $update->execute([$user_id]);
            
            echo "Email verified successfully! You can now <a href='login.php'>log in</a>.";
        } else {
            echo "Invalid or expired token.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No verification token provided.";
}
