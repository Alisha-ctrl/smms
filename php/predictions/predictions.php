<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];

$start_date = date('Y-m-01', strtotime('-3 months'));
$end_date = date('Y-m-01');

$sql = "SELECT c.category_name,
               YEAR(t.transaction_date) AS y,
               MONTH(t.transaction_date) AS m,
               SUM(t.amount) AS monthly_total
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        AND t.type = 'expense'
        AND t.transaction_date >= '$start_date'
        AND t.transaction_date < '$end_date'
        GROUP BY c.category_name, y, m";

$result = mysqli_query($conn, $sql);

$category_totals = [];
$category_months = [];

while ($row = mysqli_fetch_assoc($result)) {
    $name = $row['category_name'];

    if (!isset($category_totals[$name])) {
        $category_totals[$name] = 0;
        $category_months[$name] = 0;
    }

    $category_totals[$name] += $row['monthly_total'];
    $category_months[$name]++;
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
        WHERE b.user_id = $user_id
        AND b.month = $this_month
        AND b.year = $this_year";

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

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .page-note {
            color: #666;
            margin: -12px 0 20px;
        }

        .predict-card {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 1px 6px rgba(0,0,0,.06);
        }

        .predict-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .predict-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .predict-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #E0E0E0;
            color: #072736;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .predict-name {
            font-weight: 600;
            color: #072736;
        }

        .predict-value {
            font-weight: bold;
            color: #769FCD;
        }

        .predict-note {
            margin-top: 10px;
            padding: 8px 10px;
            border-radius: 7px;
            font-size: 13px;
        }

        .note-warning {
            background: #FBEAE8;
            color: #C0392B;
        }

        .note-ok {
            background: #E7F6EE;
            color: #1B7943;
        }

        .note-normal {
            background: #F4F5F5;
            color: #777;
        }

        .empty-box {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body class="with-sidebar">

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

    <?php include "../includes/topbar.php"; ?>

    <p class="page-note">
        Based on your average spending over the last 3 months,
        here's what you're likely to spend next month.
    </p>

    <?php if (count($predictions) == 0) { ?>

        <div class="empty-box">
            <i class="fa-solid fa-chart-line"></i>
            <p>No predictions available yet.</p>
            <small>
                Add expenses over a few months to see predictions here.
            </small>
        </div>

    <?php } ?>

    <?php foreach ($predictions as $category_name => $predicted_amount) { ?>

        <div class="predict-card">

            <div class="predict-top">

                <div class="predict-left">

                    <div class="predict-icon">
                        <?php echo category_icon($category_name); ?>
                    </div>

                    <div class="predict-name">
                        <?php echo htmlspecialchars($category_name); ?>
                    </div>

                </div>

                <div class="predict-value">
                    ~ Rs. <?php echo number_format($predicted_amount, 2); ?>
                </div>

            </div>

            <?php
            if (isset($budgets_by_category[$category_name])) {

                $budget = $budgets_by_category[$category_name];

                if ($predicted_amount > $budget) {
            ?>

                    <div class="predict-note note-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Above your Rs. <?php echo number_format($budget, 2); ?>
                        budget.
                    </div>

            <?php
                } else {
            ?>

                    <div class="predict-note note-ok">
                        <i class="fa-solid fa-circle-check"></i>
                        Within your Rs. <?php echo number_format($budget, 2); ?>
                        budget.
                    </div>

            <?php
                }

            } else {
            ?>

                <div class="predict-note note-normal">
                    <i class="fa-solid fa-circle-info"></i>
                    No budget set for this category.
                </div>

            <?php } ?>

        </div>

    <?php } ?>

</div>

</body>
</html>