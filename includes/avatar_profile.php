<?php
/**
 * Avatar Profile Integration
 * Integrates custom avatars with BuddyPress profiles
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add Avatar Customization tab to user settings
function hs_avatar_settings_nav() {
    if (function_exists('bp_core_new_nav_item')) {
        bp_core_new_nav_item([
            'name' => 'Avatar',
            'slug' => 'avatar',
            'parent_slug' => bp_get_settings_slug(),
            'screen_function' => 'hs_avatar_settings_screen_content',
            'position' => 35
        ]);
    }
}
add_action('bp_setup_nav', 'hs_avatar_settings_nav');

// Render the avatar customization screen
function hs_avatar_settings_screen_content() {
    wp_enqueue_script(
        'hs-avatar-customizer-js',
        plugin_dir_url(dirname(__FILE__)) . 'js/avatar-customizer.js',
        ['jquery'],
        '1.0.0',
        true
    );

    wp_localize_script(
        'hs-avatar-customizer-js',
        'hs_avatar_ajax',
        [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('gread/v1/avatar/'),
            'nonce' => wp_create_nonce('wp_rest')
        ]
    );

    wp_enqueue_style(
        'hs-avatar-customizer-css',
        plugin_dir_url(dirname(__FILE__)) . 'css/avatar-customizer.css',
        [],
        '1.0.0'
    );

    add_action('bp_template_content', 'hs_render_avatar_customizer');
    bp_core_load_template(apply_filters('bp_core_template_plugin', 'members/single/plugins'));
}

// Render the avatar customization interface
function hs_render_avatar_customizer() {
    $user_id = bp_displayed_user_id();

    // Get current customization
    $body_color = get_user_meta($user_id, 'hs_avatar_body_color', true) ?: '#FFFFFF';
    $gender = get_user_meta($user_id, 'hs_avatar_gender', true) ?: 'male';
    $shirt_color = get_user_meta($user_id, 'hs_avatar_shirt_color', true) ?: '#4A90E2';
    $pants_color = get_user_meta($user_id, 'hs_avatar_pants_color', true) ?: '#2C3E50';
    $equipped_items = json_decode(get_user_meta($user_id, 'hs_avatar_equipped_items', true) ?: '[]', true);

    // Get available items
    $all_items = hs_get_all_avatar_items_with_unlock_status($user_id);

    ?>
    <div class="hs-avatar-customizer-container">
        <h3>Customize Your Avatar</h3>
        <p>Create your unique avatar! Unlock new items by reading books and contributing to GRead.</p>

        <div class="hs-avatar-customizer-layout">
            <!-- Preview Section -->
            <div class="hs-avatar-preview-section">
                <h4>Preview</h4>
                <div id="hs-avatar-preview" class="hs-avatar-preview">
                    <?php echo hs_generate_user_avatar($user_id, 300); ?>
                </div>
            </div>

            <!-- Customization Controls -->
            <div class="hs-avatar-controls-section">
                <form id="hs-avatar-customization-form">
                    <?php wp_nonce_field('hs_save_avatar', 'hs_avatar_nonce'); ?>

                    <div class="hs-avatar-control-group">
                        <h4>Basic Customization</h4>

                        <div class="hs-avatar-control">
                            <label for="body_color">Body Color</label>
                            <input type="color" name="body_color" id="body_color" value="<?php echo esc_attr($body_color); ?>" class="hs-color-picker">
                            <span class="hs-color-value"><?php echo esc_html($body_color); ?></span>
                        </div>

                        <div class="hs-avatar-control">
                            <label for="gender">Gender</label>
                            <select name="gender" id="gender" class="hs-gender-select">
                                <option value="male" <?php selected($gender, 'male'); ?>>Male</option>
                                <option value="female" <?php selected($gender, 'female'); ?>>Female</option>
                            </select>
                        </div>

                        <div class="hs-avatar-control">
                            <label for="shirt_color">Shirt Color</label>
                            <input type="color" name="shirt_color" id="shirt_color" value="<?php echo esc_attr($shirt_color); ?>" class="hs-color-picker">
                            <span class="hs-color-value"><?php echo esc_html($shirt_color); ?></span>
                        </div>

                        <div class="hs-avatar-control">
                            <label for="pants_color">Pants Color</label>
                            <input type="color" name="pants_color" id="pants_color" value="<?php echo esc_attr($pants_color); ?>" class="hs-color-picker">
                            <span class="hs-color-value"><?php echo esc_html($pants_color); ?></span>
                        </div>
                    </div>

                    <?php foreach ($all_items as $category => $items): ?>
                        <div class="hs-avatar-control-group">
                            <h4><?php echo esc_html(ucwords(str_replace('_', ' ', $category))); ?></h4>
                            <div class="hs-avatar-items-grid">
                                <?php foreach ($items as $item): ?>
                                    <div class="hs-avatar-item <?php echo $item['is_unlocked'] ? 'unlocked' : 'locked'; ?>"
                                         data-item-id="<?php echo esc_attr($item['id']); ?>"
                                         data-category="<?php echo esc_attr($category); ?>">
                                        <label>
                                            <input type="radio"
                                                   name="item_<?php echo esc_attr($category); ?>"
                                                   value="<?php echo esc_attr($item['id']); ?>"
                                                   <?php echo in_array($item['id'], $equipped_items) ? 'checked' : ''; ?>
                                                   <?php echo !$item['is_unlocked'] ? 'disabled' : ''; ?>>
                                            <div class="hs-avatar-item-preview">
                                                <?php if (!empty($item['svg_data'])): ?>
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="50" height="50">
                                                        <?php echo $item['svg_data']; ?>
                                                    </svg>
                                                <?php else: ?>
                                                    <span class="hs-avatar-item-none">None</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="hs-avatar-item-name"><?php echo esc_html($item['name']); ?></span>
                                            <?php if (!$item['is_unlocked']): ?>
                                                <span class="hs-avatar-item-locked">
                                                    🔒 <?php echo esc_html($item['unlock_message']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="hs-avatar-submit">
                        <button type="submit" class="button button-primary hs-button">Save Avatar</button>
                        <span id="hs-avatar-save-status"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Get all avatar items grouped by category with unlock status
 */
