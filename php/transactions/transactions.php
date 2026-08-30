<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$message = "";
$this_month = date('n');
$this_year = date('Y');
$month_name = date('F Y');

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

        if ($type == 'expense') {
            $t_month = (int) date('n', strtotime($transaction_date));
            $t_year = (int) date('Y', strtotime($transaction_date));

            $budget_sql = "SELECT * FROM budgets WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?";
            $budget_stmt = mysqli_prepare($conn, $budget_sql);
            mysqli_stmt_bind_param($budget_stmt, "iiii", $user_id, $category_id, $t_month, $t_year);
            mysqli_stmt_execute($budget_stmt);
            $budget = mysqli_fetch_assoc(mysqli_stmt_get_result($budget_stmt));

            if ($budget) {
                $spent_sql = "SELECT COALESCE(SUM(amount), 0) AS spent FROM transactions
                              WHERE user_id = ? AND category_id = ? AND type = 'expense'
                              AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";
                $spent_stmt = mysqli_prepare($conn, $spent_sql);
                mysqli_stmt_bind_param($spent_stmt, "iiii", $user_id, $category_id, $t_month, $t_year);
                mysqli_stmt_execute($spent_stmt);
                $spent = mysqli_fetch_assoc(mysqli_stmt_get_result($spent_stmt))['spent'];

                if ($spent > $budget['budget_amount']) {
                    $existing_sql = "SELECT alert_id FROM budget_alerts WHERE budget_id = ? AND is_read = 0";
                    $existing_stmt = mysqli_prepare($conn, $existing_sql);
                    mysqli_stmt_bind_param($existing_stmt, "i", $budget['budget_id']);
                    mysqli_stmt_execute($existing_stmt);
                    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($existing_stmt));

                    if (!$existing) {
                        $cn_stmt = mysqli_prepare($conn, "SELECT category_name FROM categories WHERE category_id = ?");
                        mysqli_stmt_bind_param($cn_stmt, "i", $category_id);
                        mysqli_stmt_execute($cn_stmt);
                        $category_name = mysqli_fetch_assoc(mysqli_stmt_get_result($cn_stmt))['category_name'];

                        $over_by = $spent - $budget['budget_amount'];
                        $alert_message = "You've gone over your " . $category_name . " budget by Rs. " . number_format($over_by, 2) . ".";

                        $insert_alert_stmt = mysqli_prepare($conn, "INSERT INTO budget_alerts (user_id, budget_id, alert_message) VALUES (?, ?, ?)");
                        mysqli_stmt_bind_param($insert_alert_stmt, "iis", $user_id, $budget['budget_id'], $alert_message);
                        mysqli_stmt_execute($insert_alert_stmt);
                    }
                }
            }
        }
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id IS NULL OR user_id = $user_id");

$sql = "SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = $user_id AND MONTH(transaction_date) = $this_month AND YEAR(transaction_date) = $this_year";
$summary = mysqli_fetch_assoc(mysqli_query($conn, $sql));
$income = $summary['total_income'] ?? 0;
$expense = $summary['total_expense'] ?? 0;
$balance = $income - $expense;

$sql = "SELECT t.*, c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id AND MONTH(t.transaction_date) = $this_month AND YEAR(t.transaction_date) = $this_year
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

