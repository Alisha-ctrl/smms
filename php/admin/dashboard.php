<?php
include "admin_auth_check.php";
include "../includes/db.php";

// ---- Simple counts for the admin overview ----
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"))['total'];
$total_transactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transactions"))['total'];
$total_income = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE type = 'income'"))['total'];
$total_expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE type = 'expense'"))['total'];
?>
<!DOCTYPE html>
<html>
<head><title>Admin Dashboard - SMMS</title>
<link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h2>Admin Dashboard</h2>
    <p>
        <a href="manage_users.php">Manage Users</a> |
        <a href="admin_logout.php">Logout</a>
    </p>
    <hr>

    <h3>System Overview</h3>
    <ul>
        <li>Total Users: <?php echo $total_users; ?></li>
        <li>Total Transactions: <?php echo $total_transactions; ?></li>
        <li>Total Income (all users): <?php echo $total_income ?: 0; ?></li>
        <li>Total Expenses (all users): <?php echo $total_expense ?: 0; ?></li>
    </ul>
</body>
</html>