<?php
include "../includes/db.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST["full_name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $full_name, $email, $password);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Registration successful! You can now log in.";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Register - SMMS</title>
        <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h2>Create an Account</h2>
    <?php if ($message) echo "<p>$message</p>"; ?>

    <form method="POST" action="register.php">
        Full Name: <input type="text" name="full_name" required><br><br>
        Email: <input type="email" name="email" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>