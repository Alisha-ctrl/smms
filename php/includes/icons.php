<?php
/**
 * Shared icon + color lookup for categories and savings goals.
 *
 * Uses Font Awesome (Solid style) instead of inline SVG. This means every
 * page calling these functions MUST load Font Awesome in its <head>:
 *
 *   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
 *
 * Without that link tag present, these render as invisible/blank — same
 * failure mode as the original Bootstrap Icons bug. If an icon ever shows
 * blank, check that the CDN link is actually in that page's <head> first.
 */

function category_icon($name) {
    $icons = [
        'salary'        => 'fa-sack-dollar',
        'freelance'     => 'fa-laptop',
        'other income'  => 'fa-coins',
        'food'          => 'fa-utensils',
        'transport'     => 'fa-bus',
        'rent'          => 'fa-house-chimney',
        'utilities'     => 'fa-lightbulb',
        'entertainment' => 'fa-film',
        'education'     => 'fa-book',
        'miscellaneous' => 'fa-box',
    ];
    $key = strtolower(trim($name));
    // Fallback: same tag icon used for "Categories" in the sidebar.
    $icon = $icons[$key] ?? 'fa-tag';
    return '<i class="fa-solid ' . $icon . '"></i>';
}

function category_color($name) {
    // Per-category color, keyed the same as category_icon() above, so a
    // given category is always the same color everywhere it's rendered
    // (dashboard donut/budget widget, predictions.php, insights.php),
    // instead of a color determined by array position within one page's
    // query results.
    $colors = [
        'salary'        => ['bg' => 'rgba(33, 150, 83, 0.12)',   'text' => '#219653'],
        'freelance'     => ['bg' => 'rgba(52, 73, 94, 0.12)',    'text' => '#34495E'],
        'other income'  => ['bg' => 'rgba(201, 123, 74, 0.12)',  'text' => '#C97B4A'],
        'food'          => ['bg' => 'rgba(231, 76, 60, 0.12)',   'text' => '#E74C3C'],
        'transport'     => ['bg' => 'rgba(90, 128, 172, 0.12)',  'text' => '#5A80AC'],
        'rent'          => ['bg' => 'rgba(142, 68, 173, 0.12)',  'text' => '#8E44AD'],
        'utilities'     => ['bg' => 'rgba(233, 185, 73, 0.12)',  'text' => '#E9B949'],
        'entertainment' => ['bg' => 'rgba(22, 160, 133, 0.12)',  'text' => '#16A085'],
        'education'     => ['bg' => 'rgba(118, 159, 205, 0.12)', 'text' => '#769FCD'],
        'miscellaneous' => ['bg' => 'rgba(127, 140, 141, 0.12)','text' => '#7F8C8D'],
    ];
    $key = strtolower(trim($name));
    // Fallback: same neutral gray/navy pair as before, for any category
    // not in the map (custom user-added categories).
    return $colors[$key] ?? ['bg' => '#E0E0E0', 'text' => '#072736'];
}

function goal_icon() {
    // Same bullseye icon as "Savings Goals" in the sidebar.
    return '<i class="fa-solid fa-bullseye"></i>';
}

function goal_color() {
    return ['bg' => '#E0E0E0', 'text' => '#072736'];
}