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

    $sql = "UPDATE savings_goals
            SET goal_name = ?, target_amount = ?, saved_amount = ?, target_date = ?, status = ?
            WHERE goal_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sddssii", $goal_name, $target_amount, $saved_amount, $target_date, $status, $id, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: savings_goals.php");
    exit;
}

// FIX: was raw string interpolation ($id, $user_id straight into SQL) - now a prepared statement
$sql = "SELECT * FROM savings_goals WHERE goal_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$g = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Savings Goal - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .edit-card {
            max-width: 500px; margin: 40px auto; background: #ffffff;
            border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 24px 28px;
        }
        .edit-card h2 { color: #1B3A4B; font-size: 22px; margin-bottom: 20px; }
        .edit-card label { font-size: 14px; color: #555555; display: block; margin-bottom: 4px; margin-top: 14px; }
        .edit-card select, .edit-card input { width: 100%; box-sizing: border-box; }
        .btn-save {
            background-color: #769FCD; color: #ffffff; border: none; border-radius: 6px;
            padding: 12px; font-weight: bold; width: 100%; margin-top: 20px; cursor: pointer;
        }
        .btn-save:hover { background-color: #5A80AC; }
        .back-link { display: block; text-align: center; margin-top: 14px; color: #769FCD; font-size: 14px; }
    </style>
</head>
<body>
    <div class="edit-card">
        <h2>Edit Savings Goal</h2>
        <form method="POST" action="edit_goal.php?id=<?php echo $id; ?>">
            <label>Goal Name</label>
            <input type="text" name="goal_name" value="<?php echo htmlspecialchars($g['goal_name']); ?>" required>

            <label>Target Amount</label>
            <input type="number" step="0.01" name="target_amount" value="<?php echo $g['target_amount']; ?>" required>

            <label>Saved So Far</label>
            <input type="number" step="0.01" name="saved_amount" value="<?php echo $g['saved_amount']; ?>">

            <label>Target Date</label>
            <input type="date" name="target_date" value="<?php echo $g['target_date']; ?>">

            <label>Status</label>
            <select name="status">
                <option value="active" <?php if ($g['status'] == 'active') echo 'selected'; ?>>Active</option>
                <option value="completed" <?php if ($g['status'] == 'completed') echo 'selected'; ?>>Completed</option>
            </select>

            <button type="submit" class="btn-save">Save Changes</button>
        </form>
        <a href="savings_goals.php" class="back-link">Back to Savings Goals</a>
    </div>
</body>
</html>