<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];

// ---- Figure out "this month" and "last month" ----
$this_month = date('n');   // 1-12
$this_year  = date('Y');

$last_month = $this_month - 1;
$last_year  = $this_year;
if ($last_month == 0) {    // if current month is January, last month is December of last year
    $last_month = 12;
    $last_year  = $this_year - 1;
}

$messages = []; // we'll collect plain-English sentences here and print them all at the end


// =========================================================
// INSIGHT 1: Income vs Expense summary for this month
// =========================================================
$sql = "SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS total_expense
        FROM transactions
        WHERE user_id = $user_id
        AND MONTH(transaction_date) = $this_month
        AND YEAR(transaction_date) = $this_year";
$result = mysqli_fetch_assoc(mysqli_query($conn, $sql));

$income = $result['total_income'] ?? 0;
$expense = $result['total_expense'] ?? 0;
$net = $income - $expense;

if ($income == 0 && $expense == 0) {
    $messages[] = "You haven't added any transactions this month yet.";
} elseif ($net >= 0) {
    $messages[] = "This month you earned Rs. " . number_format($income, 2) .
                  " and spent Rs. " . number_format($expense, 2) .
                  " — you saved Rs. " . number_format($net, 2) . ". Nice work!";
} else {
    $messages[] = "This month you spent Rs. " . number_format($expense, 2) .
                  " but only earned Rs. " . number_format($income, 2) .
                  " — that's Rs. " . number_format(abs($net), 2) . " more than you brought in.";
}


// =========================================================
// INSIGHT 2: Top spending category this month
// =========================================================
$sql = "SELECT c.category_name, SUM(t.amount) AS total
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        AND t.type = 'expense'
        AND MONTH(t.transaction_date) = $this_month
        AND YEAR(t.transaction_date) = $this_year
        GROUP BY t.category_id
        ORDER BY total DESC
        LIMIT 1";
$top = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if ($top && $expense > 0) {
    $percent = round(($top['total'] / $expense) * 100);
    $messages[] = "Your biggest expense this month is " . $top['category_name'] .
                  " (Rs. " . number_format($top['total'], 2) . "), making up $percent% of your total spending.";
}


// =========================================================
// INSIGHT 3: Spending comparison vs last month (per category)
// =========================================================
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
    $curr = $row['this_month_total'];
    $prev = $row['last_month_total'];

    if ($prev == 0 || $curr == 0) {
        continue; // skip categories with no data in one of the two months - comparison wouldn't be meaningful
    }

    $change_percent = round((($curr - $prev) / $prev) * 100);

    if (abs($change_percent) < 5) {
        continue; // skip tiny changes, not worth mentioning
    }

    if ($change_percent > 0) {
        $messages[] = "You spent " . $change_percent . "% more on " . $row['category_name'] .
                      " this month (Rs. " . number_format($curr, 2) . ") compared to last month (Rs. " . number_format($prev, 2) . ").";
    } else {
        $messages[] = "Good job — you spent " . abs($change_percent) . "% less on " . $row['category_name'] .
                      " this month (Rs. " . number_format($curr, 2) . ") compared to last month (Rs. " . number_format($prev, 2) . ").";
    }
}


// =========================================================
// INSIGHT 4: Budget status for this month
// =========================================================
$sql = "SELECT b.budget_amount, c.category_name,
        COALESCE(SUM(t.amount), 0) AS spent
        FROM budgets b
        JOIN categories c ON b.category_id = c.category_id
        LEFT JOIN transactions t
            ON t.category_id = b.category_id
            AND t.user_id = b.user_id
            AND t.type = 'expense'
            AND MONTH(t.transaction_date) = b.month
            AND YEAR(t.transaction_date) = b.year
        WHERE b.user_id = $user_id
        AND b.month = $this_month
        AND b.year = $this_year
        GROUP BY b.budget_id";
$budget_status = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($budget_status)) {
    $spent = $row['spent'];
    $budget = $row['budget_amount'];
    $percent_used = $budget > 0 ? round(($spent / $budget) * 100) : 0;

    if ($spent > $budget) {
        $over_by = $spent - $budget;
        $messages[] = "You've gone over your " . $row['category_name'] . " budget by Rs. " . number_format($over_by, 2) . " this month.";
    } elseif ($percent_used >= 80) {
        $messages[] = "Heads up — you've used $percent_used% of your " . $row['category_name'] . " budget this month.";
    } else {
        $messages[] = "You're on track with your " . $row['category_name'] . " budget — $percent_used% used so far this month.";
    }
}


// =========================================================
// INSIGHT 5: Savings goal progress
// =========================================================
$sql = "SELECT * FROM savings_goals WHERE user_id = $user_id AND status = 'active'";
$goals = mysqli_query($conn, $sql);

while ($goal = mysqli_fetch_assoc($goals)) {
    $target = $goal['target_amount'];
    $saved = $goal['saved_amount'];
    $percent = $target > 0 ? round(($saved / $target) * 100) : 0;
    $remaining = $target - $saved;

    $sentence = "You're $percent% of the way to your \"" . $goal['goal_name'] . "\" goal " .
                "(Rs. " . number_format($saved, 2) . " of Rs. " . number_format($target, 2) . ").";

    if ($remaining > 0) {
        $sentence .= " Rs. " . number_format($remaining, 2) . " more to go.";
    } else {
        $sentence .= " You've reached your goal!";
    }

    $messages[] = $sentence;
}

if (mysqli_num_rows(mysqli_query($conn, "SELECT * FROM savings_goals WHERE user_id = $user_id AND status = 'active'")) == 0) {
    $messages[] = "You don't have any active savings goals yet. Add one to start tracking your progress.";
}
?>
<!DOCTYPE html>
<html>
<head><title>Insights - SMMS</title></head>
<body>
    <h2>Your Financial Insights</h2>
    <?php include "../includes/nav.php"; ?>

    <p>Here's a simple summary of your spending and saving patterns:</p>

    <ul>
        <?php foreach ($messages as $msg) { ?>
            <li style="margin-bottom: 10px;"><?php echo htmlspecialchars($msg); ?></li>
        <?php } ?>
    </ul>

    <?php if (empty($messages)) { ?>
        <p>Add some transactions, budgets, or savings goals to start seeing insights here.</p>
    <?php } ?>
</body>
</html>