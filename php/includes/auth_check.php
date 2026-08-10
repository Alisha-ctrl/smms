<?php
session_start();

// Prevents the browser from showing a cached copy of this page after logout.
// Without this, pressing "Back" after logging out can display a stale version
// of a protected page instead of properly redirecting to the login page.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}
?>