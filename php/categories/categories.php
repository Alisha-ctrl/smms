<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$message = "";

// ---- ADD a new category (Create) ----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST["category_name"]);
    $category_type = $_POST["category_type"];

    if ($category_name === "") {
        $message = "Category name can't be empty.";
    } else {
        $sql = "INSERT INTO categories (user_id, category_name, category_type) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $category_name, $category_type);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Category added!";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    }
}

// ---- Delete a category (only the user's own custom ones - never system defaults) ----
if (isset($_GET["delete_id"])) {
    $delete_id = $_GET["delete_id"];
    $stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE category_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
    mysqli_stmt_execute($stmt);
    $message = "Category deleted.";
}

// ---- Read: system defaults + this user's own custom categories, split by type ----
$sql = "SELECT * FROM categories WHERE user_id IS NULL OR user_id = $user_id
        ORDER BY category_type ASC, category_name ASC";
$categories = mysqli_query($conn, $sql);

$income_cats = [];
$expense_cats = [];
while ($row = mysqli_fetch_assoc($categories)) {
    if ($row['category_type'] == 'income') {
        $income_cats[] = $row;
    } else {
        $expense_cats[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Categories - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

    <style>
        .page-wrap { max-width: 900px; margin: 30px auto; padding: 0 15px; }
        .section-label { text-align: center; color: #666666; margin: 25px 0 10px 0; font-weight: bold; }

        .cat-card {
            background: #ffffff; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            padding: 12px 16px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;
        }
        .cat-left { display: flex; align-items: center; gap: 12px; }
        .cat-icon {
            width: 36px; height: 36px; border-radius: 50%; background: #F7FBFC; color: #769FCD;
            display: flex; align-items: center; justify-content: center; font-size: 16px;
        }
        .cat-badge { font-size: 11px; color: #999999; margin-left: 8px; }
        .cat-actions a { font-size: 12px; margin-left: 8px; color: #E74C3C; text-decoration: none; font-weight: bold; }

        .fab-wrapper { position: fixed; bottom: 25px; left: 0; right: 0; display: flex; justify-content: center; }
        .fab {
            width: 60px; height: 60px; border-radius: 50%; border: 3px solid #769FCD; background: #ffffff;
            color: #769FCD; font-size: 28px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        #addFormWrapper {
            max-width: 500px; margin: 0 auto 100px auto; background: #ffffff; border-radius: 10px;
            padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: none;
        }
    </style>
</head>
<body>
    <?php include "../includes/nav.php"; ?>

    <div class="page-wrap">
        <h2>Categories</h2>
        <p class="text-muted" style="text-align:center;">System default categories are shared by everyone. Categories you add here are private to your account.</p>

        <?php if ($message) echo "<p style='text-align:center;'>$message</p>"; ?>

        <p class="section-label">Income Categories</p>
        <?php foreach ($income_cats as $cat) { ?>
            <div class="cat-card">
                <div class="cat-left">
                    <span class="cat-icon"><i class="bi <?php echo category_icon($cat['category_name']); ?>"></i></span>
                    <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                    <?php if ($cat['user_id'] === null) { ?>
                        <span class="cat-badge">Default</span>
                    <?php } ?>
                </div>
                <?php if ($cat['user_id'] !== null) { ?>
                    <div class="cat-actions">
                        <a href="categories.php?delete_id=<?php echo $cat['category_id']; ?>"
                           onclick="return confirm('Delete this category? Existing transactions using it will keep the reference, but you won\'t be able to pick it for new ones.');">
                            Delete
                        </a>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <p class="section-label">Expense Categories</p>
        <?php foreach ($expense_cats as $cat) { ?>
            <div class="cat-card">
                <div class="cat-left">
                    <span class="cat-icon"><i class="bi <?php echo category_icon($cat['category_name']); ?>"></i></span>
                    <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                    <?php if ($cat['user_id'] === null) { ?>
                        <span class="cat-badge">Default</span>
                    <?php } ?>
                </div>
                <?php if ($cat['user_id'] !== null) { ?>
                    <div class="cat-actions">
                        <a href="categories.php?delete_id=<?php echo $cat['category_id']; ?>"
                           onclick="return confirm('Delete this category? Existing transactions using it will keep the reference, but you won\'t be able to pick it for new ones.');">
                            Delete
                        </a>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>

        <div id="addFormWrapper">
            <h3 style="text-align:center; margin-top:0;">Add Category</h3>
            <form method="POST" action="categories.php">
                Name: <input type="text" name="category_name" required><br><br>
                Type:
                <select name="category_type">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select><br><br>
                <button type="submit">Save</button>
            </form>
        </div>

        <div class="fab-wrapper">
            <div class="fab" onclick="document.getElementById('addFormWrapper').style.display='block'; document.getElementById('addFormWrapper').scrollIntoView({behavior:'smooth'});">+</div>
        </div>
    </div>
</body>
</html>