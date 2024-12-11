<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../admin_login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionStarted = false;  // Add this flag to track transaction status
    
    try {
        // Validate inputs
        if (empty($_POST['title']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
            throw new Exception("All fields are required");
        }

        $title = trim($_POST['title']);
        // Convert the datetime-local input to MySQL datetime format
        $start_date = date('Y-m-d H:i:s', strtotime($_POST['start_date']));
        $end_date = date('Y-m-d H:i:s', strtotime($_POST['end_date']));
        
        // Validate dates
        $start_timestamp = strtotime($_POST['start_date']);
        $end_timestamp = strtotime($_POST['end_date']);
        
        if ($end_timestamp <= $start_timestamp) {
            throw new Exception("End date must be after start date");
        }

        $conn->beginTransaction();
        $transactionStarted = true;  // Set flag after starting transaction

        // Create the election
        $stmt = $conn->prepare("INSERT INTO elections (title, start_date, end_date, status) VALUES (:title, :start_date, :end_date, 'pending')");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $election_id = $conn->lastInsertId();

        // Add default positions for this election
        $default_positions = [
            ['President', 1],
            ['Vice President', 2],
            ['General Secretary', 3],
            ['Financial Secretary', 4],
            ['Organizing Secretary', 5],
            ['Public Relations Officer', 6],
            ['Women Commissioner', 7],
            ['Sports Secretary', 8],
            ['Library Secretary', 9],
            ['GNUTS Ambassador', 10]
        ];

        $stmt = $conn->prepare("INSERT INTO positions (position_name, position_order, election_id) VALUES (:position_name, :position_order, :election_id)");
        foreach ($default_positions as $position) {
            $stmt->bindParam(':position_name', $position[0]);
            $stmt->bindParam(':position_order', $position[1]);
            $stmt->bindParam(':election_id', $election_id);
            $stmt->execute();
        }

        $conn->commit();
        $_SESSION['success'] = "Election created successfully!";
        header('Location: dashboard.php');
        exit();
        
    } catch (Exception $e) {
        if ($transactionStarted) {  // Only rollback if transaction was started
            $conn->rollBack();
        }
        $error = "Error creating election: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Election - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-body">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-light">
                    <i class="fas fa-user me-2"></i>
                    <?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?>
                </span>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Create New Election</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="title" class="form-label">Election Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Start Date & Time</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date & Time</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Create Election
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 