function hs_get_all_avatar_items_with_unlock_status($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';

    $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY category ASC, display_order ASC, name ASC");

    $user_stats = [
        'points' => (int) get_user_meta($user_id, 'user_points', true),
        'books_read' => (int) get_user_meta($user_id, 'hs_completed_books_count', true),
        'pages_read' => (int) get_user_meta($user_id, 'hs_total_pages_read', true),
        'books_added' => (int) get_user_meta($user_id, 'hs_books_added_count', true),
        'approved_reports' => (int) get_user_meta($user_id, 'hs_approved_reports_count', true)
    ];

    $grouped = [];
    foreach ($items as $item) {
        $category = $item->category;

        $is_unlocked = false;
        if ($item->is_default || empty($item->unlock_metric)) {
            $is_unlocked = true;
        } else {
            $metric_value = isset($user_stats[$item->unlock_metric]) ? $user_stats[$item->unlock_metric] : 0;
            $is_unlocked = $metric_value >= $item->unlock_value;
        }

        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }

        // Add "None" option for each category
        if (count($grouped[$category]) === 0) {
            $grouped[$category][] = [
                'id' => 0,
                'name' => 'None',
                'slug' => 'none',
                'svg_data' => '',
                'is_unlocked' => true,
                'unlock_message' => ''
            ];
        }

        $grouped[$category][] = [
            'id' => $item->id,
            'name' => $item->name,
            'slug' => $item->slug,
            'svg_data' => $item->svg_data,
            'is_unlocked' => $is_unlocked,
            'unlock_message' => $item->unlock_message
        ];
    }

    return $grouped;
}

// Display custom avatar on BuddyPress profiles
function hs_display_custom_avatar_on_profile() {
    $user_id = bp_displayed_user_id();
    ?>
    <div class="hs-profile-avatar">
        <?php echo hs_generate_user_avatar($user_id, 150); ?>
    </div>
    <?php
}
add_action('bp_before_member_header_meta', 'hs_display_custom_avatar_on_profile');

// Enqueue avatar styles
function hs_enqueue_avatar_styles() {
    wp_enqueue_style(
        'hs-avatar-display',
        plugin_dir_url(dirname(__FILE__)) . 'css/avatar-display.css',
        [],
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'hs_enqueue_avatar_styles');
