<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];
$id = (int)$_GET["id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $cat = $_POST["category_id"];
    $month = $_POST["month"];
    $year = $_POST["year"];
    $amount = $_POST["budget_amount"];

    $sql = "UPDATE budgets
            SET category_id=?, month=?, year=?, budget_amount=?
            WHERE budget_id=? AND user_id=?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $stmt, "iiidii",
        $cat, $month, $year, $amount, $id, $user_id
    );

    mysqli_stmt_execute($stmt);

    header("Location: budgets.php");
    exit;
}

$result = mysqli_query($conn,
    "SELECT * FROM budgets
     WHERE budget_id=$id AND user_id=$user_id"
);

$b = mysqli_fetch_assoc($result);

$categories = mysqli_query($conn,
    "SELECT * FROM categories
     WHERE (user_id IS NULL OR user_id=$user_id)
     AND category_type='expense'"
);

$current_page = "budgets";
$page_title = "Edit Budget";
?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Budget - SMMS</title>

<link rel="stylesheet" href="../includes/style.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
.form-box{
    background:#fff;
    max-width:500px;
    padding:25px;
    border-radius:12px;
}
.form-box input,.form-box select{
    width:100%;
    padding:9px;
    margin:6px 0 15px;
    box-sizing:border-box;
}
.save{
    background:#219653;
    color:#fff;
    border:0;
    padding:10px 18px;
    border-radius:7px;
}
</style>

</head>

<body class="with-sidebar">

<?php include "../includes/sidebar.php"; ?>

<div class="main-content">

<?php include "../includes/topbar.php"; ?>

<div class="form-box">

<h3>
<i class="fa-solid fa-briefcase"></i>
Edit Budget
</h3>

<form method="POST" action="edit_budget.php?id=<?php echo $id; ?>">

<label>Category</label>

<select name="category_id">

<?php while ($c = mysqli_fetch_assoc($categories)) { ?>

<option value="<?php echo $c['category_id']; ?>"
<?php if ($c['category_id'] == $b['category_id']) echo "selected"; ?>>

<?php echo htmlspecialchars($c['category_name']); ?>

</option>

<?php } ?>

</select>

<label>Month</label>
<input type="number" name="month"
value="<?php echo $b['month']; ?>" min="1" max="12" required>

<label>Year</label>
<input type="number" name="year"
value="<?php echo $b['year']; ?>" required>

<label>Budget Amount</label>
<input type="number" step="0.01"
name="budget_amount"
value="<?php echo $b['budget_amount']; ?>" required>

<button class="save">Save Changes</button>

</form>

</div>

</div>
</body>
</html>