$current_page = "transactions";
$page_title = "Transactions";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transactions - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        .balance-pill {
            max-width: 320px; margin: 0 auto 20px auto; padding: 14px;
            border-radius: 30px; text-align: center; font-size: 18px; font-weight: bold; color: #ffffff;
        }
        .balance-positive { background-color: #219653; }
        .balance-negative { background-color: #E74C3C; }

        .category-group { background: #ffffff; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 12px; max-width: none; }
        .category-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; cursor: pointer; }
        .category-header .left { display: flex; align-items: center; gap: 12px; }
        .category-icon { width: 38px; height: 38px; border-radius: 50%; background: #F7FBFC; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .category-count { background: #769FCD; color: #ffffff; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; }
        .category-total.income { color: #219653; font-weight: bold; }
        .category-total.expense { color: #E74C3C; font-weight: bold; }

        .item-list { display: none; }
        .item-row { display: flex; justify-content: space-between; padding: 10px 18px 10px 66px; border-top: 1px solid #F5F5F5; font-size: 14px; }
        .item-row .item-date { color: #999999; font-size: 12px; }
        .item-row .item-actions a { font-size: 12px; margin-left: 8px; }

        .fab-wrapper { position: fixed; bottom: 25px; right: 40px; }
        .fab { width: 56px; height: 56px; border-radius: 50%; border: none; background: #219653; color: #ffffff; font-size: 26px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }

        #addFormWrapper { max-width: 500px; margin: 0 auto 30px auto; background: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .type-toggle { display: flex; gap: 10px; margin-bottom: 20px; }
        .type-toggle button { flex: 1; padding: 12px; border-radius: 8px; border: 2px solid #DDDDDD; background: #ffffff; color: #666666; font-weight: bold; cursor: pointer; font-size: 15px; }
        .type-toggle button.active.income-active { border-color: #219653; background: #F2FBF6; color: #219653; }
        .type-toggle button.active.expense-active { border-color: #E74C3C; background: #FDEDEB; color: #E74C3C; }
    </style>
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <p style="text-align:center; color:#666; margin-top:-10px;"><?php echo $month_name; ?></p>
        <div class="balance-pill <?php echo $balance >= 0 ? 'balance-positive' : 'balance-negative'; ?>">
            Balance: Rs. <?php echo number_format($balance, 2); ?>
        </div>

        <?php if ($message) echo "<p style='text-align:center;'>$message</p>"; ?>

        <?php if (count($groups) === 0) { ?>
            <p style="text-align:center; color:#888;">No transactions yet this month. Tap the + button to add one.</p>
        <?php } ?>

        <?php foreach ($groups as $cid => $group) { $groupId = "group-" . $cid; ?>
            <div class="category-group">
                <div class="category-header" onclick="toggleGroup('<?php echo $groupId; ?>')">
                    <div class="left">
                        <span class="category-icon"><?php echo category_icon($group['name']); ?></span>
                        <span><?php echo htmlspecialchars($group['name']); ?></span>
                        <span class="category-count"><?php echo count($group['items']); ?></span>
                    </div>
                    <span class="category-total <?php echo $group['type']; ?>">
                        <?php echo $group['type'] == 'income' ? '+' : '-'; ?> Rs. <?php echo number_format($group['total'], 2); ?>
                    </span>
                </div>
                <div class="item-list" id="<?php echo $groupId; ?>">
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
            <h3 id="addFormTitle" style="text-align:center; margin-top:0;">Add Transaction</h3>
            <div class="type-toggle">
                <button type="button" id="incomeToggle" onclick="setType('income')">Income</button>
                <button type="button" id="expenseToggle" onclick="setType('expense')">Expense</button>
            </div>
            <form method="POST" action="transactions.php">
                <input type="hidden" name="type" id="typeField" value="expense">
                Category:
                <select name="category_id" id="categorySelect">
                    <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)) { ?>
                        <option value="<?php echo $cat['category_id']; ?>" data-type="<?php echo $cat['category_type']; ?>">
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php } ?>
                </select><br><br>
                Amount: <input type="number" step="0.01" name="amount" id="amountField" required><br><br>
                Description: <input type="text" name="description"><br><br>
                Date: <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required><br><br>
                <button type="submit">Save</button>
            </form>
        </div>

        <div class="fab-wrapper">
            <div class="fab" onclick="openAddForm()">+</div>
        </div>
    </div>

    <script>
        function toggleGroup(groupId) {
            var el = document.getElementById(groupId);
            el.style.display = (el.style.display === "block") ? "none" : "block";
        }

        var categorySelect = document.getElementById('categorySelect');
        var allOptions = Array.from(categorySelect.options);

        function setType(type) {
            document.getElementById('typeField').value = type;
            document.getElementById('addFormTitle').innerText = type === 'income' ? 'Add Income' : 'Add Expense';

            var incomeBtn = document.getElementById('incomeToggle');
            var expenseBtn = document.getElementById('expenseToggle');
            incomeBtn.classList.remove('active', 'income-active');
            expenseBtn.classList.remove('active', 'expense-active');
            if (type === 'income') { incomeBtn.classList.add('active', 'income-active'); }
            else { expenseBtn.classList.add('active', 'expense-active'); }

            allOptions.forEach(function(opt) { opt.hidden = opt.dataset.type !== type; });
            var firstMatch = allOptions.find(function(opt) { return opt.dataset.type === type; });
            if (firstMatch) categorySelect.value = firstMatch.value;
        }

        function openAddForm() {
            var wrapper = document.getElementById('addFormWrapper');
            wrapper.style.display = 'block';
            setType('expense');
            wrapper.scrollIntoView({ behavior: 'smooth' });
            document.getElementById('amountField').focus();
        }
    </script>
</body>
</html>