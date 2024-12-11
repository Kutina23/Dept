<?php
function displayWinners($election_id) {
    global $conn;
    
    $query = "SELECT candidates.*, COUNT(votes.id) as vote_count 
              FROM candidates 
              LEFT JOIN votes ON candidates.id = votes.candidate_id 
              WHERE candidates.election_id = '$election_id' 
              GROUP BY candidates.id 
              ORDER BY vote_count DESC";
              
    $result = mysqli_query($conn, $query);
    
    echo "<h3>Election Results</h3>";
    echo "<div class='table-responsive'>";
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>Candidate</th><th>Votes</th></tr></thead>";
    echo "<tbody>";
    
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['vote_count'] . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table></div>";
}
?> 