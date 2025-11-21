<?php
/**
 * Activity Tracking - Core Functions
 *
 * Chunk 1.1: Core tracking functionality
 * LOC: ~150
 *
 * What this does:
 * - Creates database table for activity tracking
 * - Provides core tracking function
 * - Helper functions for querying activities
 *
 * API endpoints are in: includes/api/activity-tracking-api.php
 */

if (!defined('ABSPATH')) {
    exit;
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

// Auto-create table on admin init
add_action('admin_init', function() {
    if (get_option('hs_activity_tracking_db_version') !== '1.0') {
        hs_create_activity_tracking_table();
    }
});

/**
 * Track library activity
 *
 * MAIN FUNCTION - Use this to track any library action
 *
 * @param int $user_id User ID
 * @param int $book_id Book ID
 * @param string $activity_type Type: 'added', 'started', 'completed', 'removed', 'progress_update'
 * @param array $activity_data Optional metadata
 * @return int|false Activity ID or false on failure
 */
function hs_track_library_activity($user_id, $book_id, $activity_type, $activity_data = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_library_activity';

    $data_json = $activity_data ? json_encode($activity_data) : null;

    $result = $wpdb->insert(
        $table,
        array(
            'user_id' => $user_id,
            'book_id' => $book_id,
            'activity_type' => $activity_type,
            'activity_data' => $data_json,
        ),
        array('%d', '%d', '%s', '%s')
    );

    if ($result === false) {
        return false;
    }

    $activity_id = $wpdb->insert_id;

    // Trigger action for other features to hook into (like milestones)
    do_action('hs_library_activity_tracked', $user_id, $book_id, $activity_type, $activity_data);

    return $activity_id;
}

/**
 * Get library activity for a user
 *
 * @param int $user_id User ID
 * @param array $args Optional arguments (book_id, activity_type, limit, offset)
 * @return array Activity records
 */
function hs_get_library_activity_for_user($user_id, $args = array()) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_library_activity';

    $defaults = array(
        'book_id' => null,
        'activity_type' => null,
        'limit' => 50,
        'offset' => 0,
    );

    $args = wp_parse_args($args, $defaults);

    $where = array('user_id = %d');
    $where_values = array($user_id);

    if ($args['book_id']) {
        $where[] = 'book_id = %d';
        $where_values[] = $args['book_id'];
    }

    if ($args['activity_type']) {
        $where[] = 'activity_type = %s';
        $where_values[] = $args['activity_type'];
    }

    $where_clause = implode(' AND ', $where);
    $where_values[] = $args['limit'];
    $where_values[] = $args['offset'];

    $query = "SELECT la.*, p.post_title as book_title
              FROM $table la
              LEFT JOIN {$wpdb->posts} p ON la.book_id = p.ID
              WHERE $where_clause
              ORDER BY la.created_at DESC
              LIMIT %d OFFSET %d";

    $activities = $wpdb->get_results($wpdb->prepare($query, $where_values));

    // Parse JSON data
    foreach ($activities as $activity) {
        if ($activity->activity_data) {
            $activity->activity_data = json_decode($activity->activity_data);
        }
    }

    return $activities;
}

/**
 * Get reading stats for a specific book
 *
 * @param int $user_id User ID
 * @param int $book_id Book ID
 * @return array Stats (added_at, started_at, completed_at, duration, etc.)
 */
function hs_get_book_reading_stats($user_id, $book_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_library_activity';

    // Get when book was added
    $added = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table
         WHERE user_id = %d AND book_id = %d AND activity_type = 'added'
         ORDER BY created_at ASC LIMIT 1",
        $user_id,
        $book_id
    ));

    // Get when book was started
    $started = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table
         WHERE user_id = %d AND book_id = %d AND activity_type IN ('started', 'progress_update')
         ORDER BY created_at ASC LIMIT 1",
        $user_id,
        $book_id
    ));

    // Get when book was completed
    $completed = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table
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
            'formatted' => hs_format_reading_duration($duration),
        );
    }

    // Get total progress updates
    $stats['progress_updates_count'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table
         WHERE user_id = %d AND book_id = %d AND activity_type = 'progress_update'",
        $user_id,
        $book_id
    ));

    return $stats;
}

/**
 * Format reading duration for display
 *
 * @param DateInterval $duration Duration object
 * @return string Formatted duration
 */
function hs_format_reading_duration($duration) {
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
