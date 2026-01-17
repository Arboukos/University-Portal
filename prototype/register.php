<?php


require_once 'config.php';
require_once 'Auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $roleCode = $_POST['role_code'] ?? '';
    
    // Validate password confirmation
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $auth = new Auth();
        $result = $auth->register($username, $email, $password, $roleCode);
        
        if ($result['success']) {
            $success = $result['message'];
            // Clear form
            $username = $email = '';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Metropolitan College</title>
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
                <a href="login.php" class="nav-link">Login</a>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-box">
            <h2>Create Account</h2>
            <p class="auth-subtitle">Join our university community</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo sanitizeOutput($success); ?>
                    <br><a href="login.php" style="color: inherit; text-decoration: underline;">Click here to login</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo sanitizeOutput($username ?? ''); ?>"
                        required 
                        minlength="3"
                        maxlength="50"
                        placeholder="Choose a username"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo sanitizeOutput($email ?? ''); ?>"
                        required 
                        placeholder="your.email@example.com"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        minlength="6"
                        placeholder="At least 6 characters"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        minlength="6"
                        placeholder="Re-enter your password"
                    >
                </div>

                <div class="form-group">
                    <label>Select Your Role</label>
                    <div class="role-selection">
                        <label class="role-card">
                            <input type="radio" name="role_code" value="STUD2025" required>
                            <div class="role-content">
                                <span class="role-icon">👨‍🎓</span>
                                <span class="role-name">Student</span>
                                <span class="role-code">Code: STUD2025</span>
                            </div>
                        </label>
                        <label class="role-card">
                            <input type="radio" name="role_code" value="PROF2025" required>
                            <div class="role-content">
                                <span class="role-icon">👨‍🏫</span>
                                <span class="role-name">Professor</span>
                                <span class="role-code">Code: PROF2025</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="info-box">
                    <p><strong>Registration Codes:</strong></p>
                    <p>Students: STUD2025</p>
                    <p>Professors: PROF2025</p>
                    <p style="font-size: 0.9em; margin-top: 8px;">Please use the correct code for your role</p>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script src="js/validation.js"></script>
</body>
</html>