<div class="form-group">
    <label>Election Duration (in minutes)</label>
    <input type="number" name="duration" class="form-control" required>
</div>

<?php
if(isset($_POST['create_election'])) {
    // Existing election creation code...
    $duration = $_POST['duration'];
    $start_time = date('Y-m-d H:i:s');
    $end_time = date('Y-m-d H:i:s', strtotime("+$duration minutes"));
    
    $query = "INSERT INTO elections (title, description, start_time, end_time, duration) 
              VALUES ('$title', '$description', '$start_time', '$end_time', '$duration')";
    // Execute query...
}
?> 