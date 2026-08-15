<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$id = $_GET["id"];

$sql = "UPDATE budget_alerts SET is_read = 1 WHERE alert_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);

header("Location: dashboard.php");
exit;
?>