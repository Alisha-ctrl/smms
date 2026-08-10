<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$message = "";
$this_month = date('n');
$this_year = date('Y');
$month_name = date('F Y');

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

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id IS NULL OR user_id = $user_id");

$sql = "SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = $user_id
        AND MONTH(transaction_date) = $this_month
        AND YEAR(transaction_date) = $this_year";
$summary = mysqli_fetch_assoc(mysqli_query($conn, $sql));
$income = $summary['total_income'] ?? 0;
$expense = $summary['total_expense'] ?? 0;
$balance = $income - $expense;

$sql = "SELECT t.*, c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        AND MONTH(t.transaction_date) = $this_month
        AND YEAR(t.transaction_date) = $this_year
        ORDER BY t.transaction_date DESC";
$result = mysqli_query($conn, $sql);

$groups = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cid = $row['category_id'];
    if (!isset($groups[$cid])) {
        $groups[$cid] = ['name' => $row['category_name'], 'type' => $row['type'], 'total' => 0, 'items' => []];
    }
    $groups[$cid]['total'] += $row['amount'];
    $groups[$cid]['items'][] = $row;
}

uasort($groups, function($a, $b) {
    if ($a['type'] !== $b['type']) return $a['type'] === 'income' ? -1 : 1;
    return $b['total'] <=> $a['total'];
});
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transactions - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

    <style>
        .balance-pill {
            max-width: 320px; margin: 10px auto 25px auto; padding: 14px;
            border-radius: 30px; text-align: center; font-size: 18px; font-weight: bold; color: #ffffff;
        }
        .balance-positive { background-color: #219653; }
        .balance-negative { background-color: #E74C3C; }
        .month-label { text-align: center; color: #666666; margin-bottom: 5px; }

        .category-group {
            max-width: 900px; margin: 0 auto 10px auto; background: #ffffff;
            border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden;
        }
        .category-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; cursor: pointer; }
        .category-header .left { display: flex; align-items: center; gap: 12px; }
        .category-icon {
            width: 36px; height: 36px; border-radius: 50%;
            background: #F2FBF6; color: #219653;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .category-count {
            background: #219653; color: #ffffff; border-radius: 50%;
            width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;
        }
        .category-total.income { color: #219653; font-weight: bold; }
        .category-total.expense { color: #E74C3C; font-weight: bold; }

        .item-row { display: flex; justify-content: space-between; padding: 8px 16px 8px 60px; border-top: 1px solid #F0F0F0; font-size: 14px; }
        .item-row .item-date { color: #999999; font-size: 12px; }
        .item-row .item-actions a { font-size: 12px; margin-left: 8px; }

        .fab-wrapper { position: fixed; bottom: 25px; left: 0; right: 0; display: flex; justify-content: center; gap: 30px; }
        .fab {
            width: 60px; height: 60px; border-radius: 50%; border: 3px solid; background: #ffffff;
            font-size: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .fab-expense { border-color: #E74C3C; color: #E74C3C; }
        .fab-income { border-color: #219653; color: #219653; }
        #addFormWrapper { max-width: 500px; margin: 0 auto 100px auto; }
    </style>
</head>
<body>
    <?php include "../includes/nav.php"; ?>

    <p class="month-label"><?php echo $month_name; ?></p>
    <div class="balance-pill <?php echo $balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
        Balance: Rs. <?php echo number_format($balance, 2); ?>
    </div>

    <?php if ($message) echo "<p style='text-align:center;'>$message</p>"; ?>

    <?php if (count($groups) === 0) { ?>
        <p style="text-align:center; color:#888;">No transactions yet this month. Use the buttons below to add one.</p>
    <?php } ?>

    <?php foreach ($groups as $cid => $group) { $collapseId = "group-" . $cid; ?>
        <div class="category-group">
            <div class="category-header" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>">
                <div class="left">
                    <span class="category-icon"><i class="bi <?php echo category_icon($group['name']); ?>"></i></span>
                    <span><?php echo htmlspecialchars($group['name']); ?></span>
                    <span class="category-count"><?php echo count($group['items']); ?></span>
                </div>
                <span class="category-total <?php echo $group['type']; ?>">
                    <?php echo $group['type'] == 'income' ? '+' : '-'; ?> Rs. <?php echo number_format($group['total'], 2); ?>
                </span>
            </div>
            <div class="collapse" id="<?php echo $collapseId; ?>">
                <?php foreach ($group['items'] as $item) { ?>
                    <div class="item-row">
                        <span>
                            <?php echo htmlspecialchars($item['description'] ?: $group['name']); ?>
                            <br><span class="item-date"><?php echo $item['transaction_date']; ?></span>
                        </span>
                        <span>
                            <span class="<?php echo $item['type'] == 'income' ? 'amount-income' : 'amount-expense'; ?>">
                                <?php echo $item['type'] == 'income' ? '+' : '-'; ?> Rs. <?php echo number_format($item['amount'], 2); ?>
                            </span>
                            <span class="item-actions">
                                <a href="edit_transaction.php?id=<?php echo $item['transaction_id']; ?>">Edit</a>
                                <a href="delete_transaction.php?id=<?php echo $item['transaction_id']; ?>" onclick="return confirm('Delete this transaction?');">Delete</a>
                            </span>
                        </span>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <div id="addFormWrapper" style="display:none;">
        <h3 id="addFormTitle" style="text-align:center;">Add Transaction</h3>
        <form method="POST" action="transactions.php">
            <input type="hidden" name="type" id="typeField" value="expense">
            Category:
            <select name="category_id">
                <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                <?php } ?>
            </select><br><br>
            Amount: <input type="number" step="0.01" name="amount" id="amountField" required><br><br>
            Description: <input type="text" name="description"><br><br>
            Date: <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required><br><br>
            <button type="submit">Save</button>
        </form>
    </div>

    <div class="fab-wrapper">
        <div class="fab fab-expense" onclick="openAddForm('expense')">−</div>
        <div class="fab fab-income" onclick="openAddForm('income')">+</div>
    </div>

    <script>
        function openAddForm(type) {
            document.getElementById('typeField').value = type;
            document.getElementById('addFormTitle').innerText = type === 'income' ? 'Add Income' : 'Add Expense';
            const wrapper = document.getElementById('addFormWrapper');
            wrapper.style.display = 'block';
            wrapper.scrollIntoView({ behavior: 'smooth' });
            document.getElementById('amountField').focus();
        }
    </script>
</body>
</html>