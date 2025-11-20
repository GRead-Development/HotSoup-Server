<?php
/**
 * Book Chapters API
 *
 * Users can submit chapter lists for books
 * Includes admin approval workflow and points system
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register chapters REST routes
 */
function hs_register_chapters_routes() {
    // Get chapters for a book
    register_rest_route('gread/v1', '/books/(?P<book_id>\d+)/chapters', array(
        'methods' => 'GET',
        'callback' => 'hs_get_book_chapters',
        'permission_callback' => '__return_true',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('approved', 'pending', 'all'),
                'default' => 'approved',
            ),
        ),
    ));

    // Submit chapters for a book
    register_rest_route('gread/v1', '/books/(?P<book_id>\d+)/chapters', array(
        'methods' => 'POST',
        'callback' => 'hs_submit_book_chapters',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'chapters' => array(
                'required' => true,
                'type' => 'array',
                'description' => 'Array of chapter objects with chapter_number, chapter_title, start_page, end_page',
            ),
        ),
    ));

    // Approve/reject chapter submission (admin only)
    register_rest_route('gread/v1', '/chapters/(?P<chapter_id>\d+)/review', array(
        'methods' => 'PUT',
        'callback' => 'hs_review_chapter_submission',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'chapter_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'status' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('approved', 'rejected'),
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_chapters_routes');

/**
 * Get book chapters
 */
function hs_get_book_chapters($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_book_chapters';

    $book_id = $request->get_param('book_id');
    $status = $request->get_param('status');

    $where = 'book_id = %d';
    $where_args = array($book_id);

    if ($status !== 'all') {
        $where .= ' AND status = %s';
        $where_args[] = $status;
    }

    $query = "SELECT bc.*, u.display_name as submitted_by_name
              FROM $table bc
              LEFT JOIN {$wpdb->users} u ON bc.submitted_by = u.ID
              WHERE $where
              ORDER BY bc.chapter_number ASC";

    $chapters = $wpdb->get_results($wpdb->prepare($query, $where_args));

    return new WP_REST_Response(array(
        'success' => true,
        'chapters' => $chapters,
        'count' => count($chapters),
    ), 200);
}

/**
 * Submit book chapters
 */
function hs_submit_book_chapters($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_book_chapters';

    $book_id = $request->get_param('book_id');
    $chapters = $request->get_param('chapters');
    $user_id = get_current_user_id();

    // Verify book exists
    $book = get_post($book_id);
    if (!$book || $book->post_type !== 'book') {
        return new WP_Error('invalid_book', 'Book not found', array('status' => 404));
    }

    // Check if chapters already exist for this book
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE book_id = %d AND status = 'approved'",
        $book_id
    ));

    if ($existing > 0) {
        return new WP_Error('already_exists', 'Approved chapters already exist for this book', array('status' => 400));
    }

    if (!is_array($chapters) || empty($chapters)) {
        return new WP_Error('invalid_data', 'chapters must be a non-empty array', array('status' => 400));
    }

    $inserted = 0;

    foreach ($chapters as $chapter) {
        if (!isset($chapter['chapter_number']) || !isset($chapter['chapter_title'])) {
            continue;
        }

        $result = $wpdb->insert(
            $table,
            array(
                'book_id' => $book_id,
                'chapter_number' => $chapter['chapter_number'],
                'chapter_title' => sanitize_text_field($chapter['chapter_title']),
                'start_page' => isset($chapter['start_page']) ? (int)$chapter['start_page'] : null,
                'end_page' => isset($chapter['end_page']) ? (int)$chapter['end_page'] : null,
                'submitted_by' => $user_id,
                'status' => 'pending',
            ),
            array('%d', '%d', '%s', '%d', '%d', '%d', '%s')
        );

        if ($result !== false) {
            $inserted++;
        }
    }

    // Award points (pending approval)
    $points_per_chapter = 5;
    $pending_points = $inserted * $points_per_chapter;

    return new WP_REST_Response(array(
        'success' => true,
        'message' => "Successfully submitted {$inserted} chapters for review",
        'chapters_submitted' => $inserted,
        'pending_points' => $pending_points,
    ), 201);
}

/**
 * Review chapter submission
 */
function hs_review_chapter_submission($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_book_chapters';

    $chapter_id = $request->get_param('chapter_id');
    $status = $request->get_param('status');
    $admin_id = get_current_user_id();

    // Get chapter details
    $chapter = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $chapter_id
    ));

    if (!$chapter) {
        return new WP_Error('not_found', 'Chapter not found', array('status' => 404));
    }

    // Update status
    $wpdb->update(
        $table,
        array(
            'status' => $status,
            'approved_by' => $admin_id,
            'approved_at' => current_time('mysql'),
        ),
        array('id' => $chapter_id),
        array('%s', '%d', '%s'),
        array('%d')
    );

    // Award points if approved
    if ($status === 'approved') {
        $points = 5;
        hs_award_contribution_points($chapter->submitted_by, $points, 'chapter_approved', $chapter_id);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Chapter approved',
            'points_awarded' => $points,
        ), 200);
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Chapter rejected',
    ), 200);
}

/**
 * Award contribution points
 */
function hs_award_contribution_points($user_id, $points, $reason, $related_id = null) {
    // Update user's total points
    $current_points = (int)get_user_meta($user_id, 'hs_contribution_points', true);
    $new_points = $current_points + $points;
    update_user_meta($user_id, 'hs_contribution_points', $new_points);

    // Log the points award
    do_action('hs_points_awarded', $user_id, $points, $reason, $related_id);
}

/**
 * Create chapters table
 */
function hs_create_chapters_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'hs_book_chapters';

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        book_id bigint(20) unsigned NOT NULL,
        chapter_number int(11) NOT NULL,
        chapter_title varchar(500) NOT NULL,
        start_page int(11) DEFAULT NULL,
        end_page int(11) DEFAULT NULL,
        submitted_by bigint(20) unsigned NOT NULL,
        status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        approved_at datetime DEFAULT NULL,
        approved_by bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (id),
        KEY book_id (book_id),
        KEY submitted_by (submitted_by),
        KEY status (status),
        KEY chapter_number (chapter_number)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('hs_chapters_db_version', '1.0');
}

// Auto-create table on admin init if needed
add_action('admin_init', function() {
    if (get_option('hs_chapters_db_version') !== '1.0') {
        hs_create_chapters_table();
    }
});
