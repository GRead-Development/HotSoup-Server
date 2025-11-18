<?php

// A collection of functions for tracking user and site statistics

if (!defined('ABSPATH'))
{
	exit;
}


/* Site-wide statistics */

// How many books have been added to users' libraries, collectively.
function hs_get_total_books_in_libraries()
{
	global $wpdb;
	$table_name = $wpdb -> prefix . 'user_books';
	$count = $wpdb -> get_var("SELECT COUNT(*) FROM $table_name");

	return (int) $count;
}


// Sum of page counts across all the books in the DB (this function may need to be done away with, honestly).
function hs_get_total_pages_available()
{
	global $wpdb;

	// Sum all 'nop' counts
	$total_pages = $wpdb -> get_var($wpdb -> prepare("
		SELECT SUM(CAST(pm.meta_value AS UNSIGNED))
		FROM {$wpdb -> postmeta} pm
		INNER JOIN {$wpdb -> posts} p ON pm.post_id = p.ID
		WHERE pm.meta_key = %s
		AND p.post_type = %s
		AND p.post_status = %s
		AND pm.meta_value REGEXP '^[0-9]+$'", 'nop', 'book', 'publish'));

	return (int) $total_pages;
}


// Count how many points have been earned between all the users on GRead
function hs_get_total_points_earned()
{
	global $wpdb;

	$total_points = $wpdb -> get_var($wpdb -> prepare("
		SELECT SUM(CAST(meta_value AS UNSIGNED))
		FROM {$wpdb -> usermeta}
		WHERE meta_key = %s", 'user_points'));

	return (int) $total_points;
}


// Count how many pages have been read between all the users on GRead
function hs_get_total_pages_read()
{
	global $wpdb;

	$total_pages = $wpdb -> get_var($wpdb -> prepare("
		SELECT SUM(CAST(meta_value AS UNSIGNED))
		FROM {$wpdb -> usermeta}
		WHERE meta_key = %s", 'hs_total_pages_read'));

	return (int) $total_pages;
}


// Count how many books have been completed between all the users on GRead
function hs_get_total_books_completed()
{
	global $wpdb;

	$total_completed = $wpdb -> get_var($wpdb -> prepare("
		SELECT SUM(CAST(meta_value AS UNSIGNED))
		FROM {$wpdb -> usermeta}
		WHERE meta_key = %s", 'hs_completed_books_count'));

	return (int) $total_completed;
}


// Count how many users have registered for GRead
function hs_get_total_users_registered()
{
	$user_count = count_users();
	return (int) $user_count['total_users'];
}


// Arrange all the sitewide statistics into a single array
function hs_get_site_statistics()
{
	return array(
		'books_in_libraries' => hs_get_total_books_in_libraries(),
		'pages_available' => hs_get_total_pages_available(),
		'total_points' => hs_get_total_points_earned(),
		'total_pages_read' => hs_get_total_pages_read(),
		'books_completed' => hs_get_total_books_completed(),
		'users_registered' => hs_get_total_users_registered(),
	);
}
