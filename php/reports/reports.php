<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-t');
$from = date('Y-m-d', strtotime($from));
$to   = date('Y-m-d', strtotime($to));

$sql = "SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = $user_id AND transaction_date BETWEEN '$from' AND '$to'";
$summary = mysqli_fetch_assoc(mysqli_query($conn, $sql));
$income = $summary['total_income'] ?? 0;
$expense = $summary['total_expense'] ?? 0;
$net = $income - $expense;

$sql = "SELECT t.*, c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id AND t.transaction_date BETWEEN '$from' AND '$to'
        ORDER BY t.transaction_date DESC";
$transactions = mysqli_query($conn, $sql);

$current_page = "reports";
$page_title = "Reports";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .filter-bar { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 15px; max-width: none; }
        .filter-bar label { font-size: 13px; color: #555555; display: block; margin-bottom: 4px; }
        .filter-bar input[type="date"] { width: auto; margin-bottom: 0; }

        .summary-line { margin-bottom: 20px; font-size: 15px; max-width: none; }
        .summary-line span { margin-right: 20px; }

        .action-buttons { margin-bottom: 20px; }
        .action-buttons a { display: inline-block; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; margin-right: 10px; }
        /* Was #769FCD, not in the real palette — matches style.css's own
           <button> rule (#94CBDB / dark navy text) instead of inventing a
           separate blue just for these two links. */
        .btn-csv { background: #94CBDB; color: #072736; }
        .btn-print { background: #ffffff; color: #072736; border: 1px solid #94CBDB; }

        @media print {
            .sidebar, .topbar, .filter-bar, .action-buttons { display: none !important; }
            .main-content { margin-left: 0 !important; }
        }
    </style>
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>
        <p style="color:#666; margin-top:-15px;"><?php echo date('M j, Y', strtotime($from)); ?> to <?php echo date('M j, Y', strtotime($to)); ?></p>

        <form method="GET" action="reports.php" class="filter-bar">
            <div><label>From</label><input type="date" name="from" value="<?php echo $from; ?>"></div>
            <div><label>To</label><input type="date" name="to" value="<?php echo $to; ?>"></div>
            <div><button type="submit">Apply</button></div>
        </form>

        <div class="action-buttons">
            <a class="btn-csv" href="export_csv.php?from=<?php echo $from; ?>&to=<?php echo $to; ?>"><i class="fa-solid fa-file-arrow-down"></i> Download CSV</a>
            <a class="btn-print" href="#" onclick="window.print(); return false;"><i class="fa-solid fa-print"></i> Print / Save as PDF</a>
        </div>

        <p class="summary-line">
            <span class="amount-income">Income: Rs. <?php echo number_format($income, 2); ?></span>
            <span class="amount-expense">Expenses: Rs. <?php echo number_format($expense, 2); ?></span>
            <span><strong>Net: Rs. <?php echo number_format($net, 2); ?></strong></span>
        </p>

        <table style="max-width:none;">
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
    </div>
</body>
</html>