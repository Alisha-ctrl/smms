<?php
// Shared icon lookup - returns a plain emoji character for a given
// category name. Emoji are native Unicode text, not an external
// library/font, so this works with no dependencies at all.
// Used on Transactions, Dashboard, Budgets, and Predictions so the
// same category always shows the same icon everywhere.

function category_icon($name) {
    $icons = [
        'Salary'        => '💼',
        'Freelance'     => '💻',
        'Other Income'  => '💵',
        'Food'          => '🍔',
        'Transport'     => '🚌',
        'Rent'          => '🏠',
        'Utilities'     => '💡',
        'Entertainment' => '🎬',
        'Education'     => '📚',
        'Miscellaneous' => '📦',
    ];
    return $icons[$name] ?? '🏷️';
}

// Icon used for savings goals (not category-based, so a single consistent icon)
function goal_icon() {
    return '🐷';
}
?>