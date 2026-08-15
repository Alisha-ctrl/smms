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
    $month = $_POST["month"];
    $year = $_POST["year"];
    $budget_amount = $_POST["budget_amount"];

    $sql = "INSERT INTO budgets (user_id, category_id, month, year, budget_amount) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiiid", $user_id, $category_id, $month, $year, $budget_amount);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Budget added!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE (user_id IS NULL OR user_id = $user_id) AND category_type = 'expense'");

// Budgets for this month, with actual spending calculated alongside
$sql = "SELECT b.*, c.category_name,
        COALESCE((SELECT SUM(t.amount) FROM transactions t
                  WHERE t.category_id = b.category_id AND t.user_id = b.user_id
                  AND t.type = 'expense' AND MONTH(t.transaction_date) = b.month AND YEAR(t.transaction_date) = b.year), 0) AS spent
        FROM budgets b
        JOIN categories c ON b.category_id = c.category_id
        WHERE b.user_id = $user_id AND b.month = $this_month AND b.year = $this_year
        ORDER BY b.budget_id DESC";
$budgets = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Budgets - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

    <style>
        .month-label { text-align: center; color: #666666; margin-bottom: 15px; }

        .budget-card {
            max-width: 900px; margin: 0 auto 12px auto; background: #ffffff;
            border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); padding: 14px 18px;
        }
        .budget-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .budget-left { display: flex; align-items: center; gap: 12px; }
        .budget-icon {
            width: 36px; height: 36px; border-radius: 50%; background: #F7FBFC; color: #769FCD;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .budget-amounts { font-size: 13px; color: #666666; }
        .budget-actions a { font-size: 12px; margin-left: 8px; }

        .progress-track { background: #EFEFEF; border-radius: 20px; height: 10px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 20px; }
        .fill-ok { background-color: #219653; }
        .fill-warning { background-color: #E9B949; }
        .fill-over { background-color: #E74C3C; }

        .fab-wrapper { position: fixed; bottom: 25px; left: 0; right: 0; display: flex; justify-content: center; }
        .fab {
            width: 60px; height: 60px; border-radius: 50%; border: 3px solid #769FCD; background: #ffffff;
            color: #769FCD; font-size: 28px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        #addFormWrapper { max-width: 500px; margin: 0 auto 100px auto; }
    </style>
</head>
<body>
    <?php include "../includes/nav.php"; ?>

    <p class="month-label"><?php echo $month_name; ?> Budgets</p>

    <?php if ($message) echo "<p style='text-align:center;'>$message</p>"; ?>

    <?php if (mysqli_num_rows($budgets) === 0) { ?>
        <p style="text-align:center; color:#888;">No budgets set for this month yet. Use the button below to add one.</p>
    <?php } ?>

    <?php while ($row = mysqli_fetch_assoc($budgets)) {
        $spent = $row['spent'];
        $budget = $row['budget_amount'];
        $percent = $budget > 0 ? min(100, round(($spent / $budget) * 100)) : 0;
        $fillClass = $spent > $budget ? 'fill-over' : ($percent >= 80 ? 'fill-warning' : 'fill-ok');
    ?>
        <div class="budget-card">
            <div class="budget-top">
                <div class="budget-left">
                    <span class="budget-icon"><i class="bi <?php echo category_icon($row['category_name']); ?>"></i></span>
                    <div>
                        <div><?php echo htmlspecialchars($row['category_name']); ?></div>
                        <div class="budget-amounts">Rs. <?php echo number_format($spent, 2); ?> of Rs. <?php echo number_format($budget, 2); ?></div>
                    </div>
                </div>
                <div class="budget-actions">
                    <a href="edit_budget.php?id=<?php echo $row['budget_id']; ?>">Edit</a>
                    <a href="delete_budget.php?id=<?php echo $row['budget_id']; ?>" onclick="return confirm('Delete this budget?');">Delete</a>
                </div>
            </div>
            <div class="progress-track">
                <div class="progress-fill <?php echo $fillClass; ?>" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>
    <?php } ?>

    <div id="addFormWrapper" style="display:none;">
        <h3 style="text-align:center;">Add Budget</h3>
        <form method="POST" action="budgets.php">
            Category:
            <select name="category_id">
                <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                <?php } ?>
            </select><br><br>
            Month (1-12): <input type="number" name="month" min="1" max="12" value="<?php echo $this_month; ?>" required><br><br>
            Year: <input type="number" name="year" value="<?php echo $this_year; ?>" required><br><br>
            Budget Amount: <input type="number" step="0.01" name="budget_amount" required><br><br>
            <button type="submit">Save</button>
        </form>
    </div>

    <div class="fab-wrapper">
        <div class="fab" onclick="document.getElementById('addFormWrapper').style.display='block'; document.getElementById('addFormWrapper').scrollIntoView({behavior:'smooth'});">+</div>
    </div>
</body>
</html>