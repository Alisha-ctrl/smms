<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$id = $_GET["id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $goal_name = $_POST["goal_name"];
    $target_amount = $_POST["target_amount"];
    $saved_amount = $_POST["saved_amount"];
    $target_date = $_POST["target_date"];
    $status = $_POST["status"];

    $sql = "UPDATE savings_goals SET goal_name=?, target_amount=?, saved_amount=?, target_date=?, status=?
            WHERE goal_id=? AND user_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sddssii", $goal_name, $target_amount, $saved_amount, $target_date, $status, $id, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: savings_goals.php");
    exit;
}

$sql = "SELECT * FROM savings_goals WHERE goal_id=? AND user_id=?";
$stmt = mysqli_prepare($conn, $sql);
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
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <form method="POST" action="edit_goal.php?id=<?php echo $id; ?>">
            Goal Name: <input type="text" name="goal_name" value="<?php echo htmlspecialchars($goal['goal_name']); ?>" required><br><br>
            Target Amount: <input type="number" step="0.01" name="target_amount" value="<?php echo $goal['target_amount']; ?>" required><br><br>
            Saved So Far: <input type="number" step="0.01" name="saved_amount" value="<?php echo $goal['saved_amount']; ?>"><br><br>
            Target Date: <input type="date" name="target_date" value="<?php echo $goal['target_date']; ?>"><br><br>

            Status:
            <select name="status">
                <option value="active" <?php if ($goal['status'] == 'active') echo 'selected'; ?>>Active</option>
                <option value="completed" <?php if ($goal['status'] == 'completed') echo 'selected'; ?>>Completed</option>
            </select><br><br>

            <button type="submit">Save Changes</button>
        </form>

        <p><a href="savings_goals.php">Back to Savings Goals</a></p>
    </div>
</body>
</html>