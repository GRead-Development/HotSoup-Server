<?php
/**
 * Avatar REST API Endpoints
 * Handles avatar customization API for mobile apps and web
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register REST API routes
function hs_register_avatar_rest_routes() {
    // Get user's avatar SVG
    register_rest_route('gread/v1', '/avatar/(?P<user_id>\d+)', [
        'methods' => 'GET',
        'callback' => 'hs_rest_get_user_avatar',
        'permission_callback' => '__return_true',
        'args' => [
            'user_id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ],
            'size' => [
                'default' => 200,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0 && $param <= 1000;
                }
            ]
        ]
    ]);

    // Get current user's avatar customization settings
    register_rest_route('gread/v1', '/avatar/customization', [
        'methods' => 'GET',
        'callback' => 'hs_rest_get_avatar_customization',
        'permission_callback' => 'is_user_logged_in'
    ]);

    // Update current user's avatar customization
    register_rest_route('gread/v1', '/avatar/customization', [
        'methods' => 'POST',
        'callback' => 'hs_rest_update_avatar_customization',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'body_color' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color'
            ],
            'gender' => [
                'type' => 'string',
                'enum' => ['male', 'female'],
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'shirt_color' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color'
            ],
            'pants_color' => [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color'
            ],
            'equipped_items' => [
                'type' => 'array',
                'items' => [
                    'type' => 'integer'
                ]
            ]
        ]
    ]);

    // Get available avatar items (with unlock status)
    register_rest_route('gread/v1', '/avatar/items', [
        'methods' => 'GET',
        'callback' => 'hs_rest_get_avatar_items',
        'permission_callback' => 'is_user_logged_in'
    ]);

    // Get specific user's avatar data (for profiles)
    register_rest_route('gread/v1', '/avatar/(?P<user_id>\d+)/data', [
        'methods' => 'GET',
        'callback' => 'hs_rest_get_user_avatar_data',
        'permission_callback' => '__return_true',
        'args' => [
            'user_id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ]
        ]
    ]);

    // Mobile-friendly: Get everything needed for customization UI in one call
    register_rest_route('gread/v1', '/avatar/customization/full', [
        'methods' => 'GET',
        'callback' => 'hs_rest_get_full_customization_data',
        'permission_callback' => 'is_user_logged_in'
    ]);
}
add_action('rest_api_init', 'hs_register_avatar_rest_routes');

/**
 * Get user's avatar SVG
 */
function hs_rest_get_user_avatar($request) {
    $user_id = $request->get_param('user_id');
    $size = $request->get_param('size');

    // Verify user exists
    $user = get_userdata($user_id);
    if (!$user) {
        return new WP_Error(
            'user_not_found',
            'User not found',
            ['status' => 404]
        );
    }

    // Generate avatar SVG
    $svg = hs_generate_user_avatar($user_id, $size);

    // Return as SVG with proper headers
    return new WP_REST_Response($svg, 200, [
        'Content-Type' => 'image/svg+xml'
    ]);
}

/**
 * Get current user's avatar customization settings
 */
function hs_rest_get_avatar_customization($request) {
    $user_id = get_current_user_id();

    $customization = [
        'body_color' => get_user_meta($user_id, 'hs_avatar_body_color', true) ?: '#FFFFFF',
        'gender' => get_user_meta($user_id, 'hs_avatar_gender', true) ?: 'male',
        'shirt_color' => get_user_meta($user_id, 'hs_avatar_shirt_color', true) ?: '#4A90E2',
        'pants_color' => get_user_meta($user_id, 'hs_avatar_pants_color', true) ?: '#2C3E50',
        'equipped_items' => json_decode(get_user_meta($user_id, 'hs_avatar_equipped_items', true) ?: '[]', true)
    ];

    return rest_ensure_response($customization);
}

/**
 * Update current user's avatar customization
 */
