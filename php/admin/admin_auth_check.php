<?php
// Admin-only login check - include this at the top of every admin page
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: admin_login.php");
    exit;
}
?>