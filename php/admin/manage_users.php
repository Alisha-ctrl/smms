<?php
include "admin_auth_check.php";
include "../includes/db.php";

$message = "";

// Delete user
if (!empty($_GET["delete_id"])) {

    $id = $_GET["delete_id"];

    $sql = "DELETE FROM users WHERE user_id = ? AND role = 'user'";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $message = "User deleted.";
}

// Get users
$sql = "SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC";
$users = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>

    <title>Manage Users - SMMS</title>

    <link rel="stylesheet" href="../includes/style.css">

    <style>

        .page-wrap {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .card-block {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .success-msg {
            background: #E7F6EE;
            color: #1B7943;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .user-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #F0F0F0;
        }

        .user-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #F7FBFC;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .user-email {
            font-size: 12px;
            color: #999;
        }

        .user-meta {
            font-size: 12px;
            color: #999;
            margin-top: 2px;
        }

        .delete-link {
            color: #E74C3C;
            font-size: 13px;
            text-decoration: none;
            font-weight: bold;
        }

        .delete-link:hover {
            text-decoration: underline;
        }

    </style>

</head>

<body>

<?php include "admin_nav.php"; ?>

<div class="page-wrap">

    <h2>Manage Users</h2>

    <?php
    if ($message) {
        echo "<div class='success-msg'>$message</div>";
    }
    ?>

    <div class="card-block">

        <?php

        if (mysqli_num_rows($users) > 0) {

            while ($row = mysqli_fetch_assoc($users)) {

                $user_id = $row["user_id"];
                $name = $row["full_name"];
                $email = $row["email"];
                $date = $row["created_at"];

                // Count transactions
                $sql = "SELECT * FROM transactions WHERE user_id = $user_id";
                $result = mysqli_query($conn, $sql);
                $count = mysqli_num_rows($result);

        ?>

                <div class="user-row">

                    <div class="user-left">

                        <div class="user-avatar">
                            👤
                        </div>

                        <div>

                            <div>
                                <?php echo $name; ?>
                            </div>

                            <div class="user-email">
                                <?php echo $email; ?>
                            </div>

                            <div class="user-meta">

                                Joined:
                                <?php echo date("F d, Y", strtotime($date)); ?>

                                <br>

                                Transactions:
                                <?php echo $count; ?>

                            </div>

                        </div>

                    </div>

                    <a class="delete-link"
                       href="manage_users.php?delete_id=<?php echo $user_id; ?>"
                       onclick="return confirm('Delete this user?');">

                        🗑️ Delete

                    </a>

                </div>

        <?php
            }

        } else {
        ?>

            <p style="color:#888;">
                No users have registered yet.
            </p>

        <?php
        }
        ?>

    </div>

</div>

</body>
</html>