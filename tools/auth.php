<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../tools/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];