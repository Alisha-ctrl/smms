<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$message = "";

// ---- ADD a new budget (Create) ----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = $_POST["category_id"];
    $month = $_POST["month"];
    $year = $_POST["year"];
    $budget_amount = $_POST["budget_amount"];

    $sql = "INSERT INTO budgets (user_id, category_id, month, year, budget_amount)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiiid", $user_id, $category_id, $month, $year, $budget_amount);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Budget added!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

// ---- Categories for dropdown (expense categories make the most sense for budgets) ----
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE (user_id IS NULL OR user_id = $user_id) AND category_type = 'expense'");

// ---- Read all budgets for this user ----
$sql = "SELECT b.*, c.category_name
        FROM budgets b
        JOIN categories c ON b.category_id = c.category_id
        WHERE b.user_id = $user_id
        ORDER BY b.year DESC, b.month DESC";
$budgets = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head><title>My Budgets - SMMS</title>
        <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h2>Budgets</h2>
    <?php include "../includes/nav.php"; ?>

    <?php if ($message) echo "<p>$message</p>"; ?>

    <h3>Add Budget</h3>
    <form method="POST" action="budgets.php">
        Category:
        <select name="category_id">
            <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
                <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
            <?php } ?>
        </select><br><br>

        Month (1-12): <input type="number" name="month" min="1" max="12" required><br><br>
        Year: <input type="number" name="year" value="<?php echo date('Y'); ?>" required><br><br>
        Budget Amount: <input type="number" step="0.01" name="budget_amount" required><br><br>

        <button type="submit">Add Budget</button>
    </form>

    <h3>Your Budgets</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Category</th><th>Month</th><th>Year</th><th>Budget Amount</th><th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($budgets)) { ?>
            <tr>
                <td><?php echo $row['category_name']; ?></td>
                <td><?php echo $row['month']; ?></td>
                <td><?php echo $row['year']; ?></td>
                <td><?php echo $row['budget_amount']; ?></td>
                <td>
                    <a href="edit_budget.php?id=<?php echo $row['budget_id']; ?>">Edit</a> |
                    <a href="delete_budget.php?id=<?php echo $row['budget_id']; ?>"
                       onclick="return confirm('Delete this budget?');">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>