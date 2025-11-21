<?php
/**
 * Citations - Core Functions
 *
 * Chunk 3.1: Citation formatting functions
 * LOC: ~350
 *
 * What this does:
 * - Generates citations in multiple formats (MLA, APA, Chicago, Harvard, BibTeX)
 * - No database tables needed
 *
 * API endpoints are in: includes/api/citations-api.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get book data for citation
 *
 * @param int $book_id Book ID
 * @return array Book citation data
 */
function hs_get_book_citation_data($book_id) {
    global $wpdb;

    $book = get_post($book_id);
    if (!$book) {
        return false;
    }

    // Get author from custom field or authors table
    $author = get_post_meta($book_id, 'book_author', true);

    // Try to get from authors table if not in custom field
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

    // Try to get ISBN from ISBN table if not found
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
 * Generate citation in specified format
 *
 * @param int $book_id Book ID
 * @param string $style Citation style (mla, apa, chicago, harvard, bibtex)
 * @return string|false Formatted citation or false on error
 */
function hs_generate_citation($book_id, $style = 'mla') {
    $data = hs_get_book_citation_data($book_id);

    if (!$data) {
        return false;
    }

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

    // Handle multiple authors or already formatted
    if (strpos($author, ',') !== false || strpos($author, ' and ') !== false) {
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
