<?php
/**
 * Database Schema for New Features
 *
 * Creates tables for:
 * - Feature requests & issue reports
 * - User custom lists
 * - User milestones
 * - Book addition/completion tracking
 * - Reading planner
 * - Book chapters
 * - User-submitted summaries
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create all new database tables
 */
function hs_create_new_feature_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for feature requests and issue reports
    $table_feature_requests = $wpdb->prefix . 'hs_feature_requests';
    $sql_feature_requests = "CREATE TABLE IF NOT EXISTS $table_feature_requests (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        type enum('feature','issue','bug','improvement') NOT NULL DEFAULT 'feature',
        title varchar(255) NOT NULL,
        description text NOT NULL,
        status enum('open','in_progress','resolved','closed','rejected') NOT NULL DEFAULT 'open',
        priority enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
        votes int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        resolved_at datetime DEFAULT NULL,
        admin_notes text DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY type (type),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Table for feature request votes
    $table_request_votes = $wpdb->prefix . 'hs_request_votes';
    $sql_request_votes = "CREATE TABLE IF NOT EXISTS $table_request_votes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        request_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_vote (request_id, user_id),
        KEY request_id (request_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // Table for user custom lists
    $table_user_lists = $wpdb->prefix . 'hs_user_lists';
    $sql_user_lists = "CREATE TABLE IF NOT EXISTS $table_user_lists (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        name varchar(255) NOT NULL,
        description text DEFAULT NULL,
        is_public tinyint(1) NOT NULL DEFAULT 0,
        sort_order varchar(50) NOT NULL DEFAULT 'custom',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Table for books in lists
    $table_list_books = $wpdb->prefix . 'hs_list_books';
    $sql_list_books = "CREATE TABLE IF NOT EXISTS $table_list_books (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        list_id bigint(20) unsigned NOT NULL,
        book_id bigint(20) unsigned NOT NULL,
        position int(11) NOT NULL DEFAULT 0,
        added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_list_book (list_id, book_id),
        KEY list_id (list_id),
        KEY book_id (book_id),
        KEY position (position)
    ) $charset_collate;";

    // Table for user milestones
    $table_milestones = $wpdb->prefix . 'hs_user_milestones';
    $sql_milestones = "CREATE TABLE IF NOT EXISTS $table_milestones (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        milestone_type varchar(100) NOT NULL,
        milestone_value int(11) NOT NULL,
        achieved_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        book_id bigint(20) unsigned DEFAULT NULL,
        metadata text DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY unique_milestone (user_id, milestone_type, milestone_value),
        KEY user_id (user_id),
        KEY milestone_type (milestone_type),
        KEY achieved_at (achieved_at)
    ) $charset_collate;";

    // Table for library activity tracking
    $table_library_activity = $wpdb->prefix . 'hs_library_activity';
    $sql_library_activity = "CREATE TABLE IF NOT EXISTS $table_library_activity (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        book_id bigint(20) unsigned NOT NULL,
        activity_type enum('added','started','completed','removed','progress_update') NOT NULL,
        activity_data text DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY book_id (book_id),
        KEY activity_type (activity_type),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Table for reading plans
    $table_reading_plans = $wpdb->prefix . 'hs_reading_plans';
    $sql_reading_plans = "CREATE TABLE IF NOT EXISTS $table_reading_plans (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        book_id bigint(20) unsigned NOT NULL,
        start_date date NOT NULL,
        target_date date NOT NULL,
        original_target_date date NOT NULL,
        total_pages int(11) NOT NULL,
        current_page int(11) NOT NULL DEFAULT 0,
        pages_per_day decimal(10,2) NOT NULL,
        status enum('active','completed','paused','cancelled') NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at datetime DEFAULT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY book_id (book_id),
        KEY status (status),
        KEY target_date (target_date)
    ) $charset_collate;";

    // Table for reading plan progress
    $table_plan_progress = $wpdb->prefix . 'hs_plan_progress';
    $sql_plan_progress = "CREATE TABLE IF NOT EXISTS $table_plan_progress (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        plan_id bigint(20) unsigned NOT NULL,
        pages_read int(11) NOT NULL,
        current_page int(11) NOT NULL,
        pages_expected int(11) NOT NULL,
        is_on_track tinyint(1) NOT NULL DEFAULT 1,
        recorded_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY plan_id (plan_id),
        KEY recorded_at (recorded_at)
    ) $charset_collate;";

    // Table for book chapters
    $table_book_chapters = $wpdb->prefix . 'hs_book_chapters';
    $sql_book_chapters = "CREATE TABLE IF NOT EXISTS $table_book_chapters (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        book_id bigint(20) unsigned NOT NULL,
        chapter_number int(11) NOT NULL,
        chapter_title varchar(500) NOT NULL,
        start_page int(11) DEFAULT NULL,
        end_page int(11) DEFAULT NULL,
        submitted_by bigint(20) unsigned NOT NULL,
        status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        approved_at datetime DEFAULT NULL,
        approved_by bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (id),
        KEY book_id (book_id),
        KEY submitted_by (submitted_by),
        KEY status (status),
        KEY chapter_number (chapter_number)
    ) $charset_collate;";

    // Table for user-submitted summaries
    $table_summaries = $wpdb->prefix . 'hs_user_summaries';
    $sql_summaries = "CREATE TABLE IF NOT EXISTS $table_summaries (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        book_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        summary_type enum('chapter','character','plot','theme','overall') NOT NULL,
        title varchar(500) NOT NULL,
        content text NOT NULL,
        chapter_id bigint(20) unsigned DEFAULT NULL,
        character_name varchar(255) DEFAULT NULL,
        points_awarded int(11) NOT NULL DEFAULT 0,
        status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        quality_score decimal(3,2) DEFAULT NULL,
        views int(11) NOT NULL DEFAULT 0,
        helpful_votes int(11) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        approved_at datetime DEFAULT NULL,
        approved_by bigint(20) unsigned DEFAULT NULL,
        PRIMARY KEY (id),
        KEY book_id (book_id),
        KEY user_id (user_id),
        KEY summary_type (summary_type),
        KEY status (status),
        KEY chapter_id (chapter_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    // Table for summary votes
    $table_summary_votes = $wpdb->prefix . 'hs_summary_votes';
    $sql_summary_votes = "CREATE TABLE IF NOT EXISTS $table_summary_votes (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        summary_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        vote_type enum('helpful','not_helpful') NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_vote (summary_id, user_id),
        KEY summary_id (summary_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql_feature_requests);
    dbDelta($sql_request_votes);
    dbDelta($sql_user_lists);
    dbDelta($sql_list_books);
    dbDelta($sql_milestones);
    dbDelta($sql_library_activity);
    dbDelta($sql_reading_plans);
    dbDelta($sql_plan_progress);
    dbDelta($sql_book_chapters);
    dbDelta($sql_summaries);
    dbDelta($sql_summary_votes);

    update_option('hs_new_features_db_version', '1.0');
}

// Run on plugin activation or when needed
add_action('admin_init', 'hs_check_and_create_new_tables');

function hs_check_and_create_new_tables() {
    $current_version = get_option('hs_new_features_db_version', '0');
    if (version_compare($current_version, '1.0', '<')) {
        hs_create_new_feature_tables();
    }
}
