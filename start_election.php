<?php
include 'config.php';
include 'includes/helpers.php';

if(isset($_POST['election_id'])) {
    $election_id = $_POST['election_id'];
    
    // Update election status to active
    $query = "UPDATE elections SET status = 'active' WHERE id = '$election_id'";
    if(mysqli_query($conn, $query)) {
        echo json_success("Election started successfully");
    } else {
        echo json_error("Failed to start election");
    }
}
?> 