<?php
include "../includes/auth_check.php"; // must be logged in
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$message = "";

// ---- ADD a new transaction (Create) ----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = $_POST["category_id"];
    $type = $_POST["type"];
    $amount = $_POST["amount"];
    $description = $_POST["description"];
    $transaction_date = $_POST["transaction_date"];

    $sql = "INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisdss", $user_id, $category_id, $type, $amount, $description, $transaction_date);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Transaction added!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

// ---- Get categories for the dropdown ----
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id IS NULL OR user_id = $user_id");

// ---- Get all transactions for this user (Read) ----
$sql = "SELECT t.*, c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        ORDER BY t.transaction_date DESC";
$transactions = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head><title>My Transactions - SMMS</title>
        <link rel="stylesheet" href="../includes/style.css">
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>
    <?php include "../includes/nav.php"; ?>

    <?php if ($message) echo "<p>$message</p>"; ?>

    <h3>Add Transaction</h3>
    <form method="POST" action="transactions.php">
        Type:
        <select name="type">
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select><br><br>

        Category:
        <select name="category_id">
            <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
                <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
            <?php } ?>
        </select><br><br>

        Amount: <input type="number" step="0.01" name="amount" required><br><br>
        Description: <input type="text" name="description"><br><br>
        Date: <input type="date" name="transaction_date" required><br><br>

        <button type="submit">Add Transaction</button>
    </form>

    <h3>Transaction History</h3>
    <table border="1" cellpadding="8">
        <tr>
            <th>Date</th><th>Type</th><th>Category</th><th>Amount</th><th>Description</th><th>Actions</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($transactions)) { ?>
            <tr>
                <td><?php echo $row['transaction_date']; ?></td>
                <td><?php echo $row['type']; ?></td>
                <td><?php echo $row['category_name']; ?></td>
                <td><?php echo $row['amount']; ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td>
                    <a href="edit_transaction.php?id=<?php echo $row['transaction_id']; ?>">Edit</a> |
                    <a href="delete_transaction.php?id=<?php echo $row['transaction_id']; ?>"
                       onclick="return confirm('Delete this transaction?');">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>