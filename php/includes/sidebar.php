<?php
// Shared sidebar navigation - include this on every logged-in page.
// Set $current_page BEFORE including this file, e.g.: $current_page = "dashboard";
//
// REQUIRES Font Awesome loaded in the page's <head>:
//   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
if (!isset($current_page)) { $current_page = ""; }

function nav_link($href, $label, $icon_class, $page_key, $current_page) {
    $active = ($page_key === $current_page) ? "sidebar-active" : "";
    echo "<a href='$href' class='sidebar-link $active'><i class='fa-solid $icon_class'></i> <span>$label</span></a>";
}
?>
<style>
    .sidebar-link { display: flex; align-items: center; gap: 10px; }
    .sidebar-link i { width: 18px; text-align: center; flex-shrink: 0; }
    .sidebar-logo .logo-badge i { font-size: 17px; }
</style>
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-badge"><i class="fa-solid fa-arrow-trend-up" style="color:#072736;"></i></div>
        <div>
            <div class="logo-title">SMMS</div>
            <div class="logo-subtitle">Smart Money</div>
        </div>
    </div>

    <?php nav_link("../dashboard/dashboard.php", "Dashboard", "fa-house", "dashboard", $current_page); ?>
    <?php nav_link("../transactions/transactions.php", "Transactions", "fa-right-left", "transactions", $current_page); ?>
    <?php nav_link("../budgets/budgets.php", "Budgets", "fa-briefcase", "budgets", $current_page); ?>
    <?php nav_link("../savings_goals/savings_goals.php", "Savings Goals", "fa-bullseye", "savings_goals", $current_page); ?>
    <?php nav_link("../categories/categories.php", "Categories", "fa-tag", "categories", $current_page); ?>
    <?php nav_link("../reports/reports.php", "Reports", "fa-chart-column", "reports", $current_page); ?>
    <?php nav_link("../predictions/predictions.php", "Predictions", "fa-gauge-high", "predictions", $current_page); ?>
    <?php nav_link("../insights/insights.php", "Insights", "fa-lightbulb", "insights", $current_page); ?>

    <div class="sidebar-divider"></div>

    <?php nav_link("../profile/profile.php", "Profile", "fa-user", "profile", $current_page); ?>
    <?php nav_link("../auth/logout.php", "Logout", "fa-right-from-bracket", "logout", $current_page); ?>
</div>