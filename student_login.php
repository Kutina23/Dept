<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 3600);
    ini_set('session.gc_maxlifetime', 3600);
    session_start();
}

require_once 'config/database.php';

// Clear any existing session data when accessing login page
if (!isset($_POST['index_number'])) {
    session_unset();
}

$login_message = '';
if (isset($_SESSION['error'])) {
    $login_message = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $index_number = trim($_POST['index_number']); // Add trim to remove whitespace
    
    try {
        // Verify the database connection
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        $stmt = $conn->prepare("SELECT * FROM students WHERE index_number = ?");
        if (!$stmt) {
            throw new Exception("Query preparation failed");
        }
        
        $stmt->execute([$index_number]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            // Start a fresh session
            session_regenerate_id(true);
            
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['voter_id'] = $student['id'];
            $_SESSION['index_number'] = $student['index_number'];
            $_SESSION['logged_in'] = true;
            
            error_log("Debug: Session variables set - voter_id: {$_SESSION['voter_id']}, index_number: {$_SESSION['index_number']}");
            
            // Check if there's a pending vote
            if (isset($_SESSION['redirect_after_login']) && $_SESSION['redirect_after_login'] === 'submit_vote.php') {
                if (isset($_SESSION['pending_vote'])) {
                    error_log("Debug: Restoring pending vote data");
                    $_POST = $_SESSION['pending_vote'];
                    header("Location: submit_vote.php");
                    exit();
                }
            }
            
            header("Location: voting_dashboard.php");
            exit();
        } else {
            $error = "Invalid Index Number. Please try again.";
            error_log("Login failed - Invalid index number: " . $index_number);
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .card-header {
            background: transparent;
            border-bottom: none;
            padding: 25px 25px 0;
        }
        .card-body {
            padding: 25px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(102,126,234,0.25);
            border-color: #667eea;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(0,0,0,0.1);
        }
        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-area i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 15px;
        }
        .logo-area h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        .logo-area p {
            color: #666;
            font-size: 14px;
        }
        .alert {
            border-radius: 10px;
        }
        .text-muted {
            font-size: 0.9rem;
        }
        .text-muted:hover {
            color: #667eea !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <div class="logo-area">
                            <i class="fas fa-user-graduate"></i>
                            <h1>Student Login</h1>
                            <p>Enter your index number to access the voting system</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($login_message): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i><?php echo $login_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; ?></div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label for="index_number" class="form-label">
                                    <i class="fas fa-id-card me-2"></i>Index Number
                                </label>
                                <input type="text" class="form-control" id="index_number" 
                                       name="index_number" required placeholder="Enter your index number">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="admin_login.php" class="text-muted text-decoration-none">
                                <i class="fas fa-user-shield me-1"></i>Admin Login
                            </a>
                        </div>
                    
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 