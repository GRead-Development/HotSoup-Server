<?php
/**
 * Library Enhancements API
 *
 * Features:
 * - Random book selector from uncompleted books
 * - User milestones tracking
 * - Library activity tracking with timestamps
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register library enhancement routes
 */
function hs_register_library_enhancements_routes() {
    // Get random unread book
    register_rest_route('gread/v1', '/books/random', array(
        'methods' => 'GET',
        'callback' => 'hs_get_random_unread_book',
        'permission_callback' => 'gread_check_user_permission',
    ));

    // Get user milestones
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
add_action('rest_api_init', 'hs_register_library_enhancements_routes');

/**
 * Get random unread book from user's library
 */
function hs_get_random_unread_book($request) {
    global $wpdb;

    $user_id = get_current_user_id();

    // Get all books in user's library that are not completed
    $books = $wpdb->get_results($wpdb->prepare(
        "SELECT um.meta_value as book_id, p.post_title, p.ID,
                pm_author.meta_value as book_author,
                pm_pages.meta_value as total_pages,
                um2.meta_value as current_page
         FROM {$wpdb->usermeta} um
         LEFT JOIN {$wpdb->posts} p ON um.meta_value = p.ID
         LEFT JOIN {$wpdb->postmeta} pm_author ON p.ID = pm_author.post_id AND pm_author.meta_key = 'book_author'
         LEFT JOIN {$wpdb->postmeta} pm_pages ON p.ID = pm_pages.post_id AND pm_pages.meta_key = 'nop'
         LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = %d AND um2.meta_key = CONCAT('book_', um.meta_value, '_completed')
         WHERE um.user_id = %d
         AND um.meta_key LIKE 'book_%%_in_library'
         AND um.meta_value IS NOT NULL
         AND (um2.meta_value IS NULL OR um2.meta_value != '1')
         AND p.post_type = 'book'
         AND p.post_status = 'publish'",
        $user_id,
        $user_id
    ));

    if (empty($books)) {
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'No unread books in your library',
        ), 200);
    }

    // Select random book
    $random_book = $books[array_rand($books)];

    // Get current page
    $current_page = get_user_meta($user_id, "book_{$random_book->ID}_current_page", true) ?: 0;

    $book_data = array(
        'id' => $random_book->ID,
        'title' => $random_book->post_title,
        'author' => $random_book->book_author,
        'total_pages' => (int)$random_book->total_pages,
        'current_page' => (int)$current_page,
        'progress_percentage' => $random_book->total_pages > 0
            ? round(($current_page / $random_book->total_pages) * 100, 2)
            : 0,
    );

    return new WP_REST_Response(array(
        'success' => true,
        'book' => $book_data,
        'message' => 'How about reading ' . $random_book->post_title . '?',
    ), 200);
}

/**
 * Track library activity
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

    // Check for milestones after activity
    hs_check_and_award_milestones($user_id, $activity_type, $book_id);
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
 * Format duration
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
 * Check and award milestones
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
        }
    }
}

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
