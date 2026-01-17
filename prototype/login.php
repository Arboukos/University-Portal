<?php

require_once 'config.php';
require_once 'Auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $auth = new Auth();
    $result = $auth->login($email, $password);
    
    if ($result['success']) {
        redirect('dashboard.php');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Metropolitan College</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1><a href="index.php" style="color: inherit; text-decoration: none;">🎓 Metropolitan College</a></h1>
            </div>
            <div class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="register.php" class="nav-link">Register</a>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-box">
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Sign in to your account</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        placeholder="your.email@example.com"
                        value="<?php echo sanitizeOutput($_POST['email'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

    <script src="js/validation.js"></script>
</body>
</html>