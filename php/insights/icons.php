<?php
// Shared icon lookup - returns a Bootstrap Icons class name for a given
// category name. Used on Transactions, Dashboard, Budgets, and Savings
// Goals so the same category always shows the same icon everywhere.
// Requires the Bootstrap Icons stylesheet to be linked on the page:
// https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css

function category_icon($name) {
    $icons = [
        'Salary'        => 'bi-briefcase-fill',
        'Freelance'     => 'bi-laptop',
        'Other Income'  => 'bi-cash-coin',
        'Food'          => 'bi-egg-fried',
        'Transport'     => 'bi-bus-front-fill',
        'Rent'          => 'bi-house-fill',
        'Utilities'     => 'bi-lightbulb-fill',
        'Entertainment' => 'bi-film',
        'Education'     => 'bi-book-fill',
        'Miscellaneous' => 'bi-box-seam-fill',
    ];
    return $icons[$name] ?? 'bi-tag-fill';
}

// Icon used for savings goals (not category-based, so a single consistent icon)
function goal_icon() {
    return 'bi-piggy-bank-fill';
}
?>