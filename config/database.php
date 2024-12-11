<?php
// First check if a session hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration before starting the session
    ini_set('session.cookie_lifetime', 3600); // 1 hour
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    session_start();
}

try {
    $host = 'localhost';
    $dbname = 'student_voting_system';
    $username = 'root';
    $password = '';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Make the connection available globally
    global $pdo;
    $pdo = $conn;
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?> 