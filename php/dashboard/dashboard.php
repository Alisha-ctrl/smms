<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$this_month = date('n');
$this_year = date('Y');
$month_name = date('F Y');

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

$category_breakdown = [];
while ($row = mysqli_fetch_assoc($result)) {
    $category_breakdown[] = $row;
}

// ---- Build a pure CSS conic-gradient donut chart - no charting library ----
// A conic-gradient paints colored "slices" around a circle based on percentage
// ranges, e.g. conic-gradient(red 0% 30%, blue 30% 100%) paints a pie/donut.
$palette = ['#769FCD', '#E9B949', '#8E44AD', '#16A085', '#E74C3C', '#5A80AC', '#C97B4A', '#34495E'];
$total_expense_for_chart = array_sum(array_column($category_breakdown, 'total'));
$gradient_stops = [];
$running_percent = 0;
foreach ($category_breakdown as $i => $cat) {
    $slice_percent = $total_expense_for_chart > 0 ? ($cat['total'] / $total_expense_for_chart) * 100 : 0;
    $color = $palette[$i % count($palette)];
    $start = $running_percent;
    $end = $running_percent + $slice_percent;
    $gradient_stops[] = "$color {$start}% {$end}%";
    $running_percent = $end;
}
$conic_gradient = implode(', ', $gradient_stops);

// ---- Budgets for this month, with spending calculated ----
$sql = "SELECT b.*, c.category_name,
        COALESCE((SELECT SUM(t.amount) FROM transactions t
                  WHERE t.category_id = b.category_id AND t.user_id = b.user_id
                  AND t.type = 'expense' AND MONTH(t.transaction_date) = b.month AND YEAR(t.transaction_date) = b.year), 0) AS spent
        FROM budgets b
        JOIN categories c ON b.category_id = c.category_id
        WHERE b.user_id = $user_id AND b.month = $this_month AND b.year = $this_year
        ORDER BY b.budget_id DESC
        LIMIT 2";
$budget_widget = mysqli_query($conn, $sql);

$sql = "SELECT t.*, c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        ORDER BY t.transaction_date DESC, t.transaction_id DESC
        LIMIT 6";
