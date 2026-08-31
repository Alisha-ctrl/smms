<?php
include "admin_auth_check.php";
include "../includes/db.php";

$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"))['total'];
$total_transactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transactions"))['total'];
$total_income = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE type = 'income'"))['total'] ?? 0;
$total_expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE type = 'expense'"))['total'] ?? 0;

$recent_users = mysqli_query($conn, "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");

$current_page = "dashboard";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">

    <style>
        .page-wrap { max-width: 1000px; margin: 0 auto; }

        .grid-row { display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 20px; }
        .grid-col { flex: 1 1 200px; }

        .stat-card {
            background: #ffffff; border: 1px solid #E0E0E0; border-radius: 12px; padding: 20px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        }
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center;
            justify-content: center; font-size: 18px; margin-bottom: 10px;
        }
        .stat-users { background: #E3F0F5; }
        .stat-txns  { background: #F3E9E9; }
        .stat-income { background: #E7F6EE; }
        .stat-expense { background: #FBEAE8; }

        .stat-label { font-size: 13px; color: #888888; }
        .stat-value { font-size: 26px; font-weight: bold; color: #072736; margin: 4px 0; }

        .user-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F0F0F0; }
        .user-row:last-child { border-bottom: none; }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 50%; background: #E3F0F5; color: #072736;
            display: inline-flex; align-items: center; justify-content: center; font-size: 16px; margin-right: 10px;
        }
        .user-email { font-size: 12px; color: #999999; }
    </style>
</head>
<body class="with-sidebar">
    <?php include "admin_nav.php"; ?>

    <div class="main-content">
        <div class="page-wrap">
            <h2>Admin Dashboard</h2>
            <p style="color:#666; margin-top:-15px;">System-wide overview across all users.</p>

            <div class="grid-row">
                <div class="grid-col">
                    <div class="stat-card">
                        <div class="stat-icon stat-users">👥</div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                    </div>
                </div>
                <div class="grid-col">
                    <div class="stat-card">
                        <div class="stat-icon stat-txns">🧾</div>
                        <div class="stat-label">Transactions</div>
                        <div class="stat-value"><?php echo $total_transactions; ?></div>
                    </div>
                </div>
                <div class="grid-col">
                    <div class="stat-card">
                        <div class="stat-icon stat-income">⬆️</div>
                        <div class="stat-label">Total Income</div>
                        <div class="stat-value">Rs. <?php echo number_format($total_income, 2); ?></div>
                    </div>
                </div>
                <div class="grid-col">
                    <div class="stat-card">
                        <div class="stat-icon stat-expense">⬇️</div>
                        <div class="stat-label">Total Expenses</div>
                        <div class="stat-value">Rs. <?php echo number_format($total_expense, 2); ?></div>
                    </div>
                </div>
            </div>

            <div class="col-box">
                <h5 style="margin-top:0;">Recently Joined Users</h5>
                <?php if (mysqli_num_rows($recent_users) > 0) { ?>
                    <?php while ($u = mysqli_fetch_assoc($recent_users)) { ?>
                        <div class="user-row">
                            <div>
                                <span class="user-avatar">👤</span>
                                <?php echo htmlspecialchars($u['full_name']); ?>
                                <div class="user-email" style="margin-left:44px;"><?php echo htmlspecialchars($u['email']); ?></div>
                            </div>
                            <div style="color:#999; font-size:12px;"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p style="color:#888;">No users have registered yet.</p>
                <?php } ?>
                <p style="margin-top:15px; margin-bottom:0;"><a href="manage_users.php">Manage all users &rarr;</a></p>
            </div>
        </div>
    </div>
</body>
</html>