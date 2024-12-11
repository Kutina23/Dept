<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $index_number = $_POST['index_number'];
        
        // Check if index number already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE index_number = ?");
        $stmt->execute([$index_number]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("This Index Number is already registered!");
        }
        
        // Validate index number format (assuming 10 digits)
        if (!preg_match("/^\d{10}$/", $index_number)) {
            throw new Exception("Invalid Index Number format! Must be 10 digits.");
        }
        
        // Insert new student
        $stmt = $conn->prepare("INSERT INTO students (index_number) VALUES (?)");
        $stmt->execute([$index_number]);
        
        $_SESSION['success'] = "Student registered successfully!";
        header("Location: register_student.php");
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch all registered students
$stmt = $conn->query("SELECT * FROM students ORDER BY id DESC");
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Student - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .student-table {
            background: white;
            border-radius: var(--border-radius);
        }
        .student-table th {
            background: rgba(0,0,0,0.02);
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }
        .search-box {
            position: relative;
        }
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        .search-box input {
            padding-left: 2.5rem;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="admin-body">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-light">
                <?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="text-white mb-0">
                        <i class="fas fa-user-graduate me-2"></i>Student Management
                    </h2>
                    <a href="dashboard.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Registration Form -->
            <div class="col-md-4 mb-4">
                <div class="card fade-in">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>Register New Student
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php 
                                    echo $_SESSION['success'];
                                    unset($_SESSION['success']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="index_number" class="form-label">
                                    <i class="fas fa-id-card me-2"></i>Index Number
                                </label>
                                <input type="text" class="form-control" id="index_number" 
                                       name="index_number" required pattern="\d{10}"
                                       placeholder="Enter 10-digit index number">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Must be exactly 10 digits
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-plus-circle me-2"></i>Register Student
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Students List -->
            <div class="col-md-8">
                <div class="card fade-in">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-users me-2"></i>Registered Students
                            </h4>
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="Search students...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover student-table" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Index Number</th>
                                        <th class="text-center">Voting Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo $student['id']; ?></td>
                                            <td>
                                                <i class="fas fa-id-card me-2"></i>
                                                <?php echo htmlspecialchars($student['index_number']); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($student['has_voted']): ?>
                                                    <span class="status-badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Voted
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Not Voted
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const table = document.getElementById('studentsTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const indexNumber = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
                if (indexNumber.includes(searchText)) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        });

        // Form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 