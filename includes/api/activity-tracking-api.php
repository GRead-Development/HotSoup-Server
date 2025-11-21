<?php
/**
 * Activity Tracking - API Endpoints
 *
 * Chunk 1.2: REST API routes for activity tracking
 * LOC: ~200
 *
 * Requires: includes/activity-tracking.php
 *
 * What this does:
 * - Registers REST API routes
 * - Provides API callbacks using core functions
 *
 * Endpoints:
 * - GET /wp-json/gread/v1/library/activity
 * - GET /wp-json/gread/v1/books/{id}/reading-stats
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
        'callback' => 'hs_rest_get_library_activity',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'book_id' => array(
                'type' => 'integer',
                'description' => 'Filter by specific book',
                'sanitize_callback' => 'absint',
            ),
            'activity_type' => array(
                'type' => 'string',
                'description' => 'Filter by activity type',
                'enum' => array('added', 'started', 'completed', 'removed', 'progress_update'),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'limit' => array(
                'type' => 'integer',
                'default' => 50,
                'sanitize_callback' => 'absint',
            ),
            'offset' => array(
                'type' => 'integer',
                'default' => 0,
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    // Get book reading stats
    register_rest_route('gread/v1', '/books/(?P<book_id>\d+)/reading-stats', array(
        'methods' => 'GET',
        'callback' => 'hs_rest_get_book_reading_stats',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_activity_tracking_routes');

/**
 * REST API: Get library activity
 *
 * GET /wp-json/gread/v1/library/activity
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function hs_rest_get_library_activity($request) {
    $user_id = get_current_user_id();

    $args = array(
        'book_id' => $request->get_param('book_id'),
        'activity_type' => $request->get_param('activity_type'),
        'limit' => $request->get_param('limit') ?: 50,
        'offset' => $request->get_param('offset') ?: 0,
    );

    // Use core function
    $activities = hs_get_library_activity_for_user($user_id, $args);

    return new WP_REST_Response(array(
        'success' => true,
        'activities' => $activities,
        'count' => count($activities),
    ), 200);
}

/**
 * REST API: Get book reading stats
 *
 * GET /wp-json/gread/v1/books/{id}/reading-stats
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function hs_rest_get_book_reading_stats($request) {
    $user_id = get_current_user_id();
    $book_id = $request->get_param('book_id');

    // Verify book exists
    $book = get_post($book_id);
    if (!$book || $book->post_type !== 'book') {
        return new WP_Error(
            'invalid_book',
            'Book not found',
            array('status' => 404)
        );
    }

    // Use core function
    $stats = hs_get_book_reading_stats($user_id, $book_id);

    return new WP_REST_Response(array(
        'success' => true,
        'stats' => $stats,
    ), 200);
}
