<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$this_month = date('n');
$this_year = date('Y');
$month_name = date('F Y');

// ---- This month's totals ----
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

// ---- Last month's totals, so we can show "vs last month" ----
$last_month = $this_month - 1;
$last_year = $this_year;
if ($last_month == 0) { $last_month = 12; $last_year = $this_year - 1; }

$sql = "SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = $user_id
        AND MONTH(transaction_date) = $last_month
        AND YEAR(transaction_date) = $last_year";
$last_summary = mysqli_fetch_assoc(mysqli_query($conn, $sql));
$last_income = $last_summary['total_income'] ?? 0;
$last_expense = $last_summary['total_expense'] ?? 0;
$last_balance = $last_income - $last_expense;

// Simple percentage change helper - returns null if there's nothing to compare against
function percent_change($current, $previous) {
    if ($previous == 0) return null;
    return round((($current - $previous) / abs($previous)) * 100);
}
$income_change = percent_change($income, $last_income);
$expense_change = percent_change($expense, $last_expense);
$balance_change = percent_change($balance, $last_balance);

// ---- Spending by category (for donut chart + legend) ----
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
$palette = ['#769FCD', '#E9B949', '#8E44AD', '#16A085', '#E74C3C', '#5A80AC'];
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
        LIMIT 3";
$budget_widget = mysqli_query($conn, $sql);

// ---- Recent transactions ----
$sql = "SELECT t.*, c.category_name
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        ORDER BY t.transaction_date DESC, t.transaction_id DESC
        LIMIT 5";
$recent = mysqli_query($conn, $sql);

// ---- Unread budget alerts, for the notification bell ----
$sql = "SELECT COUNT(*) AS total FROM budget_alerts WHERE user_id = $user_id AND is_read = 0";
$unread_alerts = mysqli_fetch_assoc(mysqli_query($conn, $sql))['total'];

// ---- Time-based greeting ----
$hour = (int) date('G');
if ($hour < 12) { $greeting = "Good morning"; }
elseif ($hour < 17) { $greeting = "Good afternoon"; }
else { $greeting = "Good evening"; }

$first_name = explode(' ', $_SESSION["full_name"])[0];
$avatar_letter = strtoupper(substr($_SESSION["full_name"], 0, 1));

