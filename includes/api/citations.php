<?php
/**
 * Citation/Bibliography Generator API
 *
 * Generates citations in various formats:
 * - MLA (Modern Language Association)
 * - APA (American Psychological Association)
 * - Chicago
 * - Harvard
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register citation routes
 */
function hs_register_citation_routes() {
    // Get citation for a book
    register_rest_route('gread/v1', '/books/(?P<book_id>\d+)/citation', array(
        'methods' => 'GET',
        'callback' => 'hs_get_book_citation',
        'permission_callback' => '__return_true',
        'args' => array(
            'book_id' => array(
                'required' => true,
                'type' => 'integer',
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
        'callback' => 'hs_generate_bibliography',
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
 * Get book citation
 */
function hs_get_book_citation($request) {
    $book_id = $request->get_param('book_id');
    $style = $request->get_param('style');

    // Get book details
    $book = get_post($book_id);
    if (!$book || $book->post_type !== 'book') {
        return new WP_Error('not_found', 'Book not found', array('status' => 404));
    }

    $book_data = hs_get_book_citation_data($book_id);

    $citation = hs_format_citation($book_data, $style);

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
 * Generate bibliography
 */
function hs_generate_bibliography($request) {
    $book_ids = $request->get_param('book_ids');
    $style = $request->get_param('style');
    $sort = $request->get_param('sort');

    if (!is_array($book_ids) || empty($book_ids)) {
        return new WP_Error('invalid_data', 'book_ids must be a non-empty array', array('status' => 400));
    }

    $citations = array();

    foreach ($book_ids as $book_id) {
        $book = get_post($book_id);
        if (!$book || $book->post_type !== 'book') {
            continue;
        }

        $book_data = hs_get_book_citation_data($book_id);
        $citation = hs_format_citation($book_data, $style);

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

/**
 * Get book citation data
 */
function hs_get_book_citation_data($book_id) {
    global $wpdb;

    $book = get_post($book_id);

    // Get author from custom field or authors table
    $author = get_post_meta($book_id, 'book_author', true);

    // Try to get from authors table
    if (!$author) {
        $authors_table = $wpdb->prefix . 'hs_book_authors';
        $author_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT author_id FROM $authors_table WHERE book_id = %d ORDER BY author_order ASC",
            $book_id
        ));

        if ($author_ids) {
            $authors_names_table = $wpdb->prefix . 'hs_authors';
            $authors = array();
            foreach ($author_ids as $author_id) {
                $author_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT name FROM $authors_names_table WHERE id = %d",
                    $author_id
                ));
                if ($author_name) {
                    $authors[] = $author_name;
                }
            }
            $author = implode(', ', $authors);
        }
    }

    // Get other metadata
    $publication_year = get_post_meta($book_id, 'publication_year', true);
    $publisher = get_post_meta($book_id, 'publisher', true);
    $isbn = get_post_meta($book_id, 'book_isbn', true);
    $edition = get_post_meta($book_id, 'edition', true);
    $pages = get_post_meta($book_id, 'nop', true);
    $city = get_post_meta($book_id, 'publication_city', true);

    // Try to get primary ISBN from ISBN table if not found
    if (!$isbn) {
        $isbn_table = $wpdb->prefix . 'hs_book_isbns';
        $isbn = $wpdb->get_var($wpdb->prepare(
            "SELECT isbn FROM $isbn_table WHERE post_id = %d AND is_primary = 1 LIMIT 1",
            $book_id
        ));
    }

    return array(
        'title' => $book->post_title,
        'author' => $author ?: 'Unknown Author',
        'publication_year' => $publication_year ?: 'n.d.',
        'publisher' => $publisher ?: '',
        'isbn' => $isbn ?: '',
        'edition' => $edition ?: '',
        'pages' => $pages ?: '',
        'city' => $city ?: '',
    );
}

/**
 * Format citation based on style
 */
function hs_format_citation($data, $style) {
    switch ($style) {
        case 'mla':
            return hs_format_mla_citation($data);
        case 'apa':
            return hs_format_apa_citation($data);
        case 'chicago':
            return hs_format_chicago_citation($data);
        case 'harvard':
            return hs_format_harvard_citation($data);
        case 'bibtex':
            return hs_format_bibtex_citation($data);
        default:
            return hs_format_mla_citation($data);
    }
}

/**
 * Format MLA citation
 * Format: Author. Title. Publisher, Year.
 */
function hs_format_mla_citation($data) {
    $parts = array();

    // Author (Last, First)
    $author = hs_format_author_mla($data['author']);
    if ($author) {
        $parts[] = $author . '.';
    }

    // Title (italicized - we'll use underscores)
    $parts[] = '_' . $data['title'] . '_.';

    // Publisher, Year
    $pub_parts = array();
    if ($data['publisher']) {
        $pub_parts[] = $data['publisher'];
    }
    if ($data['publication_year']) {
        $pub_parts[] = $data['publication_year'];
    }
    if (!empty($pub_parts)) {
        $parts[] = implode(', ', $pub_parts) . '.';
    }

    return implode(' ', $parts);
}

/**
 * Format APA citation
 * Format: Author. (Year). Title. Publisher.
 */
function hs_format_apa_citation($data) {
    $parts = array();

    // Author (Last, F.)
    $author = hs_format_author_apa($data['author']);
    if ($author) {
        $parts[] = $author . '.';
    }

    // Year
    if ($data['publication_year']) {
        $parts[] = '(' . $data['publication_year'] . ').';
    }

    // Title (italicized)
    $parts[] = '_' . $data['title'] . '_.';

    // Publisher
    if ($data['publisher']) {
        $parts[] = $data['publisher'] . '.';
    }

    return implode(' ', $parts);
}

/**
 * Format Chicago citation
 * Format: Author. Title. City: Publisher, Year.
 */
function hs_format_chicago_citation($data) {
    $parts = array();

    // Author (Last, First)
    $author = hs_format_author_mla($data['author']);
    if ($author) {
        $parts[] = $author . '.';
    }

    // Title (italicized)
    $parts[] = '_' . $data['title'] . '_.';

    // City: Publisher, Year
    $pub_parts = array();
    if ($data['city']) {
        $pub_parts[] = $data['city'] . ':';
    }
    if ($data['publisher']) {
        $pub_parts[] = $data['publisher'] . ',';
    }
    if ($data['publication_year']) {
        $pub_parts[] = $data['publication_year'] . '.';
    }

    if (!empty($pub_parts)) {
        $parts[] = implode(' ', $pub_parts);
    }

    return implode(' ', $parts);
}

/**
 * Format Harvard citation
 * Format: Author (Year) Title. Publisher.
 */
function hs_format_harvard_citation($data) {
    $parts = array();

    // Author
    $author = hs_format_author_mla($data['author']);
    if ($author) {
        $parts[] = $author;
    }

    // Year in parentheses
    if ($data['publication_year']) {
        $parts[] = '(' . $data['publication_year'] . ')';
    }

    // Title (italicized)
    $parts[] = '_' . $data['title'] . '_.';

    // Publisher
    if ($data['publisher']) {
        $parts[] = $data['publisher'] . '.';
    }

    return implode(' ', $parts);
}

/**
 * Format BibTeX citation
 */
function hs_format_bibtex_citation($data) {
    // Create a citation key from author last name and year
    $author_parts = explode(' ', $data['author']);
    $last_name = end($author_parts);
    $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $last_name)) . $data['publication_year'];

    $bibtex = "@book{" . $key . ",\n";
    $bibtex .= "  author = {" . $data['author'] . "},\n";
    $bibtex .= "  title = {" . $data['title'] . "},\n";

    if ($data['publisher']) {
        $bibtex .= "  publisher = {" . $data['publisher'] . "},\n";
    }
    if ($data['publication_year']) {
        $bibtex .= "  year = {" . $data['publication_year'] . "},\n";
    }
    if ($data['isbn']) {
        $bibtex .= "  isbn = {" . $data['isbn'] . "},\n";
    }
    if ($data['edition']) {
        $bibtex .= "  edition = {" . $data['edition'] . "},\n";
    }

    $bibtex .= "}";

    return $bibtex;
}

/**
 * Format author name for MLA/Chicago (Last, First)
 */
function hs_format_author_mla($author) {
    if (empty($author)) {
        return '';
    }

    // Handle multiple authors
    if (strpos($author, ',') !== false || strpos($author, ' and ') !== false) {
        // Already formatted or multiple authors - return as is
        return $author;
    }

    // Simple case: First Last -> Last, First
    $parts = explode(' ', trim($author));
    if (count($parts) >= 2) {
        $last = array_pop($parts);
        $first = implode(' ', $parts);
        return $last . ', ' . $first;
    }

    return $author;
}

/**
 * Format author name for APA (Last, F.)
 */
function hs_format_author_apa($author) {
    if (empty($author)) {
        return '';
    }

    // Simple case: First Last -> Last, F.
    $parts = explode(' ', trim($author));
    if (count($parts) >= 2) {
        $last = array_pop($parts);
        $first_initials = array();
        foreach ($parts as $part) {
            if (!empty($part)) {
                $first_initials[] = strtoupper($part[0]) . '.';
            }
        }
        return $last . ', ' . implode(' ', $first_initials);
    }

    return $author;
}

/**
 * Get sort key for citation (for alphabetical sorting)
 */
function hs_get_citation_sort_key($data) {
    // Sort by author last name, then title
    $author = $data['author'];
    $parts = explode(' ', trim($author));
    $last_name = end($parts);

    return strtolower($last_name . ' ' . $data['title']);
}
