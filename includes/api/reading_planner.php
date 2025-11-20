<?php
/**
 * Reading Planner API
 *
 * Allows users to create reading plans with target dates
 * Calculates daily page requirements and adjusts based on progress
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register reading planner REST routes
 */
function hs_register_reading_planner_routes() {
    // Get all reading plans
    register_rest_route('gread/v1', '/reading-plans', array(
        'methods' => 'GET',
        'callback' => 'hs_get_reading_plans',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'status' => array(
                'type' => 'string',
                'enum' => array('active', 'completed', 'paused', 'cancelled'),
            ),
        ),
    ));

    // Create reading plan
    register_rest_route('gread/v1', '/reading-plans', array(
        'methods' => 'POST',
        'callback' => 'hs_create_reading_plan',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'target_date' => array(
                'required' => true,
                'type' => 'string',
                'validate_callback' => 'hs_validate_date',
            ),
            'start_date' => array(
                'type' => 'string',
                'validate_callback' => 'hs_validate_date',
            ),
        ),
    ));

    // Get single reading plan
    register_rest_route('gread/v1', '/reading-plans/(?P<plan_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'hs_get_reading_plan',
        'permission_callback' => 'hs_check_plan_ownership',
        'args' => array(
            'plan_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Update reading plan
    register_rest_route('gread/v1', '/reading-plans/(?P<plan_id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'hs_update_reading_plan',
        'permission_callback' => 'hs_check_plan_ownership',
        'args' => array(
            'plan_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'target_date' => array(
                'type' => 'string',
                'validate_callback' => 'hs_validate_date',
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('active', 'completed', 'paused', 'cancelled'),
            ),
        ),
    ));

    // Record progress on reading plan
    register_rest_route('gread/v1', '/reading-plans/(?P<plan_id>\d+)/progress', array(
        'methods' => 'POST',
        'callback' => 'hs_record_plan_progress',
        'permission_callback' => 'hs_check_plan_ownership',
        'args' => array(
            'plan_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'current_page' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Get today's reading goal
    register_rest_route('gread/v1', '/reading-plans/today', array(
        'methods' => 'GET',
        'callback' => 'hs_get_todays_reading_goals',
        'permission_callback' => 'gread_check_user_permission',
    ));

    // Delete reading plan
    register_rest_route('gread/v1', '/reading-plans/(?P<plan_id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'hs_delete_reading_plan',
        'permission_callback' => 'hs_check_plan_ownership',
        'args' => array(
            'plan_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_reading_planner_routes');

/**
 * Validate date format
 */
function hs_validate_date($date, $request, $param) {
    $parsed = date_parse($date);
    return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
}

/**
 * Check if user owns the plan
 */
function hs_check_plan_ownership($request) {
    $plan_id = $request->get_param('plan_id');
    $user_id = get_current_user_id();

    if (!$user_id) {
        return false;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'hs_reading_plans';

    $owner = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $table WHERE id = %d",
        $plan_id
    ));

    return $owner == $user_id;
}

/**
 * Calculate pages per day
 */
function hs_calculate_pages_per_day($total_pages, $current_page, $target_date) {
    $pages_remaining = $total_pages - $current_page;

    if ($pages_remaining <= 0) {
        return 0;
    }

    $now = new DateTime();
    $target = new DateTime($target_date);
    $days_remaining = $now->diff($target)->days;

    if ($days_remaining <= 0) {
        return $pages_remaining; // Need to read all remaining pages today
    }

    return round($pages_remaining / $days_remaining, 2);
}

/**
 * Get reading plans
 */
function hs_get_reading_plans($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_reading_plans';

    $user_id = get_current_user_id();
    $status = $request->get_param('status');

    $where = "user_id = %d";
    $where_args = array($user_id);

    if ($status) {
        $where .= " AND status = %s";
        $where_args[] = $status;
    }

    $query = "SELECT rp.*, p.post_title as book_title,
                     pm_pages.meta_value as total_pages,
                     pm_author.meta_value as book_author
              FROM $table rp
              LEFT JOIN {$wpdb->posts} p ON rp.book_id = p.ID
              LEFT JOIN {$wpdb->postmeta} pm_pages ON p.ID = pm_pages.post_id AND pm_pages.meta_key = 'nop'
              LEFT JOIN {$wpdb->postmeta} pm_author ON p.ID = pm_author.post_id AND pm_author.meta_key = 'book_author'
              WHERE $where
              ORDER BY rp.target_date ASC";

    $plans = $wpdb->get_results($wpdb->prepare($query, $where_args));

    // Add calculated fields
    foreach ($plans as $plan) {
        $plan->pages_remaining = $plan->total_pages - $plan->current_page;
        $plan->progress_percentage = round(($plan->current_page / $plan->total_pages) * 100, 2);

        $now = new DateTime();
        $target = new DateTime($plan->target_date);
        $plan->days_remaining = max(0, $now->diff($target)->days);

        $plan->is_on_track = hs_check_if_on_track($plan->id);
        $plan->current_pages_per_day = hs_calculate_pages_per_day(
            $plan->total_pages,
            $plan->current_page,
            $plan->target_date
        );
    }

    return new WP_REST_Response(array(
        'success' => true,
        'plans' => $plans,
    ), 200);
}

/**
 * Create reading plan
 */
function hs_create_reading_plan($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_reading_plans';

    $user_id = get_current_user_id();
    $book_id = $request->get_param('book_id');
    $target_date = $request->get_param('target_date');
    $start_date = $request->get_param('start_date') ?: current_time('Y-m-d');

    // Get book details
    $book = get_post($book_id);
    if (!$book || $book->post_type !== 'book') {
        return new WP_Error('invalid_book', 'Book not found', array('status' => 404));
    }

    $total_pages = get_post_meta($book_id, 'nop', true);
    if (!$total_pages) {
        return new WP_Error('missing_pages', 'Book does not have page count', array('status' => 400));
    }

    // Get current reading progress
    $current_page = get_user_meta($user_id, "book_{$book_id}_current_page", true) ?: 0;

    // Calculate pages per day
    $pages_per_day = hs_calculate_pages_per_day($total_pages, $current_page, $target_date);

    // Check if plan already exists for this book
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE user_id = %d AND book_id = %d AND status = 'active'",
        $user_id,
        $book_id
    ));

    if ($exists) {
        return new WP_Error('plan_exists', 'Active plan already exists for this book', array('status' => 400));
    }

    $result = $wpdb->insert(
        $table,
        array(
            'user_id' => $user_id,
            'book_id' => $book_id,
            'start_date' => $start_date,
            'target_date' => $target_date,
            'original_target_date' => $target_date,
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'pages_per_day' => $pages_per_day,
            'status' => 'active',
        ),
        array('%d', '%d', '%s', '%s', '%s', '%d', '%d', '%f', '%s')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to create reading plan', array('status' => 500));
    }

    $plan_id = $wpdb->insert_id;

    return new WP_REST_Response(array(
        'success' => true,
        'plan_id' => $plan_id,
        'pages_per_day' => $pages_per_day,
        'message' => "Reading plan created! Read {$pages_per_day} pages per day to finish by {$target_date}",
    ), 201);
}

/**
 * Get single reading plan
 */
function hs_get_reading_plan($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_reading_plans';
    $progress_table = $wpdb->prefix . 'hs_plan_progress';

    $plan_id = $request->get_param('plan_id');

    $plan = $wpdb->get_row($wpdb->prepare(
        "SELECT rp.*, p.post_title as book_title,
                pm_pages.meta_value as total_pages,
                pm_author.meta_value as book_author
         FROM $table rp
         LEFT JOIN {$wpdb->posts} p ON rp.book_id = p.ID
         LEFT JOIN {$wpdb->postmeta} pm_pages ON p.ID = pm_pages.post_id AND pm_pages.meta_key = 'nop'
         LEFT JOIN {$wpdb->postmeta} pm_author ON p.ID = pm_author.post_id AND pm_author.meta_key = 'book_author'
         WHERE rp.id = %d",
        $plan_id
    ));

    if (!$plan) {
        return new WP_Error('not_found', 'Reading plan not found', array('status' => 404));
    }

    // Get progress history
    $plan->progress_history = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $progress_table WHERE plan_id = %d ORDER BY recorded_at DESC LIMIT 30",
        $plan_id
    ));

    // Calculate stats
    $plan->pages_remaining = $plan->total_pages - $plan->current_page;
    $plan->progress_percentage = round(($plan->current_page / $plan->total_pages) * 100, 2);

    $now = new DateTime();
    $target = new DateTime($plan->target_date);
    $plan->days_remaining = max(0, $now->diff($target)->days);

    $plan->is_on_track = hs_check_if_on_track($plan_id);

    return new WP_REST_Response(array(
        'success' => true,
        'plan' => $plan,
    ), 200);
}

