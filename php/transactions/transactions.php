<?php
include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$message = "";
$month = date("n");
$year = date("Y");
$month_name = date("F Y");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_id = (int)($_POST["category_id"] ?? 0);
    $type = $_POST["type"] ?? "expense";
    $amount = (float)($_POST["amount"] ?? 0);
    $description = trim($_POST["description"] ?? "");
    $date = $_POST["transaction_date"] ?? "";
    $attachment = null;

    if ($category_id <= 0 || !in_array($type, ["income", "expense"])) $message = "Please select a valid category.";
    elseif ($amount <= 0) $message = "Amount must be greater than zero.";
    elseif (!$date) $message = "Please select a date.";

    if ($message == "") {
        $check = mysqli_prepare($conn, "SELECT category_type FROM categories WHERE category_id = ? AND (user_id IS NULL OR user_id = ?)");
        mysqli_stmt_bind_param($check, "ii", $category_id, $user_id);
        mysqli_stmt_execute($check);
        $cat = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
        if (!$cat || $cat["category_type"] != $type) $message = "Invalid category for selected transaction type.";
    }

    if ($message == "" && isset($_FILES["attachment_file"]) && $_FILES["attachment_file"]["error"] != UPLOAD_ERR_NO_FILE) {
        $file = $_FILES["attachment_file"];
        if ($file["error"] != UPLOAD_ERR_OK) {
            $message = "Unable to upload attachment.";
        } elseif ($file["size"] > 5 * 1024 * 1024) {
            $message = "Attachment must be smaller than 5 MB.";
        } else {
            $allowed = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp", "application/pdf" => "pdf"];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file["tmp_name"]);
            finfo_close($finfo);
            if (!isset($allowed[$mime])) {
                $message = "Only PDF, JPG, PNG and WEBP files are allowed.";
            } else {
                $folder = "../uploads/transactions/";
                if (!is_dir($folder)) mkdir($folder, 0755, true);
                $name = "transaction_" . $user_id . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $allowed[$mime];
                if (move_uploaded_file($file["tmp_name"], $folder . $name)) $attachment = $name;
                else $message = "Failed to save attachment.";
            }
        }
    }

    if ($message == "") {
        $sql = "INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date, attachment_file) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iisdsss", $user_id, $category_id, $type, $amount, $description, $date, $attachment);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Transaction added successfully!";

            if ($type == "expense") {
                $t_month = date("n", strtotime($date));
                $t_year = date("Y", strtotime($date));

                $stmt2 = mysqli_prepare($conn, "SELECT * FROM budgets WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?");
                mysqli_stmt_bind_param($stmt2, "iiii", $user_id, $category_id, $t_month, $t_year);
                mysqli_stmt_execute($stmt2);
                $budget = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

                if ($budget) {
                    $stmt3 = mysqli_prepare($conn, "SELECT SUM(amount) AS spent FROM transactions WHERE user_id = ? AND category_id = ? AND type = 'expense' AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?");
                    mysqli_stmt_bind_param($stmt3, "iiii", $user_id, $category_id, $t_month, $t_year);
                    mysqli_stmt_execute($stmt3);
                    $spent = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt3))["spent"] ?? 0;

                    if ($spent > $budget["budget_amount"]) {
                        $check = mysqli_prepare($conn, "SELECT alert_id FROM budget_alerts WHERE budget_id = ? AND is_read = 0");
                        mysqli_stmt_bind_param($check, "i", $budget["budget_id"]);
                        mysqli_stmt_execute($check);
                        $alert = mysqli_fetch_assoc(mysqli_stmt_get_result($check));

                        if (!$alert) {
                            $cat_stmt = mysqli_prepare($conn, "SELECT category_name FROM categories WHERE category_id = ?");
                            mysqli_stmt_bind_param($cat_stmt, "i", $category_id);
                            mysqli_stmt_execute($cat_stmt);
                            $category_name = mysqli_fetch_assoc(mysqli_stmt_get_result($cat_stmt))["category_name"] ?? "Category";
                            $over = $spent - $budget["budget_amount"];
                            $alert_message = "You've gone over your " . $category_name . " budget by Rs. " . number_format($over, 2) . ".";

                            $alert_stmt = mysqli_prepare($conn, "INSERT INTO budget_alerts (user_id, budget_id, alert_message) VALUES (?, ?, ?)");
                            mysqli_stmt_bind_param($alert_stmt, "iis", $user_id, $budget["budget_id"], $alert_message);
                            mysqli_stmt_execute($alert_stmt);
                        }
                    }
                }
            }
        } else {
            if ($attachment) unlink("../uploads/transactions/" . $attachment);
            $message = "Unable to save transaction.";
        }
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories WHERE user_id IS NULL OR user_id = $user_id ORDER BY category_type, category_name");

