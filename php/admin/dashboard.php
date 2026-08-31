<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

include "../includes/db.php";

$user_count = 0;
$transaction_count = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $user_count = $row["total"];
}

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM transactions"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $transaction_count = $row["total"];
}

$admin_name = $_SESSION["full_name"];
?>

<!DOCTYPE html>
<html>

<head>

    <title>Admin Dashboard - SMMS</title>

    <link rel="stylesheet" href="../includes/style.css">

    <style>

        .admin-container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .admin-header {
            margin-bottom: 25px;
        }

        .admin-header h2 {
            margin-bottom: 5px;
        }

        .admin-header p {
            color: #666;
        }

        .admin-cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .admin-card {
            flex: 1;
            min-width: 220px;
            background: #E3DDE3;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .admin-card h3 {
            margin-top: 0;
        }

        .admin-number {
            font-size: 30px;
            font-weight: bold;
            color: #072736;
        }

        .admin-actions {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .admin-button {
            display: inline-block;
            padding: 12px 20px;
            background: #94CBDB;
            color: #072736;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .admin-button:hover {
            background: #C7A8A8;
        }

    </style>

</head>

<body>

<?php include "admin_nav.php"; ?>

<div class="admin-container">

    <div class="admin-header">

        <h2>
            Welcome, <?php echo htmlspecialchars($admin_name); ?> 👋
        </h2>

        <p>
            Admin overview of the Smart Money Management System.
        </p>

    </div>


    <div class="admin-cards">

        <div class="admin-card">

            <h3>👥 Total Users</h3>

            <div class="admin-number">
                <?php echo $user_count; ?>
            </div>

            <p>Registered regular users</p>

        </div>


        <div class="admin-card">

            <h3>💰 Transactions</h3>

            <div class="admin-number">
                <?php echo $transaction_count; ?>
            </div>

            <p>Total transactions in the system</p>

        </div>

    </div>


    <div class="admin-actions">

        <a
            href="manage_users.php"
            class="admin-button"
        >
            👥 Manage Users
        </a>

        <a
            href="admin_logout.php"
            class="admin-button"
        >
            🚪 Logout
        </a>

    </div>

</div>

</body>

</html>