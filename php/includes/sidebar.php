<?php
// Shared sidebar navigation - include this on every logged-in page.
// Set $current_page BEFORE including this file, e.g.: $current_page = "dashboard";
if (!isset($current_page)) { $current_page = ""; }

function nav_link($href, $label, $icon, $page_key, $current_page) {
    $active = ($page_key === $current_page) ? "sidebar-active" : "";
    echo "<a href='$href' class='sidebar-link $active'>$icon $label</a>";
}
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-badge">📈</div>
        <div>
            <div class="logo-title">SMMS</div>
            <div class="logo-subtitle">Smart Money</div>
        </div>
    </div>

    <?php nav_link("../dashboard/dashboard.php", "Dashboard", "🏠", "dashboard", $current_page); ?>
    <?php nav_link("../transactions/transactions.php", "Transactions", "🔁", "transactions", $current_page); ?>
    <?php nav_link("../budgets/budgets.php", "Budgets", "💼", "budgets", $current_page); ?>
    <?php nav_link("../savings_goals/savings_goals.php", "Savings Goals", "🎯", "savings_goals", $current_page); ?>
    <?php nav_link("../categories/categories.php", "Categories", "🏷️", "categories", $current_page); ?>
    <?php nav_link("../reports/reports.php", "Reports", "📊", "reports", $current_page); ?>
    <?php nav_link("../predictions/predictions.php", "Predictions", "🔮", "predictions", $current_page); ?>
    <?php nav_link("../insights/insights.php", "Insights", "💡", "insights", $current_page); ?>

    <div class="sidebar-divider"></div>

    <?php nav_link("../profile/profile.php", "Profile", "👤", "profile", $current_page); ?>
    <?php nav_link("../auth/logout.php", "Logout", "🚪", "logout", $current_page); ?>
</div>