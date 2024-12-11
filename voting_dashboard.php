<?php
session_start();
require_once 'config/database.php';

// Enhanced session validation
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['student_id']) || !isset($_SESSION['index_number'])) {
    error_log("Session check failed - redirecting to login");
    $_SESSION['error'] = "Please log in to vote.";
    header("Location: student_login.php");
    exit();
}

// Verify that the student exists in database
$stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND index_number = ?");
$stmt->execute([$_SESSION['student_id'], $_SESSION['index_number']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    error_log("Student verification failed - redirecting to login");
    session_unset();
    session_destroy();
    $_SESSION['error'] = "Invalid session. Please login again.";
    header("Location: student_login.php");
    exit();
}

// Update the logout function
function logout() {
    session_unset();
    session_destroy();
    header("Location: student_login.php");
    exit();
}

// Handle logout request before any output
if (isset($_GET['logout'])) {
    logout();
}

// Get active election and check if student has already voted
$stmt = $conn->prepare("SELECT * FROM elections WHERE status = 'active'");
$stmt->execute();
$active_election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$active_election) {
    $error = "No active election at the moment.";
} else {
    // Check if student has already voted
    $stmt = $conn->prepare("SELECT id FROM student_voting_status 
                           WHERE student_id = ? AND election_id = ?");
    $stmt->execute([$_SESSION['student_id'], $active_election['id']]);
    $vote = $stmt->fetch(PDO::FETCH_ASSOC);
    $has_voted = ($vote !== false);

    if ($has_voted) {
        $error = "You have already cast your vote in this election.";
    } else {
        // Get positions with candidates
        $stmt = $conn->prepare("
            SELECT p.id, p.position_name, p.position_order, 
                   c.id as candidate_id, c.full_name, c.photo 
            FROM positions p 
            LEFT JOIN candidates c ON p.id = c.position_id 
            WHERE p.election_id = ? 
            ORDER BY p.position_order, c.full_name
        ");
        $stmt->execute([$active_election['id']]);
        $positions = [];
        while ($row = $stmt->fetch()) {
            $position_id = $row['id'];
            if (!isset($positions[$position_id])) {
                $positions[$position_id] = [
                    'id' => $position_id,
                    'name' => $row['position_name'],
                    'candidates' => []
                ];
            }
            if ($row['candidate_id']) {
                $positions[$position_id]['candidates'][] = [
                    'id' => $row['candidate_id'],
                    'name' => $row['full_name'],
                    'photo' => $row['photo']
                ];
            }
        }
    }
}

// At the top after database connection
$stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_name = 'results_released'");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$results_released = ($result['setting_value'] === 'true');

// In the main content area
if ($results_released) {
    // Display results
    echo '<h2>Election Results</h2>';
    
    $stmt = $conn->prepare("
        SELECT p.position_name as position, c.full_name, COUNT(v.id) as vote_count 
        FROM candidates c 
        JOIN positions p ON c.position_id = p.id
        LEFT JOIN votes v ON c.id = v.candidate_id 
        WHERE p.election_id = ?
        GROUP BY c.id, p.position_name, c.full_name 
        ORDER BY p.position_order, vote_count DESC
    ");
    $stmt->execute([$active_election['id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $current_position = '';
    foreach ($results as $row) {
        if ($current_position != $row['position']) {
            $current_position = $row['position'];
            echo "<h3>{$row['position']}</h3>";
        }
        echo "<p>{$row['full_name']}: {$row['vote_count']} votes</p>";
    }
} else {
    // Show existing voting interface or "Results not yet released" message
    if (isset($has_voted) && $has_voted) {
        echo '<div class="alert alert-info">Thank you for voting. Results will be released soon.</div>';
    } else {
        // Existing voting form code...
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .position-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .position-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(102,126,234,0.5);
        }
        .candidate-option {
            border: 2px solid #eee;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .candidate-option:hover {
            border-color: #667eea;
            background: rgba(102,126,234,0.05);
        }
        .candidate-photo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 1rem;
            border: 3px solid #fff;
            box-shadow: var(--shadow-sm);
        }
        .form-check-input:checked ~ .candidate-option {
            border-color: #667eea;
            background: rgba(102,126,234,0.1);
        }
        .student-info {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 1rem;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-vote-yea me-2"></i>Student Voting System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link">
                    <i class="fas fa-user-graduate me-2"></i>
                    <?php echo htmlspecialchars($_SESSION['index_number']); ?>
                </span>
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="student-info mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-user me-2"></i>Welcome, Student
                </h4>
                <span>
                    <i class="fas fa-id-card me-2"></i>
                    Index: <?php echo htmlspecialchars($_SESSION['index_number']); ?>
                </span>
            </div>
        </div>

        <?php if (isset($_SESSION['vote_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>Your vote has been successfully recorded!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['vote_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['vote_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['vote_error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['vote_error']); ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-info fade-in">
                <i class="fas fa-info-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php else: ?>
            <form method="POST" action="submit_vote.php" class="needs-validation" novalidate>
                <input type="hidden" name="election_id" value="<?php echo $active_election['id']; ?>">
                
                <div class="row">
                    <?php foreach ($positions as $position): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card position-card fade-in">
                                <div class="card-header bg-white">
                                    <h4 class="mb-0">
                                        <?php if (strpos(strtolower($position['name']), 'president') !== false): ?>
                                            <i class="fas fa-user-tie me-2"></i>
                                        <?php else: ?>
                                            <i class="fas fa-user-edit me-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($position['name']); ?>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($position['candidates'])): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-user-slash fa-3x mb-3"></i>
                                            <p class="mb-0">No candidates available.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="candidates-list">
                                            <?php foreach ($position['candidates'] as $candidate): ?>
                                                <div class="position-relative">
                                                    <input type="radio" 
                                                           class="form-check-input position-absolute" 
                                                           style="opacity: 0;"
                                                           name="vote[<?php echo $position['id']; ?>]" 
                                                           value="<?php echo $candidate['id']; ?>" 
                                                           required>
                                                    <div class="candidate-option d-flex align-items-center">
                                                        <?php if ($candidate['photo']): ?>
                                                            <img src="uploads/candidates/<?php echo $candidate['photo']; ?>" 
                                                                 class="candidate-photo" alt="Candidate photo">
                                                        <?php else: ?>
                                                            <div class="candidate-photo d-flex align-items-center justify-content-center bg-light">
                                                                <i class="fas fa-user fa-2x text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <h5 class="mb-1"><?php echo htmlspecialchars($candidate['name']); ?></h5>
                                                            <small class="text-muted">Click to select</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4 mb-5">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-check-circle me-2"></i>Submit Vote
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Candidate selection enhancement
        document.querySelectorAll('.candidate-option').forEach(option => {
            option.addEventListener('click', function() {
                const radio = this.parentElement.querySelector('input[type="radio"]')
                radio.checked = true
            })
        })
    </script>
</body>
</html> 