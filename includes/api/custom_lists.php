<?php
/**
 * Custom Lists API
 *
 * Allows users to create custom lists to organize their book collection
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom lists REST routes
 */
function hs_register_custom_lists_routes() {
    // Get all lists for current user
    register_rest_route('gread/v1', '/lists', array(
        'methods' => 'GET',
        'callback' => 'hs_get_user_lists',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'include_books' => array(
                'type' => 'boolean',
                'default' => true,
                'description' => 'Include books in each list',
            ),
        ),
    ));

    // Create new list
    register_rest_route('gread/v1', '/lists', array(
        'methods' => 'POST',
        'callback' => 'hs_create_list',
        'permission_callback' => 'gread_check_user_permission',
        'args' => array(
            'name' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'is_public' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'sort_order' => array(
                'type' => 'string',
                'default' => 'custom',
                'enum' => array('custom', 'title', 'author', 'date_added', 'publication_year'),
            ),
        ),
    ));

    // Get single list
    register_rest_route('gread/v1', '/lists/(?P<list_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'hs_get_list',
        'permission_callback' => 'hs_check_list_access',
        'args' => array(
            'list_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Update list
    register_rest_route('gread/v1', '/lists/(?P<list_id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'hs_update_list',
        'permission_callback' => 'hs_check_list_ownership',
        'args' => array(
            'list_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'name' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'is_public' => array(
                'type' => 'boolean',
            ),
            'sort_order' => array(
                'type' => 'string',
                'enum' => array('custom', 'title', 'author', 'date_added', 'publication_year'),
            ),
        ),
    ));

    // Delete list
    register_rest_route('gread/v1', '/lists/(?P<list_id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'hs_delete_list',
        'permission_callback' => 'hs_check_list_ownership',
        'args' => array(
            'list_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Add book to list
    register_rest_route('gread/v1', '/lists/(?P<list_id>\d+)/books', array(
        'methods' => 'POST',
        'callback' => 'hs_add_book_to_list',
        'permission_callback' => 'hs_check_list_ownership',
        'args' => array(
            'list_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'position' => array(
                'type' => 'integer',
                'default' => 0,
            ),
        ),
    ));

    // Remove book from list
    register_rest_route('gread/v1', '/lists/(?P<list_id>\d+)/books/(?P<book_id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'hs_remove_book_from_list',
        'permission_callback' => 'hs_check_list_ownership',
        'args' => array(
            'list_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
        ),
    ));

    // Reorder books in list
    register_rest_route('gread/v1', '/lists/(?P<list_id>\d+)/reorder', array(
        'methods' => 'PUT',
        'callback' => 'hs_reorder_list_books',
        'permission_callback' => 'hs_check_list_ownership',
        'args' => array(
            'list_id' => array(
                'required' => true,
                'type' => 'integer',
            ),
            'book_order' => array(
                'required' => true,
                'type' => 'array',
                'description' => 'Array of book IDs in desired order',
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_custom_lists_routes');

/**
 * Check if user can access a list
 */
function hs_check_list_access($request) {
    $list_id = $request->get_param('list_id');
    $user_id = get_current_user_id();

    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_lists';

    $list = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $list_id
    ));

    if (!$list) {
        return false;
    }

    // Allow access if user owns the list or it's public
    return ($list->user_id == $user_id || $list->is_public == 1 || current_user_can('edit_posts'));
}

/**
 * Check if user owns a list
 */
function hs_check_list_ownership($request) {
    $list_id = $request->get_param('list_id');
    $user_id = get_current_user_id();

    if (!$user_id) {
        return false;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_lists';

    $owner = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $table WHERE id = %d",
        $list_id
    ));

    return ($owner == $user_id || current_user_can('edit_posts'));
}

/**
 * Get user's lists
 */
function hs_get_user_lists($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_lists';
    $books_table = $wpdb->prefix . 'hs_list_books';

    $user_id = get_current_user_id();
    $include_books = $request->get_param('include_books');

    $lists = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC",
        $user_id
    ));

    if ($include_books) {
        foreach ($lists as $list) {
            $list->books = hs_get_list_books($list->id, $list->sort_order);
            $list->book_count = count($list->books);
        }
    } else {
        // Just get counts
        foreach ($lists as $list) {
            $list->book_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $books_table WHERE list_id = %d",
                $list->id
            ));
        }
    }

    return new WP_REST_Response(array(
        'success' => true,
        'lists' => $lists,
    ), 200);
}

/**
 * Get books in a list
 */
function hs_get_list_books($list_id, $sort_order = 'custom') {
    global $wpdb;
    $books_table = $wpdb->prefix . 'hs_list_books';

    // Determine sort
    $order_by = 'lb.position ASC';
    switch ($sort_order) {
        case 'title':
            $order_by = 'p.post_title ASC';
            break;
        case 'author':
            $order_by = 'pm_author.meta_value ASC';
            break;
        case 'date_added':
            $order_by = 'lb.added_at DESC';
            break;
        case 'publication_year':
            $order_by = 'CAST(pm_year.meta_value AS UNSIGNED) DESC';
            break;
    }

    $query = "SELECT lb.*, p.post_title as book_title, p.ID as book_id,
                     pm_author.meta_value as book_author,
                     pm_pages.meta_value as num_pages,
                     pm_year.meta_value as publication_year
              FROM $books_table lb
              LEFT JOIN {$wpdb->posts} p ON lb.book_id = p.ID
              LEFT JOIN {$wpdb->postmeta} pm_author ON p.ID = pm_author.post_id AND pm_author.meta_key = 'book_author'
              LEFT JOIN {$wpdb->postmeta} pm_pages ON p.ID = pm_pages.post_id AND pm_pages.meta_key = 'nop'
              LEFT JOIN {$wpdb->postmeta} pm_year ON p.ID = pm_year.post_id AND pm_year.meta_key = 'publication_year'
              WHERE lb.list_id = %d
              ORDER BY $order_by";

    return $wpdb->get_results($wpdb->prepare($query, $list_id));
}