/**
 * Update reading plan
 */
function hs_update_reading_plan($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_reading_plans';

    $plan_id = $request->get_param('plan_id');
    $new_target_date = $request->get_param('target_date');
    $status = $request->get_param('status');

    $update_data = array();
    $update_format = array();

    if ($new_target_date) {
        // Get current plan details
        $plan = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $plan_id
        ));

        // Recalculate pages per day
        $new_pages_per_day = hs_calculate_pages_per_day(
            $plan->total_pages,
            $plan->current_page,
            $new_target_date
        );

        $update_data['target_date'] = $new_target_date;
        $update_data['pages_per_day'] = $new_pages_per_day;
        $update_format[] = '%s';
        $update_format[] = '%f';
    }

    if ($status) {
        $update_data['status'] = $status;
        $update_format[] = '%s';

        if ($status === 'completed') {
            $update_data['completed_at'] = current_time('mysql');
            $update_format[] = '%s';
        }
    }

    if (empty($update_data)) {
        return new WP_Error('no_data', 'No data to update', array('status' => 400));
    }

    $result = $wpdb->update(
        $table,
        $update_data,
        array('id' => $plan_id),
        $update_format,
        array('%d')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to update reading plan', array('status' => 500));
    }

    $response = array(
        'success' => true,
        'message' => 'Reading plan updated successfully',
    );

    if (isset($new_pages_per_day)) {
        $response['new_pages_per_day'] = $new_pages_per_day;
        $response['message'] = "Target date updated! Read {$new_pages_per_day} pages per day to finish by {$new_target_date}";
    }

    return new WP_REST_Response($response, 200);
}

