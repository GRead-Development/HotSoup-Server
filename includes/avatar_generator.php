<?php
/**
 * Avatar Generator
 * Generates 2D SVG avatars based on user customization
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
    $body_color = get_user_meta($user_id, 'hs_avatar_body_color', true) ?: '#FFFFFF';
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

    // Build SVG
    $svg = sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="%d" height="%d">',
        $size,
        $size
    );

    // Background
    if (isset($items_by_category['background'])) {
        $svg .= $items_by_category['background'];
    } else {
        $svg .= '<rect width="100" height="100" fill="#E8F4F8"/>';
    }

    // Body (head, torso, arms, legs)
    $svg .= hs_generate_avatar_body($body_color, $gender);

    // Shirt
    $svg .= hs_generate_avatar_shirt($shirt_color, $gender);

    // Shirt pattern (if equipped)
    if (isset($items_by_category['shirt_pattern'])) {
        $svg .= $items_by_category['shirt_pattern'];
    }

    // Pants
    $svg .= hs_generate_avatar_pants($pants_color);

    // Pants pattern (if equipped)
    if (isset($items_by_category['pants_pattern'])) {
        $svg .= $items_by_category['pants_pattern'];
    }

    // Face (simple features)
    $svg .= hs_generate_avatar_face($body_color);

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
 * Generate the avatar body (head, torso, arms, legs)
 */
function hs_generate_avatar_body($color, $gender) {
    $svg = '<g id="avatar-body">';

    // Head (circle)
    $svg .= sprintf(
        '<circle cx="50" cy="35" r="15" fill="%s" stroke="#000" stroke-width="0.5"/>',
        esc_attr($color)
    );

    // Torso (rounded rectangle)
    if ($gender === 'female') {
        // Female torso with slight chest bump
        $svg .= sprintf(
            '<path d="M 40 50 Q 40 47 42 47 L 42 55 Q 42 57 40 57 Z" fill="%s" stroke="#000" stroke-width="0.5"/>',
            esc_attr($color)
        );
        $svg .= sprintf(
            '<path d="M 60 50 Q 60 47 58 47 L 58 55 Q 58 57 60 57 Z" fill="%s" stroke="#000" stroke-width="0.5"/>',
            esc_attr($color)
        );
        $svg .= sprintf(
            '<rect x="40" y="52" width="20" height="18" rx="2" fill="%s" stroke="#000" stroke-width="0.5"/>',
            esc_attr($color)
        );
    } else {
        // Male torso (simple rectangle)
        $svg .= sprintf(
            '<rect x="40" y="50" width="20" height="20" rx="2" fill="%s" stroke="#000" stroke-width="0.5"/>',
            esc_attr($color)
        );
    }

    // Arms (simple rounded lines)
    $svg .= sprintf(
        '<circle cx="35" cy="55" r="5" fill="%s" stroke="#000" stroke-width="0.5"/>',
        esc_attr($color)
    );
    $svg .= sprintf(
        '<circle cx="65" cy="55" r="5" fill="%s" stroke="#000" stroke-width="0.5"/>',
        esc_attr($color)
    );

    // Legs (rounded rectangles)
    $svg .= sprintf(
        '<rect x="42" y="70" width="6" height="18" rx="3" fill="%s" stroke="#000" stroke-width="0.5"/>',
        esc_attr($color)
    );
    $svg .= sprintf(
        '<rect x="52" y="70" width="6" height="18" rx="3" fill="%s" stroke="#000" stroke-width="0.5"/>',
        esc_attr($color)
    );

    // Feet (circles)
    $svg .= sprintf(
        '<circle cx="45" cy="90" r="4" fill="%s" stroke="#000" stroke-width="0.5"/>',
        esc_attr($color)
    );
    $svg .= sprintf(
        '<circle cx="55" cy="90" r="4" fill="%s" stroke="#000" stroke-width="0.5"/>',
        esc_attr($color)
    );

    $svg .= '</g>';

    return $svg;
}

/**
 * Generate the avatar shirt
 */
function hs_generate_avatar_shirt($color, $gender) {
    $svg = '<g id="avatar-shirt">';

    if ($gender === 'female') {
        // Female shirt
        $svg .= sprintf(
            '<rect x="40" y="52" width="20" height="18" rx="2" fill="%s" opacity="0.9"/>',
            esc_attr($color)
        );
    } else {
        // Male shirt
        $svg .= sprintf(
            '<rect x="40" y="50" width="20" height="20" rx="2" fill="%s" opacity="0.9"/>',
            esc_attr($color)
        );
    }

    $svg .= '</g>';

    return $svg;
}

/**
 * Generate the avatar pants
 */
function hs_generate_avatar_pants($color) {
    $svg = '<g id="avatar-pants">';

    // Left leg
    $svg .= sprintf(
        '<rect x="42" y="70" width="6" height="18" rx="3" fill="%s" opacity="0.9"/>',
        esc_attr($color)
    );

    // Right leg
    $svg .= sprintf(
        '<rect x="52" y="70" width="6" height="18" rx="3" fill="%s" opacity="0.9"/>',
        esc_attr($color)
    );

    $svg .= '</g>';

    return $svg;
}

/**
 * Generate the avatar face (simple eyes and smile)
 */
function hs_generate_avatar_face($skin_color) {
    $svg = '<g id="avatar-face">';

    // Eyes
    $svg .= '<circle cx="45" cy="33" r="2" fill="#000"/>';
    $svg .= '<circle cx="55" cy="33" r="2" fill="#000"/>';

    // Smile
    $svg .= '<path d="M 43 38 Q 50 41 57 38" stroke="#000" stroke-width="1" fill="none" stroke-linecap="round"/>';

    $svg .= '</g>';

    return $svg;
}

/**
 * Get default avatar customization
 */
function hs_get_default_avatar_customization() {
    return [
        'body_color' => '#FFFFFF',
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
