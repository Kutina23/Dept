<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../admin_login.php');
    exit();
} 