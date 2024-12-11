<?php
include 'config.php';
include 'includes/helpers.php';

// Add this check before processing the vote
$student_id = $_POST['student_id'];
$election_id = $_POST['election_id'];

// Check if election is active
$election_query = "SELECT * FROM elections WHERE id = '$election_id'";
$election_result = mysqli_query($conn, $election_query);
$election = mysqli_fetch_assoc($election_result);

if($election['status'] != 'active') {
    echo json_error("Election is not active");
    exit();
}

// Check if student has already voted
$vote_check = "SELECT * FROM votes WHERE student_id = '$student_id' AND election_id = '$election_id'";
$vote_result = mysqli_query($conn, $vote_check);

if(mysqli_num_rows($vote_result) > 0) {
    echo json_error("You have already voted in this election");
    exit();
}

// Continue with vote processing...
?> 