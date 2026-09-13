<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["goal_name"];
    $target = $_POST["target_amount"];
    $saved = $_POST["saved_amount"];
    $date = $_POST["target_date"];

    $sql = "INSERT INTO savings_goals
            (user_id, goal_name, target_amount, saved_amount, target_date)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt, "isdds",
        $user_id, $name, $target, $saved, $date
    );

    if (mysqli_stmt_execute($stmt)) {
        $message = "Savings goal added!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

$goals = mysqli_query($conn,
    "SELECT * FROM savings_goals
     WHERE user_id=$user_id
     ORDER BY target_date ASC"
);

$current_page = "savings_goals";
$page_title = "Savings Goals";
?>

<!DOCTYPE html>
<html>
<head>

<title>Savings Goals - SMMS</title>

<link rel="stylesheet" href="../includes/style.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
.card{
    background:#fff;
    padding:18px;
    margin-bottom:14px;
    border-radius:12px;
    box-shadow:0 1px 6px #ddd;
}
.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.icon{
    display:inline-flex;
    width:40px;
    height:40px;
    border-radius:50%;
    background:#E0E0E0;
    align-items:center;
    justify-content:center;
    margin-right:10px;
}
.info{
    color:#777;
    font-size:13px;
    margin-top:5px;
}
.actions a{
    margin-left:10px;
    font-size:12px;
    text-decoration:none;
}
.edit{color:#769FCD}
.delete{color:#E74C3C}
.goal-badge{font-size:11px;padding:2px 8px;border-radius:10px;color:#fff;margin-left:8px;}
.badge-active{background-color:#219653;}
.badge-completed{background-color:#999999;}
.bar{
    height:10px;
    background:#eee;
    border-radius:10px;
    margin-top:12px;
}
.fill{
    height:100%;
    background:#769FCD;
    border-radius:10px;
}
.add{
    display:none;
    background:#fff;
    max-width:500px;
    padding:20px;
    margin:20px auto;
    border-radius:12px;
}
.add input{
    width:100%;
    padding:9px;
    margin:6px 0 14px;
    box-sizing:border-box;
}
.save{
    background:#219653;
    color:#fff;
    border:0;
    padding:10px 18px;
    border-radius:7px;
}
.fab{
    position:fixed;
    right:35px;
    bottom:25px;
    width:55px;
    height:55px;
    border:0;
    border-radius:50%;
    background:#219653;
    color:#fff;
    font-size:25px;
}
</style>

</head>

<body class="with-sidebar">

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

<?php include "../includes/topbar.php"; ?>

<?php if ($message) echo "<p>" . htmlspecialchars($message) . "</p>"; ?>

<?php if (mysqli_num_rows($goals) == 0) { ?>
<p style="color:#888;">No savings goals yet.</p>
<?php } ?>


<?php while ($g = mysqli_fetch_assoc($goals)) {

$percent = $g['target_amount'] > 0
    ? min(100, round($g['saved_amount'] / $g['target_amount'] * 100))
    : 0;
?>

<div class="card">

<div class="top">

<div>

<span class="icon">
<?php echo goal_icon(); ?>
</span>

<strong><?php echo htmlspecialchars($g['goal_name']); ?></strong>
<span class="goal-badge <?php echo $g['status'] == 'active' ? 'badge-active' : 'badge-completed'; ?>">
<?php echo ucfirst($g['status']); ?>
</span>

<div class="info">
Rs. <?php echo number_format($g['saved_amount'],2); ?>
of Rs. <?php echo number_format($g['target_amount'],2); ?>
(<?php echo $percent; ?>%)
</div>

</div>

<div class="actions">

<a class="edit"
href="edit_goal.php?id=<?php echo $g['goal_id']; ?>">
Edit
</a>

<a class="delete"
href="delete_goal.php?id=<?php echo $g['goal_id']; ?>"
onclick="return confirm('Delete this goal?')">
Delete
</a>

</div>

</div>

<div class="bar">
<div class="fill"
style="width:<?php echo $percent; ?>%"></div>
</div>

</div>

<?php } ?>


<div class="add" id="addForm">

<h3>Add Savings Goal</h3>

<form method="POST">

<label>Goal Name</label>
<input type="text" name="goal_name" required>

<label>Target Amount</label>
<input type="number" step="0.01"
name="target_amount" required>

<label>Already Saved</label>
<input type="number" step="0.01"
name="saved_amount" value="0">

<label>Target Date</label>
<input type="date" name="target_date">

<button class="save">Save Goal</button>

</form>

</div>

<button class="fab"
onclick="addForm.style.display='block'">
+
</button>

</div>
</body>
</html>