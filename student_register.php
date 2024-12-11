<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $index_number = $_POST['index_number'];
        
        // Check if index number already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE index_number = ?");
        $stmt->execute([$index_number]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("This Index Number is already registered!");
        }
        
        // Validate index number format (assuming 10 digits)
        if (!preg_match("/^\d{10}$/", $index_number)) {
            throw new Exception("Invalid Index Number format! Must be 10 digits.");
        }
        
        // Insert new student
        $stmt = $pdo->prepare("INSERT INTO students (index_number) VALUES (?)");
        $stmt->execute([$index_number]);
        
        $_SESSION['success'] = "Registration successful! You can now login.";
        header("Location: student_login.php");
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
    <title>Student Registration - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Student Registration</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="index_number" class="form-label">Index Number</label>
                                <input type="text" class="form-control" id="index_number" 
                                       name="index_number" required pattern="\d{10}" 
                                       title="Please enter a valid 10-digit index number">
                                <div class="form-text">Enter your 10-digit index number</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="student_login.php">Already registered? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 