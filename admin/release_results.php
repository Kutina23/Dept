<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../admin_login.php');
    exit();
}

if (isset($_GET['election_id'])) {
    try {
        $election_id = $_GET['election_id'];
        
        // First check if the election exists and is completed
        $stmt = $conn->prepare("SELECT * FROM elections WHERE id = :id AND status = 'completed'");
        $stmt->bindParam(':id', $election_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Insert or update the setting for this specific election
            $setting_name = 'results_released_' . $election_id;
            
            $stmt = $conn->prepare("INSERT INTO settings (setting_name, setting_value) 
                                  VALUES (:setting_name, 'true')
                                  ON DUPLICATE KEY UPDATE setting_value = 'true'");
            $stmt->bindParam(':setting_name', $setting_name);
            $stmt->execute();
            
            $_SESSION['success'] = "Results have been released successfully!";
        } else {
            $_SESSION['error'] = "Invalid election or election is not completed yet.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error releasing results: " . $e->getMessage();
    }
}

header('Location: dashboard.php');
exit();
?> 