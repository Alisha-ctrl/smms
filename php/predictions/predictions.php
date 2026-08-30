<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];

$start_date = date('Y-m-01', strtotime('-3 months'));
$end_date   = date('Y-m-01');

$sql = "SELECT c.category_name, YEAR(t.transaction_date) AS y, MONTH(t.transaction_date) AS m, SUM(t.amount) AS monthly_total
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id AND t.type = 'expense'
        AND t.transaction_date >= '$start_date' AND t.transaction_date < '$end_date'
        GROUP BY c.category_name, y, m";
$result = mysqli_query($conn, $sql);

$category_totals = [];
$category_months = [];
while ($row = mysqli_fetch_assoc($result)) {
    $name = $row['category_name'];
    if (!isset($category_totals[$name])) { $category_totals[$name] = 0; $category_months[$name] = 0; }
    $category_totals[$name] += $row['monthly_total'];
    $category_months[$name] += 1;
}

$predictions = [];
foreach ($category_totals as $name => $total) {
    $predictions[$name] = $total / $category_months[$name];
}

$this_month = date('n');
$this_year = date('Y');
$sql = "SELECT c.category_name, b.budget_amount
        FROM budgets b
        JOIN categories c ON b.category_id = c.category_id
        WHERE b.user_id = $user_id AND b.month = $this_month AND b.year = $this_year";
$result2 = mysqli_query($conn, $sql);
$budgets_by_category = [];
while ($row = mysqli_fetch_assoc($result2)) {
    $budgets_by_category[$row['category_name']] = $row['budget_amount'];
}

$current_page = "predictions";
$page_title = "Budget Predictions";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Budget Predictions - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        .predict-card { background: #ffffff; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); padding: 16px 18px; margin-bottom: 12px; max-width: none; }
        .predict-top { display: flex; align-items: center; justify-content: space-between; }
        .predict-left { display: flex; align-items: center; gap: 12px; }
        .predict-icon { width: 38px; height: 38px; border-radius: 50%; background: #F7FBFC; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .predict-value { font-weight: bold; color: #769FCD; }
        .predict-note { font-size: 13px; color: #888888; margin-top: 8px; }
        .note-warning { color: #E74C3C; }
        .note-ok { color: #219653; }
    </style>
</head>
<body class="with-sidebar">
    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>
        <p style="color:#666; margin-top:-15px;">Based on your average spending over the last 3 months, here's what you're likely to spend next month.</p>

        <?php if (count($predictions) === 0) { ?>
            <p style="color:#888;">Not enough transaction history yet. Add expenses over a few months to see predictions here.</p>
        <?php } ?>

        <?php foreach ($predictions as $category_name => $predicted_amount) { ?>
            <div class="predict-card">
                <div class="predict-top">
                    <div class="predict-left">
                        <span class="predict-icon"><?php echo category_icon($category_name); ?></span>
                        <span><?php echo htmlspecialchars($category_name); ?></span>
                    </div>
                    <span class="predict-value">~ Rs. <?php echo number_format($predicted_amount, 2); ?></span>
                </div>
                <?php if (isset($budgets_by_category[$category_name])) {
                    $budget = $budgets_by_category[$category_name];
                    if ($predicted_amount > $budget) { ?>
                        <div class="predict-note note-warning">
                            ⚠️ This is above your Rs. <?php echo number_format($budget, 2); ?> budget for this category.
                        </div>
                    <?php } else { ?>
                        <div class="predict-note note-ok">
                            ✅ This is within your Rs. <?php echo number_format($budget, 2); ?> budget for this category.
                        </div>
                    <?php }
                } else { ?>
                    <div class="predict-note">No budget set for this category this month.</div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</body>
</html>