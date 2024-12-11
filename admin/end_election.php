<?php
require_once('../config/database.php');

// Check if admin is logged in (you should implement proper admin authentication)
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Validate election ID - check both POST and GET methods
$election_id = $_POST['election_id'] ?? $_GET['election_id'] ?? null;

if (!$election_id || !is_numeric($election_id)) {
    $_SESSION['error'] = "Invalid election ID";
    header('Location: dashboard.php');
    exit();
}

$election_id = intval($election_id);

try {
    // First check if election exists and is active
    $check_stmt = $conn->prepare("SELECT status FROM elections WHERE id = :id");
    $check_stmt->bindParam(':id', $election_id);
    $check_stmt->execute();
    
    $election = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$election) {
        throw new Exception("Election not found");
    }
    
    if ($election['status'] === 'completed') {
        throw new Exception("Election is already completed");
    }
    
    // Update election status to completed
    $stmt = $conn->prepare("UPDATE elections SET status = 'completed', end_date = NOW() WHERE id = :id");
    $stmt->bindParam(':id', $election_id);
    $stmt->execute();
    
    $_SESSION['success'] = "Election has been successfully ended";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error ending election: " . $e->getMessage();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error occurred";
    error_log("Database error: " . $e->getMessage());
}

header('Location: dashboard.php');
exit();
  