function hs_rest_update_avatar_customization($request) {
    $user_id = get_current_user_id();

    // Get parameters
    $body_color = $request->get_param('body_color');
    $gender = $request->get_param('gender');
    $shirt_color = $request->get_param('shirt_color');
    $pants_color = $request->get_param('pants_color');
    $equipped_items = $request->get_param('equipped_items');

    // Update user meta
    if ($body_color !== null) {
        update_user_meta($user_id, 'hs_avatar_body_color', $body_color);
    }

    if ($gender !== null) {
        update_user_meta($user_id, 'hs_avatar_gender', $gender);
    }

    if ($shirt_color !== null) {
        update_user_meta($user_id, 'hs_avatar_shirt_color', $shirt_color);
    }

    if ($pants_color !== null) {
        update_user_meta($user_id, 'hs_avatar_pants_color', $pants_color);
    }

    if ($equipped_items !== null) {
        // Verify all items are unlocked for this user
        if (!empty($equipped_items)) {
            $unlocked_items = hs_get_unlocked_avatar_items($user_id);
            $unlocked_ids = array_column($unlocked_items, 'id');

            foreach ($equipped_items as $item_id) {
                if (!in_array($item_id, $unlocked_ids)) {
                    return new WP_Error(
                        'item_locked',
                        'One or more items are not unlocked',
                        ['status' => 403]
                    );
                }
            }
        }

        update_user_meta($user_id, 'hs_avatar_equipped_items', json_encode($equipped_items));
    }

    // Return updated customization
    return hs_rest_get_avatar_customization($request);
}

/**
 * Get available avatar items with unlock status
 */
function hs_rest_get_avatar_items($request) {
    $user_id = get_current_user_id();

    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';

    // Get all avatar items
    $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY category ASC, display_order ASC, name ASC");

    // Get user stats for unlock checking
    $user_stats = [
        'points' => (int) get_user_meta($user_id, 'user_points', true),
        'books_read' => (int) get_user_meta($user_id, 'hs_completed_books_count', true),
        'pages_read' => (int) get_user_meta($user_id, 'hs_total_pages_read', true),
        'books_added' => (int) get_user_meta($user_id, 'hs_books_added_count', true),
        'approved_reports' => (int) get_user_meta($user_id, 'hs_approved_reports_count', true)
    ];

    // Process items and add unlock status
    $items_with_status = [];
    foreach ($items as $item) {
        $is_unlocked = false;

        // Check if item is default or has no unlock requirements
        if ($item->is_default || empty($item->unlock_metric)) {
            $is_unlocked = true;
        } else {
            // Check unlock requirements
            $metric_value = isset($user_stats[$item->unlock_metric]) ? $user_stats[$item->unlock_metric] : 0;
            $is_unlocked = $metric_value >= $item->unlock_value;
        }

        $items_with_status[] = [
            'id' => $item->id,
            'slug' => $item->slug,
            'name' => $item->name,
            'category' => $item->category,
            'is_unlocked' => $is_unlocked,
            'unlock_metric' => $item->unlock_metric,
            'unlock_value' => $item->unlock_value,
            'unlock_message' => $item->unlock_message,
            'svg_preview' => !empty($item->svg_data) ? hs_generate_item_preview($item->svg_data) : null
        ];
    }

    // Group by category
    $grouped = [];
    foreach ($items_with_status as $item) {
        $category = $item['category'];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $item;
    }

    return rest_ensure_response([
        'items' => $items_with_status,
        'grouped' => $grouped
    ]);
}

/**
 * Get unlocked avatar items for a user
 */
function hs_get_unlocked_avatar_items($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';

    $items = $wpdb->get_results("SELECT * FROM $table_name");

    $user_stats = [
        'points' => (int) get_user_meta($user_id, 'user_points', true),
        'books_read' => (int) get_user_meta($user_id, 'hs_completed_books_count', true),
        'pages_read' => (int) get_user_meta($user_id, 'hs_total_pages_read', true),
        'books_added' => (int) get_user_meta($user_id, 'hs_books_added_count', true),
        'approved_reports' => (int) get_user_meta($user_id, 'hs_approved_reports_count', true)
    ];

    $unlocked = [];
    foreach ($items as $item) {
        if ($item->is_default || empty($item->unlock_metric)) {
            $unlocked[] = $item;
        } else {
            $metric_value = isset($user_stats[$item->unlock_metric]) ? $user_stats[$item->unlock_metric] : 0;
            if ($metric_value >= $item->unlock_value) {
                $unlocked[] = $item;
            }
        }
    }

    return $unlocked;
}

/**
 * Generate preview SVG for an item
 */
function hs_generate_item_preview($svg_data, $size = 100) {
    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="%d" height="%d">%s</svg>',
        $size,
        $size,
        $svg_data
    );
}

/**
 * Get user's avatar data (for profiles and other displays)
 */
