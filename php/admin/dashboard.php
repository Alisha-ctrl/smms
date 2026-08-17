<?php
include "admin_auth_check.php";
include "../includes/db.php";

$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"))['total'];
$total_transactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM transactions"))['total'];
$total_income = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE type = 'income'"))['total'] ?? 0;
$total_expense = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS total FROM transactions WHERE type = 'expense'"))['total'] ?? 0;

$recent_users = mysqli_query($conn, "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">

    <style>
        .page-wrap { max-width: 1000px; margin: 30px auto; padding: 0 15px; }

        .grid-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .grid-col { flex: 1 1 200px; }

        .stat-card { position: relative; overflow: hidden; border-radius: 16px; padding: 22px; color: #ffffff; }
        .stat-card::after {
            content: ""; position: absolute; top: -30px; right: -30px; width: 100px; height: 100px;
            border-radius: 50%; background: rgba(255,255,255,0.15);
        }
        .stat-card .stat-icon { font-size: 22px; opacity: 0.9; }
        .stat-card .stat-label { font-size: 13px; opacity: 0.9; margin-top: 6px; }
        .stat-card .stat-value { font-size: 26px; font-weight: bold; margin-top: 4px; }

        .stat-users    { background: linear-gradient(135deg, #8FB7DE, #5A80AC); }
        .stat-txns     { background: linear-gradient(135deg, #A78BCF, #6C4A9C); }
        .stat-income   { background: linear-gradient(135deg, #2FAE73, #1B7943); }
        .stat-expense  { background: linear-gradient(135deg, #EB6B5D, #C0392B); }

        .card-block { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

        .user-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F0F0F0; }
        .user-row:last-child { border-bottom: none; }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 50%; background: #F7FBFC;
            display: inline-flex; align-items: center; justify-content: center; font-size: 16px; margin-right: 10px;
        }
        .user-email { font-size: 12px; color: #999999; }
    </style>
</head>
<body>
    <?php include "admin_nav.php"; ?>

    <div class="page-wrap">
        <h2>Admin Dashboard</h2>
        <p style="color:#666;">System-wide overview across all users.</p>

        <div class="grid-row">
            <div class="grid-col">
                <div class="stat-card stat-users">
                    <div class="stat-icon">👥</div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                </div>
            </div>
            <div class="grid-col">
                <div class="stat-card stat-txns">
                    <div class="stat-icon">🧾</div>
                    <div class="stat-label">Transactions</div>
                    <div class="stat-value"><?php echo $total_transactions; ?></div>
                </div>
            </div>
            <div class="grid-col">
                <div class="stat-card stat-income">
                    <div class="stat-icon">⬆️</div>
                    <div class="stat-label">Total Income</div>
                    <div class="stat-value">Rs. <?php echo number_format($total_income, 2); ?></div>
                </div>
            </div>
            <div class="grid-col">
                <div class="stat-card stat-expense">
                    <div class="stat-icon">⬇️</div>
                    <div class="stat-label">Total Expenses</div>
                    <div class="stat-value">Rs. <?php echo number_format($total_expense, 2); ?></div>
                </div>
            </div>
        </div>

        <div class="card-block">
            <h5>Recently Joined Users</h5>
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
</body>
</html>