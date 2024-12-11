<?php
require_once '../config/database.php';
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(403);
    exit('Unauthorized');
}

// Get election_id from query parameter
$election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;

if ($election_id <= 0) {
    http_response_code(400);
    exit('Invalid election ID');
}

try {
    // Fetch positions for the selected election
    $stmt = $conn->prepare("SELECT position_id, position_name FROM positions WHERE election_id = ? ORDER BY position_order");
    $stmt->execute([$election_id]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Send positions as JSON response
    header('Content-Type: application/json');
    echo json_encode($positions);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error');
} 