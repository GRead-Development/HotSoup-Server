<?php
/**
 * API Self-Test Endpoint
 *
 * Provides comprehensive API documentation and testing
 * Lists all endpoints, parameters, and performs health checks
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register API self-test routes
 */
function hs_register_api_self_test_routes() {
    register_rest_route('gread/v1', '/api-test', array(
        'methods' => 'GET',
        'callback' => 'hs_api_self_test',
        'permission_callback' => '__return_true',
        'args' => array(
            'endpoint' => array(
                'type' => 'string',
                'description' => 'Specific endpoint to test',
            ),
            'test' => array(
                'type' => 'boolean',
                'default' => false,
                'description' => 'Run actual tests on endpoints',
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_api_self_test_routes');

/**
 * API Self-Test
 */
function hs_api_self_test($request) {
    $test_mode = $request->get_param('test');
    $specific_endpoint = $request->get_param('endpoint');

    $endpoints = hs_get_all_api_endpoints();

    $results = array(
        'api_version' => '1.0',
        'timestamp' => current_time('mysql'),
        'base_url' => rest_url('gread/v1'),
        'total_endpoints' => 0,
        'categories' => array(),
    );

    foreach ($endpoints as $category => $category_endpoints) {
        $category_data = array(
            'name' => $category,
            'endpoints' => array(),
        );

        foreach ($category_endpoints as $endpoint) {
            if ($specific_endpoint && $endpoint['path'] !== $specific_endpoint) {
                continue;
            }

            $endpoint_data = array(
                'path' => $endpoint['path'],
                'method' => $endpoint['method'],
                'description' => $endpoint['description'],
                'authentication' => $endpoint['authentication'],
                'parameters' => $endpoint['parameters'],
                'returns' => $endpoint['returns'],
                'example' => $endpoint['example'],
            );

            // Run test if requested
            if ($test_mode) {
                $endpoint_data['test_result'] = hs_test_endpoint($endpoint);
            }

            $category_data['endpoints'][] = $endpoint_data;
            $results['total_endpoints']++;
        }

        if (!empty($category_data['endpoints'])) {
            $results['categories'][] = $category_data;
        }
    }

    return new WP_REST_Response($results, 200);
}

/**
 * Get all API endpoints documentation
 */
function hs_get_all_api_endpoints() {
    return array(
        'Books & Library' => array(
            array(
                'path' => '/library',
                'method' => 'GET',
                'description' => 'Get current user\'s library',
                'authentication' => 'Required',
                'parameters' => array(
                    'status' => array('type' => 'string', 'required' => false, 'description' => 'Filter by status (reading, completed, want_to_read)'),
                    'page' => array('type' => 'integer', 'required' => false, 'description' => 'Page number for pagination'),
                ),
                'returns' => array('books' => 'array', 'total' => 'integer', 'pagination' => 'object'),
                'example' => '/wp-json/gread/v1/library?status=reading',
            ),
            array(
                'path' => '/library/add',
                'method' => 'POST',
                'description' => 'Add a book to user\'s library',
                'authentication' => 'Required',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID to add'),
                    'status' => array('type' => 'string', 'required' => false, 'description' => 'Initial status'),
                ),
                'returns' => array('success' => 'boolean', 'message' => 'string'),
                'example' => 'POST /wp-json/gread/v1/library/add with body: {"book_id": 123}',
            ),
            array(
                'path' => '/library/progress',
                'method' => 'POST',
                'description' => 'Update reading progress',
                'authentication' => 'Required',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                    'current_page' => array('type' => 'integer', 'required' => true, 'description' => 'Current page'),
                ),
                'returns' => array('success' => 'boolean', 'pages_read' => 'integer', 'milestone_unlocked' => 'boolean'),
                'example' => 'POST /wp-json/gread/v1/library/progress',
            ),
            array(
                'path' => '/books/random',
                'method' => 'GET',
                'description' => 'Get a random unread book from user\'s library',
                'authentication' => 'Required',
                'parameters' => array(),
                'returns' => array('success' => 'boolean', 'book' => 'object'),
                'example' => '/wp-json/gread/v1/books/random',
            ),
        ),
        'Notes' => array(
            array(
                'path' => '/books/{book_id}/notes',
                'method' => 'GET',
                'description' => 'Get all notes for a book',
                'authentication' => 'Optional',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                    'page_number' => array('type' => 'integer', 'required' => false, 'description' => 'Filter by page'),
                ),
                'returns' => array('notes' => 'array'),
                'example' => '/wp-json/gread/v1/books/123/notes',
            ),
            array(
                'path' => '/books/{book_id}/notes',
                'method' => 'POST',
                'description' => 'Create a new note',
                'authentication' => 'Required',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                    'note_text' => array('type' => 'string', 'required' => true, 'description' => 'Note content'),
                    'page_number' => array('type' => 'integer', 'required' => false, 'description' => 'Page number'),
                    'is_public' => array('type' => 'boolean', 'required' => false, 'description' => 'Public visibility'),
                ),
                'returns' => array('success' => 'boolean', 'note_id' => 'integer'),
                'example' => 'POST /wp-json/gread/v1/books/123/notes',
            ),
        ),
        'Authors' => array(
            array(
                'path' => '/authors',
                'method' => 'GET',
                'description' => 'List all authors (searchable, paginated)',
                'authentication' => 'Optional',
                'parameters' => array(
                    'search' => array('type' => 'string', 'required' => false, 'description' => 'Search query'),
                    'page' => array('type' => 'integer', 'required' => false, 'description' => 'Page number'),
                ),
                'returns' => array('authors' => 'array', 'total' => 'integer'),
                'example' => '/wp-json/gread/v1/authors?search=tolkien',
            ),
            array(
                'path' => '/authors/{author_id}',
                'method' => 'GET',
                'description' => 'Get author details',
                'authentication' => 'Optional',
                'parameters' => array(
                    'author_id' => array('type' => 'integer', 'required' => true, 'description' => 'Author ID'),
                ),
                'returns' => array('author' => 'object'),
                'example' => '/wp-json/gread/v1/authors/456',
            ),
        ),
        'Custom Lists' => array(
            array(
                'path' => '/lists',
                'method' => 'GET',
                'description' => 'Get user\'s custom lists',
                'authentication' => 'Required',
                'parameters' => array(),
                'returns' => array('lists' => 'array'),
                'example' => '/wp-json/gread/v1/lists',
            ),
            array(
                'path' => '/lists',
                'method' => 'POST',
                'description' => 'Create a new list',
                'authentication' => 'Required',
                'parameters' => array(
                    'name' => array('type' => 'string', 'required' => true, 'description' => 'List name'),
                    'description' => array('type' => 'string', 'required' => false, 'description' => 'List description'),
                    'is_public' => array('type' => 'boolean', 'required' => false, 'description' => 'Public visibility'),
                ),
                'returns' => array('success' => 'boolean', 'list_id' => 'integer'),
                'example' => 'POST /wp-json/gread/v1/lists',
            ),
            array(
                'path' => '/lists/{list_id}/books',
                'method' => 'POST',
                'description' => 'Add book to list',
                'authentication' => 'Required',
                'parameters' => array(
                    'list_id' => array('type' => 'integer', 'required' => true, 'description' => 'List ID'),
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                ),
                'returns' => array('success' => 'boolean'),
                'example' => 'POST /wp-json/gread/v1/lists/1/books',
            ),
        ),
        'Milestones' => array(
            array(
                'path' => '/milestones',
                'method' => 'GET',
                'description' => 'Get user milestones',
                'authentication' => 'Required',
                'parameters' => array(),
                'returns' => array('milestones' => 'array'),
                'example' => '/wp-json/gread/v1/milestones',
            ),
        ),
        'Reading Planner' => array(
            array(
                'path' => '/reading-plans',
                'method' => 'GET',
                'description' => 'Get user\'s reading plans',
                'authentication' => 'Required',
                'parameters' => array(
                    'status' => array('type' => 'string', 'required' => false, 'description' => 'Filter by status'),
                ),
                'returns' => array('plans' => 'array'),
                'example' => '/wp-json/gread/v1/reading-plans',
            ),
            array(
                'path' => '/reading-plans',
                'method' => 'POST',
                'description' => 'Create a reading plan',
                'authentication' => 'Required',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                    'target_date' => array('type' => 'string', 'required' => true, 'description' => 'Target completion date (YYYY-MM-DD)'),
                ),
                'returns' => array('success' => 'boolean', 'plan' => 'object', 'pages_per_day' => 'float'),
                'example' => 'POST /wp-json/gread/v1/reading-plans',
            ),
            array(
                'path' => '/reading-plans/{plan_id}',
                'method' => 'PUT',
                'description' => 'Update reading plan target date',
                'authentication' => 'Required',
                'parameters' => array(
                    'plan_id' => array('type' => 'integer', 'required' => true, 'description' => 'Plan ID'),
                    'target_date' => array('type' => 'string', 'required' => true, 'description' => 'New target date'),
                ),
                'returns' => array('success' => 'boolean', 'new_pages_per_day' => 'float'),
                'example' => 'PUT /wp-json/gread/v1/reading-plans/1',
            ),
        ),
        'Citations' => array(
            array(
                'path' => '/books/{book_id}/citation',
                'method' => 'GET',
                'description' => 'Generate citation for a book',
                'authentication' => 'Optional',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                    'style' => array('type' => 'string', 'required' => false, 'description' => 'Citation style (mla, apa, chicago)'),
                ),
                'returns' => array('citation' => 'string', 'book' => 'object'),
                'example' => '/wp-json/gread/v1/books/123/citation?style=mla',
            ),
        ),
        'Chapters' => array(
            array(
                'path' => '/books/{book_id}/chapters',
                'method' => 'GET',
                'description' => 'Get chapters for a book',
                'authentication' => 'Optional',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                ),
                'returns' => array('chapters' => 'array'),
                'example' => '/wp-json/gread/v1/books/123/chapters',
            ),
            array(
                'path' => '/books/{book_id}/chapters',
                'method' => 'POST',
                'description' => 'Submit chapters for a book',
                'authentication' => 'Required',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                    'chapters' => array('type' => 'array', 'required' => true, 'description' => 'Array of chapter objects'),
                ),
                'returns' => array('success' => 'boolean', 'points_awarded' => 'integer'),
                'example' => 'POST /wp-json/gread/v1/books/123/chapters',
            ),
        ),
        'Summaries' => array(
            array(
                'path' => '/books/{book_id}/summaries',
                'method' => 'GET',
                'description' => 'Get summaries for a book',
                'authentication' => 'Optional',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                    'type' => array('type' => 'string', 'required' => false, 'description' => 'Summary type filter'),
                ),
                'returns' => array('summaries' => 'array'),
                'example' => '/wp-json/gread/v1/books/123/summaries?type=chapter',
            ),
            array(
                'path' => '/summaries',
                'method' => 'POST',
                'description' => 'Submit a summary',
                'authentication' => 'Required',
                'parameters' => array(
                    'book_id' => array('type' => 'integer', 'required' => true, 'description' => 'Book ID'),
                    'summary_type' => array('type' => 'string', 'required' => true, 'description' => 'Type of summary'),
                    'title' => array('type' => 'string', 'required' => true, 'description' => 'Summary title'),
                    'content' => array('type' => 'string', 'required' => true, 'description' => 'Summary content'),
                ),
                'returns' => array('success' => 'boolean', 'points_awarded' => 'integer'),
                'example' => 'POST /wp-json/gread/v1/summaries',
            ),
        ),
        'Feature Requests' => array(
            array(
                'path' => '/feature-requests',
                'method' => 'GET',
                'description' => 'List all feature requests and issues',
                'authentication' => 'Optional',
                'parameters' => array(
                    'type' => array('type' => 'string', 'required' => false, 'description' => 'Filter by type'),
                    'status' => array('type' => 'string', 'required' => false, 'description' => 'Filter by status'),
                ),
                'returns' => array('requests' => 'array', 'pagination' => 'object'),
                'example' => '/wp-json/gread/v1/feature-requests?type=feature',
            ),
            array(
                'path' => '/feature-requests',
                'method' => 'POST',
                'description' => 'Submit a feature request or issue',
                'authentication' => 'Required',
                'parameters' => array(
                    'type' => array('type' => 'string', 'required' => true, 'description' => 'Request type'),
                    'title' => array('type' => 'string', 'required' => true, 'description' => 'Request title'),
                    'description' => array('type' => 'string', 'required' => true, 'description' => 'Request description'),
                ),
                'returns' => array('success' => 'boolean', 'request_id' => 'integer'),
                'example' => 'POST /wp-json/gread/v1/feature-requests',
            ),
        ),
        'System' => array(
            array(
                'path' => '/api-test',
                'method' => 'GET',
                'description' => 'API self-test and documentation',
                'authentication' => 'None',
                'parameters' => array(
                    'test' => array('type' => 'boolean', 'required' => false, 'description' => 'Run actual tests'),
                ),
                'returns' => array('endpoints' => 'array', 'total_endpoints' => 'integer'),
                'example' => '/wp-json/gread/v1/api-test',
            ),
        ),
    );
}

/**
 * Test an endpoint
 */
function hs_test_endpoint($endpoint) {
    global $wpdb;

    $result = array(
        'status' => 'unknown',
        'message' => '',
    );

    try {
        // Check if route is registered
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes('gread/v1');

        $route_exists = false;
        foreach ($routes as $route => $handlers) {
            if (strpos($route, $endpoint['path']) !== false) {
                $route_exists = true;
                break;
            }
        }

        if ($route_exists) {
            $result['status'] = 'working';
            $result['message'] = 'Endpoint is registered and accessible';
        } else {
            $result['status'] = 'error';
            $result['message'] = 'Endpoint not registered';
        }

        // Additional database checks for specific endpoints
        if (strpos($endpoint['path'], 'library') !== false) {
            $table = $wpdb->prefix . 'usermeta';
            $test = $wpdb->get_var("SELECT COUNT(*) FROM $table LIMIT 1");
            if ($test === null) {
                $result['status'] = 'error';
                $result['message'] = 'Database connection error';
            }
        }

    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['message'] = $e->getMessage();
    }

    return $result;
}