$current_page = "dashboard";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">

    <style>
        .summary-row { display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 20px; }
        .summary-card {
            flex: 1; min-width: 220px;
            background: #ffffff; border-radius: 12px; padding: 20px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.05);
            position: relative; overflow: hidden;
        }
        .summary-card .icon-circle {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 10px;
        }
        .icon-earned { background: #E7F6EE; }
        .icon-spent { background: #FBEAE8; }
        .icon-balance { background: #EAF1FB; }

        .summary-card .label { font-size: 13px; color: #888888; }
        .summary-card .value { font-size: 24px; font-weight: bold; margin: 4px 0 8px 0; }
        .val-earned { color: #219653; }
        .val-spent { color: #E74C3C; }
        .val-balance { color: #769FCD; }

        .change-tag { font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-right: 6px; }
        .change-up { background: #E7F6EE; color: #1B7943; }
        .change-down { background: #FBEAE8; color: #C0392B; }
        .change-label { font-size: 11px; color: #999999; }

        /* Simple wave-like decoration at the bottom of each card using a rounded shape */
        .card-wave {
            position: absolute; bottom: -20px; left: -20px; right: -20px; height: 40px;
            border-radius: 50%;
        }
        .wave-earned { background: #E7F6EE; }
        .wave-spent { background: #FBEAE8; }
        .wave-balance { background: #EAF1FB; }

        .two-col { display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 20px; }
        .col-box { flex: 1; min-width: 320px; background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); }
        .col-box h3 { margin-top: 0; }
        .box-header { display: flex; justify-content: space-between; align-items: center; }
        .view-all-link { font-size: 13px; color: #219653; text-decoration: none; font-weight: bold; }

        .donut-chart { width: 150px; height: 150px; border-radius: 50%; margin: 15px auto; position: relative; }
        .donut-hole { position: absolute; top: 22px; left: 22px; width: 106px; height: 106px; background: #ffffff; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .donut-hole .donut-total { font-weight: bold; font-size: 16px; }
        .donut-hole .donut-label { font-size: 11px; color: #999999; }

        .legend-row { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; font-size: 13px; }
        .legend-left { display: flex; align-items: center; gap: 8px; }
        .legend-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .legend-percent { color: #999999; margin-left: 6px; }

        .budget-mini { margin-bottom: 16px; display: flex; gap: 12px; }
        .budget-mini:last-child { margin-bottom: 0; }
        .budget-mini-icon { width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .budget-mini-body { flex: 1; }
        .budget-mini-top { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; }
        .progress-track { background: #EFEFEF; border-radius: 20px; height: 7px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 20px; }
        .fill-ok { background-color: #219653; }
        .fill-warning { background-color: #E9B949; }
        .fill-over { background-color: #E74C3C; }
        .status-pill { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-top: 5px; }
        .status-ok { background: #E7F6EE; color: #1B7943; }
        .status-warning { background: #FDF3E0; color: #B8860B; }
        .status-over { background: #FBEAE8; color: #C0392B; }

        .txn-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F5F5F5; }
        .txn-row:last-child { border-bottom: none; }
        .txn-left { display: flex; align-items: center; gap: 10px; }
        .txn-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .txn-icon-income { background: #E7F6EE; }
        .txn-icon-expense { background: #FBEAE8; }
        .txn-name { font-size: 14px; }
        .txn-sub { font-size: 11px; color: #999999; }
        .txn-amount { font-weight: bold; font-size: 14px; text-align: right; }
        .txn-date { font-size: 11px; color: #999999; text-align: right; }
    </style>
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-greeting">
                <h2><?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?> 👋</h2>
                <p>Here's your financial overview for <?php echo $month_name; ?></p>
            </div>
            <div class="topbar-right">
                <div class="notif-bell">
                    🔔
                    <?php if ($unread_alerts > 0) { ?>
                        <span class="notif-badge"><?php echo $unread_alerts; ?></span>
                    <?php } ?>
                </div>
                <div class="avatar-block">
                    <div class="avatar-circle"><?php echo $avatar_letter; ?></div>
                    <span class="avatar-name"><?php echo htmlspecialchars($first_name); ?></span>
                </div>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="summary-row">
            <div class="summary-card">
                <div class="icon-circle icon-earned">⬆️</div>
                <div class="label">Total Earned</div>
                <div class="value val-earned">Rs. <?php echo number_format($income, 2); ?></div>
                <?php if ($income_change !== null) { ?>
                    <span class="change-tag <?php echo $income_change >= 0 ? 'change-up' : 'change-down'; ?>">
                        <?php echo ($income_change >= 0 ? '+' : '') . $income_change; ?>%
                    </span>
                    <span class="change-label">vs last month</span>
                <?php } ?>
                <div class="card-wave wave-earned"></div>
            </div>

            <div class="summary-card">
                <div class="icon-circle icon-spent">⬇️</div>
                <div class="label">Total Spent</div>
                <div class="value val-spent">Rs. <?php echo number_format($expense, 2); ?></div>
                <?php if ($expense_change !== null) { ?>
                    <span class="change-tag <?php echo $expense_change >= 0 ? 'change-down' : 'change-up'; ?>">
                        <?php echo ($expense_change >= 0 ? '+' : '') . $expense_change; ?>%
                    </span>
                    <span class="change-label">vs last month</span>
                <?php } ?>
                <div class="card-wave wave-spent"></div>
            </div>

            <div class="summary-card">
                <div class="icon-circle icon-balance">👛</div>
                <div class="label">Current Balance</div>
                <div class="value val-balance">Rs. <?php echo number_format($balance, 2); ?></div>
                <?php if ($balance_change !== null) { ?>
                    <span class="change-tag <?php echo $balance_change >= 0 ? 'change-up' : 'change-down'; ?>">
                        <?php echo ($balance_change >= 0 ? '+' : '') . $balance_change; ?>%
                    </span>
                    <span class="change-label">vs last month</span>
                <?php } ?>
                <div class="card-wave wave-balance"></div>
            </div>
        </div>

        <!-- Spending Overview + Budget Status -->
        <div class="two-col">
            <div class="col-box">
                <div class="box-header"><h3>Spending Overview</h3></div>
                <?php if (count($category_breakdown) > 0) { ?>
                    <div class="donut-chart" style="background: conic-gradient(<?php echo $conic_gradient; ?>);">
                        <div class="donut-hole">
                            <div class="donut-total">Rs. <?php echo number_format($total_expense_for_chart, 0); ?></div>
                            <div class="donut-label">Total Spent</div>
                        </div>
                    </div>
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
                    <p style="text-align:right; margin-top:12px;"><a class="view-all-link" href="../reports/reports.php">View full report &rarr;</a></p>
                <?php } else { ?>
                    <p style="color:#888;">No expenses recorded yet this month.</p>
                <?php } ?>
            </div>

            <div class="col-box">
                <div class="box-header">
                    <h3>Budget Status</h3>
                    <a class="view-all-link" href="../budgets/budgets.php">View All</a>
                </div>
                <?php if (mysqli_num_rows($budget_widget) === 0) { ?>
                    <p style="color:#888;">No budgets set for this month yet. <a href="../budgets/budgets.php">Add one</a>.</p>
                <?php } else { while ($b = mysqli_fetch_assoc($budget_widget)) {
                    $spent = $b['spent']; $budget = $b['budget_amount'];
                    $percent = $budget > 0 ? min(100, round(($spent / $budget) * 100)) : 0;
                    if ($spent > $budget) { $fill = 'fill-over'; $status = 'status-over'; $msg = 'Over budget'; }
                    elseif ($percent >= 80) { $fill = 'fill-warning'; $status = 'status-warning'; $msg = 'Approaching limit'; }
                    else { $fill = 'fill-ok'; $status = 'status-ok'; $msg = 'On track'; }
                    $gc = category_color($b['category_name']);
                ?>
                    <div class="budget-mini">
                        <div class="budget-mini-icon" style="background: <?php echo $gc['bg']; ?>; color: <?php echo $gc['text']; ?>;"><?php echo category_icon($b['category_name']); ?></div>
                        <div class="budget-mini-body">
                            <div class="budget-mini-top">
                                <span><?php echo htmlspecialchars($b['category_name']); ?></span>
                                <span>Rs. <?php echo number_format($spent, 2); ?> / <?php echo number_format($budget, 2); ?></span>
                            </div>
                            <div class="progress-track"><div class="progress-fill <?php echo $fill; ?>" style="width: <?php echo $percent; ?>%;"></div></div>
                            <span class="status-pill <?php echo $status; ?>"><?php echo $msg; ?></span>
                        </div>
                    </div>
                <?php } } ?>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-box">
            <div class="box-header">
                <h3>Recent Transactions</h3>
                <a class="view-all-link" href="../transactions/transactions.php">View All</a>
            </div>
            <?php if (mysqli_num_rows($recent) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($recent)) { ?>
                    <div class="txn-row">
                        <div class="txn-left">
                            <div class="txn-icon <?php echo $row['type'] == 'income' ? 'txn-icon-income' : 'txn-icon-expense'; ?>">
                                <?php echo category_icon($row['category_name']); ?>
                            </div>
                            <div>
                                <div class="txn-name"><?php echo htmlspecialchars($row['description'] ?: $row['category_name']); ?></div>
                                <div class="txn-sub"><?php echo htmlspecialchars($row['category_name']); ?></div>
                            </div>
                        </div>
                        <div>
                            <div class="txn-amount <?php echo $row['type'] == 'income' ? 'amount-income' : 'amount-expense'; ?>">
                                Rs. <?php echo number_format($row['amount'], 2); ?>
                            </div>
                            <div class="txn-date"><?php echo date('M j, Y', strtotime($row['transaction_date'])); ?></div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p style="color:#888;">No transactions yet. <a href="../transactions/transactions.php">Add your first one</a>.</p>
            <?php } ?>
        </div>
    </div>
</body>
</html>