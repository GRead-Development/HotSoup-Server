<?php
/**
 * Filter WordPress search results to exclude merged books
 * Only show canonical books in search results
 */

// Filter main WordPress query to exclude merged books
add_action('pre_get_posts', 'hs_exclude_merged_books_from_search');

function hs_exclude_merged_books_from_search($query)
{
    // Only modify public search queries for books
    if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
        return;
    }

    // Check if this is a book search
    $post_type = $query->get('post_type');
    if ($post_type !== 'book' && (!is_array($post_type) || !in_array('book', $post_type))) {
        // If no post type specified, still filter books
        if (empty($post_type)) {
            // Will handle below
        } else {
            return;
        }
    }

    global $wpdb;

    // Get IDs of merged books (not canonical)
    $merged_book_ids = $wpdb->get_col("
        SELECT post_id
        FROM {$wpdb->prefix}hs_gid
        WHERE is_canonical = 0
    ");

    if (!empty($merged_book_ids)) {
        // Exclude merged books from search
        $query->set('post__not_in', array_merge(
            (array) $query->get('post__not_in'),
            $merged_book_ids
        ));
    }
}

// Also filter book archives and taxonomy queries
add_action('pre_get_posts', 'hs_exclude_merged_books_from_archives');

function hs_exclude_merged_books_from_archives($query)
{
    // Only modify public archive queries
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // Check if this is a book archive or taxonomy
    if (!is_post_type_archive('book') && !is_tax() && !is_category() && !is_tag()) {
        return;
    }

    global $wpdb;

    // Get IDs of merged books (not canonical)
    $merged_book_ids = $wpdb->get_col("
        SELECT post_id
        FROM {$wpdb->prefix}hs_gid
        WHERE is_canonical = 0
    ");

    if (!empty($merged_book_ids)) {
        // Exclude merged books
        $query->set('post__not_in', array_merge(
            (array) $query->get('post__not_in'),
            $merged_book_ids
        ));
    }
}
