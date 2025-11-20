<?php
/**
 * User Summaries API
 *
 * Users can submit summaries (chapter, character, plot, theme, overall)
 * Includes points system, voting, and quality scoring
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register summaries REST routes
 */
function hs_register_summaries_routes() {
    // Get summaries for a book
    register_rest_route('gread/v1', '/books/(?P<book_id>\d+)/summaries', array(
        'methods' => 'GET',
        'callback' => 'hs_get_book_summaries',
        'permission_callback' => '__return_true',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'type' => array(
                'type' => 'string',
                'enum' => array('chapter', 'character', 'plot', 'theme', 'overall'),
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('approved', 'pending', 'all'),
                'default' => 'approved',
            ),
        ),
    ));

    // Submit a summary
    register_rest_route('gread/v1', '/summaries', array(
        'methods' => 'POST',
        'callback' => 'hs_submit_summary',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'summary_type' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('chapter', 'character', 'plot', 'theme', 'overall'),
            ),
            'title' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'content' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'chapter_id' => array(
                'type' => 'integer',
                'description' => 'Required if summary_type is chapter',
            ),
            'character_name' => array(
                'type' => 'string',
                'description' => 'Required if summary_type is character',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // Get single summary
    register_rest_route('gread/v1', '/summaries/(?P<summary_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'hs_get_summary',
        'permission_callback' => '__return_true',
        'args' => array(
            'summary_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Vote on summary
    register_rest_route('gread/v1', '/summaries/(?P<summary_id>\d+)/vote', array(
        'methods' => 'POST',
        'callback' => 'hs_vote_on_summary',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'summary_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'vote_type' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('helpful', 'not_helpful'),
            ),
        ),
    ));

    // Review summary (admin only)
    register_rest_route('gread/v1', '/summaries/(?P<summary_id>\d+)/review', array(
        'methods' => 'PUT',
        'callback' => 'hs_review_summary',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'summary_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'status' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('approved', 'rejected'),
            ),
            'quality_score' => array(
                'type' => 'number',
                'minimum' => 0,
                'maximum' => 5,
            ),
        ),
    ));

    // Get user's contribution stats
    register_rest_route('gread/v1', '/user/contributions', array(
        'methods' => 'GET',
        'callback' => 'hs_get_user_contributions',
        'permission_callback' => 'gread_check_user_permission',
    ));
}
add_action('rest_api_init', 'hs_register_summaries_routes');

/**
 * Get book summaries
 */
function hs_get_book_summaries($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_summaries';

    $book_id = $request->get_param('book_id');
    $type = $request->get_param('type');
    $status = $request->get_param('status');

    $where = array('book_id = %d');
    $where_args = array($book_id);

    if ($type) {
        $where[] = 'summary_type = %s';
        $where_args[] = $type;
    }

    if ($status !== 'all') {
        $where[] = 'status = %s';
        $where_args[] = $status;
    }

    $where_clause = implode(' AND ', $where);

    $query = "SELECT s.*, u.display_name as author_name
              FROM $table s
              LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
              WHERE $where_clause
              ORDER BY s.helpful_votes DESC, s.created_at DESC";

    $summaries = $wpdb->get_results($wpdb->prepare($query, $where_args));

    return new WP_REST_Response(array(
        'success' => true,
        'summaries' => $summaries,
        'count' => count($summaries),
    ), 200);
}

/**
 * Submit summary
 */
function hs_submit_summary($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_summaries';

    $user_id = get_current_user_id();
    $book_id = $request->get_param('book_id');
    $summary_type = $request->get_param('summary_type');
    $title = $request->get_param('title');
    $content = $request->get_param('content');
    $chapter_id = $request->get_param('chapter_id');
    $character_name = $request->get_param('character_name');

    // Verify book exists
    $book = get_post($book_id);
    if (!$book || $book->post_type !== 'book') {
        return new WP_Error('invalid_book', 'Book not found', array('status' => 404));
    }

    // Validation
    if ($summary_type === 'chapter' && !$chapter_id) {
        return new WP_Error('missing_chapter', 'chapter_id is required for chapter summaries', array('status' => 400));
    }

    if ($summary_type === 'character' && !$character_name) {
        return new WP_Error('missing_character', 'character_name is required for character summaries', array('status' => 400));
    }

    // Check content length (minimum 50 characters)
    if (strlen($content) < 50) {
        return new WP_Error('content_too_short', 'Summary content must be at least 50 characters', array('status' => 400));
    }

    $result = $wpdb->insert(
        $table,
        array(
            'book_id' => $book_id,
            'user_id' => $user_id,
            'summary_type' => $summary_type,
            'title' => $title,
            'content' => $content,
            'chapter_id' => $chapter_id,
            'character_name' => $character_name,
            'status' => 'pending',
        ),
        array('%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to submit summary', array('status' => 500));
    }

    $summary_id = $wpdb->insert_id;

    // Calculate base points (pending approval)
    $points = hs_calculate_summary_points($content, $summary_type);

    return new WP_REST_Response(array(
        'success' => true,
        'summary_id' => $summary_id,
        'message' => 'Summary submitted for review',
        'pending_points' => $points,
    ), 201);
}

