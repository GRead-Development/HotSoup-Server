<?php
/**
 * Avatar Item Seeder - Part 2
 * Backgrounds, Shirt Patterns, and Pants Patterns
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BACKGROUNDS - ~100 items
 */
function hs_generate_background_items() {
    $items = [];
    $display_order = 0;

    // Solid Color Backgrounds - 20 items
    $solid_colors = [
        ['name' => 'White Background', 'color' => '#FFFFFF', 'unlock' => 0],
        ['name' => 'Black Background', 'color' => '#000000', 'unlock' => 5],
        ['name' => 'Gray Background', 'color' => '#95A5A6', 'unlock' => 5],
        ['name' => 'Red Background', 'color' => '#E74C3C', 'unlock' => 10],
        ['name' => 'Blue Background', 'color' => '#3498DB', 'unlock' => 10],
        ['name' => 'Green Background', 'color' => '#27AE60', 'unlock' => 10],
        ['name' => 'Yellow Background', 'color' => '#F1C40F', 'unlock' => 15],
        ['name' => 'Purple Background', 'color' => '#9B59B6', 'unlock' => 15],
        ['name' => 'Orange Background', 'color' => '#E67E22', 'unlock' => 15],
        ['name' => 'Pink Background', 'color' => '#FF69B4', 'unlock' => 20],
        ['name' => 'Teal Background', 'color' => '#1ABC9C', 'unlock' => 20],
        ['name' => 'Navy Background', 'color' => '#2C3E50', 'unlock' => 20],
        ['name' => 'Brown Background', 'color' => '#8B4513', 'unlock' => 25],
        ['name' => 'Beige Background', 'color' => '#F5F5DC', 'unlock' => 25],
        ['name' => 'Lavender Background', 'color' => '#E6E6FA', 'unlock' => 30],
        ['name' => 'Mint Background', 'color' => '#98FF98', 'unlock' => 30],
        ['name' => 'Peach Background', 'color' => '#FFE5B4', 'unlock' => 30],
        ['name' => 'Sky Blue Background', 'color' => '#87CEEB', 'unlock' => 35],
        ['name' => 'Rose Background', 'color' => '#FFE4E1', 'unlock' => 35],
        ['name' => 'Gold Background', 'color' => '#FFD700', 'unlock' => 50],
    ];

    foreach ($solid_colors as $bg) {
        $items[] = [
            'slug' => 'bg-solid-' . strtolower(str_replace(' ', '-', $bg['name'])),
            'name' => $bg['name'],
            'category' => 'background',
            'svg_data' => '<rect width="100" height="100" fill="' . $bg['color'] . '"/>',
            'unlock_metric' => $bg['unlock'] > 0 ? 'books_read' : '',
            'unlock_value' => $bg['unlock'],
            'unlock_message' => $bg['unlock'] > 0 ? 'Read ' . $bg['unlock'] . ' books to unlock!' : '',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Gradient Backgrounds - 15 items
    $gradients = [
        ['name' => 'Sunset Gradient', 'from' => '#FF512F', 'to' => '#F09819', 'unlock' => 40],
        ['name' => 'Ocean Gradient', 'from' => '#2E3192', 'to' => '#1BFFFF', 'unlock' => 40],
        ['name' => 'Forest Gradient', 'from' => '#134E5E', 'to' => '#71B280', 'unlock' => 45],
        ['name' => 'Purple Dream Gradient', 'from' => '#c94b4b', 'to' => '#4b134f', 'unlock' => 45],
        ['name' => 'Fire Gradient', 'from' => '#FF0000', 'to' => '#FFFF00', 'unlock' => 50],
        ['name' => 'Ice Gradient', 'from' => '#76b2fe', 'to' => '#b69efe', 'unlock' => 50],
        ['name' => 'Rainbow Gradient', 'from' => '#FF0000', 'to' => '#0000FF', 'unlock' => 75],
        ['name' => 'Dawn Gradient', 'from' => '#F3904F', 'to' => '#3B4371', 'unlock' => 55],
        ['name' => 'Dusk Gradient', 'from' => '#2C3E50', 'to' => '#FD746C', 'unlock' => 55],
        ['name' => 'Emerald Gradient', 'from' => '#348F50', 'to' => '#56B4D3', 'unlock' => 60],
        ['name' => 'Ruby Gradient', 'from' => '#D31027', 'to' => '#EA384D', 'unlock' => 60],
        ['name' => 'Sapphire Gradient', 'from' => '#0F2027', 'to' => '#2C5364', 'unlock' => 60],
        ['name' => 'Galaxy Gradient', 'from' => '#000000', 'to' => '#434343', 'unlock' => 100],
        ['name' => 'Cosmic Gradient', 'from' => '#8E2DE2', 'to' => '#4A00E0', 'unlock' => 125],
        ['name' => 'Aurora Gradient', 'from' => '#00F260', 'to' => '#0575E6', 'unlock' => 150],
    ];

    foreach ($gradients as $grad) {
        $items[] = [
            'slug' => 'bg-gradient-' . strtolower(str_replace(' ', '-', $grad['name'])),
            'name' => $grad['name'],
            'category' => 'background',
            'svg_data' => '<defs><linearGradient id="grad-' . sanitize_key($grad['name']) . '" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:' . $grad['from'] . ';stop-opacity:1" /><stop offset="100%" style="stop-color:' . $grad['to'] . ';stop-opacity:1" /></linearGradient></defs><rect width="100" height="100" fill="url(#grad-' . sanitize_key($grad['name']) . ')"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $grad['unlock'],
            'unlock_message' => 'Read ' . $grad['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Pattern Backgrounds - 20 items
    $patterns = [
        ['name' => 'Polka Dots Background', 'unlock' => 30],
        ['name' => 'Stripes Background', 'unlock' => 25],
        ['name' => 'Diagonal Stripes Background', 'unlock' => 28],
        ['name' => 'Checkered Background', 'unlock' => 32],
        ['name' => 'Chevron Background', 'unlock' => 35],
        ['name' => 'Zigzag Background', 'unlock' => 35],
        ['name' => 'Honeycomb Background', 'unlock' => 40],
        ['name' => 'Diamond Pattern Background', 'unlock' => 38],
        ['name' => 'Argyle Background', 'unlock' => 45],
        ['name' => 'Plaid Background', 'unlock' => 42],
        ['name' => 'Herringbone Background', 'unlock' => 48],
        ['name' => 'Houndstooth Background', 'unlock' => 50],
        ['name' => 'Paisley Background', 'unlock' => 55],
        ['name' => 'Damask Background', 'unlock' => 60],
        ['name' => 'Moroccan Pattern Background', 'unlock' => 65],
        ['name' => 'Geometric Pattern Background', 'unlock' => 50],
        ['name' => 'Triangles Background', 'unlock' => 45],
        ['name' => 'Hexagons Background', 'unlock' => 47],
        ['name' => 'Circles Background', 'unlock' => 40],
        ['name' => 'Waves Background', 'unlock' => 52],
    ];

    foreach ($patterns as $pattern) {
        $svg = '<rect width="100" height="100" fill="#E8F4F8"/>';

        if ($pattern['name'] === 'Polka Dots Background') {
            $svg .= '<g fill="#3498DB" opacity="0.3">';
            for ($i = 10; $i < 100; $i += 20) {
                for ($j = 10; $j < 100; $j += 20) {
                    $svg .= '<circle cx="' . $i . '" cy="' . $j . '" r="3"/>';
                }
            }
            $svg .= '</g>';
        } elseif ($pattern['name'] === 'Stripes Background') {
            $svg = '<rect width="100" height="100" fill="#3498DB"/>';
            for ($i = 0; $i < 100; $i += 10) {
                $svg .= '<rect x="0" y="' . $i . '" width="100" height="5" fill="#FFFFFF" opacity="0.2"/>';
            }
        }

        $items[] = [
            'slug' => 'bg-pattern-' . strtolower(str_replace(' ', '-', $pattern['name'])),
            'name' => $pattern['name'],
            'category' => 'background',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $pattern['unlock'],
            'unlock_message' => 'Read ' . $pattern['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Themed Backgrounds - 25 items
    $themed = [
        ['name' => 'Bookshelf Background', 'unlock' => 50],
        ['name' => 'Library Background', 'unlock' => 75],
        ['name' => 'Stars Background', 'unlock' => 40],
        ['name' => 'Moon and Stars Background', 'unlock' => 45],
        ['name' => 'Hearts Background', 'unlock' => 35],
        ['name' => 'Clouds Background', 'unlock' => 38],
        ['name' => 'Rainbow Background', 'unlock' => 60],
        ['name' => 'Music Notes Background', 'unlock' => 42],
        ['name' => 'Flowers Background', 'unlock' => 40],
        ['name' => 'Cherry Blossoms Background', 'unlock' => 55],
        ['name' => 'Autumn Leaves Background', 'unlock' => 50],
        ['name' => 'Snowflakes Background', 'unlock' => 45],
        ['name' => 'Bubbles Background', 'unlock' => 35],
        ['name' => 'Confetti Background', 'unlock' => 40],
        ['name' => 'Fireworks Background', 'unlock' => 65],
        ['name' => 'Lightning Background', 'unlock' => 70],
        ['name' => 'Crystals Background', 'unlock' => 80],
        ['name' => 'Gems Background', 'unlock' => 85],
        ['name' => 'Circuit Board Background', 'unlock' => 90],
        ['name' => 'Matrix Code Background', 'unlock' => 100],
        ['name' => 'Space Background', 'unlock' => 100],
        ['name' => 'Galaxy Background', 'unlock' => 125],
        ['name' => 'Nebula Background', 'unlock' => 150],
        ['name' => 'Magical Sparkles Background', 'unlock' => 95],
        ['name' => 'Ancient Runes Background', 'unlock' => 200],
    ];

    foreach ($themed as $theme) {
        $svg = '<rect width="100" height="100" fill="#E8F4F8"/>';

        if ($theme['name'] === 'Stars Background') {
            $svg = '<rect width="100" height="100" fill="#2C3E50"/>';
            $svg .= '<circle cx="20" cy="20" r="1" fill="#FFD700"/><circle cx="40" cy="30" r="1.5" fill="#FFD700"/><circle cx="60" cy="15" r="1" fill="#FFD700"/><circle cx="80" cy="25" r="1.2" fill="#FFD700"/><circle cx="30" cy="50" r="1" fill="#FFD700"/><circle cx="70" cy="60" r="1.5" fill="#FFD700"/><circle cx="50" cy="70" r="1" fill="#FFD700"/><circle cx="85" cy="80" r="1.3" fill="#FFD700"/>';
        } elseif ($theme['name'] === 'Bookshelf Background') {
            $svg = '<rect width="100" height="100" fill="#8B4513"/>';
            for ($i = 0; $i < 3; $i++) {
                $y = 20 + ($i * 30);
                $svg .= '<rect x="5" y="' . $y . '" width="10" height="20" fill="#E74C3C"/>';
                $svg .= '<rect x="18" y="' . $y . '" width="12" height="20" fill="#3498DB"/>';
                $svg .= '<rect x="33" y="' . $y . '" width="8" height="20" fill="#27AE60"/>';
                $svg .= '<rect x="44" y="' . $y . '" width="14" height="20" fill="#9B59B6"/>';
                $svg .= '<rect x="61" y="' . $y . '" width="9" height="20" fill="#F1C40F"/>';
                $svg .= '<rect x="73" y="' . $y . '" width="11" height="20" fill="#E67E22"/>';
                $svg .= '<rect x="87" y="' . $y . '" width="10" height="20" fill="#1ABC9C"/>';
            }
        } elseif ($theme['name'] === 'Hearts Background') {
            $svg = '<rect width="100" height="100" fill="#FFE4E1"/>';
            for ($i = 15; $i < 100; $i += 25) {
                for ($j = 15; $j < 100; $j += 25) {
                    $svg .= '<path d="M ' . $i . ' ' . ($j + 3) . ' Q ' . ($i - 3) . ' ' . ($j - 2) . ' ' . $i . ' ' . ($j - 5) . ' Q ' . ($i + 3) . ' ' . ($j - 2) . ' ' . $i . ' ' . ($j + 3) . '" fill="#FF69B4" opacity="0.3"/>';
                }
            }
        }

        $items[] = [
            'slug' => 'bg-themed-' . strtolower(str_replace(' ', '-', $theme['name'])),
            'name' => $theme['name'],
            'category' => 'background',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $theme['unlock'],
            'unlock_message' => 'Read ' . $theme['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Seasonal Backgrounds - 12 items
    $seasonal = [
        ['name' => 'Spring Flowers Background', 'unlock' => 40],
        ['name' => 'Summer Beach Background', 'unlock' => 40],
        ['name' => 'Autumn Forest Background', 'unlock' => 40],
        ['name' => 'Winter Snow Background', 'unlock' => 40],
        ['name' => 'Valentine Hearts Background', 'unlock' => 35],
        ['name' => 'Easter Eggs Background', 'unlock' => 35],
        ['name' => 'Halloween Pumpkins Background', 'unlock' => 50],
        ['name' => 'Halloween Ghosts Background', 'unlock' => 50],
        ['name' => 'Thanksgiving Background', 'unlock' => 45],
        ['name' => 'Christmas Background', 'unlock' => 45],
        ['name' => 'New Year Fireworks Background', 'unlock' => 40],
        ['name' => 'Birthday Confetti Background', 'unlock' => 30],
    ];

    foreach ($seasonal as $season) {
        $items[] = [
            'slug' => 'bg-seasonal-' . strtolower(str_replace(' ', '-', $season['name'])),
            'name' => $season['name'],
            'category' => 'background',
            'svg_data' => '<rect width="100" height="100" fill="#E8F4F8"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $season['unlock'],
            'unlock_message' => 'Read ' . $season['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Achievement Backgrounds - 8 items (high unlock requirements)
    $achievements = [
        ['name' => 'Bronze Reader Background', 'color' => '#CD7F32', 'unlock' => 100],
        ['name' => 'Silver Reader Background', 'color' => '#C0C0C0', 'unlock' => 250],
        ['name' => 'Gold Reader Background', 'color' => '#FFD700', 'unlock' => 500],
        ['name' => 'Platinum Reader Background', 'color' => '#E5E4E2', 'unlock' => 1000],
        ['name' => 'Diamond Reader Background', 'color' => '#B9F2FF', 'unlock' => 1500],
        ['name' => 'Master Reader Background', 'color' => '#9B59B6', 'unlock' => 2500],
        ['name' => 'Legendary Reader Background', 'color' => '#E74C3C', 'unlock' => 5000],
        ['name' => 'Ultimate Reader Background', 'color' => '#FFD700', 'unlock' => 10000],
    ];

    foreach ($achievements as $ach) {
        $items[] = [
            'slug' => 'bg-achievement-' . strtolower(str_replace(' ', '-', $ach['name'])),
            'name' => $ach['name'],
            'category' => 'background',
            'svg_data' => '<defs><radialGradient id="ach-' . sanitize_key($ach['name']) . '"><stop offset="0%" style="stop-color:' . $ach['color'] . ';stop-opacity:1" /><stop offset="100%" style="stop-color:#000000;stop-opacity:0.8" /></radialGradient></defs><rect width="100" height="100" fill="url(#ach-' . sanitize_key($ach['name']) . ')"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $ach['unlock'],
            'unlock_message' => 'Read ' . $ach['unlock'] . ' books to unlock this legendary background!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    return $items;
}

/**
 * SHIRT PATTERNS - ~80 items
 */
function hs_generate_shirt_pattern_items() {
    $items = [];
    $display_order = 0;

    // Stripes - 15 items
    $stripes = [
        ['name' => 'Horizontal Stripes (White)', 'color' => '#FFFFFF', 'unlock' => 5],
        ['name' => 'Horizontal Stripes (Black)', 'color' => '#000000', 'unlock' => 5],
        ['name' => 'Horizontal Stripes (Red)', 'color' => '#E74C3C', 'unlock' => 10],
        ['name' => 'Horizontal Stripes (Blue)', 'color' => '#3498DB', 'unlock' => 10],
        ['name' => 'Horizontal Stripes (Green)', 'color' => '#27AE60', 'unlock' => 10],
        ['name' => 'Vertical Stripes (White)', 'color' => '#FFFFFF', 'unlock' => 8],
        ['name' => 'Vertical Stripes (Black)', 'color' => '#000000', 'unlock' => 8],
        ['name' => 'Diagonal Stripes (White)', 'color' => '#FFFFFF', 'unlock' => 12],
        ['name' => 'Diagonal Stripes (Black)', 'color' => '#000000', 'unlock' => 12],
        ['name' => 'Thin Stripes', 'color' => '#FFFFFF', 'unlock' => 15],
        ['name' => 'Wide Stripes', 'color' => '#000000', 'unlock' => 15],
        ['name' => 'Rainbow Stripes', 'color' => '#FF0000', 'unlock' => 50],
        ['name' => 'Candy Stripes', 'color' => '#E74C3C', 'unlock' => 25],
        ['name' => 'Barber Stripes', 'color' => '#E74C3C', 'unlock' => 30],
        ['name' => 'Prison Stripes', 'color' => '#2C3E50', 'unlock' => 40],
    ];

    foreach ($stripes as $stripe) {
        $svg = '<g opacity="0.6">';
        for ($i = 50; $i < 70; $i += 4) {
            $svg .= '<line x1="40" y1="' . $i . '" x2="60" y2="' . $i . '" stroke="' . $stripe['color'] . '" stroke-width="2"/>';
        }
        $svg .= '</g>';

        $items[] = [
            'slug' => 'shirt-stripes-' . strtolower(str_replace(['(', ')', ' '], ['', '', '-'], $stripe['name'])),
            'name' => 'Shirt: ' . $stripe['name'],
            'category' => 'shirt_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $stripe['unlock'],
            'unlock_message' => 'Read ' . $stripe['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Dots/Polka Dots - 10 items
    $dots = [
        ['name' => 'Small Polka Dots', 'color' => '#FFFFFF', 'unlock' => 10],
        ['name' => 'Large Polka Dots', 'color' => '#000000', 'unlock' => 10],
        ['name' => 'Red Polka Dots', 'color' => '#E74C3C', 'unlock' => 15],
        ['name' => 'Blue Polka Dots', 'color' => '#3498DB', 'unlock' => 15],
        ['name' => 'Yellow Polka Dots', 'color' => '#F1C40F', 'unlock' => 20],
        ['name' => 'Green Polka Dots', 'color' => '#27AE60', 'unlock' => 20],
        ['name' => 'Rainbow Polka Dots', 'color' => '#FF69B4', 'unlock' => 40],
        ['name' => 'Gold Polka Dots', 'color' => '#FFD700', 'unlock' => 60],
        ['name' => 'Silver Polka Dots', 'color' => '#C0C0C0', 'unlock' => 50],
        ['name' => 'Glitter Dots', 'color' => '#FFD700', 'unlock' => 75],
    ];

    foreach ($dots as $dot) {
        $svg = '<g opacity="0.7">';
        for ($x = 42; $x < 58; $x += 5) {
            for ($y = 52; $y < 68; $y += 5) {
                $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="1.5" fill="' . $dot['color'] . '"/>';
            }
        }
        $svg .= '</g>';

        $items[] = [
            'slug' => 'shirt-dots-' . strtolower(str_replace(' ', '-', $dot['name'])),
            'name' => 'Shirt: ' . $dot['name'],
            'category' => 'shirt_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $dot['unlock'],
            'unlock_message' => 'Read ' . $dot['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Designs/Graphics - 25 items
    $designs = [
        ['name' => 'Heart Design', 'unlock' => 20],
        ['name' => 'Star Design', 'unlock' => 20],
        ['name' => 'Lightning Bolt', 'unlock' => 25],
        ['name' => 'Skull Design', 'unlock' => 30],
        ['name' => 'Peace Sign', 'unlock' => 25],
        ['name' => 'Smiley Face', 'unlock' => 15],
        ['name' => 'Music Note', 'unlock' => 22],
        ['name' => 'Treble Clef', 'unlock' => 28],
        ['name' => 'Anchor', 'unlock' => 35],
        ['name' => 'Crown Design', 'unlock' => 50],
        ['name' => 'Diamond Design', 'unlock' => 60],
        ['name' => 'Flame Design', 'unlock' => 40],
        ['name' => 'Ice Crystal', 'unlock' => 45],
        ['name' => 'Yin Yang', 'unlock' => 50],
        ['name' => 'Infinity Symbol', 'unlock' => 55],
        ['name' => 'Book Icon', 'unlock' => 30],
        ['name' => 'Open Book', 'unlock' => 35],
        ['name' => 'Stack of Books', 'unlock' => 40],
        ['name' => 'Reading Lamp', 'unlock' => 45],
        ['name' => 'Quill Pen', 'unlock' => 50],
        ['name' => 'Feather', 'unlock' => 38],
        ['name' => 'Tree Design', 'unlock' => 42],
        ['name' => 'Mountain Design', 'unlock' => 48],
        ['name' => 'Wave Design', 'unlock' => 44],
        ['name' => 'Sun Design', 'unlock' => 46],
    ];

    foreach ($designs as $design) {
        $svg = '<path d="M 45 55 Q 50 52 55 55 Q 50 58 45 55" fill="#FFFFFF" opacity="0.8"/>';

        if ($design['name'] === 'Heart Design') {
            $svg = '<path d="M 50 58 Q 46 54 46 52 Q 46 50 48 50 Q 50 50 50 52 Q 50 50 52 50 Q 54 50 54 52 Q 54 54 50 58" fill="#E74C3C" opacity="0.8"/>';
        } elseif ($design['name'] === 'Star Design') {
            $svg = '<path d="M 50 52 L 51 55 L 54 55 L 52 57 L 53 60 L 50 58 L 47 60 L 48 57 L 46 55 L 49 55 Z" fill="#FFD700" opacity="0.9"/>';
        } elseif ($design['name'] === 'Book Icon') {
            $svg = '<rect x="47" y="54" width="6" height="8" rx="0.5" fill="#8B4513" opacity="0.8"/><line x1="50" y1="54" x2="50" y2="62" stroke="#FFFFFF" stroke-width="0.5" opacity="0.6"/>';
        }

        $items[] = [
            'slug' => 'shirt-design-' . strtolower(str_replace(' ', '-', $design['name'])),
            'name' => 'Shirt: ' . $design['name'],
            'category' => 'shirt_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $design['unlock'],
            'unlock_message' => 'Read ' . $design['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Pockets & Buttons - 10 items
    $pockets = [
        ['name' => 'Chest Pocket', 'unlock' => 12],
        ['name' => 'Pocket with Pen', 'unlock' => 18],
        ['name' => 'Double Pockets', 'unlock' => 22],
        ['name' => 'Buttoned Pocket', 'unlock' => 25],
        ['name' => 'Three Buttons', 'unlock' => 10],
        ['name' => 'Five Buttons', 'unlock' => 15],
        ['name' => 'Gold Buttons', 'unlock' => 40],
        ['name' => 'Silver Buttons', 'unlock' => 35],
        ['name' => 'Diamond Buttons', 'unlock' => 75],
        ['name' => 'Button-Up Shirt', 'unlock' => 30],
    ];

    foreach ($pockets as $pocket) {
        $svg = '<rect x="53" y="54" width="4" height="5" rx="0.5" fill="none" stroke="#FFFFFF" stroke-width="0.5" opacity="0.6"/>';

        if (strpos($pocket['name'], 'Button') !== false) {
            $svg = '';
            for ($i = 54; $i < 64; $i += 3) {
                $svg .= '<circle cx="50" cy="' . $i . '" r="0.8" fill="#FFFFFF" stroke="#000" stroke-width="0.3"/>';
            }
        }

        $items[] = [
            'slug' => 'shirt-pocket-' . strtolower(str_replace(' ', '-', $pocket['name'])),
            'name' => 'Shirt: ' . $pocket['name'],
            'category' => 'shirt_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $pocket['unlock'],
            'unlock_message' => 'Read ' . $pocket['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Ties & Bow Ties - 10 items
    $ties = [
        ['name' => 'Red Tie', 'unlock' => 25],
        ['name' => 'Blue Tie', 'unlock' => 25],
        ['name' => 'Black Tie', 'unlock' => 30],
        ['name' => 'Striped Tie', 'unlock' => 35],
        ['name' => 'Polka Dot Tie', 'unlock' => 35],
        ['name' => 'Red Bow Tie', 'unlock' => 28],
        ['name' => 'Black Bow Tie', 'unlock' => 32],
        ['name' => 'Rainbow Bow Tie', 'unlock' => 50],
        ['name' => 'Formal Tie', 'unlock' => 45],
        ['name' => 'Novelty Tie', 'unlock' => 40],
    ];

    foreach ($ties as $tie) {
        $color = '#E74C3C';
        if (strpos($tie['name'], 'Blue') !== false) $color = '#3498DB';
        if (strpos($tie['name'], 'Black') !== false) $color = '#000000';

        $svg = '<path d="M 50 50 L 48 54 L 50 58 L 52 54 Z" fill="' . $color . '"/>';

        if (strpos($tie['name'], 'Bow') !== false) {
            $svg = '<path d="M 46 52 Q 48 50 50 52 Q 52 50 54 52 Q 52 54 50 52 Q 48 54 46 52" fill="' . $color . '"/><rect x="49" y="51" width="2" height="2" fill="' . $color . '"/>';
        }

        $items[] = [
            'slug' => 'shirt-tie-' . strtolower(str_replace(' ', '-', $tie['name'])),
            'name' => 'Shirt: ' . $tie['name'],
            'category' => 'shirt_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $tie['unlock'],
            'unlock_message' => 'Read ' . $tie['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Special Patterns - 10 items
    $special = [
        ['name' => 'Plaid Pattern', 'unlock' => 45],
        ['name' => 'Checkered Pattern', 'unlock' => 40],
        ['name' => 'Argyle Pattern', 'unlock' => 50],
        ['name' => 'Chevron Pattern', 'unlock' => 42],
        ['name' => 'Zigzag Pattern', 'unlock' => 38],
        ['name' => 'Diamond Pattern', 'unlock' => 48],
        ['name' => 'Geometric Pattern', 'unlock' => 52],
        ['name' => 'Abstract Pattern', 'unlock' => 55],
        ['name' => 'Camouflage Pattern', 'unlock' => 60],
        ['name' => 'Galaxy Pattern', 'unlock' => 100],
    ];

    foreach ($special as $spec) {
        $items[] = [
            'slug' => 'shirt-special-' . strtolower(str_replace(' ', '-', $spec['name'])),
            'name' => 'Shirt: ' . $spec['name'],
            'category' => 'shirt_pattern',
            'svg_data' => '<g opacity="0.5"><line x1="42" y1="54" x2="58" y2="54" stroke="#FFFFFF" stroke-width="1"/><line x1="42" y1="58" x2="58" y2="58" stroke="#FFFFFF" stroke-width="1"/><line x1="42" y1="62" x2="58" y2="62" stroke="#FFFFFF" stroke-width="1"/></g>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $spec['unlock'],
            'unlock_message' => 'Read ' . $spec['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    return $items;
}

/**
 * PANTS PATTERNS - ~50 items
 */
function hs_generate_pants_pattern_items() {
    $items = [];
    $display_order = 0;

    // Basic Stripes - 10 items
    $stripes = [
        ['name' => 'White Side Stripes', 'color' => '#FFFFFF', 'unlock' => 8],
        ['name' => 'Red Side Stripes', 'color' => '#E74C3C', 'unlock' => 10],
        ['name' => 'Blue Side Stripes', 'color' => '#3498DB', 'unlock' => 10],
        ['name' => 'Yellow Side Stripes', 'color' => '#F1C40F', 'unlock' => 12],
        ['name' => 'Green Side Stripes', 'color' => '#27AE60', 'unlock' => 12],
        ['name' => 'Gold Side Stripes', 'color' => '#FFD700', 'unlock' => 40],
        ['name' => 'Silver Side Stripes', 'color' => '#C0C0C0', 'unlock' => 35],
        ['name' => 'Double Side Stripes', 'color' => '#FFFFFF', 'unlock' => 18],
        ['name' => 'Rainbow Side Stripes', 'color' => '#FF0000', 'unlock' => 60],
        ['name' => 'Racing Stripes', 'color' => '#E74C3C', 'unlock' => 50],
    ];

    foreach ($stripes as $stripe) {
        $svg = '<line x1="43" y1="70" x2="43" y2="88" stroke="' . $stripe['color'] . '" stroke-width="1.5" opacity="0.8"/>';
        $svg .= '<line x1="57" y1="70" x2="57" y2="88" stroke="' . $stripe['color'] . '" stroke-width="1.5" opacity="0.8"/>';

        $items[] = [
            'slug' => 'pants-stripes-' . strtolower(str_replace(' ', '-', $stripe['name'])),
            'name' => 'Pants: ' . $stripe['name'],
            'category' => 'pants_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $stripe['unlock'],
            'unlock_message' => 'Read ' . $stripe['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Patches - 12 items
    $patches = [
        ['name' => 'Knee Patch (Left)', 'unlock' => 15],
        ['name' => 'Knee Patch (Right)', 'unlock' => 15],
        ['name' => 'Both Knee Patches', 'unlock' => 20],
        ['name' => 'Heart Patch', 'unlock' => 22],
        ['name' => 'Star Patch', 'unlock' => 22],
        ['name' => 'Flower Patch', 'unlock' => 25],
        ['name' => 'Peace Patch', 'unlock' => 28],
        ['name' => 'Smiley Patch', 'unlock' => 18],
        ['name' => 'Book Patch', 'unlock' => 30],
        ['name' => 'Rainbow Patch', 'unlock' => 45],
        ['name' => 'Vintage Patches', 'unlock' => 50],
        ['name' => 'Collector Patches', 'unlock' => 75],
    ];

    foreach ($patches as $patch) {
        $svg = '<rect x="44" y="78" width="4" height="4" rx="0.5" fill="#8B4513" opacity="0.7"/>';

        if ($patch['name'] === 'Both Knee Patches') {
            $svg .= '<rect x="52" y="78" width="4" height="4" rx="0.5" fill="#8B4513" opacity="0.7"/>';
        }

        $items[] = [
            'slug' => 'pants-patch-' . strtolower(str_replace(['(', ')', ' '], ['', '', '-'], $patch['name'])),
            'name' => 'Pants: ' . $patch['name'],
            'category' => 'pants_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $patch['unlock'],
            'unlock_message' => 'Read ' . $patch['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Patterns - 15 items
    $patterns = [
        ['name' => 'Polka Dots', 'unlock' => 20],
        ['name' => 'Checkered', 'unlock' => 25],
        ['name' => 'Plaid', 'unlock' => 30],
        ['name' => 'Camouflage', 'unlock' => 40],
        ['name' => 'Leopard Print', 'unlock' => 45],
        ['name' => 'Zebra Print', 'unlock' => 45],
        ['name' => 'Tiger Print', 'unlock' => 50],
        ['name' => 'Snake Print', 'unlock' => 48],
        ['name' => 'Denim Texture', 'unlock' => 35],
        ['name' => 'Ripped Jeans', 'unlock' => 42],
        ['name' => 'Distressed', 'unlock' => 38],
        ['name' => 'Bleached', 'unlock' => 36],
        ['name' => 'Acid Wash', 'unlock' => 55],
        ['name' => 'Paint Splatter', 'unlock' => 50],
        ['name' => 'Glitter', 'unlock' => 65],
    ];

    foreach ($patterns as $pattern) {
        $svg = '<g opacity="0.6">';
        for ($y = 72; $y < 86; $y += 4) {
            $svg .= '<circle cx="45" cy="' . $y . '" r="1" fill="#FFFFFF"/>';
            $svg .= '<circle cx="55" cy="' . ($y + 2) . '" r="1" fill="#FFFFFF"/>';
        }
        $svg .= '</g>';

        $items[] = [
            'slug' => 'pants-pattern-' . strtolower(str_replace(' ', '-', $pattern['name'])),
            'name' => 'Pants: ' . $pattern['name'],
            'category' => 'pants_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $pattern['unlock'],
            'unlock_message' => 'Read ' . $pattern['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Cargo/Utility - 8 items
    $cargo = [
        ['name' => 'Cargo Pockets', 'unlock' => 30],
        ['name' => 'Side Pockets', 'unlock' => 25],
        ['name' => 'Utility Belt', 'unlock' => 35],
        ['name' => 'Tool Loops', 'unlock' => 38],
        ['name' => 'Zipper Details', 'unlock' => 32],
        ['name' => 'Button Details', 'unlock' => 28],
        ['name' => 'Drawstring', 'unlock' => 22],
        ['name' => 'Elastic Cuffs', 'unlock' => 24],
    ];

    foreach ($cargo as $item) {
        $svg = '<rect x="44" y="74" width="3" height="4" rx="0.3" fill="none" stroke="#000" stroke-width="0.4" opacity="0.6"/>';
        $svg .= '<rect x="53" y="74" width="3" height="4" rx="0.3" fill="none" stroke="#000" stroke-width="0.4" opacity="0.6"/>';

        $items[] = [
            'slug' => 'pants-cargo-' . strtolower(str_replace(' ', '-', $item['name'])),
            'name' => 'Pants: ' . $item['name'],
            'category' => 'pants_pattern',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $item['unlock'],
            'unlock_message' => 'Read ' . $item['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Special/Fancy - 5 items
    $special = [
        ['name' => 'Chain Details', 'unlock' => 60],
        ['name' => 'Studs', 'unlock' => 55],
        ['name' => 'Sequins', 'unlock' => 70],
        ['name' => 'Rhinestones', 'unlock' => 75],
        ['name' => 'Embroidery', 'unlock' => 65],
    ];

    foreach ($special as $spec) {
        $items[] = [
            'slug' => 'pants-special-' . strtolower(str_replace(' ', '-', $spec['name'])),
            'name' => 'Pants: ' . $spec['name'],
            'category' => 'pants_pattern',
            'svg_data' => '<circle cx="46" cy="74" r="0.8" fill="#FFD700"/><circle cx="48" cy="76" r="0.8" fill="#FFD700"/><circle cx="54" cy="74" r="0.8" fill="#FFD700"/><circle cx="52" cy="76" r="0.8" fill="#FFD700"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $spec['unlock'],
            'unlock_message' => 'Read ' . $spec['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    return $items;
}
