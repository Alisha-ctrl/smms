<?php
include "../includes/auth_check.php";
include "../includes/db.php";
// icons.php dropped — icons are plain emoji via get_category_emoji() below,
// no external icon font dependency.

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

function get_category_emoji(string $name, string $type): string
{
    $map = [
        'salary'         => '💼',
        'freelance'      => '💻',
        'other income'   => '💰',
        'investment'     => '📈',
        'gift'           => '🎁',
        'transport'      => '🚐',
        'education'      => '📚',
        'entertainment'  => '🎬',
        'food'           => '🍔',
        'miscellaneous'  => '📦',
        'rent'           => '🏠',
        'health'         => '🏥',
        'shopping'       => '🛍️',
        'utilities'      => '💡',
        'bills'          => '🧾',
        'travel'         => '✈️',
    ];
    $key = strtolower(trim($name));
    return $map[$key] ?? ($type === 'income' ? '💰' : '📦');
}

// Every category renders the same way — no "Default" badge. Custom
// (user-owned) categories get a Delete link; system ones don't.
function render_category_cards(array $cats, string $type): void
{
    foreach ($cats as $cat) {
        $emoji = get_category_emoji($cat['category_name'], $type);
        $isCustom = $cat['user_id'] !== null;
        echo '<div class="col-box cat-card">';
        echo '  <div class="cat-left">';
        echo '    <span class="cat-icon">' . $emoji . '</span>';
        echo '    <span>' . htmlspecialchars($cat['category_name']) . '</span>';
        echo '  </div>';
        if ($isCustom) {
            echo '  <div class="cat-actions">';
            echo '    <a href="categories.php?delete_id=' . (int) $cat['category_id'] . '"';
            echo '       onclick="return confirm(\'Delete this category? Existing transactions using it will keep the reference, but you won\\\'t be able to pick it for new ones.\');">';
            echo '      Delete</a>';
            echo '  </div>';
        }
        echo '</div>';
    }
}

$current_page = "categories"; // read by sidebar.php to highlight the active link
$page_title = "Categories";   // read by topbar.php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Categories - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        /* Page-specific structure only — no new colors, everything below
           reuses the palette already defined in style.css. */
        .cat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .cat-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .cat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #E0E0E0; /* Alabaster Grey, from the shared palette */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }
        .cat-actions a {
            color: #E74C3C; /* same red already used for .amount-expense */
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
        }
        .cat-actions a:hover {
            text-decoration: underline;
        }

        /* .fab in style.css only defines color/hover transition — structural
           positioning (size, shape, placement) is page-specific by design. */
        .fab {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            border: none;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        #addFormWrapper {
            display: none;
        }
    </style>
</head>
<body class="with-sidebar">

    <?php include "../includes/sidebar.php"; ?>

    <div class="main-content">
        <?php include "../includes/topbar.php"; ?>

        <?php if ($message): ?>
            <div class="col-box" style="text-align:center;"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <h3>Income Categories</h3>
        <?php render_category_cards($income_cats, 'income'); ?>

        <h3>Expense Categories</h3>
        <?php render_category_cards($expense_cats, 'expense'); ?>

        <div id="addFormWrapper">
            <h3>Add Category</h3>
            <form method="POST" action="categories.php">
                <label for="category_name">Name</label><br>
                <input type="text" id="category_name" name="category_name" required><br>

                <label for="category_type">Type</label><br>
                <select id="category_type" name="category_type">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select><br>

                <button type="submit">Save</button>
            </form>
        </div>

        <button class="fab" onclick="
            var w = document.getElementById('addFormWrapper');
            w.style.display = (w.style.display === 'none' || w.style.display === '') ? 'block' : 'none';
            w.scrollIntoView({behavior:'smooth'});
        ">+</button>
    </div>

</body>
</html>