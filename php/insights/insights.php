<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];

$this_month = date('n');
$this_year  = date('Y');
$last_month = $this_month - 1;
$last_year  = $this_year;
if ($last_month == 0) { $last_month = 12; $last_year = $this_year - 1; }

$messages = [];

// ---- Income vs expense summary ----
$sql = "SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = $user_id AND MONTH(transaction_date) = $this_month AND YEAR(transaction_date) = $this_year";
$result = mysqli_fetch_assoc(mysqli_query($conn, $sql));
$income = $result['total_income'] ?? 0;
$expense = $result['total_expense'] ?? 0;
$net = $income - $expense;

if ($income == 0 && $expense == 0) {
    $messages[] = "You haven't added any transactions this month yet.";
} elseif ($net >= 0) {
    $messages[] = "This month you earned Rs. " . number_format($income, 2) . " and spent Rs. " . number_format($expense, 2) . " - you saved Rs. " . number_format($net, 2) . ". Nice work!";
} else {
    $messages[] = "This month you spent Rs. " . number_format($expense, 2) . " but only earned Rs. " . number_format($income, 2) . " - that's Rs. " . number_format(abs($net), 2) . " more than you brought in.";
}

// ---- Top spending category ----
$sql = "SELECT c.category_name, SUM(t.amount) AS total
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id AND t.type = 'expense'
        AND MONTH(t.transaction_date) = $this_month AND YEAR(t.transaction_date) = $this_year
        GROUP BY t.category_id ORDER BY total DESC LIMIT 1";
$top = mysqli_fetch_assoc(mysqli_query($conn, $sql));
if ($top && $expense > 0) {
    $percent = round(($top['total'] / $expense) * 100);
    $messages[] = "Your biggest expense this month is " . $top['category_name'] . " (Rs. " . number_format($top['total'], 2) . "), making up $percent% of your total spending.";
}

// ---- Month-over-month comparison, per category ----
$sql = "SELECT c.category_name,
        SUM(CASE WHEN MONTH(t.transaction_date) = $this_month AND YEAR(t.transaction_date) = $this_year THEN t.amount ELSE 0 END) AS this_month_total,
        SUM(CASE WHEN MONTH(t.transaction_date) = $last_month AND YEAR(t.transaction_date) = $last_year THEN t.amount ELSE 0 END) AS last_month_total
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id AND t.type = 'expense'
        GROUP BY t.category_id
        HAVING this_month_total > 0 OR last_month_total > 0";
$comparisons = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($comparisons)) {
    $curr = $row['this_month_total']; $prev = $row['last_month_total'];
    if ($prev == 0 || $curr == 0) continue;
    $change_percent = round((($curr - $prev) / $prev) * 100);
    if (abs($change_percent) < 5) continue;
    if ($change_percent > 0) {
        $messages[] = "You spent " . $change_percent . "% more on " . $row['category_name'] . " this month (Rs. " . number_format($curr, 2) . ") compared to last month (Rs. " . number_format($prev, 2) . ").";
    } else {
        $messages[] = "Good job - you spent " . abs($change_percent) . "% less on " . $row['category_name'] . " this month (Rs. " . number_format($curr, 2) . ") compared to last month (Rs. " . number_format($prev, 2) . ").";
    }
}

// ---- Budget status ----
$sql = "SELECT b.budget_amount, c.category_name, COALESCE(SUM(t.amount), 0) AS spent
        FROM budgets b
        JOIN categories c ON b.category_id = c.category_id
        LEFT JOIN transactions t ON t.category_id = b.category_id AND t.user_id = b.user_id
            AND t.type = 'expense' AND MONTH(t.transaction_date) = b.month AND YEAR(t.transaction_date) = b.year
        WHERE b.user_id = $user_id AND b.month = $this_month AND b.year = $this_year
        GROUP BY b.budget_id";
$budget_status = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($budget_status)) {
    $spent = $row['spent']; $budget = $row['budget_amount'];
    $percent_used = $budget > 0 ? round(($spent / $budget) * 100) : 0;
    if ($spent > $budget) {
        $messages[] = "You've gone over your " . $row['category_name'] . " budget by Rs. " . number_format($spent - $budget, 2) . " this month.";
    } elseif ($percent_used >= 80) {
        $messages[] = "Heads up - you've used $percent_used% of your " . $row['category_name'] . " budget this month.";
    } else {
        $messages[] = "You're on track with your " . $row['category_name'] . " budget - $percent_used% used so far this month.";
    }
}

// ---- Savings goal progress ----
$sql = "SELECT * FROM savings_goals WHERE user_id = $user_id AND status = 'active'";
$goals = mysqli_query($conn, $sql);
$active_goal_count = 0;
while ($goal = mysqli_fetch_assoc($goals)) {
    $active_goal_count++;
    $target = $goal['target_amount']; $saved = $goal['saved_amount'];
    $percent = $target > 0 ? round(($saved / $target) * 100) : 0;
    $remaining = $target - $saved;
    $sentence = "You're $percent% of the way to your \"" . $goal['goal_name'] . "\" goal (Rs. " . number_format($saved, 2) . " of Rs. " . number_format($target, 2) . ").";
    $sentence .= $remaining > 0 ? " Rs. " . number_format($remaining, 2) . " more to go." : " You've reached your goal!";
    $messages[] = $sentence;
}
if ($active_goal_count == 0) {
    $messages[] = "You don't have any active savings goals yet. Add one to start tracking your progress.";
}

