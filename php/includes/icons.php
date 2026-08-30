<?php
// Shared icon + color lookup - one place mapping each category name to
// an emoji icon AND a color pair from the site's palette, so every page
// shows the same category the same way.

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

// Returns ['bg' => background, 'text' => matching accent color] for a
// category, using the 5 "Pastel Dreamland" tones. Two categories share
// each tone (10 categories, 5 colors), paired with a darker version of
// the same hue for readable text.
function category_color($name) {
    $colors = [
        'Salary'        => ['bg' => '#CDB4DB', 'text' => '#4A3B66'], // lavender
        'Rent'          => ['bg' => '#CDB4DB', 'text' => '#4A3B66'], // lavender
        'Freelance'     => ['bg' => '#FFC8DD', 'text' => '#8A3B5C'], // light pink
        'Utilities'     => ['bg' => '#FFC8DD', 'text' => '#8A3B5C'], // light pink
        'Other Income'  => ['bg' => '#FFAFCC', 'text' => '#7A2E52'], // pink
        'Entertainment' => ['bg' => '#FFAFCC', 'text' => '#7A2E52'], // pink
        'Food'          => ['bg' => '#BDE0FE', 'text' => '#2E5C8A'], // light blue
        'Education'     => ['bg' => '#BDE0FE', 'text' => '#2E5C8A'], // light blue
        'Transport'     => ['bg' => '#A2D2FF', 'text' => '#1F4E79'], // blue
        'Miscellaneous' => ['bg' => '#A2D2FF', 'text' => '#1F4E79'], // blue
    ];
    return $colors[$name] ?? ['bg' => '#F0F0F5', 'text' => '#555555'];
}

// Icon + color for savings goals (not category-based, so a single consistent look)
function goal_icon() {
    return '🐷';
}
function goal_color() {
    return ['bg' => '#FFC8DD', 'text' => '#8A3B5C'];
}
?>