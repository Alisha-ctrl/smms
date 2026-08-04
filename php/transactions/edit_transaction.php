<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$id = $_GET["id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = $_POST["category_id"];
    $type = $_POST["type"];
    $amount = $_POST["amount"];
    $description = $_POST["description"];
    $transaction_date = $_POST["transaction_date"];

    $sql = "UPDATE transactions
            SET category_id = ?, type = ?, amount = ?, description = ?, transaction_date = ?
            WHERE transaction_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isdssii", $category_id, $type, $amount, $description, $transaction_date, $id, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: transactions.php");
    exit;
}

$sql = "SELECT * FROM transactions WHERE transaction_id = $id AND user_id = $user_id";
$result = mysqli_query($conn, $sql);
$t = mysqli_fetch_assoc($result);

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id IS NULL OR user_id = $user_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Transaction - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h2>Edit Transaction</h2>

    <form method="POST" action="edit_transaction.php?id=<?php echo $id; ?>">
        Type:
        <select name="type">
            <option value="income" <?php if ($t['type'] == 'income') echo 'selected'; ?>>Income</option>
            <option value="expense" <?php if ($t['type'] == 'expense') echo 'selected'; ?>>Expense</option>
        </select><br><br>

        Category:
        <select name="category_id">
            <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
                <option value="<?php echo $cat['category_id']; ?>"
                    <?php if ($cat['category_id'] == $t['category_id']) echo 'selected'; ?>>
                    <?php echo $cat['category_name']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        Amount: <input type="number" step="0.01" name="amount" value="<?php echo $t['amount']; ?>" required><br><br>
        Description: <input type="text" name="description" value="<?php echo htmlspecialchars($t['description']); ?>"><br><br>
        Date: <input type="date" name="transaction_date" value="<?php echo $t['transaction_date']; ?>" required><br><br>

        <button type="submit">Save Changes</button>
    </form>

    <p><a href="transactions.php">Back to Transactions</a></p>
</body>
</html>