<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];

// ---- Date range filter (defaults to the current month) ----
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-t');
$from = date('Y-m-d', strtotime($from));
$to   = date('Y-m-d', strtotime($to));

// ---- Overall summary for the selected range ----
$sql = "SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = $user_id
        AND transaction_date BETWEEN '$from' AND '$to'";
$summary = mysqli_fetch_assoc(mysqli_query($conn, $sql));
$income = $summary['total_income'] ?? 0;
$expense = $summary['total_expense'] ?? 0;
$net = $income - $expense;

// ---- Transaction list for the selected range ----
$sql = "SELECT t.*, c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        AND t.transaction_date BETWEEN '$from' AND '$to'
        ORDER BY t.transaction_date DESC";
$transactions = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">

    <style>
        .filter-bar { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; max-width: 900px; margin: 0 auto 15px auto; }
        .filter-bar label { font-size: 13px; color: #555555; display: block; margin-bottom: 4px; }
        .filter-bar input[type="date"] { width: auto; margin-bottom: 0; }

        .summary-line { max-width: 900px; margin: 0 auto 20px auto; font-size: 15px; }
        .summary-line span { margin-right: 20px; }

        .action-buttons { max-width: 900px; margin: 0 auto 20px auto; }
        .action-buttons a {
            display: inline-block; padding: 8px 16px; border-radius: 6px; text-decoration: none;
            font-weight: bold; font-size: 14px; margin-right: 10px;
        }
        .btn-csv { background: #769FCD; color: #ffffff; }
        .btn-print { background: #ffffff; color: #769FCD; border: 1px solid #769FCD; }

        /* Hides nav/buttons when printing, so only the report itself shows */
        @media print {
            nav, .filter-bar, .action-buttons { display: none !important; }
        }
    </style>
</head>
<body>
    <?php include "../includes/nav.php"; ?>

    <h2>Reports</h2>
    <p style="max-width:900px; margin:0 auto 15px auto; color:#666;">
        <?php echo date('M j, Y', strtotime($from)); ?> to <?php echo date('M j, Y', strtotime($to)); ?>
    </p>

    <form method="GET" action="reports.php" class="filter-bar">
        <div>
            <label>From</label>
            <input type="date" name="from" value="<?php echo $from; ?>">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="<?php echo $to; ?>">
        </div>
        <div>
            <button type="submit">Apply</button>
        </div>
    </form>

    <div class="action-buttons">
        <a class="btn-csv" href="export_csv.php?from=<?php echo $from; ?>&to=<?php echo $to; ?>">Download CSV</a>
        <a class="btn-print" href="#" onclick="window.print(); return false;">Print / Save as PDF</a>
    </div>

    <p class="summary-line">
        <span class="amount-income">Income: Rs. <?php echo number_format($income, 2); ?></span>
        <span class="amount-expense">Expenses: Rs. <?php echo number_format($expense, 2); ?></span>
        <span><strong>Net: Rs. <?php echo number_format($net, 2); ?></strong></span>
    </p>

    <table>
        <tr><th>Date</th><th>Category</th><th>Type</th><th>Description</th><th>Amount</th></tr>
        <?php if (mysqli_num_rows($transactions) === 0) { ?>
            <tr><td colspan="5" style="text-align:center; color:#888;">No transactions in this date range.</td></tr>
        <?php } else { while ($row = mysqli_fetch_assoc($transactions)) { ?>
            <tr>
                <td><?php echo $row['transaction_date']; ?></td>
                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                <td><?php echo ucfirst($row['type']); ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td class="<?php echo $row['type'] == 'income' ? 'amount-income' : 'amount-expense'; ?>">
                    Rs. <?php echo number_format($row['amount'], 2); ?>
                </td>
            </tr>
        <?php } } ?>
    </table>
</body>
</html>