$recent = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">

    <style>
        .page-wrap { max-width: 1050px; margin: 30px auto; padding: 0 15px; }

        /* ---- Custom flexbox grid, replacing Bootstrap's row/col ---- */
        .grid-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .grid-col-3 { flex: 1 1 220px; }
        .grid-col-6 { flex: 1 1 400px; }

        .hero-card { position: relative; overflow: hidden; border-radius: 16px; padding: 22px; color: #ffffff; }
        .hero-card::after {
            content: ""; position: absolute; top: -30px; right: -30px; width: 100px; height: 100px;
            border-radius: 50%; background: rgba(255,255,255,0.15);
        }
        .hero-card .hc-label { font-size: 13px; opacity: 0.9; }
        .hero-card .hc-value { font-size: 26px; font-weight: bold; margin-top: 6px; }
        .hero-earned { background: linear-gradient(135deg, #2FAE73, #1B7943); }
        .hero-spent { background: linear-gradient(135deg, #EB6B5D, #C0392B); }
        .hero-balance { background: linear-gradient(135deg, #8FB7DE, #5A80AC); }

        .card-block { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

        /* ---- Pure CSS donut chart ---- */
        .donut-chart {
            width: 160px; height: 160px; border-radius: 50%;
            margin: 10px auto;
            position: relative;
        }
        .donut-chart .donut-hole {
            position: absolute; top: 25px; left: 25px; width: 110px; height: 110px;
            background: #ffffff; border-radius: 50%;
        }

        .legend-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .legend-left { display: flex; align-items: center; gap: 8px; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .legend-percent { color: #999999; margin-left: 6px; }

        .top-cat-row { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }
        .top-cat-item { text-align: center; width: 70px; }
        .top-cat-icon {
            width: 50px; height: 50px; border-radius: 14px; background: #F7FBFC;
            display: flex; align-items: center; justify-content: center; font-size: 22px; margin: 0 auto 6px auto;
        }
        .top-cat-item span { font-size: 11px; color: #555555; }

        .budget-mini { margin-bottom: 16px; }
        .budget-mini:last-child { margin-bottom: 0; }
        .budget-mini-top { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; }
        .progress-track { background: #EFEFEF; border-radius: 20px; height: 8px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 20px; }
        .fill-ok { background-color: #219653; }
        .fill-warning { background-color: #E9B949; }
        .fill-over { background-color: #E74C3C; }
        .budget-status { font-size: 12px; margin-top: 4px; }
        .status-ok { color: #219653; }
        .status-warning { color: #B8860B; }
        .status-over { color: #E74C3C; }

        .txn-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F0F0F0; }
        .txn-row:last-child { border-bottom: none; }
        .txn-left { display: flex; align-items: center; gap: 10px; }
        .txn-icon {
            width: 38px; height: 38px; border-radius: 50%; background: #F7FBFC;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .txn-name { font-size: 14px; }
        .txn-date { font-size: 11px; color: #999999; }
        .txn-amount { font-weight: bold; font-size: 14px; }
        .direction-income { color: #219653; }
        .direction-expense { color: #E74C3C; }

        .quick-links { margin-top: 15px; margin-bottom: 30px; }
        .quick-links a {
            display: inline-block; padding: 8px 16px; margin-right: 10px; margin-bottom: 8px;
            border: 1px solid #D6E6F2; border-radius: 6px; color: #769FCD; text-decoration: none; font-size: 13px;
        }
        .quick-links a:hover { background: #F7FBFC; }
    </style>
</head>
<body>
    <?php include "../includes/nav.php"; ?>

    <div class="page-wrap">
        <h2>Dashboard</h2>
        <p style="color:#666;">Welcome back, <?php echo htmlspecialchars($_SESSION["full_name"]); ?> - here's <?php echo $month_name; ?> at a glance.</p>

        <div class="grid-row">
            <div class="grid-col-3">
                <div class="hero-card hero-earned">
                    <div class="hc-label">⬆️ Earned</div>
                    <div class="hc-value">Rs. <?php echo number_format($income, 2); ?></div>
                </div>
            </div>
            <div class="grid-col-3">
                <div class="hero-card hero-spent">
                    <div class="hc-label">⬇️ Spent</div>
                    <div class="hc-value">Rs. <?php echo number_format($expense, 2); ?></div>
                </div>
            </div>
            <div class="grid-col-3">
                <div class="hero-card hero-balance">
                    <div class="hc-label">👛 Balance</div>
                    <div class="hc-value">Rs. <?php echo number_format($balance, 2); ?></div>
                </div>
            </div>
        </div>

        <div class="grid-row">
            <div class="grid-col-6">
                <div class="card-block">
                    <h5>Where it went</h5>
                    <?php if (count($category_breakdown) > 0) { ?>
                        <div class="donut-chart" style="background: conic-gradient(<?php echo $conic_gradient; ?>);">
                            <div class="donut-hole"></div>
                        </div>
                        <div class="mt-3">
                            <?php foreach ($category_breakdown as $i => $cat) {
                                $pct = $total_expense_for_chart > 0 ? round(($cat['total'] / $total_expense_for_chart) * 100) : 0;
                                $color = $palette[$i % count($palette)];
                            ?>
                                <div class="legend-row">
                                    <div class="legend-left">
                                        <span class="legend-dot" style="background: <?php echo $color; ?>;"></span>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </div>
                                    <div>
                                        Rs. <?php echo number_format($cat['total'], 2); ?>
                                        <span class="legend-percent">(<?php echo $pct; ?>%)</span>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <p style="color:#888;">No expenses recorded yet this month.</p>
                    <?php } ?>
                </div>
            </div>

            <div class="grid-col-6">
                <div class="card-block">
                    <h5>Budget Status</h5>
                    <?php if (mysqli_num_rows($budget_widget) === 0) { ?>
                        <p style="color:#888;">No budgets set for this month yet. <a href="../budgets/budgets.php">Add one</a>.</p>
                    <?php } else { while ($b = mysqli_fetch_assoc($budget_widget)) {
                        $spent = $b['spent']; $budget = $b['budget_amount'];
                        $percent = $budget > 0 ? min(100, round(($spent / $budget) * 100)) : 0;
                        if ($spent > $budget) { $fill = 'fill-over'; $status = 'status-over'; $msg = '❗ Over budget'; }
                        elseif ($percent >= 80) { $fill = 'fill-warning'; $status = 'status-warning'; $msg = '⚠️ Approaching limit'; }
                        else { $fill = 'fill-ok'; $status = 'status-ok'; $msg = '✅ On track'; }
                    ?>
                        <div class="budget-mini">
                            <div class="budget-mini-top">
                                <span><?php echo htmlspecialchars($b['category_name']); ?></span>
                                <span>Rs. <?php echo number_format($spent, 2); ?> / <?php echo number_format($budget, 2); ?></span>
                            </div>
                            <div class="progress-track"><div class="progress-fill <?php echo $fill; ?>" style="width: <?php echo $percent; ?>%;"></div></div>
                            <div class="budget-status <?php echo $status; ?>"><?php echo $msg; ?></div>
                        </div>
                    <?php } } ?>
                    <p style="margin-top:10px; margin-bottom:0;"><a href="../budgets/budgets.php" style="font-size:13px;">Manage all budgets &rarr;</a></p>
                </div>
            </div>
        </div>

        <?php if (count($category_breakdown) > 0) { ?>
        <div class="card-block" style="margin-bottom:20px;">
            <h5>Top Categories</h5>
            <div class="top-cat-row">
                <?php foreach (array_slice($category_breakdown, 0, 5) as $cat) { ?>
                    <div class="top-cat-item">
                        <div class="top-cat-icon"><?php echo category_icon($cat['category_name']); ?></div>
                        <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>

        <div class="card-block">
            <h5>Recent Transactions</h5>
            <?php if (mysqli_num_rows($recent) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($recent)) { ?>
                    <div class="txn-row">
                        <div class="txn-left">
                            <div class="txn-icon"><?php echo category_icon($row['category_name']); ?></div>
                            <div>
                                <div class="txn-name"><?php echo htmlspecialchars($row['category_name']); ?></div>
                                <div class="txn-date"><?php echo $row['transaction_date']; ?></div>
                            </div>
                        </div>
                        <div class="txn-amount <?php echo $row['type'] == 'income' ? 'direction-income' : 'direction-expense'; ?>">
                            <?php echo $row['type'] == 'income' ? '⬆️' : '⬇️'; ?>
                            Rs. <?php echo number_format($row['amount'], 2); ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p style="color:#888;">No transactions yet. <a href="../transactions/transactions.php">Add your first one</a>.</p>
            <?php } ?>
        </div>

        <div class="quick-links">
            <a href="../transactions/transactions.php">View All Transactions</a>
            <a href="../budgets/budgets.php">Manage Budgets</a>
            <a href="../insights/insights.php">View Insights</a>
        </div>
    </div>
</body>
</html>