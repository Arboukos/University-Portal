<?php

require_once 'config.php';

class Auth {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    

    public function register($username, $email, $password, $roleCode) {
        try {
            // Validate inputs
            if (empty($username) || empty($email) || empty($password) || empty($roleCode)) {
                return ['success' => false, 'message' => 'All fields are required'];
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Validate password strength (minimum 6 characters)
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters'];
            }
            
            // Check if role code is valid and get role_id
            $stmt = $this->pdo->prepare("SELECT role_id, role_name FROM roles WHERE role_code = ?");
            $stmt->execute([$roleCode]);
            $role = $stmt->fetch();
            
            if (!$role) {
                return ['success' => false, 'message' => 'Invalid registration code'];
            }
            
            // Check if username already exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Check if email already exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$username, $email, $passwordHash, $role['role_id']]);
            
            return [
                'success' => true, 
                'message' => 'Registration successful as ' . $role['role_name']
            ];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    

    public function login($email, $password) {
        try {
            // Validate inputs
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email and password are required'];
            }
            
            // Get user from database
            $stmt = $this->pdo->prepare(
                "SELECT u.user_id, u.username, u.password_hash, u.role_id, r.role_name 
                 FROM users u 
                 INNER JOIN roles r ON u.role_id = r.role_id 
                 WHERE u.email = ?"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Log login attempt
            $this->logLoginAttempt($email, false);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Update last login
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Log successful login
            $this->logLoginAttempt($email, true);
            
            // Create session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['login_time'] = time();
            
            return [
                'success' => true, 
                'message' => 'Login successful',
                'role_name' => $user['role_name']
            ];
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    

    public function logout() {
        // Destroy all session data
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        return true;
    }
    

    private function logLoginAttempt($email, $success) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt = $this->pdo->prepare(
                "INSERT INTO login_attempts (email, success, ip_address) VALUES (?, ?, ?)"
            );
            $stmt->execute([$email, $success ? 1 : 0, $ipAddress]);
        } catch (PDOException $e) {
            error_log("Error logging login attempt: " . $e->getMessage());
        }
    }
}
?>