/**
 * Record progress on reading plan
 */
function hs_record_plan_progress($request) {
    global $wpdb;
    $plans_table = $wpdb->prefix . 'hs_reading_plans';
    $progress_table = $wpdb->prefix . 'hs_plan_progress';

    $plan_id = $request->get_param('plan_id');
    $current_page = $request->get_param('current_page');

    // Get plan
    $plan = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $plans_table WHERE id = %d",
        $plan_id
    ));

    if (!$plan) {
        return new WP_Error('not_found', 'Reading plan not found', array('status' => 404));
    }

    // Calculate expected progress
    $start = new DateTime($plan->start_date);
    $now = new DateTime();
    $target = new DateTime($plan->target_date);

    $total_days = $start->diff($target)->days;
    $days_elapsed = $start->diff($now)->days;

    $expected_page = 0;
    if ($total_days > 0) {
        $expected_page = floor(($days_elapsed / $total_days) * $plan->total_pages);
    }

    $pages_read = $current_page - $plan->current_page;
    $is_on_track = $current_page >= $expected_page;

    // Record progress
    $wpdb->insert(
        $progress_table,
        array(
            'plan_id' => $plan_id,
            'pages_read' => $pages_read,
            'current_page' => $current_page,
            'pages_expected' => $expected_page,
            'is_on_track' => $is_on_track ? 1 : 0,
        ),
        array('%d', '%d', '%d', '%d', '%d')
    );

    // Recalculate pages per day based on actual progress
    $new_pages_per_day = hs_calculate_pages_per_day(
        $plan->total_pages,
        $current_page,
        $plan->target_date
    );

    // Update plan
    $wpdb->update(
        $plans_table,
        array(
            'current_page' => $current_page,
            'pages_per_day' => $new_pages_per_day,
        ),
        array('id' => $plan_id),
        array('%d', '%f'),
        array('%d')
    );

    // Update user's book progress
    update_user_meta($plan->user_id, "book_{$plan->book_id}_current_page", $current_page);

    // Check if completed
    if ($current_page >= $plan->total_pages) {
        $wpdb->update(
            $plans_table,
            array(
                'status' => 'completed',
                'completed_at' => current_time('mysql'),
            ),
            array('id' => $plan_id),
            array('%s', '%s'),
            array('%d')
        );

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Congratulations! You completed your reading plan!',
            'completed' => true,
            'pages_read' => $pages_read,
        ), 200);
    }

    $message = $is_on_track
        ? "Great progress! Read {$new_pages_per_day} pages per day to stay on track."
        : "You're behind schedule. Read {$new_pages_per_day} pages per day to catch up.";

    return new WP_REST_Response(array(
        'success' => true,
        'message' => $message,
        'current_page' => $current_page,
        'new_pages_per_day' => $new_pages_per_day,
        'is_on_track' => $is_on_track,
        'pages_read' => $pages_read,
    ), 200);
}

