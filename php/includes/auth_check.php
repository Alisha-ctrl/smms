<?php

session_start();

if (
    empty($_SESSION["user_id"]) ||
    !isset($_SESSION["role"])
) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION["role"] !== "user") {
    if ($_SESSION["role"] === "admin") {
        header("Location: ../admin/dashboard.php");
        exit;
    }

    session_destroy();

    header("Location: ../auth/login.php");
    exit;
}

?>