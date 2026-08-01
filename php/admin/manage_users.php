<?php
include "admin_auth_check.php";
include "../includes/db.php";

$message = "";

// ---- Delete a user (admin action) ----
if (isset($_GET["delete_id"])) {
    $delete_id = $_GET["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ? AND role = 'user'");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $message = "User deleted.";
}

// ---- List all normal users (Read) ----
$users = mysqli_query($conn, "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head><title>Manage Users - SMMS</title></head>
<body>
    <h2>Manage Users</h2>
    <p><a href="dashboard.php">Back to Dashboard</a></p>

    <?php if ($message) echo "<p>$message</p>"; ?>

    <table border="1" cellpadding="8">
        <tr>
            <th>Name</th><th>Email</th><th>Joined</th><th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($users)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td>
                    <a href="manage_users.php?delete_id=<?php echo $row['user_id']; ?>"
                       onclick="return confirm('Delete this user? This also removes their data.');">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>