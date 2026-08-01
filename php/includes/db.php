<?php
// Shared database connection - used by every feature folder
// Default XAMPP settings: no password on root user

$conn = mysqli_connect("localhost", "root", "", "smms_db");

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>