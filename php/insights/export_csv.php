<?php
include "../includes/auth_check.php";
include "../includes/db.php";

$user_id = $_SESSION["user_id"];

$sql = "SELECT t.transaction_date, t.type, c.category_name, t.amount, t.description
        FROM transactions t
        JOIN categories c ON t.category_id = c.category_id
        WHERE t.user_id = $user_id
        ORDER BY t.transaction_date DESC";
$result = mysqli_query($conn, $sql);

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=smms_transactions_" . date('Y-m-d') . ".csv");

$output = fopen("php://output", "w");
fputcsv($output, ["Date", "Type", "Category", "Amount", "Description"]);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['transaction_date'],
        ucfirst($row['type']),
        $row['category_name'],
        $row['amount'],
        $row['description']
    ]);
}

fclose($output);
exit;
?>