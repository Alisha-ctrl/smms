<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$month = date('n');
$year = date('Y');
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cat = $_POST["category_id"];
    $m = $_POST["month"];
    $y = $_POST["year"];
    $amount = $_POST["budget_amount"];

    $sql = "INSERT INTO budgets (user_id, category_id, month, year, budget_amount)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiiid", $user_id, $cat, $m, $y, $amount);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Budget added!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

$categories = mysqli_query($conn,
    "SELECT * FROM categories
     WHERE (user_id IS NULL OR user_id=$user_id)
     AND category_type='expense'"
);

$sql = "SELECT b.*, c.category_name,
        COALESCE((SELECT SUM(t.amount) FROM transactions t
        WHERE t.category_id=b.category_id AND t.user_id=$user_id
        AND t.type='expense'
        AND MONTH(t.transaction_date)=b.month
        AND YEAR(t.transaction_date)=b.year),0) spent
        FROM budgets b
        JOIN categories c ON b.category_id=c.category_id
        WHERE b.user_id=$user_id AND b.month=$month AND b.year=$year
        ORDER BY b.budget_id DESC";

$budgets = mysqli_query($conn, $sql);

$current_page = "budgets";
$page_title = "Budgets";
?>

<!DOCTYPE html>
<html>
<head>
<title>Budgets - SMMS</title>
<link rel="stylesheet" href="../includes/style.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
.card{
    background:#fff;
    padding:18px;
    margin-bottom:14px;
    border-radius:12px;
    box-shadow:0 1px 6px rgba(0,0,0,0.05);
}
.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.icon{
    display:inline-flex;
    width:40px;
    height:40px;
    border-radius:50%;
    background:#E0E0E0;
    color:#072736;
    align-items:center;
    justify-content:center;
    margin-right:10px;
}
.amount{color:#777;font-size:13px}
.actions a{margin-left:10px;font-size:12px;text-decoration:none}
.edit{color:#072736;font-weight:bold}
.delete{color:#E74C3C}
.bar{height:10px;background:#E0E0E0;border-radius:10px;margin-top:12px}
.fill{height:100%;border-radius:10px}
.ok{background:#219653}
.warn{background:#C7A8A8}
.over{background:#E74C3C}
.status{font-size:11px;margin-top:7px;display:inline-block}
.add{
    display:none;
    background:#fff;
    padding:20px;
    max-width:500px;
    margin:20px auto;
    border-radius:12px;
}
.add input,.add select{
    width:100%;
    padding:9px;
    margin:6px 0 14px;
    box-sizing:border-box;
}
.save{
    background:#219653;
    color:white;
    border:0;
    padding:10px 18px;
    border-radius:7px;
}
.fab{
    position:fixed;
    right:28px;
    bottom:28px;
    width:58px;
    height:58px;
    border:0;
    border-radius:50%;
    background:#219653;
    color:white;
    font-size:22px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    box-shadow:0 3px 10px rgba(0,0,0,0.2);
}
</style>
</head>

<body class="with-sidebar">

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

<?php include "../includes/topbar.php"; ?>

<p style="color:#666;"><?php echo date('F Y'); ?> Budgets</p>

<?php if ($message) echo "<p>" . htmlspecialchars($message) . "</p>"; ?>

<?php if (mysqli_num_rows($budgets) == 0) { ?>
<p style="color:#888;">No budgets set for this month.</p>
<?php } ?>

<?php while ($b = mysqli_fetch_assoc($budgets)) {

$percent = $b['budget_amount'] > 0
    ? min(100, round($b['spent'] / $b['budget_amount'] * 100))
    : 0;

if ($b['spent'] > $b['budget_amount']) {
    $class = "over";
    $msg = "Over budget";
} elseif ($percent >= 80) {
    $class = "warn";
    $msg = "Approaching limit";
} else {
    $class = "ok";
    $msg = "On track";
}
?>

<div class="card">

<div class="top">

<div>
<span class="icon">
<?php echo category_icon($b['category_name']); ?>
</span>

<strong><?php echo htmlspecialchars($b['category_name']); ?></strong>

<div class="amount">
Rs. <?php echo number_format($b['spent'],2); ?>
of Rs. <?php echo number_format($b['budget_amount'],2); ?>
</div>
</div>

<div class="actions">
<a class="edit" href="edit_budget.php?id=<?php echo $b['budget_id']; ?>">Edit</a>
<a class="delete"
href="delete_budget.php?id=<?php echo $b['budget_id']; ?>"
onclick="return confirm('Delete this budget?')">Delete</a>
</div>

</div>

<div class="bar">
<div class="fill <?php echo $class; ?>"
style="width:<?php echo $percent; ?>%"></div>
</div>

<span class="status"><?php echo $msg; ?></span>

</div>

<?php } ?>


<div class="add" id="addForm">

<h3>Add Budget</h3>

<form method="POST">

<label>Category</label>
<select name="category_id" required>

<?php while ($c = mysqli_fetch_assoc($categories)) { ?>
<option value="<?php echo $c['category_id']; ?>">
<?php echo htmlspecialchars($c['category_name']); ?>
</option>
<?php } ?>

</select>

<label>Month</label>
<input type="number" name="month"
value="<?php echo $month; ?>" min="1" max="12" required>

<label>Year</label>
<input type="number" name="year"
value="<?php echo $year; ?>" required>

<label>Budget Amount</label>
<input type="number" step="0.01"
name="budget_amount" required>

<button class="save">Save Budget</button>

</form>
</div>

<button class="fab" onclick="addForm.style.display='block'"><i class="fa-solid fa-plus"></i></button>

</div>
</body>
</html>