/**
 * Calculate points for summary
 */
function hs_calculate_summary_points($content, $type) {
    $base_points = array(
        'chapter' => 10,
        'character' => 15,
        'plot' => 20,
        'theme' => 25,
        'overall' => 30,
    );

    $points = $base_points[$type] ?? 10;

    // Bonus for longer, quality content
    $word_count = str_word_count($content);
    if ($word_count > 200) {
        $points += 5;
    }
    if ($word_count > 500) {
        $points += 10;
    }

    return $points;
}

/**
 * Get single summary
 */
function hs_get_summary($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_summaries';

    $summary_id = $request->get_param('summary_id');

    $summary = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, u.display_name as author_name, p.post_title as book_title
         FROM $table s
         LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
         LEFT JOIN {$wpdb->posts} p ON s.book_id = p.ID
         WHERE s.id = %d",
        $summary_id
    ));

    if (!$summary) {
        return new WP_Error('not_found', 'Summary not found', array('status' => 404));
    }

    // Increment view count
    $wpdb->query($wpdb->prepare(
        "UPDATE $table SET views = views + 1 WHERE id = %d",
        $summary_id
    ));

    // Check if current user has voted
    $user_id = get_current_user_id();
    if ($user_id) {
        $votes_table = $wpdb->prefix . 'hs_summary_votes';
        $user_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT vote_type FROM $votes_table WHERE summary_id = %d AND user_id = %d",
            $summary_id,
            $user_id
        ));
        $summary->user_vote = $user_vote;
    }

    return new WP_REST_Response(array(
        'success' => true,
        'summary' => $summary,
    ), 200);
}

/**
 * Vote on summary
 */
function hs_vote_on_summary($request) {
    global $wpdb;
    $votes_table = $wpdb->prefix . 'hs_summary_votes';
    $summaries_table = $wpdb->prefix . 'hs_user_summaries';

    $summary_id = $request->get_param('summary_id');
    $vote_type = $request->get_param('vote_type');
    $user_id = get_current_user_id();

    // Check if summary exists
    $summary = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $summaries_table WHERE id = %d",
        $summary_id
    ));

    if (!$summary) {
        return new WP_Error('not_found', 'Summary not found', array('status' => 404));
    }

    // Can't vote on own summary
    if ($summary->user_id == $user_id) {
        return new WP_Error('own_summary', 'Cannot vote on your own summary', array('status' => 400));
    }

    // Check existing vote
    $existing_vote = $wpdb->get_var($wpdb->prepare(
        "SELECT vote_type FROM $votes_table WHERE summary_id = %d AND user_id = %d",
        $summary_id,
        $user_id
    ));

    if ($existing_vote) {
        // Update vote if different
        if ($existing_vote !== $vote_type) {
            $wpdb->update(
                $votes_table,
                array('vote_type' => $vote_type),
                array('summary_id' => $summary_id, 'user_id' => $user_id),
                array('%s'),
                array('%d', '%d')
            );

            hs_recalculate_summary_votes($summary_id);

            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Vote updated',
            ), 200);
        } else {
            return new WP_Error('already_voted', 'You have already voted', array('status' => 400));
        }
    }

    // Add new vote
    $wpdb->insert(
        $votes_table,
        array(
            'summary_id' => $summary_id,
            'user_id' => $user_id,
            'vote_type' => $vote_type,
        ),
        array('%d', '%d', '%s')
    );

    hs_recalculate_summary_votes($summary_id);

    // Award points for helpful votes
    if ($vote_type === 'helpful') {
        hs_award_contribution_points($summary->user_id, 2, 'summary_helpful_vote', $summary_id);
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Vote recorded',
    ), 200);
}

/**
 * Recalculate summary votes
 */
function hs_recalculate_summary_votes($summary_id) {
    global $wpdb;
    $votes_table = $wpdb->prefix . 'hs_summary_votes';
    $summaries_table = $wpdb->prefix . 'hs_user_summaries';

    $helpful_votes = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $votes_table WHERE summary_id = %d AND vote_type = 'helpful'",
        $summary_id
    ));

    $wpdb->update(
        $summaries_table,
        array('helpful_votes' => $helpful_votes),
        array('id' => $summary_id),
        array('%d'),
        array('%d')
    );
}

/**
 * Review summary (admin only)
 */
