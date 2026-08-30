<? php
ssession_start();
if(empty($_SESSION["user_id"])|| $_SESSION["role"]!= "admin"){
    header("Location: admin_login.php");
    exit;
}