<?php
// Admin-only login check - include this at the top of every admin page
session_start();

// Prevents the browser from showing a cached copy of an admin page
// after logout, which would otherwise error out or show stale data.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: admin_login.php");
    exit;
}
?>