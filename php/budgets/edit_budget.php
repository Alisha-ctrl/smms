<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$id = $_GET["id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = $_POST["category_id"];
    $month = $_POST["month"];
    $year = $_POST["year"];
    $budget_amount = $_POST["budget_amount"];

    $sql = "UPDATE budgets
            SET category_id = ?, month = ?, year = ?, budget_amount = ?
            WHERE budget_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiidii", $category_id, $month, $year, $budget_amount, $id, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: budgets.php");
    exit;
}

// FIX: was raw string interpolation ($id, $user_id straight into SQL) - now a prepared statement
$sql = "SELECT * FROM budgets WHERE budget_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$b = mysqli_fetch_assoc($result);

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE (user_id IS NULL OR user_id = $user_id) AND category_type = 'expense'");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Budget - SMMS</title>
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
        <h2>Edit Budget</h2>
        <form method="POST" action="edit_budget.php?id=<?php echo $id; ?>">
            <label>Category</label>
            <select name="category_id">
                <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?php echo $cat['category_id']; ?>"
                        <?php if ($cat['category_id'] == $b['category_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php } ?>
            </select>

            <label>Month (1-12)</label>
            <input type="number" name="month" min="1" max="12" value="<?php echo $b['month']; ?>" required>

            <label>Year</label>
            <input type="number" name="year" value="<?php echo $b['year']; ?>" required>

            <label>Budget Amount</label>
            <input type="number" step="0.01" name="budget_amount" value="<?php echo $b['budget_amount']; ?>" required>

            <button type="submit" class="btn-save">Save Changes</button>
        </form>
        <a href="budgets.php" class="back-link">Back to Budgets</a>
    </div>
</body>
</html>