/**
 * Create new list
 */
function hs_create_list($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_lists';

    $user_id = get_current_user_id();
    $name = $request->get_param('name');
    $description = $request->get_param('description');
    $is_public = $request->get_param('is_public') ? 1 : 0;
    $sort_order = $request->get_param('sort_order');

    $result = $wpdb->insert(
        $table,
        array(
            'user_id' => $user_id,
            'name' => $name,
            'description' => $description,
            'is_public' => $is_public,
            'sort_order' => $sort_order,
        ),
        array('%d', '%s', '%s', '%d', '%s')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to create list', array('status' => 500));
    }

    $list_id = $wpdb->insert_id;

    return new WP_REST_Response(array(
        'success' => true,
        'list_id' => $list_id,
        'message' => 'List created successfully',
    ), 201);
}

/**
 * Get single list
 */
function hs_get_list($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_lists';

    $list_id = $request->get_param('list_id');

    $list = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d",
        $list_id
    ));

    if (!$list) {
        return new WP_Error('not_found', 'List not found', array('status' => 404));
    }

    $list->books = hs_get_list_books($list_id, $list->sort_order);
    $list->book_count = count($list->books);

    return new WP_REST_Response(array(
        'success' => true,
        'list' => $list,
    ), 200);
}

/**
 * Update list
 */
function hs_update_list($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_lists';

    $list_id = $request->get_param('list_id');

    $update_data = array();
    $update_format = array();

    if ($request->get_param('name') !== null) {
        $update_data['name'] = $request->get_param('name');
        $update_format[] = '%s';
    }

    if ($request->get_param('description') !== null) {
        $update_data['description'] = $request->get_param('description');
        $update_format[] = '%s';
    }

    if ($request->get_param('is_public') !== null) {
        $update_data['is_public'] = $request->get_param('is_public') ? 1 : 0;
        $update_format[] = '%d';
    }

    if ($request->get_param('sort_order') !== null) {
        $update_data['sort_order'] = $request->get_param('sort_order');
        $update_format[] = '%s';
    }

    if (empty($update_data)) {
        return new WP_Error('no_data', 'No data to update', array('status' => 400));
    }

    $result = $wpdb->update(
        $table,
        $update_data,
        array('id' => $list_id),
        $update_format,
        array('%d')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to update list', array('status' => 500));
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'List updated successfully',
    ), 200);
}

/**
 * Delete list
 */
function hs_delete_list($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_user_lists';
    $books_table = $wpdb->prefix . 'hs_list_books';

    $list_id = $request->get_param('list_id');

    // Delete books from list first
    $wpdb->delete($books_table, array('list_id' => $list_id), array('%d'));

    // Delete list
    $result = $wpdb->delete($table, array('id' => $list_id), array('%d'));

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to delete list', array('status' => 500));
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'List deleted successfully',
    ), 200);
}

/**
 * Add book to list
 */
function hs_add_book_to_list($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_list_books';

    $list_id = $request->get_param('list_id');
    $book_id = $request->get_param('book_id');
    $position = $request->get_param('position');

    // Check if book exists
    $book_exists = get_post($book_id);
    if (!$book_exists || $book_exists->post_type !== 'book') {
        return new WP_Error('invalid_book', 'Book not found', array('status' => 404));
    }

    // Check if already in list
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE list_id = %d AND book_id = %d",
        $list_id,
        $book_id
    ));

    if ($exists) {
        return new WP_Error('already_exists', 'Book already in list', array('status' => 400));
    }

    // If position not specified, add to end
    if (!$position) {
        $position = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(position) + 1 FROM $table WHERE list_id = %d",
            $list_id
        ));
        $position = $position ?: 0;
    }

    $result = $wpdb->insert(
        $table,
        array(
            'list_id' => $list_id,
            'book_id' => $book_id,
            'position' => $position,
        ),
        array('%d', '%d', '%d')
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to add book to list', array('status' => 500));
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Book added to list',
    ), 200);
}

/**
 * Remove book from list
 */
function hs_remove_book_from_list($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_list_books';

    $list_id = $request->get_param('list_id');
    $book_id = $request->get_param('book_id');

    $result = $wpdb->delete(
        $table,
        array(
            'list_id' => $list_id,
            'book_id' => $book_id,
        ),
        array('%d', '%d')
    );

    if ($result === false || $result === 0) {
        return new WP_Error('not_found', 'Book not in list', array('status' => 404));
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Book removed from list',
    ), 200);
}

/**
 * Reorder books in list
 */
function hs_reorder_list_books($request) {
    global $wpdb;
    $table = $wpdb->prefix . 'hs_list_books';

    $list_id = $request->get_param('list_id');
    $book_order = $request->get_param('book_order');

    if (!is_array($book_order)) {
        return new WP_Error('invalid_data', 'book_order must be an array', array('status' => 400));
    }

    // Update positions
    foreach ($book_order as $position => $book_id) {
        $wpdb->update(
            $table,
            array('position' => $position),
            array('list_id' => $list_id, 'book_id' => $book_id),
            array('%d'),
            array('%d', '%d')
        );
    }

    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'List reordered successfully',
    ), 200);
}
