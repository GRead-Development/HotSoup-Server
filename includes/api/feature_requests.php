<?php
/**
 * Feature Requests & Issue Reporting API
 *
 * Endpoints:
 * - POST /feature-requests - Create new request
 * - GET /feature-requests - List all requests
 * - GET /feature-requests/{id} - Get single request
 * - PUT /feature-requests/{id} - Update request (admin only)
 * - DELETE /feature-requests/{id} - Delete request (admin only)
 * - POST /feature-requests/{id}/vote - Vote for request
 * - DELETE /feature-requests/{id}/vote - Remove vote
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register feature request REST routes
 */
function hs_register_feature_request_routes() {
    // Create new feature request
    register_rest_route('gread/v1', '/feature-requests', array(
        'methods' => 'POST',
        'callback' => 'hs_create_feature_request',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'type' => array(
                'required' => true,
                'type' => 'string',
                'enum' => array('feature', 'issue', 'bug', 'improvement'),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'title' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
        ),
    ));

    // Get all feature requests
    register_rest_route('gread/v1', '/feature-requests', array(
        'methods' => 'GET',
        'callback' => 'hs_get_feature_requests',
        'permission_callback' => '__return_true',
        'args' => array(
            'type' => array(
                'type' => 'string',
                'enum' => array('feature', 'issue', 'bug', 'improvement'),
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('open', 'in_progress', 'resolved', 'closed', 'rejected'),
            ),
            'sort' => array(
                'type' => 'string',
                'default' => 'votes',
                'enum' => array('votes', 'recent', 'oldest'),
            ),
            'page' => array(
                'type' => 'integer',
                'default' => 1,
            ),
            'per_page' => array(
                'type' => 'integer',
                'default' => 20,
            ),
        ),
    ));

    // Get single feature request
    register_rest_route('gread/v1', '/feature-requests/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'hs_get_feature_request',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Update feature request (admin only)
    register_rest_route('gread/v1', '/feature-requests/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'hs_update_feature_request',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'status' => array(
                'type' => 'string',
                'enum' => array('open', 'in_progress', 'resolved', 'closed', 'rejected'),
            ),
            'priority' => array(
                'type' => 'string',
                'enum' => array('low', 'medium', 'high', 'critical'),
            ),
            'admin_notes' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
        ),
    ));

    // Delete feature request (admin only)
    register_rest_route('gread/v1', '/feature-requests/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'hs_delete_feature_request',
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Vote for feature request
    register_rest_route('gread/v1', '/feature-requests/(?P<id>\d+)/vote', array(
        'methods' => 'POST',
        'callback' => 'hs_vote_feature_request',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Remove vote
    register_rest_route('gread/v1', '/feature-requests/(?P<id>\d+)/vote', array(
        'methods' => 'DELETE',
        'callback' => 'hs_unvote_feature_request',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_feature_request_routes');

/**
 * Create new feature request
 */
function hs_create_feature_request($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_feature_requests';

    $user_id = get_current_user_id();
    $type = $request->get_param('type');
    $title = $request->get_param('title');
    $description = $request->get_param('description');

    $result = $wpdb->insert(
        $table,
        array(
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
        ),
        array('%d', '%s', '%s', '%s')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to create feature request', array('status' => 500));
    }

    $request_id = $wpdb->insert_id;

    return new WP_REST_Response(array(
        'success' => true,
        'request_id' => $request_id,
        'message' => 'Feature request submitted successfully',
    ), 201);
}

/**
 * Get all feature requests
 */
function hs_get_feature_requests($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_feature_requests';

    $type = $request->get_param('type');
    $status = $request->get_param('status');
    $sort = $request->get_param('sort');
    $page = $request->get_param('page');
    $per_page = $request->get_param('per_page');
    $offset = ($page - 1) * $per_page;

    // Build WHERE clause
    $where = array('1=1');
    $where_args = array();

    if ($type) {
        $where[] = 'type = %s';
        $where_args[] = $type;
    }

    if ($status) {
        $where[] = 'status = %s';
        $where_args[] = $status;
    }

    $where_clause = implode(' AND ', $where);

    // Build ORDER BY clause
    $order_by = 'votes DESC, created_at DESC';
    if ($sort === 'recent') {
        $order_by = 'created_at DESC';
    } elseif ($sort === 'oldest') {
        $order_by = 'created_at ASC';
    }

    // Get total count
    $count_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
    if (!empty($where_args)) {
        $count_query = $wpdb->prepare($count_query, $where_args);
    }
    $total = $wpdb->get_var($count_query);

    // Get requests
    $query = "SELECT fr.*, u.display_name as author_name
              FROM $table fr
              LEFT JOIN {$wpdb->users} u ON fr.user_id = u.ID
              WHERE $where_clause
              ORDER BY $order_by
              LIMIT %d OFFSET %d";

    $where_args[] = $per_page;
    $where_args[] = $offset;

    $requests = $wpdb->get_results($wpdb->prepare($query, $where_args));

    // Check if current user has voted
    $user_id = get_current_user_id();
    if ($user_id) {
        $votes_table = $wpdb->prefix . 'hs_request_votes';
        foreach ($requests as $req) {
            $has_voted = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $votes_table WHERE request_id = %d AND user_id = %d",
                $req->id,
                $user_id
            ));
            $req->user_has_voted = (bool)$has_voted;
        }
    }

    return new WP_REST_Response(array(
        'success' => true,
        'requests' => $requests,
        'pagination' => array(
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page),
        ),
    ), 200);
}

/**
 * Get single feature request
 */
function hs_get_feature_request($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_feature_requests';

    $id = $request->get_param('id');

    $feature_request = $wpdb->get_row($wpdb->prepare(
        "SELECT fr.*, u.display_name as author_name
         FROM $table fr
         LEFT JOIN {$wpdb->users} u ON fr.user_id = u.ID
         WHERE fr.id = %d",
        $id
    ));

    if (!$feature_request) {
        return new WP_Error('not_found', 'Feature request not found', array('status' => 404));
    }

    // Check if current user has voted
    $user_id = get_current_user_id();
    if ($user_id) {
        $votes_table = $wpdb->prefix . 'hs_request_votes';
        $has_voted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $votes_table WHERE request_id = %d AND user_id = %d",
            $id,
            $user_id
        ));
        $feature_request->user_has_voted = (bool)$has_voted;
    }

    return new WP_REST_Response(array(
        'success' => true,
        'request' => $feature_request,
    ), 200);
}

