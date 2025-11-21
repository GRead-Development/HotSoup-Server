<?php
/**
 * Citations - API Endpoints
 *
 * Chunk 3.2: REST API routes for citations
 * LOC: ~150
 *
 * Requires: includes/citations.php
 *
 * Endpoints:
 * - GET /wp-json/gread/v1/books/{id}/citation
 * - POST /wp-json/gread/v1/bibliography
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register citation REST routes
 */
function hs_register_citation_routes() {
    // Get citation for a book
    register_rest_route('gread/v1', '/books/(?P<book_id>\d+)/citation', array(
        'methods' => 'GET',
        'callback' => 'hs_rest_get_book_citation',
        'permission_callback' => '__return_true',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
                'sanitize_callback' => 'absint',
            ),
            'style' => array(
                'type' => 'string',
                'default' => 'mla',
                'enum' => array('mla', 'apa', 'chicago', 'harvard', 'bibtex'),
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));

    // Generate bibliography for multiple books
    register_rest_route('gread/v1', '/bibliography', array(
        'methods' => 'POST',
        'callback' => 'hs_rest_generate_bibliography',
        'permission_callback' => '__return_true',
        'args' => array(
            'book_ids' => array(
                'required' => true,
                'type' => 'array',
                'description' => 'Array of book IDs',
            ),
            'style' => array(
                'type' => 'string',
                'default' => 'mla',
                'enum' => array('mla', 'apa', 'chicago', 'harvard', 'bibtex'),
            ),
            'sort' => array(
                'type' => 'string',
                'default' => 'alphabetical',
                'enum' => array('alphabetical', 'order'),
            ),
        ),
    ));
}
add_action('rest_api_init', 'hs_register_citation_routes');

/**
 * REST API: Get book citation
 *
 * GET /wp-json/gread/v1/books/{id}/citation?style=mla
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function hs_rest_get_book_citation($request) {
    $book_id = $request->get_param('book_id');
    $style = $request->get_param('style');

    // Get book
    $book = get_post($book_id);
    if (!$book || $book->post_type !== 'book') {
        return new WP_Error(
            'not_found',
            'Book not found',
            array('status' => 404)
        );
    }

    // Use core function
    $citation = hs_generate_citation($book_id, $style);

    if (!$citation) {
        return new WP_Error(
            'generation_failed',
            'Failed to generate citation',
            array('status' => 500)
        );
    }

    $book_data = hs_get_book_citation_data($book_id);

    return new WP_REST_Response(array(
        'success' => true,
        'book' => array(
            'id' => $book_id,
            'title' => $book_data['title'],
            'author' => $book_data['author'],
        ),
        'style' => $style,
        'citation' => $citation,
    ), 200);
}

/**
 * REST API: Generate bibliography
 *
 * POST /wp-json/gread/v1/bibliography
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function hs_rest_generate_bibliography($request) {
    $book_ids = $request->get_param('book_ids');
    $style = $request->get_param('style');
    $sort = $request->get_param('sort');

    if (!is_array($book_ids) || empty($book_ids)) {
        return new WP_Error(
            'invalid_data',
            'book_ids must be a non-empty array',
            array('status' => 400)
        );
    }

    $citations = array();

    foreach ($book_ids as $book_id) {
        $book = get_post($book_id);
        if (!$book || $book->post_type !== 'book') {
            continue;
        }

        $book_data = hs_get_book_citation_data($book_id);
        $citation = hs_generate_citation($book_id, $style);

        $citations[] = array(
            'book_id' => $book_id,
            'title' => $book_data['title'],
            'author' => $book_data['author'],
            'citation' => $citation,
            'sort_key' => hs_get_citation_sort_key($book_data),
        );
    }

    // Sort if needed
    if ($sort === 'alphabetical') {
        usort($citations, function($a, $b) {
            return strcmp($a['sort_key'], $b['sort_key']);
        });
    }

    // Remove sort keys from output
    foreach ($citations as &$citation) {
        unset($citation['sort_key']);
    }

    // Create formatted bibliography
    $formatted_bibliography = implode("\n\n", array_column($citations, 'citation'));

    return new WP_REST_Response(array(
        'success' => true,
        'style' => $style,
        'count' => count($citations),
        'citations' => $citations,
        'formatted_bibliography' => $formatted_bibliography,
    ), 200);
}
