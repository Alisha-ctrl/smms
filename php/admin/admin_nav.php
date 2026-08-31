<?php
// Shared admin sidebar navigation - include this on every admin page.
// Set $current_page BEFORE including this file, e.g.: $current_page = "dashboard";
if (!isset($current_page)) { $current_page = ""; }

function admin_nav_link($href, $label, $icon, $page_key, $current_page) {
    $active = ($page_key === $current_page) ? "sidebar-active" : "";
    echo "<a href='$href' class='sidebar-link $active'>$icon $label</a>";
}
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-badge">🛡️</div>
        <div>
            <div class="logo-title">SMMS Admin</div>
            <div class="logo-subtitle">Control Panel</div>
        </div>
    </div>

    <?php admin_nav_link("dashboard.php", "Dashboard", "🏠", "dashboard", $current_page); ?>
    <?php admin_nav_link("manage_users.php", "Manage Users", "👥", "manage_users", $current_page); ?>

    <div class="sidebar-divider"></div>

    <?php admin_nav_link("admin_logout.php", "Logout", "🚪", "logout", $current_page); ?>
</div>