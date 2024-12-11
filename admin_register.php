<?php
session_start();
require_once 'config/database.php';

// Simple security key to prevent unauthorized admin registration
define('ADMIN_REGISTRATION_KEY', '000000');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Verify registration key
        if ($_POST['registration_key'] !== ADMIN_REGISTRATION_KEY) {
            throw new Exception("Invalid registration key!");
        }
        
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate username
        if (strlen($username) < 4) {
            throw new Exception("Username must be at least 4 characters long!");
        }
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Username already exists!");
        }
        
        // Validate password
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long!");
        }
        
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match!");
        }
        
        // Hash password and insert admin
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password_hash]);
        
        $_SESSION['success'] = "Admin account created successfully! You can now login.";
        header("Location: admin_login.php");
        exit();
        
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
    <title>Admin Registration - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Admin Registration</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" 
                                       name="username" required minlength="4">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" 
                                       name="password" required minlength="8">
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="registration_key" class="form-label">Registration Key</label>
                                <input type="password" class="form-control" id="registration_key" 
                                       name="registration_key" required>
                                <div class="form-text">Enter the admin registration key</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Register Admin</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="admin_login.php">Already registered? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 