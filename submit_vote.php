<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 3600);
    ini_set('session.gc_maxlifetime', 3600);
    session_start();
}

require_once 'config/database.php';

// Debug logging
error_log("Debug: Starting vote submission process");
error_log("Debug: POST data: " . print_r($_POST, true));
error_log("Debug: Session data: " . print_r($_SESSION, true));

// Comprehensive session check
$required_session_vars = ['logged_in', 'voter_id', 'index_number'];
$missing_vars = [];

foreach ($required_session_vars as $var) {
    if (!isset($_SESSION[$var])) {
        $missing_vars[] = $var;
    }
}

if (!empty($missing_vars)) {
    error_log("Debug: Missing session variables: " . implode(', ', $missing_vars));
    $_SESSION['pending_vote'] = $_POST;
    $_SESSION['redirect_after_login'] = 'submit_vote.php';
    $_SESSION['vote_error'] = "Please log in to vote.";
    header("Location: student_login.php");
    exit();
}

// Verify the POST data
if (!isset($_POST['election_id']) || !isset($_POST['vote']) || empty($_POST['vote'])) {
    error_log("Debug: Missing POST data - election_id or vote");
    $_SESSION['vote_error'] = "Invalid vote data. Please try again.";
    header("Location: voting_dashboard.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // Debug logging
    error_log("Debug: Starting vote transaction for election_id: " . $_POST['election_id']);

    // Check if student hasn't already voted - optimized query
    $stmt = $pdo->prepare("
        SELECT EXISTS(
            SELECT 1 
            FROM student_voting_status 
            WHERE student_id = ? AND election_id = ?
        ) as has_voted
    ");
    $stmt->execute([$_SESSION['index_number'], $_POST['election_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['has_voted']) {
        throw new Exception("You have already cast your vote in this election.");
    }

    // Verify election is active - improved query
    $stmt = $pdo->prepare("
        SELECT id, status 
        FROM elections 
        WHERE id = ? 
        AND status = 'active' 
        AND NOW() BETWEEN start_date AND end_date
        FOR UPDATE
    ");
    $stmt->execute([$_POST['election_id']]);
    $election = $stmt->fetch();
    if (!$election) {
        throw new Exception("This election is not currently active.");
    }

    // Prepare vote insertion statement
    $voteStmt = $pdo->prepare("
        INSERT INTO votes (
            voter_student_id,
            candidate_id,
            position_id,
            election_id,
            timestamp
        ) VALUES (?, ?, ?, ?, NOW())
    ");
    
    // Verify all votes in a single query
    $positionIds = array_keys($_POST['vote']);
    $candidateIds = array_values($_POST['vote']);
    
    $verifyStmt = $pdo->prepare("
        SELECT id as candidate_id, position_id
        FROM candidates 
        WHERE election_id = ? 
        AND id IN (" . str_repeat('?,', count($candidateIds) - 1) . "?)
        AND position_id IN (" . str_repeat('?,', count($positionIds) - 1) . "?)
    ");
    
    $params = array_merge([$_POST['election_id']], $candidateIds, $positionIds);
    $verifyStmt->execute($params);
    $validCandidates = $verifyStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Verify each vote matches the position
    foreach ($_POST['vote'] as $position_id => $candidate_id) {
        if (!isset($validCandidates[$candidate_id]) || 
            $validCandidates[$candidate_id] != $position_id) {
            throw new Exception("Invalid candidate selection for position");
        }
        
        // Insert vote (removed IP address from parameters)
        $result = $voteStmt->execute([
            $_SESSION['index_number'],
            $candidate_id,
            $position_id,
            $_POST['election_id']
        ]);
        
        if (!$result) {
            throw new Exception("Failed to save vote for position " . $position_id);
        }
    }

    // Record voting status and create audit log
    $stmt = $pdo->prepare("
        INSERT INTO student_voting_status (
            student_id,
            election_id,
            voted_at
        ) VALUES (?, ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['index_number'], 
        $_POST['election_id']
    ]);
    
    // Update the has_voted status in students table
    $updateStmt = $pdo->prepare("
        UPDATE students 
        SET has_voted = 1 
        WHERE index_number = ?
    ");
    $updateStmt->execute([$_SESSION['index_number']]);
    
    // Add to activity log
    $logStmt = $pdo->prepare("
        INSERT INTO activity_logs (
            action,
            election_id,
            created_at
        ) VALUES ('VOTE_CAST', ?, NOW())
    ");
    $logStmt->execute([
        $_POST['election_id']
    ]);

    if ($pdo->commit()) {
        error_log("Debug: Vote successfully committed");
        $_SESSION['vote_success'] = true;
        $_SESSION['vote_message'] = "Your vote has been successfully recorded.";
        
        // Clear any pending vote data
        unset($_SESSION['pending_vote']);
        unset($_SESSION['redirect_after_login']);
        
        header("Location: thank_you.php?status=success");
        exit();
    } else {
        throw new Exception("Failed to commit the vote transaction");
    }

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Vote submission error: " . $e->getMessage());
    error_log("Debug: Rolling back transaction due to error");
    $_SESSION['vote_error'] = $e->getMessage();
    header("Location: thank_you.php?status=error");
    exit();
}
?> 