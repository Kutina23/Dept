<?php
require_once 'config.php';
require_once 'includes/helpers.php';

try {
    if(isset($_POST['election_id'])) {
        $election_id = $_POST['election_id'];
        
        // Start transaction
        $conn->beginTransaction();
        
        // Update election status to active
        $query = "UPDATE elections SET status = 'active' WHERE id = :election_id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['election_id' => $election_id]);
        
        // Log the activity
        $log_query = "INSERT INTO activity_logs (user_id, action, description) 
                     VALUES (:user_id, :action, :description)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'action' => 'START_ELECTION',
            'description' => "Election ID: $election_id was activated"
        ]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_success("Election started successfully");
    }
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error starting election: " . $e->getMessage());
    echo json_error("Failed to start election: " . $e->getMessage());
}
?> 