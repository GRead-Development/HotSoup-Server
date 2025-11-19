<?php
/**
 * Avatar Customization System
 * Manages unlockable avatar items (hats, accessories, backgrounds, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Create avatar items table on activation
function hs_avatar_items_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        slug varchar(50) NOT NULL,
        name varchar(100) NOT NULL,
        category varchar(50) NOT NULL,
        svg_data text NOT NULL,
        unlock_metric varchar(50),
        unlock_value int(11) DEFAULT 0,
        unlock_message text,
        is_default tinyint(1) DEFAULT 0,
        display_order int(11) DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug),
        KEY category (category)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Insert some default items
    hs_avatar_items_insert_defaults();
}

// Insert default avatar items
function hs_avatar_items_insert_defaults() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';

    // Check if defaults already exist
    $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_default = 1");
    if ($existing > 0) {
        return;
    }

    $default_items = [
        // Default background
        [
            'slug' => 'bg-default',
            'name' => 'Default Background',
            'category' => 'background',
            'svg_data' => '',
            'is_default' => 1,
            'display_order' => 0
        ],
        // Starter hat (unlockable)
        [
            'slug' => 'hat-baseball',
            'name' => 'Baseball Cap',
            'category' => 'hat',
            'svg_data' => '<path d="M 30 25 Q 50 20 70 25 L 70 30 Q 50 27 30 30 Z" fill="#FF5733"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => 5,
            'unlock_message' => 'Read 5 books to unlock!',
            'display_order' => 1
        ],
        // Bookworm glasses
        [
            'slug' => 'accessory-glasses',
            'name' => 'Reading Glasses',
            'category' => 'accessory',
            'svg_data' => '<g><circle cx="35" cy="50" r="8" fill="none" stroke="#333" stroke-width="2"/><circle cx="65" cy="50" r="8" fill="none" stroke="#333" stroke-width="2"/><line x1="43" y1="50" x2="57" y2="50" stroke="#333" stroke-width="2"/></g>',
            'unlock_metric' => 'books_read',
            'unlock_value' => 10,
            'unlock_message' => 'Read 10 books to unlock!',
            'display_order' => 2
        ]
    ];

    foreach ($default_items as $item) {
        $wpdb->insert($table_name, $item);
    }
}

// Add Avatar Manager to admin menu
function hs_avatar_items_add_admin_page() {
    add_menu_page(
        'Avatar Manager',
        'Avatar Manager',
        'manage_options',
        'hs-avatar-manager',
        'hs_avatar_items_admin_page_html',
        'dashicons-admin-users',
        28
    );
}
add_action('admin_menu', 'hs_avatar_items_add_admin_page');

// Admin page HTML
function hs_avatar_items_admin_page_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';

    // Handle form submission
    if (isset($_POST['hs_save_avatar_item_nonce']) && wp_verify_nonce($_POST['hs_save_avatar_item_nonce'], 'hs_save_avatar_item')) {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        $data = [
            'slug' => sanitize_key($_POST['slug']),
            'name' => sanitize_text_field($_POST['name']),
            'category' => sanitize_key($_POST['category']),
            'svg_data' => wp_kses_post($_POST['svg_data']),
            'unlock_metric' => sanitize_key($_POST['unlock_metric']),
            'unlock_value' => intval($_POST['unlock_value']),
            'unlock_message' => sanitize_text_field($_POST['unlock_message']),
            'display_order' => intval($_POST['display_order'])
        ];

        if ($item_id > 0) {
            $wpdb->update($table_name, $data, ['id' => $item_id]);
            echo '<div class="notice notice-success"><p>Avatar item updated successfully!</p></div>';
        } else {
            $wpdb->insert($table_name, $data);
            echo '<div class="notice notice-success"><p>Avatar item created successfully!</p></div>';
        }
    }

    // Handle deletion
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'hs_delete_avatar_item_' . $_GET['id'])) {
            $item_to_delete = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['id'])));
            if ($item_to_delete && !$item_to_delete->is_default) {
                $wpdb->delete($table_name, ['id' => intval($_GET['id'])]);
                echo '<div class="notice notice-success"><p>Avatar item deleted successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Cannot delete default items!</p></div>';
            }
        }
    }

    // Get item to edit
    $item_to_edit = null;
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $item_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['id'])));
    }

    $all_items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY category ASC, display_order ASC, name ASC");
    ?>

    <div class="wrap">
        <h1>Avatar Item Manager</h1>
        <p>Create and manage unlockable avatar customization items like hats, accessories, backgrounds, and more!</p>

        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <h2><?php echo $item_to_edit ? 'Edit Avatar Item' : 'Add New Avatar Item'; ?></h2>

                    <form method="post">
                        <?php wp_nonce_field('hs_save_avatar_item', 'hs_save_avatar_item_nonce'); ?>
                        <input type="hidden" name="item_id" value="<?php echo $item_to_edit ? esc_attr($item_to_edit->id) : '0'; ?>">

                        <div class="form-field">
                            <label for="name">Item Name *</label>
                            <input type="text" name="name" id="name" value="<?php echo $item_to_edit ? esc_attr($item_to_edit->name) : ''; ?>" required>
                        </div>

                        <div class="form-field">
                            <label for="slug">Item Slug *</label>
                            <input type="text" name="slug" id="slug" value="<?php echo $item_to_edit ? esc_attr($item_to_edit->slug) : ''; ?>" required>
                            <p class="description">Lowercase letters, numbers, and dashes only.</p>
                        </div>

                        <div class="form-field">
                            <label for="category">Category *</label>
                            <select name="category" id="category" required>
                                <option value="hat" <?php echo $item_to_edit && $item_to_edit->category === 'hat' ? 'selected' : ''; ?>>Hat</option>
                                <option value="accessory" <?php echo $item_to_edit && $item_to_edit->category === 'accessory' ? 'selected' : ''; ?>>Accessory</option>
                                <option value="background" <?php echo $item_to_edit && $item_to_edit->category === 'background' ? 'selected' : ''; ?>>Background</option>
                                <option value="shirt_pattern" <?php echo $item_to_edit && $item_to_edit->category === 'shirt_pattern' ? 'selected' : ''; ?>>Shirt Pattern</option>
                                <option value="pants_pattern" <?php echo $item_to_edit && $item_to_edit->category === 'pants_pattern' ? 'selected' : ''; ?>>Pants Pattern</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="svg_data">SVG Data</label>
                            <textarea name="svg_data" id="svg_data" rows="10" style="font-family: monospace;"><?php echo $item_to_edit ? esc_textarea($item_to_edit->svg_data) : ''; ?></textarea>
                            <p class="description">SVG code for this item (without &lt;svg&gt; wrapper). Use coordinates relative to 100x100 viewbox.</p>
                        </div>

                        <h3>Unlock Requirements</h3>
                        <p class="description">Leave blank if item should be unlocked by default</p>

                        <div class="form-field">
                            <label for="unlock_metric">Unlock Metric</label>
                            <select name="unlock_metric" id="unlock_metric">
                                <option value="">None (Always Unlocked)</option>
                                <option value="points" <?php echo $item_to_edit && $item_to_edit->unlock_metric === 'points' ? 'selected' : ''; ?>>Points</option>
                                <option value="books_read" <?php echo $item_to_edit && $item_to_edit->unlock_metric === 'books_read' ? 'selected' : ''; ?>>Books Read</option>
                                <option value="pages_read" <?php echo $item_to_edit && $item_to_edit->unlock_metric === 'pages_read' ? 'selected' : ''; ?>>Pages Read</option>
                                <option value="books_added" <?php echo $item_to_edit && $item_to_edit->unlock_metric === 'books_added' ? 'selected' : ''; ?>>Books Added</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label for="unlock_value">Unlock Value</label>
                            <input type="number" name="unlock_value" id="unlock_value" value="<?php echo $item_to_edit ? esc_attr($item_to_edit->unlock_value) : '0'; ?>">
                        </div>

                        <div class="form-field">
                            <label for="unlock_message">Unlock Message</label>
                            <input type="text" name="unlock_message" id="unlock_message" value="<?php echo $item_to_edit ? esc_attr($item_to_edit->unlock_message) : ''; ?>" placeholder="e.g., Read 10 books to unlock!">
                        </div>

                        <div class="form-field">
                            <label for="display_order">Display Order</label>
                            <input type="number" name="display_order" id="display_order" value="<?php echo $item_to_edit ? esc_attr($item_to_edit->display_order) : '0'; ?>">
                            <p class="description">Lower numbers appear first</p>
                        </div>

                        <p class="submit">
                            <input type="submit" class="button button-primary" value="<?php echo $item_to_edit ? 'Update Item' : 'Create Item'; ?>">
                            <?php if ($item_to_edit): ?>
                                <a href="?page=hs-avatar-manager" class="button">Cancel</a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            </div>

            <div id="col-right">
                <div class="col-wrap">
                    <h2>Existing Avatar Items</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Unlock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($all_items): ?>
                                <?php foreach ($all_items as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($item->name); ?></strong>
                                            <?php if ($item->is_default): ?>
                                                <span class="dashicons dashicons-star-filled" style="color: gold;" title="Default Item"></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html(ucwords(str_replace('_', ' ', $item->category))); ?></td>
                                        <td>
                                            <?php if (empty($item->unlock_metric)): ?>
                                                <em>Always unlocked</em>
                                            <?php else: ?>
                                                <?php echo esc_html(ucwords(str_replace('_', ' ', $item->unlock_metric))); ?>: <?php echo esc_html($item->unlock_value); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?page=hs-avatar-manager&action=edit&id=<?php echo $item->id; ?>">Edit</a>
                                            <?php if (!$item->is_default): ?>
                                                | <a href="?page=hs-avatar-manager&action=delete&id=<?php echo $item->id; ?>&_wpnonce=<?php echo wp_create_nonce('hs_delete_avatar_item_' . $item->id); ?>" onclick="return confirm('Are you sure?')">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">No avatar items found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        #col-container { display: flex; gap: 20px; }
        #col-left { flex: 0 0 500px; }
        #col-right { flex: 1; }
        .form-field { margin-bottom: 15px; }
        .form-field label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-field input[type="text"],
        .form-field input[type="number"],
        .form-field select,
        .form-field textarea { width: 100%; }
        .form-field .description { color: #666; font-size: 12px; margin-top: 5px; }
    </style>
    <?php
}