/**
 * Update feature request (admin only)
 */
function hs_update_feature_request($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_feature_requests';

    $id = $request->get_param('id');
    $status = $request->get_param('status');
    $priority = $request->get_param('priority');
    $admin_notes = $request->get_param('admin_notes');

    $update_data = array();
    $update_format = array();

    if ($status) {
        $update_data['status'] = $status;
        $update_format[] = '%s';

        if (in_array($status, array('resolved', 'closed'))) {
            $update_data['resolved_at'] = current_time('mysql');
            $update_format[] = '%s';
        }
    }

    if ($priority) {
        $update_data['priority'] = $priority;
        $update_format[] = '%s';
    }

    if ($admin_notes !== null) {
        $update_data['admin_notes'] = $admin_notes;
        $update_format[] = '%s';
    }

    if (empty($update_data)) {
        return new WP_Error('no_data', 'No data to update', array('status' => 400));
    }

    $result = $wpdb->update(
        $table,
        $update_data,
        array('id' => $id),
        $update_format,
        array('%d')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to update feature request', array('status' => 500));
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Feature request updated successfully',
    ), 200);
}

/**
 * Delete feature request (admin only)
 */
function hs_delete_feature_request($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_feature_requests';
    $votes_table = $wpdb->prefix . 'hs_request_votes';

    $id = $request->get_param('id');

    // Delete votes first
    $wpdb->delete($votes_table, array('request_id' => $id), array('%d'));

    // Delete request
    $result = $wpdb->delete($table, array('id' => $id), array('%d'));

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to delete feature request', array('status' => 500));
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Feature request deleted successfully',
    ), 200);
}

/**
 * Vote for feature request
 */
function hs_vote_feature_request($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_request_votes';
    $requests_table = $wpdb->prefix . 'hs_feature_requests';

    $id = $request->get_param('id');
    $user_id = get_current_user_id();

    // Check if request exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $requests_table WHERE id = %d",
        $id
    ));

    if (!$exists) {
        return new WP_Error('not_found', 'Feature request not found', array('status' => 404));
    }

    // Check if already voted
    $already_voted = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE request_id = %d AND user_id = %d",
        $id,
        $user_id
    ));

    if ($already_voted) {
        return new WP_Error('already_voted', 'You have already voted for this request', array('status' => 400));
    }

    // Add vote
    $result = $wpdb->insert(
        $table,
        array(
            'request_id' => $id,
            'user_id' => $user_id,
        ),
        array('%d', '%d')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to add vote', array('status' => 500));
    }

    // Update vote count
    $wpdb->query($wpdb->prepare(
        "UPDATE $requests_table SET votes = (SELECT COUNT(*) FROM $table WHERE request_id = %d) WHERE id = %d",
        $id,
        $id
    ));

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Vote added successfully',
    ), 200);
}

/**
 * Remove vote
 */
function hs_unvote_feature_request($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_request_votes';
    $requests_table = $wpdb->prefix . 'hs_feature_requests';

    $id = $request->get_param('id');
    $user_id = get_current_user_id();

    // Remove vote
    $result = $wpdb->delete(
        $table,
        array(
            'request_id' => $id,
            'user_id' => $user_id,
        ),
        array('%d', '%d')
    );

    if ($result === false || $result === 0) {
        return new WP_Error('not_voted', 'You have not voted for this request', array('status' => 400));
    }

    // Update vote count
    $wpdb->query($wpdb->prepare(
        "UPDATE $requests_table SET votes = (SELECT COUNT(*) FROM $table WHERE request_id = %d) WHERE id = %d",
        $id,
        $id
    ));

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Vote removed successfully',
    ), 200);
}
