<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $goal_name = $_POST["goal_name"];
    $target_amount = $_POST["target_amount"];
    $saved_amount = $_POST["saved_amount"];
    $target_date = $_POST["target_date"];

    $sql = "INSERT INTO savings_goals (user_id, goal_name, target_amount, saved_amount, target_date) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isdds", $user_id, $goal_name, $target_amount, $saved_amount, $target_date);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Savings goal added!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

$sql = "SELECT * FROM savings_goals WHERE user_id = $user_id ORDER BY target_date ASC";
$goals = mysqli_query($conn, $sql);

$current_page = "savings_goals";
$page_title = "Savings Goals";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Savings Goals - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        .goal-card { background: #ffffff; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); padding: 16px 18px; margin-bottom: 12px; max-width: none; }
        .goal-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .goal-left { display: flex; align-items: center; gap: 12px; }
        .goal-icon { width: 38px; height: 38px; border-radius: 50%; background: #F7FBFC; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .goal-amounts { font-size: 13px; color: #666666; }
        .goal-actions a { font-size: 12px; margin-left: 8px; }
        .goal-badge { font-size: 11px; padding: 2px 8px; border-radius: 10px; color: #ffffff; margin-left: 8px; }
        .badge-active { background-color: #219653; }
        .badge-completed { background-color: #999999; }

        .progress-track { background: #EFEFEF; border-radius: 20px; height: 10px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 20px; background-color: #769FCD; }

        .fab-wrapper { position: fixed; bottom: 25px; right: 40px; }
        .fab { width: 56px; height: 56px; border-radius: 50%; border: none; background: #219653; color: #ffffff; font-size: 26px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        #addFormWrapper { max-width: 500px; margin: 0 auto 30px auto; }
    </style>
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <?php if ($message) echo "<p>$message</p>"; ?>

        <?php if (mysqli_num_rows($goals) === 0) { ?>
            <p style="color:#888;">No savings goals yet. Tap the + button to add one.</p>
        <?php } ?>

        <?php while ($row = mysqli_fetch_assoc($goals)) {
            $target = $row['target_amount']; $saved = $row['saved_amount'];
            $percent = $target > 0 ? min(100, round(($saved / $target) * 100)) : 0;
        ?>
            <div class="goal-card">
                <div class="goal-top">
                    <div class="goal-left">
                        <span class="goal-icon"><?php echo goal_icon(); ?></span>
                        <div>
                            <div>
                                <?php echo htmlspecialchars($row['goal_name']); ?>
                                <span class="goal-badge <?php echo $row['status'] == 'active' ? 'badge-active' : 'badge-completed'; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </div>
                            <div class="goal-amounts">Rs. <?php echo number_format($saved, 2); ?> of Rs. <?php echo number_format($target, 2); ?> (<?php echo $percent; ?>%)</div>
                        </div>
                    </div>
                    <div class="goal-actions">
                        <a href="edit_goal.php?id=<?php echo $row['goal_id']; ?>">Edit</a>
                        <a href="delete_goal.php?id=<?php echo $row['goal_id']; ?>" onclick="return confirm('Delete this goal?');">Delete</a>
                    </div>
                </div>
                <div class="progress-track"><div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div></div>
            </div>
        <?php } ?>

        <div id="addFormWrapper" style="display:none;">
            <h3 style="text-align:center;">Add Savings Goal</h3>
            <form method="POST" action="savings_goals.php">
                Goal Name: <input type="text" name="goal_name" required><br><br>
                Target Amount: <input type="number" step="0.01" name="target_amount" required><br><br>
                Already Saved: <input type="number" step="0.01" name="saved_amount" value="0"><br><br>
                Target Date: <input type="date" name="target_date"><br><br>
                <button type="submit">Save</button>
            </form>
        </div>

        <div class="fab-wrapper">
            <div class="fab" onclick="document.getElementById('addFormWrapper').style.display='block'; document.getElementById('addFormWrapper').scrollIntoView({behavior:'smooth'});">+</div>
        </div>
    </div>
</body>
</html>