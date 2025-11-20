<?php
/**
 * User Milestones API
 *
 * Tracks and awards user milestones/achievements
 * Requires: Activity Tracking feature
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register milestones REST routes
 */
function hs_register_milestones_routes() {
    register_rest_route('gread/v1', '/milestones', array(
        'methods' => 'GET',
        'callback' => 'hs_get_user_milestones',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'type' => array(
                'type' => 'string',
                'description' => 'Filter by milestone type',
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_milestones_routes');

/**
 * Get user milestones
 */
function hs_get_user_milestones($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_milestones';

    $user_id = get_current_user_id();
    $type = $request->get_param('type');

    $where = 'user_id = %d';
    $where_args = array($user_id);

    if ($type) {
        $where .= ' AND milestone_type = %s';
        $where_args[] = $type;
    }

    $query = "SELECT * FROM $table WHERE $where ORDER BY achieved_at DESC";

    $milestones = $wpdb->get_results($wpdb->prepare($query, $where_args));

    // Parse metadata
    foreach ($milestones as $milestone) {
        if ($milestone->metadata) {
            $milestone->metadata = json_decode($milestone->metadata);
        }
    }

    return new WP_REST_Response(array(
        'success' => true,
        'milestones' => $milestones,
        'total_milestones' => count($milestones),
    ), 200);
}

/**
 * Check and award milestones
 *
 * Automatically called when library activity is tracked
 */
function hs_check_and_award_milestones($user_id, $activity_type, $book_id = null) {
    global $wpdb;
    $milestones_table = $wpdb->prefix . 'hs_user_milestones';

    // Define milestones to check
    $milestones_to_check = array();

    if ($activity_type === 'completed') {
        // Check books completed milestones
        $completed_count = (int)get_user_meta($user_id, 'hs_completed_books_count', true);

        $milestone_values = array(1, 5, 10, 25, 50, 100, 250, 500, 1000);

        foreach ($milestone_values as $value) {
            if ($completed_count === $value) {
                $milestones_to_check[] = array(
                    'type' => 'books_completed',
                    'value' => $value,
                );
            }
        }
    } elseif ($activity_type === 'added') {
        // Check books added milestones
        $added_count = (int)get_user_meta($user_id, 'hs_books_added_count', true);

        $milestone_values = array(1, 10, 50, 100, 500);

        foreach ($milestone_values as $value) {
            if ($added_count === $value) {
                $milestones_to_check[] = array(
                    'type' => 'books_added',
                    'value' => $value,
                );
            }
        }
    }

    // Award milestones
    foreach ($milestones_to_check as $milestone) {
        // Check if already awarded
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $milestones_table
             WHERE user_id = %d AND milestone_type = %s AND milestone_value = %d",
            $user_id,
            $milestone['type'],
            $milestone['value']
        ));

        if (!$exists) {
            $metadata = array(
                'book_id' => $book_id,
                'milestone_name' => hs_get_milestone_name($milestone['type'], $milestone['value']),
            );

            $wpdb->insert(
                $milestones_table,
                array(
                    'user_id' => $user_id,
                    'milestone_type' => $milestone['type'],
                    'milestone_value' => $milestone['value'],
                    'book_id' => $book_id,
                    'metadata' => json_encode($metadata),
                ),
                array('%d', '%s', '%d', '%d', '%s')
            );

            // Trigger action for notifications, etc.
            do_action('hs_milestone_achieved', $user_id, $milestone['type'], $milestone['value'], $book_id);
        }
    }
}

// Hook into activity tracking
add_action('hs_library_activity_tracked', function($user_id, $book_id, $activity_type, $activity_data) {
    hs_check_and_award_milestones($user_id, $activity_type, $book_id);
}, 10, 4);

/**
 * Get milestone name
 */
function hs_get_milestone_name($type, $value) {
    $names = array(
        'books_completed' => array(
            1 => 'First Book Completed',
            5 => 'Book Worm',
            10 => 'Avid Reader',
            25 => 'Reading Enthusiast',
            50 => 'Half Century Reader',
            100 => 'Century Club',
            250 => 'Reading Legend',
            500 => 'Master Reader',
            1000 => 'Reading Titan',
        ),
        'books_added' => array(
            1 => 'Library Started',
            10 => 'Growing Collection',
            50 => 'Book Collector',
            100 => 'Library Builder',
            500 => 'Bibliophile',
        ),
    );

    return $names[$type][$value] ?? "{$type}: {$value}";
}

/**
 * Create milestones table
 */
function hs_create_milestones_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'hs_user_milestones';

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        milestone_type varchar(100) NOT NULL,
        milestone_value int(11) NOT NULL,
        achieved_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        book_id bigint(20) unsigned DEFAULT NULL,
        metadata text DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_milestone (user_id, milestone_type, milestone_value),
        KEY user_id (user_id),
        KEY milestone_type (milestone_type),
        KEY achieved_at (achieved_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('hs_milestones_db_version', '1.0');
}

// Auto-create table on admin init if needed
add_action('admin_init', function() {
    if (get_option('hs_milestones_db_version') !== '1.0') {
        hs_create_milestones_table();
    }
});
