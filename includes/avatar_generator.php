<?php
/**
 * Avatar Generator - Human Fall Flat Style (Clean & Smooth)
 * Generates cute, blob-like 2D SVG avatars
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate an SVG avatar for a user
 */
function hs_generate_user_avatar($user_id, $size = 200) {
    // Get user's avatar customization
    $body_color = get_user_meta($user_id, 'hs_avatar_body_color', true) ?: '#FFE5CC';
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

    // Simple shadow
    $svg .= '<ellipse cx="50" cy="93" rx="16" ry="2.5" fill="#000000" opacity="0.1"/>';

    // Body parts
    $svg .= hs_generate_avatar_body($body_color, $shirt_color, $pants_color, $gender);

    // Pants pattern
    if (isset($items_by_category['pants_pattern'])) {
        $svg .= $items_by_category['pants_pattern'];
    }

    // Shirt pattern
    if (isset($items_by_category['shirt_pattern'])) {
        $svg .= $items_by_category['shirt_pattern'];
    }

    // Face
    $svg .= hs_generate_avatar_face();

    // Accessories
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
 * Generate complete avatar body
 */
function hs_generate_avatar_body($body_color, $shirt_color, $pants_color, $gender) {
    $svg = '<g id="avatar">';

    // Back arm
    $svg .= '<ellipse cx="32" cy="56" rx="5" ry="11" fill="' . esc_attr($body_color) . '" transform="rotate(-25 32 56)"/>';
    $svg .= '<ellipse cx="28" cy="67" rx="4.5" ry="9" fill="' . esc_attr($body_color) . '" transform="rotate(-15 28 67)"/>';
    $svg .= '<ellipse cx="26" cy="76" rx="5.5" ry="6.5" fill="' . esc_attr($body_color) . '"/>';

    // Legs with pants
    $svg .= '<ellipse cx="43" cy="78" rx="6.5" ry="13" fill="' . esc_attr($pants_color) . '"/>';
    $svg .= '<ellipse cx="57" cy="78" rx="6.5" ry="13" fill="' . esc_attr($pants_color) . '"/>';

    // Feet
    $svg .= '<ellipse cx="42" cy="91" rx="6" ry="5" fill="' . esc_attr($body_color) . '"/>';
    $svg .= '<ellipse cx="58" cy="91" rx="6" ry="5" fill="' . esc_attr($body_color) . '"/>';

    // Torso with shirt
    if ($gender === 'female') {
        $svg .= '<ellipse cx="50" cy="58" rx="15" ry="14" fill="' . esc_attr($shirt_color) . '"/>';
        $svg .= '<ellipse cx="45" cy="54" rx="5" ry="6" fill="' . esc_attr($shirt_color) . '" opacity="0.4"/>';
        $svg .= '<ellipse cx="55" cy="54" rx="5" ry="6" fill="' . esc_attr($shirt_color) . '" opacity="0.4"/>';
    } else {
        $svg .= '<ellipse cx="50" cy="58" rx="16" ry="15" fill="' . esc_attr($shirt_color) . '"/>';
    }

    // Front arm
    $svg .= '<ellipse cx="68" cy="56" rx="5" ry="11" fill="' . esc_attr($body_color) . '" transform="rotate(25 68 56)"/>';
    $svg .= '<ellipse cx="72" cy="67" rx="4.5" ry="9" fill="' . esc_attr($body_color) . '" transform="rotate(15 72 67)"/>';
    $svg .= '<ellipse cx="74" cy="76" rx="5.5" ry="6.5" fill="' . esc_attr($body_color) . '"/>';

    // Neck
    $svg .= '<ellipse cx="50" cy="46" rx="7" ry="5" fill="' . esc_attr($body_color) . '"/>';

    // Head
    $svg .= '<ellipse cx="50" cy="32" rx="15" ry="17" fill="' . esc_attr($body_color) . '"/>';

    $svg .= '</g>';

    return $svg;
}

/**
 * Generate face
 */
function hs_generate_avatar_face() {
    $svg = '<g id="face">';

    // Eyes
    $svg .= '<circle cx="44" cy="30" r="2.5" fill="#000000"/>';
    $svg .= '<circle cx="56" cy="30" r="2.5" fill="#000000"/>';

    // Eye shine
    $svg .= '<circle cx="45" cy="29.5" r="1" fill="#FFFFFF" opacity="0.7"/>';
    $svg .= '<circle cx="57" cy="29.5" r="1" fill="#FFFFFF" opacity="0.7"/>';

    // Smile
    $svg .= '<path d="M 43 37 Q 50 39.5 57 37" stroke="#000000" stroke-width="1.5" fill="none" stroke-linecap="round"/>';

    $svg .= '</g>';

    return $svg;
}

/**
 * Get default avatar customization
 */
function hs_get_default_avatar_customization() {
    return [
        'body_color' => '#FFE5CC', // Light peachy skin tone
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
