<?php
/**
 * Avatar Generator - Human Fall Flat Style
 * Generates cute, blob-like 2D SVG avatars
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate an SVG avatar for a user
 *
 * @param int $user_id The user ID
 * @param int $size The size in pixels (default: 200)
 * @return string The SVG markup
 */
function hs_generate_user_avatar($user_id, $size = 200) {
    // Get user's avatar customization
    $body_color = get_user_meta($user_id, 'hs_avatar_body_color', true) ?: '#F5E6D3';
    $gender = get_user_meta($user_id, 'hs_avatar_gender', true) ?: 'male';
    $shirt_color = get_user_meta($user_id, 'hs_avatar_shirt_color', true) ?: '#4A90E2';
    $pants_color = get_user_meta($user_id, 'hs_avatar_pants_color', true) ?: '#2C3E50';
    $equipped_items = get_user_meta($user_id, 'hs_avatar_equipped_items', true);

    if (empty($equipped_items)) {
        $equipped_items = [];
    } else {
        $equipped_items = json_decode($equipped_items, true);
    }

    // Get avatar items from database
    $items_by_category = hs_get_avatar_items_by_category($equipped_items);

    // Build SVG with gradients for depth
    $svg = sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="%d" height="%d">',
        $size,
        $size
    );

    // Add gradient definitions for depth/shading
    $svg .= hs_generate_avatar_gradients($body_color, $shirt_color, $pants_color);

    // Background
    if (isset($items_by_category['background'])) {
        $svg .= $items_by_category['background'];
    } else {
        $svg .= '<rect width="100" height="100" fill="#E8F4F8"/>';
    }

    // Shadow under character
    $svg .= '<ellipse cx="50" cy="92" rx="18" ry="3" fill="#000000" opacity="0.15"/>';

    // Body parts in proper order (back to front)

    // Back arm
    $svg .= hs_generate_avatar_arm($body_color, 'left');

    // Legs
    $svg .= hs_generate_avatar_legs($body_color, $pants_color, $gender);

    // Torso
    $svg .= hs_generate_avatar_torso($body_color, $shirt_color, $gender);

    // Pants pattern (if equipped)
    if (isset($items_by_category['pants_pattern'])) {
        $svg .= $items_by_category['pants_pattern'];
    }

    // Shirt pattern (if equipped)
    if (isset($items_by_category['shirt_pattern'])) {
        $svg .= $items_by_category['shirt_pattern'];
    }

    // Front arm
    $svg .= hs_generate_avatar_arm($body_color, 'right');

    // Head
    $svg .= hs_generate_avatar_head($body_color);

    // Face (simple features)
    $svg .= hs_generate_avatar_face();

    // Accessories (glasses, etc.)
    if (isset($items_by_category['accessory'])) {
        $svg .= $items_by_category['accessory'];
    }

    // Hat
    if (isset($items_by_category['hat'])) {
        $svg .= $items_by_category['hat'];
    }

    $svg .= '</svg>';

    return $svg;
}

/**
 * Get avatar items organized by category
 */
