<?php
require_once '../includes/init.php';

$database = new Database();
$db = $database->connect();
$user = new User($db);

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user->register(
            $_POST['full_name'],
            $_POST['email'],
            $_POST['password'],
            $_POST['phone'] ?? null,
            $_POST['address'] ?? null
        );
        
        $success = true;
        // Optionally log them in automatically after registration
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
  <title>SuperMarket - Register</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <form class="auth-form" method="POST">
        <div class="auth-header">
          <h1>Create Account</h1>
          <p>Join us today</p>
        </div>

        <?php if ($error): ?>
        <div class="auth-error" role="alert">
          <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="auth-success" role="alert">
          <h3>Registration Successful!</h3>
          <p>You can now <a href="login.php">log in</a></p>
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
          Create Account
        </button>

        <p class="auth-alternate">
          Already have an account? <a href="login.php">Log in</a>
        </p>

        <?php endif; ?>
      </form>
    </div>
  </div>
</body>
</html>