$sql = "SELECT SUM(CASE WHEN type='income' THEN amount ELSE 0 END) AS income, SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) AS expense FROM transactions WHERE user_id = $user_id AND MONTH(transaction_date) = $month AND YEAR(transaction_date) = $year";
$summary = mysqli_fetch_assoc(mysqli_query($conn, $sql));
$income = $summary["income"] ?? 0;
$expense = $summary["expense"] ?? 0;
$balance = $income - $expense;

$sql = "SELECT t.*, c.category_name FROM transactions t JOIN categories c ON t.category_id = c.category_id WHERE t.user_id = $user_id AND MONTH(t.transaction_date) = $month AND YEAR(t.transaction_date) = $year ORDER BY t.transaction_date DESC, t.transaction_id DESC";
$result = mysqli_query($conn, $sql);

$groups = [];
while ($row = mysqli_fetch_assoc($result)) {
    $id = $row["category_id"];
    if (!isset($groups[$id])) $groups[$id] = ["name" => $row["category_name"], "type" => $row["type"], "total" => 0, "items" => []];
    $groups[$id]["total"] += $row["amount"];
    $groups[$id]["items"][] = $row;
}

uasort($groups, function ($a, $b) {
    if ($a["type"] != $b["type"]) return $a["type"] == "income" ? -1 : 1;
    return $b["total"] <=> $a["total"];
});

