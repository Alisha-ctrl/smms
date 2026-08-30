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

    $sql = "UPDATE budgets SET category_id = ?, month = ?, year = ?, budget_amount = ? WHERE budget_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiidii", $category_id, $month, $year, $budget_amount, $id, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: budgets.php");
    exit;
}

$sql = "SELECT * FROM budgets WHERE budget_id = $id AND user_id = $user_id";
$result = mysqli_query($conn, $sql);
$b = mysqli_fetch_assoc($result);

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE (user_id IS NULL OR user_id = $user_id) AND category_type = 'expense'");

$current_page = "budgets";
$page_title = "Edit Budget";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Budget - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <form method="POST" action="edit_budget.php?id=<?php echo $id; ?>">
            Category:
            <select name="category_id">
                <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php if ($cat['category_id'] == $b['category_id']) echo 'selected'; ?>>
                        <?php echo $cat['category_name']; ?>
                    </option>
                <?php } ?>
            </select><br><br>

            Month (1-12): <input type="number" name="month" min="1" max="12" value="<?php echo $b['month']; ?>" required><br><br>
            Year: <input type="number" name="year" value="<?php echo $b['year']; ?>" required><br><br>
            Budget Amount: <input type="number" step="0.01" name="budget_amount" value="<?php echo $b['budget_amount']; ?>" required><br><br>

            <button type="submit">Save Changes</button>
        </form>

        <p><a href="budgets.php">Back to Budgets</a></p>
    </div>
</body>
</html>