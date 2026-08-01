<?php
// Shared login check - include this at the top of any page that requires login
// Usage (from inside a feature folder): include "../includes/auth_check.php";

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}
?>