// ---- Category breakdown, for the pie chart (pure CSS conic-gradient, no library) ----
$sql = "SELECT c.category_name, SUM(t.amount) AS total
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id AND t.type = 'expense'
        AND MONTH(t.transaction_date) = $this_month AND YEAR(t.transaction_date) = $this_year
        GROUP BY t.category_id ORDER BY total DESC";
$chart_result = mysqli_query($conn, $sql);
$category_breakdown = [];
while ($row = mysqli_fetch_assoc($chart_result)) {
    $category_breakdown[] = $row;
}

$palette = ['#769FCD', '#E9B949', '#E74C3C', '#8E44AD', '#219653', '#5A80AC', '#C97B4A', '#34495E'];
$total_for_chart = array_sum(array_column($category_breakdown, 'total'));
$gradient_stops = [];
$running_percent = 0;
foreach ($category_breakdown as $i => $cat) {
    $slice_percent = $total_for_chart > 0 ? ($cat['total'] / $total_for_chart) * 100 : 0;
    $color = $palette[$i % count($palette)];
    $gradient_stops[] = "$color {$running_percent}% " . ($running_percent + $slice_percent) . "%";
    $running_percent += $slice_percent;
}
$conic_gradient = implode(', ', $gradient_stops);

// ---- Last month's income/expense, for the comparison bars ----
$sql = "SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = $user_id AND MONTH(transaction_date) = $last_month AND YEAR(transaction_date) = $last_year";
$last_result = mysqli_fetch_assoc(mysqli_query($conn, $sql));
$last_income = $last_result['total_income'] ?? 0;
$last_expense = $last_result['total_expense'] ?? 0;

// Scale every bar's width against the single largest value, so they're
// visually comparable on the same 0-100% track.
$max_value = max($income, $expense, $last_income, $last_expense, 1);

$current_page = "insights";
$page_title = "Insights";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Insights - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        .insight-card { background: #ffffff; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); padding: 14px 18px; margin-bottom: 10px; max-width: none; }

        .two-col { display: flex; gap: 18px; flex-wrap: wrap; }
        .two-col .col-box { flex: 1; min-width: 320px; }

        .donut-chart { width: 150px; height: 150px; border-radius: 50%; margin: 15px auto; position: relative; }
        .donut-hole { position: absolute; top: 22px; left: 22px; width: 106px; height: 106px; background: #ffffff; border-radius: 50%; }
        .legend-row { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; font-size: 13px; }
        .legend-left { display: flex; align-items: center; gap: 8px; }
        .legend-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }

        .compare-row { margin-bottom: 14px; }
        .compare-label { font-size: 13px; margin-bottom: 5px; display: flex; justify-content: space-between; }
        .compare-track { background: #EFEFEF; border-radius: 6px; height: 16px; overflow: hidden; }
        .compare-fill { height: 100%; border-radius: 6px; }
        .fill-income { background-color: #219653; }
        .fill-expense { background-color: #E74C3C; }
    </style>
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>
        <p style="color:#666; margin-top:-15px;">Here's a simple summary of your spending and saving patterns:</p>

        <?php foreach ($messages as $msg) { ?>
            <div class="insight-card"><?php echo htmlspecialchars($msg); ?></div>
        <?php } ?>

        <div class="two-col" style="margin-top:20px;">
            <div class="col-box">
                <h5 style="margin-top:0;">Expense Breakdown by Category (This Month)</h5>
                <?php if (count($category_breakdown) > 0) { ?>
                    <div class="donut-chart" style="background: conic-gradient(<?php echo $conic_gradient; ?>);">
                        <div class="donut-hole"></div>
                    </div>
                    <?php foreach ($category_breakdown as $i => $cat) {
                        $pct = $total_for_chart > 0 ? round(($cat['total'] / $total_for_chart) * 100) : 0;
                        $color = $palette[$i % count($palette)];
                    ?>
                        <div class="legend-row">
                            <div class="legend-left">
                                <span class="legend-dot" style="background: <?php echo $color; ?>;"></span>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </div>
                            <div>Rs. <?php echo number_format($cat['total'], 2); ?> (<?php echo $pct; ?>%)</div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p style="color:#888;">No expenses recorded yet this month.</p>
                <?php } ?>
            </div>

            <div class="col-box">
                <h5 style="margin-top:0;">Income vs Expense: This Month vs Last Month</h5>

                <div class="compare-row">
                    <div class="compare-label"><span>Income - Last Month</span><span>Rs. <?php echo number_format($last_income, 2); ?></span></div>
                    <div class="compare-track"><div class="compare-fill fill-income" style="width: <?php echo ($last_income / $max_value) * 100; ?>%;"></div></div>
                </div>

                <div class="compare-row">
                    <div class="compare-label"><span>Income - This Month</span><span>Rs. <?php echo number_format($income, 2); ?></span></div>
                    <div class="compare-track"><div class="compare-fill fill-income" style="width: <?php echo ($income / $max_value) * 100; ?>%;"></div></div>
                </div>

                <div class="compare-row">
                    <div class="compare-label"><span>Expense - Last Month</span><span>Rs. <?php echo number_format($last_expense, 2); ?></span></div>
                    <div class="compare-track"><div class="compare-fill fill-expense" style="width: <?php echo ($last_expense / $max_value) * 100; ?>%;"></div></div>
                </div>

                <div class="compare-row">
                    <div class="compare-label"><span>Expense - This Month</span><span>Rs. <?php echo number_format($expense, 2); ?></span></div>
                    <div class="compare-track"><div class="compare-fill fill-expense" style="width: <?php echo ($expense / $max_value) * 100; ?>%;"></div></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>