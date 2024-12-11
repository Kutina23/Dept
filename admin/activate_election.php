<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

if (isset($_POST['election_id'])) {
    $election_id = $_POST['election_id'];
    
    // Check if election exists and is not already active
    $check_stmt = $conn->prepare("SELECT status FROM elections WHERE id = ?");
    $check_stmt->execute([$election_id]);
    $election = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($election) {
        if ($election['status'] === 'active') {
            $_SESSION['error'] = "Election is already active";
        } else {
            // Update election status
            $update_stmt = $conn->prepare("UPDATE elections SET status = 'active', start_date = NOW() WHERE id = ?");
            if ($update_stmt->execute([$election_id])) {
                // Log the activation
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, action, election_id) VALUES (?, 'started_election', ?)");
                $log_stmt->execute([$_SESSION['admin_id'], $election_id]);
                
                $_SESSION['success'] = "Election started successfully";
            } else {
                $_SESSION['error'] = "Failed to start election";
            }
        }
    } else {
        $_SESSION['error'] = "Election not found";
    }
    
    header('Location: dashboard.php');
    exit();
}

$_SESSION['error'] = "Election ID not provided";
header('Location: dashboard.php');
exit();
?> 