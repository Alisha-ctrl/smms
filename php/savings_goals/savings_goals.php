<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$message = "";

// ---- ADD a new savings goal (Create) ----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $goal_name = $_POST["goal_name"];
    $target_amount = $_POST["target_amount"];
    $saved_amount = $_POST["saved_amount"];
    $target_date = $_POST["target_date"];

    $sql = "INSERT INTO savings_goals (user_id, goal_name, target_amount, saved_amount, target_date)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isdds", $user_id, $goal_name, $target_amount, $saved_amount, $target_date);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Savings goal added!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

// ---- Read all goals for this user ----
$sql = "SELECT * FROM savings_goals WHERE user_id = $user_id ORDER BY target_date ASC";
$goals = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head><title>My Savings Goals - SMMS</title>
        <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h2>Savings Goals</h2>
    <?php include "../includes/nav.php"; ?>

    <?php if ($message) echo "<p>$message</p>"; ?>

    <h3>Add Savings Goal</h3>
    <form method="POST" action="savings_goals.php">
        Goal Name: <input type="text" name="goal_name" required><br><br>
        Target Amount: <input type="number" step="0.01" name="target_amount" required><br><br>
        Already Saved: <input type="number" step="0.01" name="saved_amount" value="0"><br><br>
        Target Date: <input type="date" name="target_date"><br><br>

        <button type="submit">Add Goal</button>
    </form>

    <h3>Your Goals</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Goal</th><th>Target</th><th>Saved</th><th>Target Date</th><th>Status</th><th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($goals)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['goal_name']); ?></td>
                <td><?php echo $row['target_amount']; ?></td>
                <td><?php echo $row['saved_amount']; ?></td>
                <td><?php echo $row['target_date']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td>
                    <a href="edit_goal.php?id=<?php echo $row['goal_id']; ?>">Edit</a> |
                    <a href="delete_goal.php?id=<?php echo $row['goal_id']; ?>"
                       onclick="return confirm('Delete this goal?');">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>