/**
 * Check if plan is on track
 */
function hs_check_if_on_track($plan_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_reading_plans';

    $plan = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $plan_id
    ));

    if (!$plan) {
        return false;
    }

    $start = new DateTime($plan->start_date);
    $now = new DateTime();
    $target = new DateTime($plan->target_date);

    $total_days = $start->diff($target)->days;
    $days_elapsed = $start->diff($now)->days;

    if ($total_days <= 0) {
        return true;
    }

    $expected_page = floor(($days_elapsed / $total_days) * $plan->total_pages);

    return $plan->current_page >= $expected_page;
}

/**
 * Get today's reading goals
 */
function hs_get_todays_reading_goals($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_reading_plans';

    $user_id = get_current_user_id();

    $plans = $wpdb->get_results($wpdb->prepare(
        "SELECT rp.*, p.post_title as book_title,
                pm_pages.meta_value as total_pages
         FROM $table rp
         LEFT JOIN {$wpdb->posts} p ON rp.book_id = p.ID
         LEFT JOIN {$wpdb->postmeta} pm_pages ON p.ID = pm_pages.post_id AND pm_pages.meta_key = 'nop'
         WHERE rp.user_id = %d AND rp.status = 'active'
         ORDER BY rp.target_date ASC",
        $user_id
    ));

    $goals = array();
    $total_pages_today = 0;

    foreach ($plans as $plan) {
        $goal = array(
            'plan_id' => $plan->id,
            'book_title' => $plan->book_title,
            'pages_to_read_today' => ceil($plan->pages_per_day),
            'current_page' => $plan->current_page,
            'target_date' => $plan->target_date,
            'is_on_track' => hs_check_if_on_track($plan->id),
        );

        $goals[] = $goal;
        $total_pages_today += $goal['pages_to_read_today'];
    }

    return new WP_REST_Response(array(
        'success' => true,
        'date' => current_time('Y-m-d'),
        'total_pages_today' => $total_pages_today,
        'goals' => $goals,
    ), 200);
}

/**
 * Delete reading plan
 */
function hs_delete_reading_plan($request) {
    global $wpdb;
    $plans_table = $wpdb->prefix . 'hs_reading_plans';
    $progress_table = $wpdb->prefix . 'hs_plan_progress';

    $plan_id = $request->get_param('plan_id');

    // Delete progress records
    $wpdb->delete($progress_table, array('plan_id' => $plan_id), array('%d'));

    // Delete plan
    $result = $wpdb->delete($plans_table, array('id' => $plan_id), array('%d'));

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to delete reading plan', array('status' => 500));
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Reading plan deleted successfully',
    ), 200);
}
