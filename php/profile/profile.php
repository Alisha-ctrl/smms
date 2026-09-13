<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$message = "";

// ---- Load current user info ----
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ---- Update name / email ----
    if (isset($_POST["update_details"])) {
        $full_name = trim($_POST["full_name"]);
        $email = trim($_POST["email"]);

        if ($full_name === "" || $email === "") {
            $message = "Name and email can't be empty.";
        } else {
            $sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $full_name, $email, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["full_name"] = $full_name;
                $message = "Profile updated!";
                $user['full_name'] = $full_name;
                $user['email'] = $email;
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
        }
    }

    // ---- Change password (requires the current password to confirm) ----
    if (isset($_POST["change_password"])) {
        $current_password = $_POST["current_password"];
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];

        if (!password_verify($current_password, $user["password"])) {
            $message = "Current password is incorrect.";
        } elseif (strlen($new_password) < 6) {
            $message = "New password must be at least 6 characters.";
        } elseif ($new_password !== $confirm_password) {
            $message = "New password and confirmation don't match.";
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $hashed, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Password changed successfully!";
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
        }
    }
}

$current_page = "profile";
$page_title = "Your Profile";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .profile-wrap { max-width: 500px; }
        .col-box input { width: 100%; box-sizing: border-box; }
    </style>
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <div class="profile-wrap">
            <?php if ($message) echo "<p>" . htmlspecialchars($message) . "</p>"; ?>

            <div class="col-box">
                <h5 style="margin-top:0;">Account Details</h5>
                <form method="POST" action="profile.php">
                    Full Name: <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required><br><br>
                    Email: <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br><br>
                    <button type="submit" name="update_details">Save Changes</button>
                </form>
            </div>

            <div class="col-box">
                <h5 style="margin-top:0;">Change Password</h5>
                <form method="POST" action="profile.php">
                    Current Password: <input type="password" name="current_password" required><br><br>
                    New Password: <input type="password" name="new_password" minlength="6" required><br><br>
                    Confirm New Password: <input type="password" name="confirm_password" minlength="6" required><br><br>
                    <button type="submit" name="change_password">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>