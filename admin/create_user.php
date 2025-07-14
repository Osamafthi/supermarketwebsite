<?php
require_once '../includes/init.php';

// Only allow admins to access this page


$database = new Database();
$db = $database->connect();
$user = new User($db);

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $role = $_POST['role'] ?? 'customer'; // Default to customer if not specified
        
        // Additional validation for admin creation
        if ($role === 'admin' && !User::isAdmin()) {
            throw new Exception("Only admins can create admin accounts");
        }
        
        $user->register(
            $_POST['full_name'],
            $_POST['email'],
            $_POST['password'],
            $_POST['phone'] ?? null,
            $_POST['address'] ?? null,
            $role // Add role parameter
        );
        
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SuperMarket - Create User (Admin)</title>
  
</head>
<body>
  <?php include '../includes/header.php'; ?>
  
  <div class="admin-container">
    <div class="admin-card">
      <form class="admin-form" method="POST">
        <div class="admin-header">
          <h1>Create User Account</h1>
          <p>Admin panel - User creation</p>
        </div>

        <?php if ($error): ?>
        <div class="auth-error" role="alert">
          <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="auth-success" role="alert">
          <h3>User Created Successfully!</h3>
          <p><a href="create_user.php">Create another user</a></p>
        </div>
        <?php else: ?>

        <!-- Full Name -->
        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" required>
        </div>

        <!-- Email -->
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required minlength="8">
        </div>

        <!-- Role (Admin Only) -->
        <div class="form-group">
          <label for="role">Account Role</label>
          <select id="role" name="role" required>
            <option value="customer">Customer</option>
            <option value="manager">Manager</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <!-- Phone (Optional) -->
        <div class="form-group">
          <label for="phone">Phone Number (Optional)</label>
          <input type="tel" id="phone" name="phone">
        </div>

        <!-- Address (Optional) -->
        <div class="form-group">
          <label for="address">Address (Optional)</label>
          <textarea id="address" name="address" rows="2"></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
          Create User Account
        </button>

        <?php endif; ?>
      </form>
    </div>
  </div>
</body>
</html>