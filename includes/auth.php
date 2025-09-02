<?php
// includes/auth.php
session_start();

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isClient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'client';
}

function requireAdmin() {
    if (!isAdmin()) { header("Location: ../index.php"); exit; }
}

function requireClient() {
    if (!isClient()) { header("Location: ../index.php"); exit; }
}
?>
