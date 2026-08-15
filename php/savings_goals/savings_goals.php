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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Savings Goals - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

    <style>
        .goal-card {
            max-width: 900px; margin: 0 auto 12px auto; background: #ffffff;
            border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); padding: 14px 18px;
        }
        .goal-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .goal-left { display: flex; align-items: center; gap: 12px; }
        .goal-icon {
            width: 36px; height: 36px; border-radius: 50%; background: #F7FBFC; color: #769FCD;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .goal-amounts { font-size: 13px; color: #666666; }
        .goal-actions a { font-size: 12px; margin-left: 8px; }
        .goal-badge {
            font-size: 11px; padding: 2px 8px; border-radius: 10px; color: #ffffff; margin-left: 8px;
        }
        .badge-active { background-color: #219653; }
        .badge-completed { background-color: #999999; }

        .progress-track { background: #EFEFEF; border-radius: 20px; height: 10px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 20px; background-color: #769FCD; }

        .fab-wrapper { position: fixed; bottom: 25px; left: 0; right: 0; display: flex; justify-content: center; }
        .fab {
            width: 60px; height: 60px; border-radius: 50%; border: 3px solid #769FCD; background: #ffffff;
            color: #769FCD; font-size: 28px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        #addFormWrapper { max-width: 500px; margin: 0 auto 100px auto; }
    </style>
</head>
<body>
    <?php include "../includes/nav.php"; ?>

    <p class="month-label" style="text-align:center; color:#666;">Your Savings Goals</p>

    <?php if ($message) echo "<p style='text-align:center;'>$message</p>"; ?>

    <?php if (mysqli_num_rows($goals) === 0) { ?>
        <p style="text-align:center; color:#888;">No savings goals yet. Use the button below to add one.</p>
    <?php } ?>

    <?php while ($row = mysqli_fetch_assoc($goals)) {
        $target = $row['target_amount'];
        $saved = $row['saved_amount'];
        $percent = $target > 0 ? min(100, round(($saved / $target) * 100)) : 0;
    ?>
        <div class="goal-card">
            <div class="goal-top">
                <div class="goal-left">
                    <span class="goal-icon"><i class="bi <?php echo goal_icon(); ?>"></i></span>
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
            <div class="progress-track">
                <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
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
</body>
</html>