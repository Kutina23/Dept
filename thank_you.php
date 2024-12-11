<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = '';
$alertClass = '';

if ($status === 'success') {
    $message = isset($_SESSION['vote_message']) ? $_SESSION['vote_message'] : 'Your vote has been successfully recorded.';
    $alertClass = 'alert-success';
} else if ($status === 'error') {
    $message = isset($_SESSION['vote_error']) ? $_SESSION['vote_error'] : 'An error occurred while processing your vote.';
    $alertClass = 'alert-danger';
}

// Clear session messages after using them
unset($_SESSION['vote_message']);
unset($_SESSION['vote_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3><?php echo $status === 'success' ? 'Vote Submitted Successfully' : 'Vote Status'; ?></h3>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($message): ?>
                            <div class="alert <?php echo $alertClass; ?>" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="student_login.php" class="btn btn-primary">Return to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 