function hs_rest_get_user_avatar_data($request) {
    $user_id = $request->get_param('user_id');

    // Verify user exists
    $user = get_userdata($user_id);
    if (!$user) {
        return new WP_Error(
            'user_not_found',
            'User not found',
            ['status' => 404]
        );
    }

    $customization = [
        'user_id' => $user_id,
        'username' => $user->display_name,
        'body_color' => get_user_meta($user_id, 'hs_avatar_body_color', true) ?: '#FFFFFF',
        'gender' => get_user_meta($user_id, 'hs_avatar_gender', true) ?: 'male',
        'shirt_color' => get_user_meta($user_id, 'hs_avatar_shirt_color', true) ?: '#4A90E2',
        'pants_color' => get_user_meta($user_id, 'hs_avatar_pants_color', true) ?: '#2C3E50',
        'equipped_items' => json_decode(get_user_meta($user_id, 'hs_avatar_equipped_items', true) ?: '[]', true),
        'avatar_url' => rest_url('gread/v1/avatar/' . $user_id)
    ];

    return rest_ensure_response($customization);
}

/**
 * Get full customization data for mobile apps
 * This endpoint returns everything needed to build the customization UI in one call
 */
function hs_rest_get_full_customization_data($request) {
    $user_id = get_current_user_id();

    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';

    // Get current customization
    $current_customization = [
        'body_color' => get_user_meta($user_id, 'hs_avatar_body_color', true) ?: '#FFFFFF',
        'gender' => get_user_meta($user_id, 'hs_avatar_gender', true) ?: 'male',
        'shirt_color' => get_user_meta($user_id, 'hs_avatar_shirt_color', true) ?: '#4A90E2',
        'pants_color' => get_user_meta($user_id, 'hs_avatar_pants_color', true) ?: '#2C3E50',
        'equipped_items' => json_decode(get_user_meta($user_id, 'hs_avatar_equipped_items', true) ?: '[]', true)
    ];

    // Get all items
    $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY category ASC, display_order ASC, name ASC");

    // Get user stats
    $user_stats = [
        'points' => (int) get_user_meta($user_id, 'user_points', true),
        'books_read' => (int) get_user_meta($user_id, 'hs_completed_books_count', true),
        'pages_read' => (int) get_user_meta($user_id, 'hs_total_pages_read', true),
        'books_added' => (int) get_user_meta($user_id, 'hs_books_added_count', true),
        'approved_reports' => (int) get_user_meta($user_id, 'hs_approved_reports_count', true)
    ];

    // Process items
    $items_by_category = [];
    foreach ($items as $item) {
        $is_unlocked = false;

        if ($item->is_default || empty($item->unlock_metric)) {
            $is_unlocked = true;
        } else {
            $metric_value = isset($user_stats[$item->unlock_metric]) ? $user_stats[$item->unlock_metric] : 0;
            $is_unlocked = $metric_value >= $item->unlock_value;

            // Include progress information
            $progress = $item->unlock_value > 0 ? ($metric_value / $item->unlock_value) * 100 : 0;
            $item->unlock_progress = min(100, $progress);
            $item->current_value = $metric_value;
        }

        $category = $item->category;
        if (!isset($items_by_category[$category])) {
            $items_by_category[$category] = [
                'category_name' => ucwords(str_replace('_', ' ', $category)),
                'items' => []
            ];
        }

        $items_by_category[$category]['items'][] = [
            'id' => (int) $item->id,
            'slug' => $item->slug,
            'name' => $item->name,
            'category' => $item->category,
            'svg_data' => $item->svg_data,
            'is_unlocked' => $is_unlocked,
            'unlock_metric' => $item->unlock_metric,
            'unlock_value' => (int) $item->unlock_value,
            'unlock_message' => $item->unlock_message,
            'unlock_progress' => isset($item->unlock_progress) ? round($item->unlock_progress, 1) : 100,
            'current_value' => isset($item->current_value) ? $item->current_value : 0
        ];
    }

    // Add "None" option for each category
    foreach ($items_by_category as $category => &$data) {
        array_unshift($data['items'], [
            'id' => 0,
            'slug' => 'none',
            'name' => 'None',
            'category' => $category,
            'svg_data' => '',
            'is_unlocked' => true,
            'unlock_metric' => null,
            'unlock_value' => 0,
            'unlock_message' => '',
            'unlock_progress' => 100,
            'current_value' => 0
        ]);
    }

    return rest_ensure_response([
        'current_customization' => $current_customization,
        'user_stats' => $user_stats,
        'categories' => array_values($items_by_category),
        'avatar_url' => rest_url('gread/v1/avatar/' . $user_id),
        'customization_endpoint' => rest_url('gread/v1/avatar/customization'),
        'gender_options' => [
            ['value' => 'male', 'label' => 'Male'],
            ['value' => 'female', 'label' => 'Female']
        ]
    ]);
}
