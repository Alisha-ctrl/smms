```php
<?php

include "../includes/auth_check.php";
include "../includes/db.php";
include "../includes/icons.php";

$user_id = $_SESSION["user_id"];
$message = "";

$this_month = date('n');
$this_year = date('Y');
$month_name = date('F Y');


/* =========================================================
   ADD TRANSACTION + OPTIONAL ATTACHMENT
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category_id = isset($_POST["category_id"]) ? (int)$_POST["category_id"] : 0;
    $type = isset($_POST["type"]) ? $_POST["type"] : "expense";
    $amount = isset($_POST["amount"]) ? (float)$_POST["amount"] : 0;
    $description = isset($_POST["description"]) ? trim($_POST["description"]) : "";
    $transaction_date = isset($_POST["transaction_date"]) ? $_POST["transaction_date"] : "";

    $attachment_file = NULL;
    $upload_success = true;


    /* ---------------------------------------------------------
       BASIC VALIDATION
       --------------------------------------------------------- */

    if ($category_id <= 0) {

        $message = "Please select a category.";

    } elseif (!in_array($type, ["income", "expense"])) {

        $message = "Invalid transaction type.";

    } elseif ($amount <= 0) {

        $message = "Amount must be greater than zero.";

    } elseif (empty($transaction_date)) {

        $message = "Please select a transaction date.";
    }


    /* ---------------------------------------------------------
       OPTIONAL ATTACHMENT UPLOAD
       --------------------------------------------------------- */

    if ($message == "" && isset($_FILES["attachment_file"])) {

        if ($_FILES["attachment_file"]["error"] != UPLOAD_ERR_NO_FILE) {

            if ($_FILES["attachment_file"]["error"] !== UPLOAD_ERR_OK) {

                $message = "There was a problem uploading the attachment.";
                $upload_success = false;

            } else {

                /*
                 * Maximum file size = 5 MB
                 */
                $max_size = 5 * 1024 * 1024;

                if ($_FILES["attachment_file"]["size"] > $max_size) {

                    $message = "Attachment must be smaller than 5 MB.";
                    $upload_success = false;

                } else {

                    /*
                     * Allowed MIME types
                     */
                    $allowed_types = [
                        "image/jpeg",
                        "image/png",
                        "image/webp",
                        "application/pdf"
                    ];

                    /*
                     * Check actual file MIME type
                     * instead of trusting the file extension.
                     */
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);

                    $file_type = finfo_file(
                        $finfo,
                        $_FILES["attachment_file"]["tmp_name"]
                    );

                    finfo_close($finfo);


                    if (!in_array($file_type, $allowed_types)) {

                        $message = "Invalid attachment. Please upload PDF, JPG, JPEG, PNG or WEBP.";
                        $upload_success = false;

                    } else {

                        /*
                         * Create upload directory if it does not exist.
                         */
                        $upload_dir = "../uploads/transactions/";

                        if (!is_dir($upload_dir)) {

                            if (!mkdir($upload_dir, 0755, true)) {

                                $message = "Unable to create upload folder.";
                                $upload_success = false;
                            }
                        }


                        if ($upload_success) {

                            /*
                             * Determine safe extension
                             */
                            $extension = strtolower(
                                pathinfo(
                                    $_FILES["attachment_file"]["name"],
                                    PATHINFO_EXTENSION
                                )
                            );


                            /*
                             * Generate unique filename.
                             *
                             * We do NOT use the original filename
                             * to prevent filename conflicts.
                             */
                            $new_file_name =
                                "transaction_" .
                                $user_id . "_" .
                                time() . "_" .
                                bin2hex(random_bytes(5)) .
                                "." .
                                $extension;


                            $upload_path =
                                $upload_dir .
                                $new_file_name;


                            /*
                             * Move uploaded file
                             */
                            if (move_uploaded_file(
                                $_FILES["attachment_file"]["tmp_name"],
                                $upload_path
                            )) {

                                $attachment_file = $new_file_name;

                            } else {

                                $message = "Failed to save the attachment.";
                                $upload_success = false;
                            }
                        }
                    }
                }
            }
        }
    }


    /* ---------------------------------------------------------
       INSERT TRANSACTION
       --------------------------------------------------------- */

    if ($message == "" && $upload_success) {

        $sql = "INSERT INTO transactions
                (
                    user_id,
                    category_id,
                    type,
                    amount,
                    description,
                    transaction_date,
                    attachment_file
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)";


        $stmt = mysqli_prepare($conn, $sql);


        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "iisdsss",
                $user_id,
                $category_id,
                $type,
                $amount,
                $description,
                $transaction_date,
                $attachment_file
            );


            if (mysqli_stmt_execute($stmt)) {

                $message = "Transaction added successfully!";


                /* =================================================
                   BUDGET ALERT SYSTEM
                   ================================================= */

                if ($type == 'expense') {

                    $t_month = (int)date(
                        'n',
                        strtotime($transaction_date)
                    );

                    $t_year = (int)date(
                        'Y',
                        strtotime($transaction_date)
                    );


                    /* -------------------------------------------------
                       FIND BUDGET
                       ------------------------------------------------- */

                    $budget_sql =
                        "SELECT *
                         FROM budgets
                         WHERE user_id = ?
                         AND category_id = ?
                         AND month = ?
                         AND year = ?";


                    $budget_stmt = mysqli_prepare(
                        $conn,
                        $budget_sql
                    );


                    if ($budget_stmt) {

                        mysqli_stmt_bind_param(
                            $budget_stmt,
                            "iiii",
                            $user_id,
                            $category_id,
                            $t_month,
                            $t_year
                        );


                        mysqli_stmt_execute($budget_stmt);


                        $budget = mysqli_fetch_assoc(
                            mysqli_stmt_get_result($budget_stmt)
                        );


                        /* -------------------------------------------------
                           IF BUDGET EXISTS
                           ------------------------------------------------- */

                        if ($budget) {

                            $spent_sql =
                                "SELECT COALESCE(SUM(amount), 0) AS spent
                                 FROM transactions
                                 WHERE user_id = ?
                                 AND category_id = ?
                                 AND type = 'expense'
                                 AND MONTH(transaction_date) = ?
                                 AND YEAR(transaction_date) = ?";


                            $spent_stmt = mysqli_prepare(
                                $conn,
                                $spent_sql
                            );


                            if ($spent_stmt) {

                                mysqli_stmt_bind_param(
                                    $spent_stmt,
                                    "iiii",
                                    $user_id,
                                    $category_id,
                                    $t_month,
                                    $t_year
                                );


                                mysqli_stmt_execute($spent_stmt);


                                $spent_result =
                                    mysqli_stmt_get_result($spent_stmt);


                                $spent_row =
                                    mysqli_fetch_assoc($spent_result);


                                $spent =
                                    $spent_row['spent'] ?? 0;


                                /* -------------------------------------------------
                                   CHECK WHETHER BUDGET IS EXCEEDED
                                   ------------------------------------------------- */

                                if ($spent > $budget['budget_amount']) {


                                    $existing_sql =
                                        "SELECT alert_id
                                         FROM budget_alerts
                                         WHERE budget_id = ?
                                         AND is_read = 0";


                                    $existing_stmt =
                                        mysqli_prepare(
                                            $conn,
                                            $existing_sql
                                        );


                                    if ($existing_stmt) {

                                        mysqli_stmt_bind_param(
                                            $existing_stmt,
                                            "i",
                                            $budget['budget_id']
                                        );


                                        mysqli_stmt_execute(
                                            $existing_stmt
                                        );


                                        $existing =
                                            mysqli_fetch_assoc(
                                                mysqli_stmt_get_result(
                                                    $existing_stmt
                                                )
                                            );


                                        /* -------------------------------------------------
                                           CREATE ALERT ONLY IF ONE DOES NOT EXIST
                                           ------------------------------------------------- */

                                        if (!$existing) {


                                            $cn_stmt =
                                                mysqli_prepare(
                                                    $conn,
                                                    "SELECT category_name
                                                     FROM categories
                                                     WHERE category_id = ?"
                                                );


                                            if ($cn_stmt) {

                                                mysqli_stmt_bind_param(
                                                    $cn_stmt,
                                                    "i",
                                                    $category_id
                                                );


                                                mysqli_stmt_execute(
                                                    $cn_stmt
                                                );


                                                $category_row =
                                                    mysqli_fetch_assoc(
                                                        mysqli_stmt_get_result(
                                                            $cn_stmt
                                                        )
                                                    );


                                                $category_name =
                                                    $category_row['category_name']
                                                    ?? "Category";


                                                $over_by =
                                                    $spent -
                                                    $budget['budget_amount'];


                                                $alert_message =
                                                    "You've gone over your " .
                                                    $category_name .
                                                    " budget by Rs. " .
                                                    number_format(
                                                        $over_by,
                                                        2
                                                    ) .
                                                    ".";


                                                $insert_alert_stmt =
                                                    mysqli_prepare(
                                                        $conn,
                                                        "INSERT INTO budget_alerts
                                                         (
                                                             user_id,
                                                             budget_id,
                                                             alert_message
                                                         )
                                                         VALUES (?, ?, ?)"
                                                    );


                                                if ($insert_alert_stmt) {

                                                    mysqli_stmt_bind_param(
                                                        $insert_alert_stmt,
                                                        "iis",
                                                        $user_id,
                                                        $budget['budget_id'],
                                                        $alert_message
                                                    );


                                                    mysqli_stmt_execute(
                                                        $insert_alert_stmt
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

            } else {

                /*
                 * If transaction insertion fails,
                 * delete the uploaded file so that
                 * unused files are not left in the folder.
                 */
                if ($attachment_file !== NULL) {

                    $uploaded_path =
                        "../uploads/transactions/" .
                        $attachment_file;


                    if (file_exists($uploaded_path)) {
                        unlink($uploaded_path);
                    }
                }


                $message =
                    "Error: Unable to save transaction.";
            }

            mysqli_stmt_close($stmt);

        } else {

            /*
             * If statement preparation fails,
             * remove uploaded attachment.
             */
            if ($attachment_file !== NULL) {

                $uploaded_path =
                    "../uploads/transactions/" .
                    $attachment_file;


                if (file_exists($uploaded_path)) {
                    unlink($uploaded_path);
                }
            }


            $message =
                "Error preparing transaction.";
        }
    }
}


/* =========================================================
   GET CATEGORIES
   ========================================================= */

$categories = mysqli_query(
    $conn,
    "SELECT *
     FROM categories
     WHERE user_id IS NULL
     OR user_id = $user_id"
);


/* =========================================================
   MONTHLY SUMMARY
   ========================================================= */

$sql =
    "SELECT
        SUM(
            CASE
                WHEN type = 'income'
                THEN amount
                ELSE 0
            END
        ) AS total_income,

        SUM(
            CASE
                WHEN type = 'expense'
                THEN amount
                ELSE 0
            END
        ) AS total_expense

     FROM transactions

     WHERE user_id = $user_id

     AND MONTH(transaction_date) = $this_month

     AND YEAR(transaction_date) = $this_year";


$summary =
    mysqli_fetch_assoc(
        mysqli_query($conn, $sql)
    );


$income =
    $summary['total_income'] ?? 0;

$expense =
    $summary['total_expense'] ?? 0;

$balance =
    $income - $expense;


/* =========================================================
   GET TRANSACTIONS
   ========================================================= */

$sql =
    "SELECT
        t.*,
        c.category_name

     FROM transactions t

     JOIN categories c
     ON t.category_id = c.category_id

     WHERE t.user_id = $user_id

     AND MONTH(t.transaction_date) = $this_month

     AND YEAR(t.transaction_date) = $this_year

     ORDER BY t.transaction_date DESC";


$result =
    mysqli_query($conn, $sql);


/* =========================================================
   GROUP TRANSACTIONS BY CATEGORY
   ========================================================= */

$groups = [];


while ($row = mysqli_fetch_assoc($result)) {

    $cid = $row['category_id'];


    if (!isset($groups[$cid])) {

        $groups[$cid] = [
            'name' => $row['category_name'],
            'type' => $row['type'],
            'total' => 0,
            'items' => []
        ];
    }


    $groups[$cid]['total'] += $row['amount'];

    $groups[$cid]['items'][] = $row;
}


/* =========================================================
   SORT GROUPS
   ========================================================= */

uasort(
    $groups,
    function ($a, $b) {

        if ($a['type'] !== $b['type']) {

            return $a['type'] === 'income' ? -1 : 1;
        }


        return $b['total'] <=> $a['total'];
    }
);


/* =========================================================
   PAGE INFORMATION
   ========================================================= */

$current_page = "transactions";
$page_title = "Transactions";

?>

<!DOCTYPE html>

<html>

<head>

    <title>Transactions - SMMS</title>

    <link
        rel="stylesheet"
        href="../includes/style.css"
    >


    <style>

        /* =====================================================
           BALANCE
           ===================================================== */

        .balance-pill {

            max-width: 320px;

            margin: 0 auto 20px auto;

            padding: 14px;

            border-radius: 30px;

            text-align: center;

            font-size: 18px;

            font-weight: bold;

            color: #ffffff;
        }


        .balance-positive {

            background-color: #219653;
        }


        .balance-negative {

            background-color: #E74C3C;
        }


        /* =====================================================
           CATEGORY GROUP
           ===================================================== */

        .category-group {

            background: #ffffff;

            border-radius: 10px;

            box-shadow:
                0 1px 6px rgba(0,0,0,0.05);

            overflow: hidden;

            margin-bottom: 12px;

            max-width: none;
        }


        .category-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 14px 18px;

            cursor: pointer;
        }


        .category-header .left {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .category-icon {

            width: 38px;

            height: 38px;

            border-radius: 50%;

            background: #F7FBFC;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;
        }


        .category-count {

            background: #769FCD;

            color: #ffffff;

            border-radius: 50%;

            width: 20px;

            height: 20px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            font-size: 11px;
        }


        .category-total.income {

            color: #219653;

            font-weight: bold;
        }


        .category-total.expense {

            color: #E74C3C;

            font-weight: bold;
        }


        /* =====================================================
           TRANSACTION ITEMS
           ===================================================== */

        .item-list {

            display: none;
        }


        .item-row {

            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 10px 18px 10px 66px;

            border-top: 1px solid #F5F5F5;

            font-size: 14px;

            gap: 15px;
        }


        .item-row .item-date {

            color: #999999;

            font-size: 12px;
        }


        .item-actions {

            display: inline-flex;

            align-items: center;

            flex-wrap: wrap;

            gap: 8px;

            margin-left: 10px;
        }


        .item-actions a {

            font-size: 12px;

            text-decoration: none;
        }


        .attachment-link {

            color: #219653;

            font-weight: 600;
        }


        .attachment-link:hover {

            text-decoration: underline;
        }


        /* =====================================================
           ADD BUTTON
           ===================================================== */

        .fab-wrapper {

            position: fixed;

            bottom: 25px;

            right: 40px;
        }


        .fab {

            width: 56px;

            height: 56px;

            border-radius: 50%;

            border: none;

            background: #219653;

            color: #ffffff;

            font-size: 26px;

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.2);
        }


        /* =====================================================
           ADD FORM
           ===================================================== */

        #addFormWrapper {

            max-width: 500px;

            margin: 0 auto 30px auto;

            background: #ffffff;

            border-radius: 10px;

            padding: 20px;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.08);
        }


        .type-toggle {

            display: flex;

            gap: 10px;

            margin-bottom: 20px;
        }


        .type-toggle button {

            flex: 1;

            padding: 12px;

            border-radius: 8px;

            border: 2px solid #DDDDDD;

            background: #ffffff;

            color: #666666;

            font-weight: bold;

            cursor: pointer;

            font-size: 15px;
        }


        .type-toggle button.active.income-active {

            border-color: #219653;

            background: #F2FBF6;

            color: #219653;
        }


        .type-toggle button.active.expense-active {

            border-color: #E74C3C;

            background: #FDEDEB;

            color: #E74C3C;
        }


        /* =====================================================
           FORM FIELDS
           ===================================================== */

        .form-label {

            display: block;

            font-weight: 600;

            margin-bottom: 6px;
        }


        .attachment-box {

            border: 2px dashed #DDDDDD;

            border-radius: 8px;

            padding: 14px;

            background: #FAFAFA;

            margin-top: 5px;
        }


        .attachment-box input[type="file"] {

            width: 100%;
        }


        .attachment-help {

            display: block;

            color: #777777;

            font-size: 12px;

            margin-top: 6px;

            line-height: 1.5;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 600px) {

            .fab-wrapper {

                right: 20px;

                bottom: 20px;
            }


            .item-row {

                padding-left: 20px;

                flex-direction: column;

                align-items: flex-start;
            }


            .item-row > span:last-child {

                width: 100%;
            }


            .item-actions {

                margin-left: 0;

                margin-top: 5px;
            }
        }

    </style>

</head>


<body class="with-sidebar">


    <?php include "../includes/sidebar.php"; ?>


    <div class="main-content">


        <?php include "../includes/topbar.php"; ?>


        <p
            style="
                text-align:center;
                color:#666;
                margin-top:-10px;
            "
        >
            <?php echo htmlspecialchars($month_name); ?>
        </p>


        <!-- ==================================================
             BALANCE
             ================================================== -->

        <div
            class="balance-pill
            <?php
                echo $balance >= 0
                    ? 'balance-positive'
                    : 'balance-negative';
            ?>"
        >

            Balance:
            Rs.
            <?php echo number_format($balance, 2); ?>

        </div>


        <!-- ==================================================
             MESSAGE
             ================================================== -->

        <?php if ($message) { ?>

            <p
                style="
                    text-align:center;
                    color:
                    <?php
                        echo (
                            strpos(
                                strtolower($message),
                                'error'
                            ) !== false ||
                            strpos(
                                strtolower($message),
                                'invalid'
                            ) !== false
                        )
                        ? '#E74C3C'
                        : '#219653';
                    ?>;
                    font-weight:600;
                "
            >

                <?php echo htmlspecialchars($message); ?>

            </p>

        <?php } ?>


        <!-- ==================================================
             NO TRANSACTIONS
             ================================================== -->

        <?php if (count($groups) === 0) { ?>

            <p
                style="
                    text-align:center;
                    color:#888;
                "
            >
                No transactions yet this month.
                Tap the + button to add one.
            </p>

        <?php } ?>


        <!-- ==================================================
             TRANSACTION GROUPS
             ================================================== -->

        <?php foreach ($groups as $cid => $group) {

            $groupId = "group-" . $cid;

        ?>

            <div class="category-group">


                <!-- CATEGORY HEADER -->

                <div
                    class="category-header"
                    onclick="
                        toggleGroup(
                            '<?php echo $groupId; ?>'
                        )
                    "
                >

                    <div class="left">


                        <span class="category-icon">

                            <?php
                            echo category_icon(
                                $group['name']
                            );
                            ?>

                        </span>


                        <span>

                            <?php
                            echo htmlspecialchars(
                                $group['name']
                            );
                            ?>

                        </span>


                        <span class="category-count">

                            <?php
                            echo count(
                                $group['items']
                            );
                            ?>

                        </span>

                    </div>


                    <span
                        class="
                            category-total
                            <?php echo $group['type']; ?>
                        "
                    >

                        <?php
                        echo $group['type'] == 'income'
                            ? '+'
                            : '-';
                        ?>

                        Rs.

                        <?php
                        echo number_format(
                            $group['total'],
                            2
                        );
                        ?>

                    </span>

                </div>


                <!-- TRANSACTION LIST -->

                <div
                    class="item-list"
                    id="<?php echo $groupId; ?>"
                >


                    <?php foreach (
                        $group['items']
                        as $item
                    ) { ?>


                        <div class="item-row">


                            <!-- DESCRIPTION + DATE -->

                            <span>

                                <?php

                                echo htmlspecialchars(
                                    $item['description']
                                    ?: $group['name']
                                );

                                ?>

                                <br>

                                <span class="item-date">

                                    <?php
                                    echo htmlspecialchars(
                                        $item['transaction_date']
                                    );
                                    ?>

                                </span>

                            </span>


                            <!-- AMOUNT + ACTIONS -->

                            <span>


                                <span
                                    class="
                                        <?php
                                        echo $item['type']
                                            == 'income'
                                            ? 'amount-income'
                                            : 'amount-expense';
                                        ?>
                                    "
                                >

                                    <?php
                                    echo $item['type']
                                        == 'income'
                                        ? '+'
                                        : '-';
                                    ?>

                                    Rs.

                                    <?php
                                    echo number_format(
                                        $item['amount'],
                                        2
                                    );
                                    ?>

                                </span>


                                <!-- ACTIONS -->

                                <span class="item-actions">


                                    <?php
                                    /*
                                     * Show attachment button
                                     * only when this transaction
                                     * has an attachment.
                                     */
                                    if (
                                        !empty(
                                            $item['attachment_file']
                                        )
                                    ) {
                                    ?>

                                        <a
                                            href="../uploads/transactions/<?php
                                                echo rawurlencode(
                                                    $item['attachment_file']
                                                );
                                            ?>"
                                            target="_blank"
                                            class="attachment-link"
                                            title="View attachment"
                                        >
                                            📎 Attachment
                                        </a>

                                    <?php } ?>


                                    <a
                                        href="
                                            edit_transaction.php?id=<?php
                                                echo (int)$item[
                                                    'transaction_id'
                                                ];
                                            ?>
                                        "
                                    >
                                        Edit
                                    </a>


                                    <a
                                        href="
                                            delete_transaction.php?id=<?php
                                                echo (int)$item[
                                                    'transaction_id'
                                                ];
                                            ?>
                                        "
                                        onclick="
                                            return confirm(
                                                'Delete this transaction?'
                                            );
                                        "
                                    >
                                        Delete
                                    </a>


                                </span>

                            </span>


                        </div>


                    <?php } ?>


                </div>

            </div>


        <?php } ?>


        <!-- ==================================================
             ADD TRANSACTION FORM
             ================================================== -->

        <div
            id="addFormWrapper"
            style="display:none;"
        >


            <h3
                id="addFormTitle"
                style="
                    text-align:center;
                    margin-top:0;
                "
            >
                Add Transaction
            </h3>


            <!-- INCOME / EXPENSE -->

            <div class="type-toggle">


                <button
                    type="button"
                    id="incomeToggle"
                    onclick="setType('income')"
                >
                    Income
                </button>


                <button
                    type="button"
                    id="expenseToggle"
                    onclick="setType('expense')"
                >
                    Expense
                </button>


            </div>


            <!-- ==================================================
                 FORM

                 enctype is REQUIRED for file uploading.
                 ================================================== -->

            <form
                method="POST"
                action="transactions.php"
                enctype="multipart/form-data"
            >


                <input
                    type="hidden"
                    name="type"
                    id="typeField"
                    value="expense"
                >


                <!-- CATEGORY -->

                <label class="form-label">

                    Category:

                </label>


                <select
                    name="category_id"
                    id="categorySelect"
                    required
                >


                    <?php

                    mysqli_data_seek(
                        $categories,
                        0
                    );


                    while (
                        $cat =
                        mysqli_fetch_assoc(
                            $categories
                        )
                    ) {

                    ?>

                        <option
                            value="<?php
                                echo (int)$cat[
                                    'category_id'
                                ];
                            ?>"
                            data-type="<?php
                                echo htmlspecialchars(
                                    $cat['category_type']
                                );
                            ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $cat['category_name']
                            );
                            ?>

                        </option>


                    <?php } ?>


                </select>


                <br><br>


                <!-- AMOUNT -->

                <label class="form-label">

                    Amount:

                </label>


                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="amount"
                    id="amountField"
                    required
                >


                <br><br>


                <!-- DESCRIPTION -->

                <label class="form-label">

                    Description:

                </label>


                <input
                    type="text"
                    name="description"
                    placeholder="e.g. Grocery shopping"
                >


                <br><br>


                <!-- DATE -->

                <label class="form-label">

                    Date:

                </label>


                <input
                    type="date"
                    name="transaction_date"
                    value="<?php
                        echo date('Y-m-d');
                    ?>"
                    required
                >


                <br><br>


                <!-- ==================================================
                     OPTIONAL ATTACHMENT
                     ================================================== -->

                <label class="form-label">

                    Attachment:
                    <span
                        style="
                            color:#888;
                            font-weight:normal;
                        "
                    >
                        (Optional)
                    </span>

                </label>


                <div class="attachment-box">


                    <input
                        type="file"
                        name="attachment_file"
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                    >


                    <span class="attachment-help">

                        You can upload a bill, receipt,
                        invoice, payment proof, screenshot,
                        bank document, salary slip or any
                        other file related to this transaction.

                        <br>

                        Supported formats:
                        PDF, JPG, JPEG, PNG, WEBP

                        <br>

                        Maximum size: 5 MB

                    </span>


                </div>


                <br>


                <!-- SAVE -->

                <button
                    type="submit"
                >
                    Save Transaction
                </button>


            </form>


        </div>


        <!-- ==================================================
             FLOATING ADD BUTTON
             ================================================== -->

        <div class="fab-wrapper">


            <div
                class="fab"
                onclick="openAddForm()"
                title="Add Transaction"
            >
                +
            </div>


        </div>


    </div>


    <!-- ======================================================
         JAVASCRIPT
         ====================================================== -->

    <script>


        /* ------------------------------------------------------
           OPEN / CLOSE CATEGORY
           ------------------------------------------------------ */

        function toggleGroup(groupId) {

            var el =
                document.getElementById(groupId);


            el.style.display =
                (el.style.display === "block")
                ? "none"
                : "block";
        }



        /* ------------------------------------------------------
           CATEGORY SELECT
           ------------------------------------------------------ */

        var categorySelect =
            document.getElementById(
                'categorySelect'
            );


        var allOptions =
            Array.from(
                categorySelect.options
            );



        /* ------------------------------------------------------
           INCOME / EXPENSE TYPE
           ------------------------------------------------------ */

        function setType(type) {


            document.getElementById(
                'typeField'
            ).value = type;


            document.getElementById(
                'addFormTitle'
            ).innerText =
                type === 'income'
                ? 'Add Income'
                : 'Add Expense';



            var incomeBtn =
                document.getElementById(
                    'incomeToggle'
                );


            var expenseBtn =
                document.getElementById(
                    'expenseToggle'
                );



            incomeBtn.classList.remove(
                'active',
                'income-active'
            );


            expenseBtn.classList.remove(
                'active',
                'expense-active'
            );



            if (type === 'income') {

                incomeBtn.classList.add(
                    'active',
                    'income-active'
                );

            } else {

                expenseBtn.classList.add(
                    'active',
                    'expense-active'
                );
            }



            /* --------------------------------------------------
               SHOW ONLY CATEGORIES OF SELECTED TYPE
               -------------------------------------------------- */

            allOptions.forEach(
                function(opt) {

                    opt.hidden =
                        opt.dataset.type !== type;

                }
            );



            var firstMatch =
                allOptions.find(
                    function(opt) {

                        return opt.dataset.type === type;

                    }
                );


            if (firstMatch) {

                categorySelect.value =
                    firstMatch.value;
            }

        }



        /* ------------------------------------------------------
           OPEN ADD FORM
           ------------------------------------------------------ */

        function openAddForm() {


            var wrapper =
                document.getElementById(
                    'addFormWrapper'
                );


            wrapper.style.display =
                'block';


            setType('expense');


            wrapper.scrollIntoView({
                behavior: 'smooth'
            });


            document.getElementById(
                'amountField'
            ).focus();

        }


    </script>


</body>

</html>
```
