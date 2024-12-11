<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../admin_login.php');
    exit();
}

try {
    // Change $pdo to $conn in all database queries
    $total_candidates = $conn->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
    $total_voters = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_votes = $conn->query("SELECT COUNT(*) FROM votes")->fetchColumn();
    
    // Get recent elections
    $stmt = $conn->query("SELECT * FROM elections ORDER BY created_at DESC LIMIT 5");
    $recent_elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active election if any
    $stmt = $conn->prepare("SELECT * FROM elections WHERE status = 'active' LIMIT 1");
    $stmt->execute();
    $active_election = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get total number of students
$total_students = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Get total number of elections
$total_elections = $conn->query("SELECT COUNT(*) FROM elections")->fetchColumn();

// Get total number of candidates
$total_candidates = $conn->query("SELECT COUNT(*) FROM candidates")->fetchColumn();

// Get total number of votes cast
$total_votes = $conn->query("SELECT COUNT(*) FROM votes")->fetchColumn();

// Get active election
$stmt = $conn->prepare("SELECT e.*, 
    (SELECT setting_value FROM settings WHERE setting_name = CONCAT('results_released_', e.id)) as results_released 
    FROM elections e 
    ORDER BY created_at DESC");
$stmt->execute();
$elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_name = 'results_released'");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$results_released = ($result['setting_value'] === 'true');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="admin-body">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card card text-center p-4">
                    <i class="fas fa-vote-yea stats-icon"></i>
                    <h3><?php echo count($elections); ?></h3>
                    <p class="text-muted mb-0">Total Elections</p>
                </div>
            </div>
            <!-- Add more stats cards as needed -->
        </div>

        <div class="row mb-4">
            <div class="col">
                <h2 class="text-white mb-4">
                    <i class="fas fa-poll me-2"></i>Elections Management
                </h2>
                <div class="btn-group mb-4">
                    <a href="create_election.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create New Election
                    </a>
                    <a href="register_student.php" class="btn btn-success ms-2">
                        <i class="fas fa-user-plus me-2"></i>Register New Student
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Active Elections</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($elections as $election): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($election['title']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?></td>
                                        <td>
                                            <?php if ($election['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php elseif ($election['status'] === 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($election['status'] === 'pending'): ?>
                                                <form method="POST" action="activate_election.php" style="display: inline;">
                                                    <input type="hidden" name="election_id" value="<?php echo $election['id']; ?>">
                                                    <button type="submit" class="btn btn-success" <?php echo ($election['status'] === 'active') ? 'disabled' : ''; ?>>
                                                        Start Election
                                                    </button>
                                                </form>
                                            <?php elseif ($election['status'] === 'active'): ?>
                                                <a href="end_election.php?election_id=<?php echo $election['id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to end this election?')" 
                                                   class="btn btn-danger btn-sm">End</a>
                                            <?php endif; ?>
                                            <a href="manage_candidates.php?election_id=<?php echo $election['id']; ?>" 
                                               class="btn btn-primary btn-sm">Manage Candidates</a>
                                            <a href="view_results.php?election_id=<?php echo $election['id']; ?>" 
                                               class="btn btn-info btn-sm ms-1">View Results</a>
                                            <?php if ($election['status'] === 'completed' && (!isset($election['results_released']) || $election['results_released'] !== 'true')): ?>
                                                <a href="release_results.php?election_id=<?php echo $election['id']; ?>" 
                                                   class="btn btn-sm btn-warning">Release Results</a>
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

        <div class="card mb-4">
            <div class="card-header">
                <h5>Manage Results Release</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="release_results.php">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="releaseResults" 
                               name="release_results" <?php echo $results_released ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="releaseResults">
                            Release election results to students
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Results Status</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 