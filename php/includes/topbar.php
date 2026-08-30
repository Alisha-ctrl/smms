<?php
// Shared top bar - now shows only the page title.
// Expects $page_title to be set by the page that includes this file.
?>
<div class="topbar">
    <div class="topbar-greeting">
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
    </div>
</div>