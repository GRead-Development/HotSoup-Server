<?php
/**
 * Activity Tracking API
 *
 * Tracks user library activities with timestamps
 * Required by: Milestones feature
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register activity tracking REST routes
 */
function hs_register_activity_tracking_routes() {
    // Get library activity
    register_rest_route('gread/v1', '/library/activity', array(
        'methods' => 'GET',
        'callback' => 'hs_get_library_activity',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'book_id' => array(
                'type' => 'integer',
                'description' => 'Filter by book',
            ),
            'activity_type' => array(
                'type' => 'string',
                'enum' => array('added', 'started', 'completed', 'removed', 'progress_update'),
            ),
            'limit' => array(
                'type' => 'integer',
                'default' => 50,
            ),
        ),
    ));

    // Get book reading stats (when added, when completed, duration)
    register_rest_route('gread/v1', '/books/(?P<book_id>\d+)/reading-stats', array(
        'methods' => 'GET',
        'callback' => 'hs_get_book_reading_stats',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_activity_tracking_routes');

/**
 * Track library activity
 *
 * Call this function whenever a user performs a library action
 *
 * @param int $user_id User ID
 * @param int $book_id Book ID
 * @param string $activity_type Type: 'added', 'started', 'completed', 'removed', 'progress_update'
 * @param array $activity_data Optional metadata
 */
function hs_track_library_activity($user_id, $book_id, $activity_type, $activity_data = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_library_activity';

    $data_json = $activity_data ? json_encode($activity_data) : null;

    $wpdb->insert(
        $table,
        array(
            'user_id' => $user_id,
            'book_id' => $book_id,
            'activity_type' => $activity_type,
            'activity_data' => $data_json,
        ),
        array('%d', '%d', '%s', '%s')
    );

    // Trigger action for other features to hook into
    do_action('hs_library_activity_tracked', $user_id, $book_id, $activity_type, $activity_data);
}

/**
 * Get library activity
 */
function hs_get_library_activity($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_library_activity';

    $user_id = get_current_user_id();
    $book_id = $request->get_param('book_id');
    $activity_type = $request->get_param('activity_type');
    $limit = $request->get_param('limit');

    $where = array('user_id = %d');
    $where_args = array($user_id);

    if ($book_id) {
        $where[] = 'book_id = %d';
        $where_args[] = $book_id;
    }

    if ($activity_type) {
        $where[] = 'activity_type = %s';
        $where_args[] = $activity_type;
    }

    $where_clause = implode(' AND ', $where);
    $where_args[] = $limit;

    $query = "SELECT la.*, p.post_title as book_title
              FROM $table la
              LEFT JOIN {$wpdb->posts} p ON la.book_id = p.ID
              WHERE $where_clause
              ORDER BY la.created_at DESC
              LIMIT %d";

    $activities = $wpdb->get_results($wpdb->prepare($query, $where_args));

    // Parse JSON data
    foreach ($activities as $activity) {
        if ($activity->activity_data) {
            $activity->activity_data = json_decode($activity->activity_data);
        }
    }

    return new WP_REST_Response(array(
        'success' => true,
        'activities' => $activities,
    ), 200);
}

/**
 * Get book reading stats
 */
function hs_get_book_reading_stats($request) {
    global $wpdb;
    $activity_table = $wpdb->prefix . 'hs_library_activity';

    $user_id = get_current_user_id();
    $book_id = $request->get_param('book_id');

    // Get when book was added
    $added = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $activity_table
         WHERE user_id = %d AND book_id = %d AND activity_type = 'added'
         ORDER BY created_at ASC LIMIT 1",
        $user_id,
        $book_id
    ));

    // Get when book was started (first progress update)
    $started = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $activity_table
         WHERE user_id = %d AND book_id = %d AND activity_type IN ('started', 'progress_update')
         ORDER BY created_at ASC LIMIT 1",
        $user_id,
        $book_id
    ));

    // Get when book was completed
    $completed = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $activity_table
         WHERE user_id = %d AND book_id = %d AND activity_type = 'completed'
         ORDER BY created_at DESC LIMIT 1",
        $user_id,
        $book_id
    ));

    $stats = array(
        'book_id' => $book_id,
        'added_at' => $added ? $added->created_at : null,
        'started_at' => $started ? $started->created_at : null,
        'completed_at' => $completed ? $completed->created_at : null,
        'is_completed' => (bool)$completed,
    );

    // Calculate reading duration
    if ($started && $completed) {
        $start_date = new DateTime($started->created_at);
        $end_date = new DateTime($completed->created_at);
        $duration = $start_date->diff($end_date);

        $stats['reading_duration'] = array(
            'days' => $duration->days,
            'formatted' => hs_format_duration($duration),
        );
    }

    // Get total progress updates
    $stats['progress_updates_count'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $activity_table
         WHERE user_id = %d AND book_id = %d AND activity_type = 'progress_update'",
        $user_id,
        $book_id
    ));

    return new WP_REST_Response(array(
        'success' => true,
        'stats' => $stats,
    ), 200);
}

/**
 * Format duration for display
 */
function hs_format_duration($duration) {
    if ($duration->days == 0) {
        return 'Less than a day';
    } elseif ($duration->days == 1) {
        return '1 day';
    } elseif ($duration->days < 7) {
        return $duration->days . ' days';
    } elseif ($duration->days < 30) {
        $weeks = floor($duration->days / 7);
        return $weeks . ($weeks == 1 ? ' week' : ' weeks');
    } elseif ($duration->days < 365) {
        $months = floor($duration->days / 30);
        return $months . ($months == 1 ? ' month' : ' months');
    } else {
        $years = floor($duration->days / 365);
        return $years . ($years == 1 ? ' year' : ' years');
    }
}

/**
 * Create activity tracking table
 */
function hs_create_activity_tracking_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'hs_library_activity';

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        book_id bigint(20) unsigned NOT NULL,
        activity_type enum('added','started','completed','removed','progress_update') NOT NULL,
        activity_data text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY book_id (book_id),
        KEY activity_type (activity_type),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('hs_activity_tracking_db_version', '1.0');
}

// Auto-create table on admin init if needed
add_action('admin_init', function() {
    if (get_option('hs_activity_tracking_db_version') !== '1.0') {
        hs_create_activity_tracking_table();
    }
});