$current_page = "transactions";
$page_title = "Transactions";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Transactions - SMMS</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .balance { max-width: 320px; margin: 10px auto 20px; padding: 12px; border-radius: 25px; text-align: center; color: white; font-weight: bold; }
        .positive { background: #219653; }
        .negative { background: #e74c3c; }
        .group { background: white; margin-bottom: 12px; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); overflow: hidden; }
        .group-head { padding: 14px; display: flex; justify-content: space-between; cursor: pointer; }
        .group-head span { margin-right: 8px; }
        .items { display: none; }
        .item { padding: 10px 18px; border-top: 1px solid #E0E0E0; display: flex; justify-content: space-between; }
        .date { color: #888; font-size: 12px; }
        .income { color: #219653; font-weight: bold; }
        .expense { color: #e74c3c; font-weight: bold; }
        .actions a { margin-left: 8px; font-size: 12px; text-decoration: none; }
        #form { max-width: 500px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,0.05); }
        .types { display: flex; gap: 10px; }
        .types button { width: 50%; padding: 10px; cursor: pointer; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 9px; box-sizing: border-box; }
        .field { margin-bottom: 15px; }
        .file-box { border: 1px dashed #E0E0E0; padding: 10px; }
        .fab { position: fixed; bottom: 28px; right: 28px; width: 58px; height: 58px; border-radius: 50%; border: none; font-size: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 3px 10px rgba(0,0,0,0.2); }
        @media(max-width:600px) { .item { display: block; } .actions { margin-top: 8px; } }
    </style>
</head>
<body class="with-sidebar">
<?php include "../includes/sidebar.php"; ?>
<div class="main-content">
<?php include "../includes/topbar.php"; ?>

<p style="text-align:center;color:#666;"><?php echo $month_name; ?></p>
<div class="balance <?php echo $balance >= 0 ? 'positive' : 'negative'; ?>">Balance: Rs. <?php echo number_format($balance, 2); ?></div>

<?php if ($message) { ?>
    <p style="text-align:center;font-weight:bold;color:<?php
        echo (strpos(strtolower($message), 'error') !== false || strpos(strtolower($message), 'invalid') !== false || strpos(strtolower($message), 'unable') !== false || strpos(strtolower($message), 'please') !== false || strpos(strtolower($message), 'must be') !== false) ? '#E74C3C' : '#219653';
    ?>;"><?php echo htmlspecialchars($message); ?></p>
<?php } ?>

<?php if (!$groups) { ?>
    <p style="text-align:center;color:#888;">No transactions this month.</p>
<?php } ?>

<?php foreach ($groups as $id => $group) { ?>
    <div class="group">
        <div class="group-head" onclick="toggleGroup('group<?php echo $id; ?>')">
            <div><span><?php echo category_icon($group["name"]); ?></span> <?php echo htmlspecialchars($group["name"]); ?> <small>(<?php echo count($group["items"]); ?>)</small></div>
            <span class="<?php echo $group["type"]; ?>"><?php echo $group["type"] == "income" ? "+" : "-"; ?> Rs. <?php echo number_format($group["total"], 2); ?></span>
        </div>
        <div class="items" id="group<?php echo $id; ?>">
            <?php foreach ($group["items"] as $item) { ?>
                <div class="item">
                    <div>
                        <?php echo htmlspecialchars($item["description"] ?: $group["name"]); ?><br>
                        <span class="date"><?php echo htmlspecialchars($item["transaction_date"]); ?></span>
                    </div>
                    <div>
                        <span class="<?php echo $item["type"]; ?>"><?php echo $item["type"] == "income" ? "+" : "-"; ?> Rs. <?php echo number_format($item["amount"], 2); ?></span>
                        <span class="actions">
                            <?php if (!empty($item["attachment_file"])) { ?>
                                <a href="../uploads/transactions/<?php echo rawurlencode($item["attachment_file"]); ?>" target="_blank" title="View attachment"><i class="fa-solid fa-paperclip"></i></a>
                            <?php } ?>
                            <a href="edit_transaction.php?id=<?php echo (int)$item["transaction_id"]; ?>">Edit</a>
                            <a href="delete_transaction.php?id=<?php echo (int)$item["transaction_id"]; ?>" onclick="return confirm('Delete this transaction?');">Delete</a>
                        </span>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php } ?>

<div id="form" style="display:none;">
    <h3 style="text-align:center;">Add Transaction</h3>
    <div class="types">
        <button type="button" onclick="setType('income')">Income</button>
        <button type="button" onclick="setType('expense')">Expense</button>
    </div>
    <br>
    <form method="POST" action="transactions.php" enctype="multipart/form-data">
        <input type="hidden" name="type" id="type" value="expense">
        <div class="field">
            <label>Category</label>
            <select name="category_id" id="category" required>
                <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)) { ?>
                    <option value="<?php echo (int)$cat["category_id"]; ?>" data-type="<?php echo htmlspecialchars($cat["category_type"]); ?>"><?php echo htmlspecialchars($cat["category_name"]); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="field"><label>Amount</label><input type="number" name="amount" min="0.01" step="0.01" required></div>
        <div class="field"><label>Description</label><input type="text" name="description" placeholder="e.g. Grocery shopping"></div>
        <div class="field"><label>Date</label><input type="date" name="transaction_date" value="<?php echo date("Y-m-d"); ?>" required></div>
        <div class="field">
            <label>Attachment (Optional)</label>
            <div class="file-box">
                <input type="file" name="attachment_file" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <small>PDF, JPG, PNG or WEBP. Maximum 5 MB.</small>
            </div>
        </div>
        <button type="submit">Save Transaction</button>
    </form>
</div>

<button class="fab" onclick="openForm()"><i class="fa-solid fa-plus"></i></button>
</div>

<script>
function toggleGroup(id) {
    var box = document.getElementById(id);
    box.style.display = box.style.display == "block" ? "none" : "block";
}
var category = document.getElementById("category");
var options = Array.from(category.options);
function setType(type) {
    document.getElementById("type").value = type;
    options.forEach(function(option) { option.hidden = option.dataset.type != type; });
    var first = options.find(function(option) { return option.dataset.type == type; });
    if (first) category.value = first.value;
}
function openForm() {
    document.getElementById("form").style.display = "block";
    setType("expense");
    document.getElementById("form").scrollIntoView({ behavior: "smooth" });
}
setType("expense");
if (new URLSearchParams(window.location.search).get("add") === "1") openForm();
</script>
</body>
</html>