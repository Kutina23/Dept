<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id']) || !isset($_GET['election_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT photo FROM candidates WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $candidate = $stmt->fetch();
    
    if ($candidate && $candidate['photo']) {
        unlink("../uploads/" . $candidate['photo']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    header("Location: manage_candidates.php?election_id=" . $_GET['election_id']);
    exit();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} 