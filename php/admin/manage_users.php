<?php
include "admin_auth_check.php";
include "../includes/db.php";

$message = "";

if (isset($_GET["delete_id"])) {
    $delete_id = $_GET["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ? AND role = 'user'");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $message = "User deleted.";
}

$sql = "SELECT u.*, COUNT(t.transaction_id) AS txn_count
        FROM users u
        LEFT JOIN transactions t ON t.user_id = u.user_id
        WHERE u.role = 'user'
        GROUP BY u.user_id
        ORDER BY u.created_at DESC";
$users = mysqli_query($conn, $sql);

$current_page = "manage_users";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        .page-wrap { max-width: 1000px; margin: 0 auto; }
        .success-msg { background: #E7F6EE; color: #1B7943; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; }

        .user-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #F0F0F0; }
        .user-row:last-child { border-bottom: none; }
        .user-left { display: flex; align-items: center; gap: 12px; }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%; background: #E3F0F5; color: #072736;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .user-email { font-size: 12px; color: #999999; }
        .user-meta { font-size: 12px; color: #999999; margin-top: 2px; }
        .delete-link { color: #E74C3C; font-size: 13px; text-decoration: none; font-weight: bold; }
        .delete-link:hover { text-decoration: underline; }
    </style>
</head>
<body class="with-sidebar">
    <?php include "admin_nav.php"; ?>

    <div class="main-content">
        <div class="page-wrap">
            <h2>Manage Users</h2>

            <?php if ($message) { ?>
                <div class="success-msg"><?php echo $message; ?></div>
            <?php } ?>

            <div class="col-box">
                <?php if (mysqli_num_rows($users) > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($users)) { ?>
                        <div class="user-row">
                            <div class="user-left">
                                <div class="user-avatar">👤</div>
                                <div>
                                    <div><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                                    <div class="user-meta">
                                        Joined <?php echo date('M j, Y', strtotime($row['created_at'])); ?>
                                        &middot; <?php echo $row['txn_count']; ?> transaction<?php echo $row['txn_count'] != 1 ? 's' : ''; ?>
                                    </div>
                                </div>
                            </div>
                            <a class="delete-link" href="manage_users.php?delete_id=<?php echo $row['user_id']; ?>"
                               onclick="return confirm('Delete this user? This also removes their data.');">
                                🗑️ Delete
                            </a>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p style="color:#888;">No users have registered yet.</p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>