<?php
session_start();
include "../includes/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = '$email' AND role = 'admin'";
    $result = mysqli_query($conn, $sql);

    $admin = mysqli_fetch_assoc($result);

    if ($admin && password_verify($password, $admin["password"])) {

        $_SESSION["user_id"] = $admin["user_id"];
        $_SESSION["full_name"] = $admin["full_name"];
        $_SESSION["role"] = $admin["role"];

        header("Location: dashboard.php");
        exit;

    } else {
        $message = "Invalid admin email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
</head>

<body>

<h2>Admin Login</h2>

<?php
if ($message) {
    echo "<p>$message</p>";
}
?>

<form method="POST" action="admin_login.php">

    Email:
    <input type="email" name="email" required>
    <br><br>

    Password:
    <input type="password" name="password" required>
    <br><br>

    <button type="submit">Login</button>

</form>

<p>
    Not an admin?
    <a href="/smms/php/auth/login.php">Login as a regular user</a>
</p>

<p>
    <a href="/smms/index.php">Back to Homepage</a>
</p>

</body>
</html>