function hs_review_summary($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_summaries';

    $summary_id = $request->get_param('summary_id');
    $status = $request->get_param('status');
    $quality_score = $request->get_param('quality_score');
    $admin_id = get_current_user_id();

    // Get summary details
    $summary = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $summary_id
    ));

    if (!$summary) {
        return new WP_Error('not_found', 'Summary not found', array('status' => 404));
    }

    // Update status
    $update_data = array(
        'status' => $status,
        'approved_by' => $admin_id,
        'approved_at' => current_time('mysql'),
    );

    if ($quality_score !== null) {
        $update_data['quality_score'] = $quality_score;
    }

    $wpdb->update(
        $table,
        $update_data,
        array('id' => $summary_id),
        array('%s', '%d', '%s', '%f'),
        array('%d')
    );

    // Award points if approved
    if ($status === 'approved') {
        $points = hs_calculate_summary_points($summary->content, $summary->summary_type);

        // Bonus for high quality
        if ($quality_score >= 4.5) {
            $points += 20;
        } elseif ($quality_score >= 4.0) {
            $points += 10;
        } elseif ($quality_score >= 3.5) {
            $points += 5;
        }

        $wpdb->update(
            $table,
            array('points_awarded' => $points),
            array('id' => $summary_id),
            array('%d'),
            array('%d')
        );

        hs_award_contribution_points($summary->user_id, $points, 'summary_approved', $summary_id);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Summary approved',
            'points_awarded' => $points,
        ), 200);
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Summary rejected',
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
 * Get user contributions
 */
function hs_get_user_contributions($request) {
    global $wpdb;
    $user_id = get_current_user_id();

    $summaries_table = $wpdb->prefix . 'hs_user_summaries';
    $chapters_table = $wpdb->prefix . 'hs_book_chapters';

    $stats = array(
        'total_points' => (int)get_user_meta($user_id, 'hs_contribution_points', true),
        'summaries_submitted' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $summaries_table WHERE user_id = %d",
            $user_id
        )),
        'summaries_approved' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $summaries_table WHERE user_id = %d AND status = 'approved'",
            $user_id
        )),
        'total_summary_views' => $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(views) FROM $summaries_table WHERE user_id = %d AND status = 'approved'",
            $user_id
        )),
        'total_helpful_votes' => $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(helpful_votes) FROM $summaries_table WHERE user_id = %d AND status = 'approved'",
            $user_id
        )),
        'chapters_submitted' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT book_id) FROM $chapters_table WHERE submitted_by = %d",
            $user_id
        )),
        'chapters_approved' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT book_id) FROM $chapters_table WHERE submitted_by = %d AND status = 'approved'",
            $user_id
        )),
    );

    // Recent contributions
    $recent_summaries = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, p.post_title as book_title
         FROM $summaries_table s
         LEFT JOIN {$wpdb->posts} p ON s.book_id = p.ID
         WHERE s.user_id = %d
         ORDER BY s.created_at DESC
         LIMIT 10",
        $user_id
    ));

    return new WP_REST_Response(array(
        'success' => true,
        'stats' => $stats,
        'recent_summaries' => $recent_summaries,
    ), 200);
}

/**
 * Create summaries tables
 */
function hs_create_summaries_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table 1: User summaries
    $table_summaries = $wpdb->prefix . 'hs_user_summaries';
    $sql_summaries = "CREATE TABLE IF NOT EXISTS $table_summaries (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        book_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        summary_type enum('chapter','character','plot','theme','overall') NOT NULL,
        title varchar(500) NOT NULL,
        content text NOT NULL,
        chapter_id bigint(20) unsigned DEFAULT NULL,
        character_name varchar(255) DEFAULT NULL,
        points_awarded int(11) NOT NULL DEFAULT 0,
        status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        quality_score decimal(3,2) DEFAULT NULL,
        views int(11) NOT NULL DEFAULT 0,
        helpful_votes int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        approved_at datetime DEFAULT NULL,
        approved_by bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (id),
        KEY book_id (book_id),
        KEY user_id (user_id),
        KEY summary_type (summary_type),
        KEY status (status),
        KEY chapter_id (chapter_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Table 2: Summary votes
    $table_votes = $wpdb->prefix . 'hs_summary_votes';
    $sql_votes = "CREATE TABLE IF NOT EXISTS $table_votes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        summary_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        vote_type enum('helpful','not_helpful') NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_vote (summary_id, user_id),
        KEY summary_id (summary_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_summaries);
    dbDelta($sql_votes);

    update_option('hs_summaries_db_version', '1.0');
}

// Auto-create tables on admin init if needed
add_action('admin_init', function() {
    if (get_option('hs_summaries_db_version') !== '1.0') {
        hs_create_summaries_tables();
    }
});
