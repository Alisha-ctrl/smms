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

// FIX: was raw string interpolation ($id, $user_id straight into SQL) - now a prepared statement
$sql = "SELECT * FROM transactions WHERE transaction_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$t = mysqli_fetch_assoc($result);

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id IS NULL OR user_id = $user_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Transaction - SMMS</title>
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
        <h2>Edit Transaction</h2>
        <form method="POST" action="edit_transaction.php?id=<?php echo $id; ?>">
            <label>Type</label>
            <select name="type" id="type_select">
                <option value="income" <?php if ($t['type'] == 'income') echo 'selected'; ?>>Income</option>
                <option value="expense" <?php if ($t['type'] == 'expense') echo 'selected'; ?>>Expense</option>
            </select>

            <label>Category</label>
            <select name="category_id" id="category_select">
                <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?php echo $cat['category_id']; ?>"
                        data-type="<?php echo $cat['category_type']; ?>"
                        <?php if ($cat['category_id'] == $t['category_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php } ?>
            </select>

            <label>Amount</label>
            <input type="number" step="0.01" name="amount" value="<?php echo $t['amount']; ?>" required>

            <label>Description</label>
            <input type="text" name="description" value="<?php echo htmlspecialchars($t['description']); ?>">

            <label>Date</label>
            <input type="date" name="transaction_date" value="<?php echo $t['transaction_date']; ?>" required>

            <button type="submit" class="btn-save">Save Changes</button>
        </form>
        <a href="transactions.php" class="back-link">Back to Transactions</a>
    </div>

    <script>
        const typeSelect = document.getElementById('type_select');
        const categorySelect = document.getElementById('category_select');
        const allOptions = Array.from(categorySelect.options);

        typeSelect.addEventListener('change', function() {
            const selectedType = typeSelect.value;
            allOptions.forEach(opt => { opt.hidden = opt.dataset.type !== selectedType; });
            const firstMatch = allOptions.find(opt => opt.dataset.type === selectedType);
            if (firstMatch) categorySelect.value = firstMatch.value;
        });
    </script>
</body>
</html>