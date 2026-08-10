<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$this_month = date('n');
$this_year = date('Y');

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

$sql = "SELECT c.category_name, SUM(t.amount) AS total
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        AND t.type = 'expense'
        AND MONTH(t.transaction_date) = $this_month
        AND YEAR(t.transaction_date) = $this_year
        GROUP BY t.category_id
        ORDER BY total DESC";
$result = mysqli_query($conn, $sql);

$chart_labels = [];
$chart_values = [];
while ($row = mysqli_fetch_assoc($result)) {
    $chart_labels[] = $row['category_name'];
    $chart_values[] = $row['total'];
}

$sql = "SELECT t.*, c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        ORDER BY t.transaction_date DESC, t.transaction_id DESC
        LIMIT 5";
$recent = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>

    <style>
        .summary-card { border-radius: 10px; padding: 20px; color: #ffffff; }
        .summary-card .label { font-size: 14px; opacity: 0.85; }
        .summary-card .value { font-size: 26px; font-weight: bold; margin-top: 5px; }
        .bg-income { background-color: #219653; }
        .bg-expense { background-color: #E74C3C; }
        .bg-balance { background-color: #1B7943; }
        .chart-card, .recent-card { background: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .recent-icon {
            width: 32px; height: 32px; border-radius: 50%; background: #F2FBF6; color: #219653;
            display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include "../includes/nav.php"; ?>

    <div class="container" style="max-width: 1000px; margin: 30px auto;">
        <h2>Dashboard</h2>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION["full_name"]); ?> - here's your snapshot for this month.</p>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="summary-card bg-income">
                    <div class="label">Income</div>
                    <div class="value">Rs. <?php echo number_format($income, 2); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card bg-expense">
                    <div class="label">Expenses</div>
                    <div class="value">Rs. <?php echo number_format($expense, 2); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card bg-balance">
                    <div class="label">Balance</div>
                    <div class="value">Rs. <?php echo number_format($balance, 2); ?></div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="chart-card">
                    <h5>Spending by Category</h5>
                    <?php if (count($chart_labels) > 0) { ?>
                        <canvas id="spendingChart" height="220"></canvas>
                    <?php } else { ?>
                        <p class="text-muted">No expenses recorded yet this month.</p>
                    <?php } ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="recent-card">
                    <h5>Recent Transactions</h5>
                    <?php if (mysqli_num_rows($recent) > 0) { ?>
                        <ul class="list-group list-group-flush">
                            <?php while ($row = mysqli_fetch_assoc($recent)) { ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <span class="recent-icon"><i class="bi <?php echo category_icon($row['category_name']); ?>"></i></span>
                                        <?php echo htmlspecialchars($row['category_name']); ?>
                                        <br><small class="text-muted" style="margin-left:42px;"><?php echo $row['transaction_date']; ?></small>
                                    </span>
                                    <span class="<?php echo $row['type'] == 'income' ? 'text-success' : 'text-danger'; ?> fw-bold">
                                        <?php echo $row['type'] == 'income' ? '+' : '-'; ?> Rs. <?php echo number_format($row['amount'], 2); ?>
                                    </span>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <p class="text-muted">No transactions yet. <a href="../transactions/transactions.php">Add your first one</a>.</p>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="../transactions/transactions.php" class="btn btn-outline-secondary btn-sm">View All Transactions</a>
            <a href="../budgets/budgets.php" class="btn btn-outline-secondary btn-sm">Manage Budgets</a>
            <a href="../insights/insights.php" class="btn btn-outline-secondary btn-sm">View Insights</a>
        </div>
    </div>

    <?php if (count($chart_labels) > 0) { ?>
    <script>
        const labels = <?php echo json_encode($chart_labels); ?>;
        const values = <?php echo json_encode($chart_values); ?>;
        const colors = ['#219653', '#E74C3C', '#E9B949', '#8E44AD', '#2980B9', '#16A085', '#C97B4A', '#34495E'];

        new Chart(document.getElementById('spendingChart'), {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors.slice(0, labels.length) }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    </script>
    <?php } ?>
</body>
</html>