function hs_get_avatar_items_by_category($item_ids) {
    if (empty($item_ids) || !is_array($item_ids)) {
        return [];
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';

    $placeholders = implode(',', array_fill(0, count($item_ids), '%d'));
    $query = "SELECT category, svg_data FROM $table_name WHERE id IN ($placeholders)";
    $items = $wpdb->get_results($wpdb->prepare($query, $item_ids));

    $items_by_category = [];
    foreach ($items as $item) {
        $items_by_category[$item->category] = $item->svg_data;
    }

    return $items_by_category;
}

/**
 * Generate gradients for shading/depth
 */
function hs_generate_avatar_gradients($body_color, $shirt_color, $pants_color) {
    $svg = '<defs>';

    // Body gradient (subtle shading)
    $svg .= '<radialGradient id="bodyGrad">';
    $svg .= '<stop offset="0%" style="stop-color:' . esc_attr($body_color) . ';stop-opacity:1" />';
    $svg .= '<stop offset="100%" style="stop-color:' . esc_attr(hs_darken_color($body_color, 15)) . ';stop-opacity:1" />';
    $svg .= '</radialGradient>';

    // Shirt gradient
    $svg .= '<radialGradient id="shirtGrad">';
    $svg .= '<stop offset="0%" style="stop-color:' . esc_attr($shirt_color) . ';stop-opacity:1" />';
    $svg .= '<stop offset="100%" style="stop-color:' . esc_attr(hs_darken_color($shirt_color, 20)) . ';stop-opacity:1" />';
    $svg .= '</radialGradient>';

    // Pants gradient
    $svg .= '<radialGradient id="pantsGrad">';
    $svg .= '<stop offset="0%" style="stop-color:' . esc_attr($pants_color) . ';stop-opacity:1" />';
    $svg .= '<stop offset="100%" style="stop-color:' . esc_attr(hs_darken_color($pants_color, 20)) . ';stop-opacity:1" />';
    $svg .= '</radialGradient>';

    $svg .= '</defs>';

    return $svg;
}

/**
 * Darken a hex color by a percentage
 */
function hs_darken_color($hex, $percent) {
    $hex = str_replace('#', '', $hex);

    if (strlen($hex) == 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r - ($r * $percent / 100)));
    $g = max(0, min(255, $g - ($g * $percent / 100)));
    $b = max(0, min(255, $b - ($b * $percent / 100)));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
               . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
               . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

/**
 * Generate the avatar head (large, rounded, blob-like)
 */
function hs_generate_avatar_head($color) {
    $svg = '<g id="avatar-head">';

    // Main head - large and round like Human Fall Flat
    $svg .= '<ellipse cx="50" cy="30" rx="16" ry="18" fill="url(#bodyGrad)"/>';

    // Subtle highlight on top of head for depth
    $svg .= '<ellipse cx="50" cy="25" rx="12" ry="8" fill="#FFFFFF" opacity="0.2"/>';

    $svg .= '</g>';

    return $svg;
}

/**
 * Generate the avatar torso (blob-like body)
 */
function hs_generate_avatar_torso($body_color, $shirt_color, $gender) {
    $svg = '<g id="avatar-torso">';

    // Main body blob - rounded and soft
    if ($gender === 'female') {
        // Female: slightly narrower waist, subtle chest
        $svg .= '<path d="M 35 48 Q 33 55 35 62 L 35 68 Q 35 70 37 70 L 63 70 Q 65 70 65 68 L 65 62 Q 67 55 65 48 Q 63 46 60 46 L 40 46 Q 37 46 35 48" fill="url(#shirtGrad)"/>';

        // Subtle chest indication
        $svg .= '<ellipse cx="44" cy="52" rx="5" ry="6" fill="' . esc_attr($shirt_color) . '" opacity="0.3"/>';
        $svg .= '<ellipse cx="56" cy="52" rx="5" ry="6" fill="' . esc_attr($shirt_color) . '" opacity="0.3"/>';
    } else {
        // Male: more rectangular blob
        $svg .= '<path d="M 36 46 Q 34 48 34 52 L 34 68 Q 34 70 36 70 L 64 70 Q 66 70 66 68 L 66 52 Q 66 48 64 46 Z" fill="url(#shirtGrad)"/>';
    }

    // Neck connection (blob connection)
    $svg .= '<ellipse cx="50" cy="45" rx="8" ry="6" fill="url(#bodyGrad)"/>';

    // Highlight on torso for depth
    $svg .= '<ellipse cx="50" cy="52" rx="10" ry="8" fill="#FFFFFF" opacity="0.15"/>';

    $svg .= '</g>';

    return $svg;
}

/**
 * Generate avatar arm (stubby, blob-like)
 */
function hs_generate_avatar_arm($color, $side) {
    $svg = '<g id="avatar-arm-' . $side . '">';

    if ($side === 'left') {
        // Back arm (left side)
        // Upper arm
        $svg .= '<ellipse cx="32" cy="55" rx="5" ry="10" fill="url(#bodyGrad)" transform="rotate(-20 32 55)"/>';
        // Lower arm
        $svg .= '<ellipse cx="28" cy="65" rx="4" ry="8" fill="url(#bodyGrad)" transform="rotate(-10 28 65)"/>';
        // Hand (round blob)
        $svg .= '<ellipse cx="27" cy="73" rx="5" ry="6" fill="url(#bodyGrad)"/>';
        // Highlight on hand
        $svg .= '<ellipse cx="27" cy="71" rx="3" ry="3" fill="#FFFFFF" opacity="0.25"/>';
    } else {
        // Front arm (right side)
        // Upper arm
        $svg .= '<ellipse cx="68" cy="55" rx="5" ry="10" fill="url(#bodyGrad)" transform="rotate(20 68 55)"/>';
        // Lower arm
        $svg .= '<ellipse cx="72" cy="65" rx="4" ry="8" fill="url(#bodyGrad)" transform="rotate(10 72 65)"/>';
        // Hand (round blob)
        $svg .= '<ellipse cx="73" cy="73" rx="5" ry="6" fill="url(#bodyGrad)"/>';
        // Highlight on hand
        $svg .= '<ellipse cx="73" cy="71" rx="3" ry="3" fill="#FFFFFF" opacity="0.25"/>';
    }

    $svg .= '</g>';

    return $svg;
}

/**
 * Generate avatar legs (stubby, blob-like)
 */
function hs_generate_avatar_legs($body_color, $pants_color, $gender) {
    $svg = '<g id="avatar-legs">';

    // Pants on legs
    // Left leg
    $svg .= '<ellipse cx="43" cy="80" rx="6" ry="14" fill="url(#pantsGrad)"/>';

    // Right leg
    $svg .= '<ellipse cx="57" cy="80" rx="6" ry="14" fill="url(#pantsGrad)"/>';

    // Highlights on pants
    $svg .= '<ellipse cx="43" cy="75" rx="4" ry="6" fill="#FFFFFF" opacity="0.1"/>';
    $svg .= '<ellipse cx="57" cy="75" rx="4" ry="6" fill="#FFFFFF" opacity="0.1"/>';

    // Feet (round blobs)
    // Left foot
    $svg .= '<ellipse cx="42" cy="90" rx="6" ry="5" fill="url(#bodyGrad)"/>';
    $svg .= '<ellipse cx="42" cy="89" rx="4" ry="3" fill="#FFFFFF" opacity="0.2"/>';

    // Right foot
    $svg .= '<ellipse cx="58" cy="90" rx="6" ry="5" fill="url(#bodyGrad)"/>';
    $svg .= '<ellipse cx="58" cy="89" rx="4" ry="3" fill="#FFFFFF" opacity="0.2"/>';

    $svg .= '</g>';

    return $svg;
}

/**
 * Generate the avatar face (simple, cute, Human Fall Flat style)
 */
function hs_generate_avatar_face() {
    $svg = '<g id="avatar-face">';

    // Eyes - simple dots
    $svg .= '<circle cx="44" cy="28" r="2.5" fill="#000000"/>';
    $svg .= '<circle cx="56" cy="28" r="2.5" fill="#000000"/>';

    // Eye highlights for life
    $svg .= '<circle cx="44.8" cy="27.2" r="1" fill="#FFFFFF" opacity="0.6"/>';
    $svg .= '<circle cx="56.8" cy="27.2" r="1" fill="#FFFFFF" opacity="0.6"/>';

    // Simple smile curve
    $svg .= '<path d="M 42 34 Q 50 37 58 34" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round"/>';

    $svg .= '</g>';

    return $svg;
}

/**
 * Get default avatar customization
 */
function hs_get_default_avatar_customization() {
    return [
        'body_color' => '#F5E6D3', // Softer beige/cream color like Human Fall Flat
        'gender' => 'male',
        'shirt_color' => '#4A90E2',
        'pants_color' => '#2C3E50',
        'equipped_items' => []
    ];
}

/**
 * Initialize default avatar for new users
 */
function hs_initialize_user_avatar($user_id) {
    $defaults = hs_get_default_avatar_customization();

    update_user_meta($user_id, 'hs_avatar_body_color', $defaults['body_color']);
    update_user_meta($user_id, 'hs_avatar_gender', $defaults['gender']);
    update_user_meta($user_id, 'hs_avatar_shirt_color', $defaults['shirt_color']);
    update_user_meta($user_id, 'hs_avatar_pants_color', $defaults['pants_color']);
    update_user_meta($user_id, 'hs_avatar_equipped_items', json_encode($defaults['equipped_items']));
}
add_action('user_register', 'hs_initialize_user_avatar');
