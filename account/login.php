
<?php 
require_once '../includes/init.php';
if(User::isLoggedIn()){
  header("Location: ../index.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SuperMarket - Login</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <!-- Login Form -->
      <form class="auth-form" method="POST" action="../api/login.php" >
        <div class="auth-header">
          <h1>Welcome Back</h1>
          <p>Sign in to your account</p>
        </div>

        <!-- Email Input -->
        <div class="form-group">
          <label for="email">Email Address</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            required
            autocomplete="email"
            aria-describedby="email-help"
          >
        </div>

        <!-- Password Input -->
        <div class="form-group">
          <label for="password">Password</label>
          <input 
            type="password" 
            id="password" 
            name="password" 
            required
            autocomplete="current-password"
            minlength="8"
          >
        </div>

        <!-- Form Extras -->
        <div class="form-options">
          <label class="remember-me">
            <input type="checkbox" name="remember">
            Remember me
          </label>
          <a href="../account/password-reset.html" class="forgot-password">
            Forgot password?
          </a>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary btn-block">
          Sign In
        </button>

        <!-- Registration Prompt -->
        <p class="auth-alternate">
          Don't have an account? 
          <a href="register.php">Create one</a>
        </p>
      </form>

      <!-- Error Message (Hidden by default) -->
      <div class="auth-error" role="alert" hidden>
        Invalid email or password. Please try again.
      </div>
    </div>
  </div>
</body>
</html>

