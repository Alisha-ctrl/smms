<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$id = (int)$_GET["id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["goal_name"];
    $target = $_POST["target_amount"];
    $saved = $_POST["saved_amount"];
    $date = $_POST["target_date"];
    $status = $_POST["status"];

    $sql = "UPDATE savings_goals
            SET goal_name=?, target_amount=?, saved_amount=?,
                target_date=?, status=?
            WHERE goal_id=? AND user_id=?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt, "sddssii",
        $name, $target, $saved, $date, $status, $id, $user_id
    );

    mysqli_stmt_execute($stmt);

    header("Location: savings_goals.php");
    exit;
}

$stmt = mysqli_prepare($conn,
    "SELECT * FROM savings_goals
     WHERE goal_id=? AND user_id=?"
);

mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);

$goal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$current_page = "savings_goals";
$page_title = "Edit Savings Goal";
?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Savings Goal - SMMS</title>

<link rel="stylesheet" href="../includes/style.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
.form-box{
    background:#fff;
    max-width:500px;
    padding:25px;
    border-radius:12px;
}
.form-box input,.form-box select{
    width:100%;
    padding:9px;
    margin:6px 0 15px;
    box-sizing:border-box;
}
.save{
    background:#219653;
    color:white;
    border:0;
    padding:10px 18px;
    border-radius:7px;
}
</style>

</head>

<body class="with-sidebar">

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

<?php include "../includes/topbar.php"; ?>

<div class="form-box">

<h3>
<i class="fa-solid fa-bullseye"></i>
Edit Savings Goal
</h3>

<form method="POST" action="edit_goal.php?id=<?php echo $id; ?>">

<label>Goal Name</label>
<input type="text" name="goal_name"
value="<?php echo htmlspecialchars($goal['goal_name']); ?>"
required>

<label>Target Amount</label>
<input type="number" step="0.01"
name="target_amount"
value="<?php echo $goal['target_amount']; ?>"
required>

<label>Saved Amount</label>
<input type="number" step="0.01"
name="saved_amount"
value="<?php echo $goal['saved_amount']; ?>">

<label>Target Date</label>
<input type="date" name="target_date"
value="<?php echo $goal['target_date']; ?>">

<label>Status</label>

<select name="status">

<option value="active"
<?php if ($goal['status']=="active") echo "selected"; ?>>
Active
</option>

<option value="completed"
<?php if ($goal['status']=="completed") echo "selected"; ?>>
Completed
</option>

</select>

<button class="save">Save Changes</button>

</form>

<p><a href="savings_goals.php">Back to Savings Goals</a></p>

</div>

</div>
</body>
</html>