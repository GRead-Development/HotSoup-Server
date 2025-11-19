<?php
/**
 * Avatar Item Seeder
 * Generates thousands of avatar customization items
 *
 * To run: Go to WordPress Admin → Avatar Manager → Seed Items
 */

if (!defined('ABSPATH')) {
    exit;
}

// Add seeder submenu
function hs_avatar_seeder_add_submenu() {
    add_submenu_page(
        'hs-avatar-manager',
        'Seed Avatar Items',
        'Seed Items',
        'manage_options',
        'hs-avatar-seeder',
        'hs_avatar_seeder_page_html'
    );
}
add_action('admin_menu', 'hs_avatar_seeder_add_submenu');

// Seeder page
function hs_avatar_seeder_page_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';

    if (isset($_POST['seed_items']) && wp_verify_nonce($_POST['_wpnonce'], 'hs_seed_avatar_items')) {
        $count = hs_seed_all_avatar_items();
        echo '<div class="notice notice-success"><p>Successfully seeded ' . $count . ' avatar items!</p></div>';
    }

    if (isset($_POST['clear_items']) && wp_verify_nonce($_POST['_wpnonce'], 'hs_clear_avatar_items')) {
        $wpdb->query("DELETE FROM $table_name WHERE is_default = 0");
        echo '<div class="notice notice-success"><p>Cleared all non-default items!</p></div>';
    }

    $item_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    ?>
    <div class="wrap">
        <h1>Avatar Item Seeder</h1>
        <p>Generate thousands of avatar customization items automatically!</p>

        <div class="card" style="max-width: 800px;">
            <h2>Current Status</h2>
            <p><strong>Total Items in Database:</strong> <?php echo $item_count; ?></p>

            <h3>Categories to be Created:</h3>
            <ul>
                <li><strong>Hats:</strong> ~200 items (caps, crowns, helmets, etc.)</li>
                <li><strong>Accessories:</strong> ~150 items (glasses, masks, facial hair, etc.)</li>
                <li><strong>Backgrounds:</strong> ~100 items (gradients, patterns, themed)</li>
                <li><strong>Shirt Patterns:</strong> ~80 items (stripes, dots, designs)</li>
                <li><strong>Pants Patterns:</strong> ~50 items (stripes, patches, etc.)</li>
                <li><strong>Total:</strong> ~580+ unique items</li>
            </ul>

            <form method="post" style="margin: 20px 0;">
                <?php wp_nonce_field('hs_seed_avatar_items'); ?>
                <button type="submit" name="seed_items" class="button button-primary button-large" onclick="return confirm('This will add hundreds of items to the database. Continue?')">
                    🌱 Seed Avatar Items
                </button>
            </form>

            <form method="post" style="margin: 20px 0;">
                <?php wp_nonce_field('hs_clear_avatar_items'); ?>
                <button type="submit" name="clear_items" class="button button-secondary" onclick="return confirm('This will delete all non-default items. Are you sure?')">
                    🗑️ Clear Non-Default Items
                </button>
            </form>
        </div>

        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>ℹ️ What Gets Created</h2>
            <p>The seeder creates a comprehensive collection of avatar items with varied unlock requirements:</p>
            <ul>
                <li><strong>Free items:</strong> Available to everyone immediately</li>
                <li><strong>Easy unlocks:</strong> 1-10 books read</li>
                <li><strong>Medium unlocks:</strong> 25-100 books, 500-1000 points</li>
                <li><strong>Hard unlocks:</strong> 250+ books, 5000+ points</li>
                <li><strong>Epic unlocks:</strong> 1000+ books, 10000+ points</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Seed all avatar items
 */
function hs_seed_all_avatar_items() {
    $items = array_merge(
        hs_generate_hat_items(),
        hs_generate_accessory_items(),
        hs_generate_background_items(),
        hs_generate_shirt_pattern_items(),
        hs_generate_pants_pattern_items()
    );

    global $wpdb;
    $table_name = $wpdb->prefix . 'hs_avatar_items';
    $count = 0;

    foreach ($items as $item) {
        // Check if item already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE slug = %s",
            $item['slug']
        ));

        if (!$exists) {
            $wpdb->insert($table_name, $item);
            $count++;
        }
    }

    return $count;
}

/**
 * HATS - ~200 items
 */
function hs_generate_hat_items() {
    $items = [];
    $display_order = 0;

    // Baseball Caps (various colors) - 15 items
    $cap_colors = [
        ['name' => 'Red Baseball Cap', 'color' => '#E74C3C', 'unlock' => 0],
        ['name' => 'Blue Baseball Cap', 'color' => '#3498DB', 'unlock' => 5],
        ['name' => 'Green Baseball Cap', 'color' => '#27AE60', 'unlock' => 5],
        ['name' => 'Yellow Baseball Cap', 'color' => '#F1C40F', 'unlock' => 10],
        ['name' => 'Purple Baseball Cap', 'color' => '#9B59B6', 'unlock' => 10],
        ['name' => 'Orange Baseball Cap', 'color' => '#E67E22', 'unlock' => 10],
        ['name' => 'Pink Baseball Cap', 'color' => '#FF69B4', 'unlock' => 15],
        ['name' => 'Black Baseball Cap', 'color' => '#2C3E50', 'unlock' => 15],
        ['name' => 'White Baseball Cap', 'color' => '#ECF0F1', 'unlock' => 20],
        ['name' => 'Gray Baseball Cap', 'color' => '#95A5A6', 'unlock' => 20],
        ['name' => 'Navy Baseball Cap', 'color' => '#34495E', 'unlock' => 25],
        ['name' => 'Teal Baseball Cap', 'color' => '#1ABC9C', 'unlock' => 25],
        ['name' => 'Brown Baseball Cap', 'color' => '#8B4513', 'unlock' => 30],
        ['name' => 'Gold Baseball Cap', 'color' => '#FFD700', 'unlock' => 50],
        ['name' => 'Rainbow Baseball Cap', 'color' => 'url(#rainbow)', 'unlock' => 100],
    ];

    foreach ($cap_colors as $cap) {
        $items[] = [
            'slug' => 'hat-cap-' . strtolower(str_replace(' ', '-', $cap['name'])),
            'name' => $cap['name'],
            'category' => 'hat',
            'svg_data' => '<path d="M 30 25 Q 50 20 70 25 L 70 30 Q 50 27 30 30 Z" fill="' . $cap['color'] . '"/><ellipse cx="50" cy="28" rx="15" ry="3" fill="' . $cap['color'] . '" opacity="0.8"/>',
            'unlock_metric' => $cap['unlock'] > 0 ? 'books_read' : '',
            'unlock_value' => $cap['unlock'],
            'unlock_message' => $cap['unlock'] > 0 ? 'Read ' . $cap['unlock'] . ' books to unlock!' : '',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Beanies - 12 items
    $beanie_colors = [
        ['name' => 'Red Beanie', 'color' => '#E74C3C', 'unlock' => 5],
        ['name' => 'Blue Beanie', 'color' => '#3498DB', 'unlock' => 5],
        ['name' => 'Green Beanie', 'color' => '#27AE60', 'unlock' => 10],
        ['name' => 'Yellow Beanie', 'color' => '#F1C40F', 'unlock' => 10],
        ['name' => 'Purple Beanie', 'color' => '#9B59B6', 'unlock' => 15],
        ['name' => 'Orange Beanie', 'color' => '#E67E22', 'unlock' => 15],
        ['name' => 'Pink Beanie', 'color' => '#FF69B4', 'unlock' => 20],
        ['name' => 'Black Beanie', 'color' => '#2C3E50', 'unlock' => 20],
        ['name' => 'Gray Beanie', 'color' => '#95A5A6', 'unlock' => 25],
        ['name' => 'Striped Beanie', 'color' => '#E74C3C', 'unlock' => 30],
        ['name' => 'Pom-Pom Beanie', 'color' => '#3498DB', 'unlock' => 35],
        ['name' => 'Slouchy Beanie', 'color' => '#9B59B6', 'unlock' => 40],
    ];

    foreach ($beanie_colors as $beanie) {
        $pom_pom = $beanie['name'] === 'Pom-Pom Beanie' ? '<circle cx="50" cy="18" r="3" fill="' . $beanie['color'] . '"/>' : '';
        $items[] = [
            'slug' => 'hat-beanie-' . strtolower(str_replace(' ', '-', $beanie['name'])),
            'name' => $beanie['name'],
            'category' => 'hat',
            'svg_data' => '<path d="M 32 22 Q 50 18 68 22 L 68 28 Q 50 25 32 28 Z" fill="' . $beanie['color'] . '"/>' . $pom_pom,
            'unlock_metric' => 'books_read',
            'unlock_value' => $beanie['unlock'],
            'unlock_message' => 'Read ' . $beanie['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Crowns - 10 items
    $crowns = [
        ['name' => 'Gold Crown', 'color' => '#FFD700', 'accent' => '#DAA520', 'unlock' => 100, 'metric' => 'books_read'],
        ['name' => 'Silver Crown', 'color' => '#C0C0C0', 'accent' => '#A8A8A8', 'unlock' => 75, 'metric' => 'books_read'],
        ['name' => 'Bronze Crown', 'color' => '#CD7F32', 'accent' => '#B87333', 'unlock' => 50, 'metric' => 'books_read'],
        ['name' => 'Ruby Crown', 'color' => '#E74C3C', 'accent' => '#C0392B', 'unlock' => 150, 'metric' => 'books_read'],
        ['name' => 'Emerald Crown', 'color' => '#27AE60', 'accent' => '#229954', 'unlock' => 150, 'metric' => 'books_read'],
        ['name' => 'Sapphire Crown', 'color' => '#3498DB', 'accent' => '#2980B9', 'unlock' => 150, 'metric' => 'books_read'],
        ['name' => 'Diamond Crown', 'color' => '#ECF0F1', 'accent' => '#BDC3C7', 'unlock' => 250, 'metric' => 'books_read'],
        ['name' => 'Royal Crown', 'color' => '#9B59B6', 'accent' => '#8E44AD', 'unlock' => 200, 'metric' => 'books_read'],
        ['name' => 'King\'s Crown', 'color' => '#FFD700', 'accent' => '#DAA520', 'unlock' => 500, 'metric' => 'books_read'],
        ['name' => 'Emperor\'s Crown', 'color' => '#FFD700', 'accent' => '#FF6347', 'unlock' => 1000, 'metric' => 'books_read'],
    ];

    foreach ($crowns as $crown) {
        $items[] = [
            'slug' => 'hat-crown-' . strtolower(str_replace(['\'', ' '], ['', '-'], $crown['name'])),
            'name' => $crown['name'],
            'category' => 'hat',
            'svg_data' => '<path d="M 35 20 L 40 15 L 42 20 L 50 13 L 58 20 L 60 15 L 65 20 L 65 25 L 35 25 Z" fill="' . $crown['color'] . '" stroke="' . $crown['accent'] . '" stroke-width="1"/><circle cx="40" cy="15" r="2" fill="#FF0000"/><circle cx="50" cy="13" r="2" fill="#FF0000"/><circle cx="60" cy="15" r="2" fill="#FF0000"/>',
            'unlock_metric' => $crown['metric'],
            'unlock_value' => $crown['unlock'],
            'unlock_message' => 'Read ' . $crown['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Top Hats - 8 items
    $top_hats = [
        ['name' => 'Classic Top Hat', 'color' => '#000000', 'unlock' => 50],
        ['name' => 'Red Top Hat', 'color' => '#E74C3C', 'unlock' => 60],
        ['name' => 'Blue Top Hat', 'color' => '#3498DB', 'unlock' => 60],
        ['name' => 'Purple Top Hat', 'color' => '#9B59B6', 'unlock' => 70],
        ['name' => 'Green Top Hat', 'color' => '#27AE60', 'unlock' => 70],
        ['name' => 'White Top Hat', 'color' => '#ECF0F1', 'unlock' => 80],
        ['name' => 'Gold Top Hat', 'color' => '#FFD700', 'unlock' => 100],
        ['name' => 'Rainbow Top Hat', 'color' => '#FF69B4', 'unlock' => 150],
    ];

    foreach ($top_hats as $hat) {
        $items[] = [
            'slug' => 'hat-tophat-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => '<rect x="40" y="15" width="20" height="12" rx="1" fill="' . $hat['color'] . '"/><ellipse cx="50" cy="27" rx="15" ry="3" fill="' . $hat['color'] . '"/><ellipse cx="50" cy="15" rx="10" ry="2" fill="' . $hat['color'] . '" opacity="0.7"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Wizard Hats - 8 items
    $wizard_hats = [
        ['name' => 'Blue Wizard Hat', 'color' => '#3498DB', 'unlock' => 75],
        ['name' => 'Purple Wizard Hat', 'color' => '#9B59B6', 'unlock' => 75],
        ['name' => 'Black Wizard Hat', 'color' => '#2C3E50', 'unlock' => 80],
        ['name' => 'Red Wizard Hat', 'color' => '#E74C3C', 'unlock' => 85],
        ['name' => 'Green Wizard Hat', 'color' => '#27AE60', 'unlock' => 90],
        ['name' => 'Starry Wizard Hat', 'color' => '#2C3E50', 'unlock' => 100],
        ['name' => 'Moon Wizard Hat', 'color' => '#34495E', 'unlock' => 125],
        ['name' => 'Grand Wizard Hat', 'color' => '#9B59B6', 'unlock' => 200],
    ];

    foreach ($wizard_hats as $hat) {
        $stars = strpos($hat['name'], 'Starry') !== false ? '<circle cx="45" cy="15" r="1" fill="#FFD700"/><circle cx="52" cy="18" r="1" fill="#FFD700"/><circle cx="48" cy="12" r="1" fill="#FFD700"/>' : '';
        $moon = strpos($hat['name'], 'Moon') !== false ? '<path d="M 48 15 Q 50 13 52 15 Q 50 17 48 15" fill="#F1C40F"/>' : '';
        $items[] = [
            'slug' => 'hat-wizard-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => '<path d="M 35 28 L 50 10 L 65 28 Z" fill="' . $hat['color'] . '"/><ellipse cx="50" cy="28" rx="16" ry="3" fill="' . $hat['color'] . '"/>' . $stars . $moon,
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Party Hats - 10 items
    $party_hats = [
        ['name' => 'Red Party Hat', 'color' => '#E74C3C', 'unlock' => 10],
        ['name' => 'Blue Party Hat', 'color' => '#3498DB', 'unlock' => 10],
        ['name' => 'Green Party Hat', 'color' => '#27AE60', 'unlock' => 10],
        ['name' => 'Yellow Party Hat', 'color' => '#F1C40F', 'unlock' => 15],
        ['name' => 'Purple Party Hat', 'color' => '#9B59B6', 'unlock' => 15],
        ['name' => 'Orange Party Hat', 'color' => '#E67E22', 'unlock' => 15],
        ['name' => 'Pink Party Hat', 'color' => '#FF69B4', 'unlock' => 20],
        ['name' => 'Rainbow Party Hat', 'color' => '#FF69B4', 'unlock' => 50],
        ['name' => 'Striped Party Hat', 'color' => '#E74C3C', 'unlock' => 25],
        ['name' => 'Polka Dot Party Hat', 'color' => '#3498DB', 'unlock' => 30],
    ];

    foreach ($party_hats as $hat) {
        $pom = '<circle cx="50" cy="10" r="2" fill="' . $hat['color'] . '" opacity="0.8"/>';
        $items[] = [
            'slug' => 'hat-party-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => '<path d="M 40 28 L 50 10 L 60 28 Z" fill="' . $hat['color'] . '"/>' . $pom,
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Chef Hats - 6 items
    $chef_hats = [
        ['name' => 'White Chef Hat', 'unlock' => 40],
        ['name' => 'Tall Chef Hat', 'unlock' => 50],
        ['name' => 'Professional Chef Hat', 'unlock' => 75],
        ['name' => 'Master Chef Hat', 'unlock' => 100],
        ['name' => 'Executive Chef Hat', 'unlock' => 150],
        ['name' => 'Michelin Chef Hat', 'unlock' => 250],
    ];

    foreach ($chef_hats as $hat) {
        $items[] = [
            'slug' => 'hat-chef-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => '<rect x="35" y="25" width="30" height="3" rx="1" fill="#FFFFFF" stroke="#000" stroke-width="0.5"/><path d="M 37 18 Q 37 12 43 12 Q 43 15 50 15 Q 57 15 57 12 Q 63 12 63 18 L 63 25 L 37 25 Z" fill="#FFFFFF" stroke="#000" stroke-width="0.5"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Pirate Hats - 6 items
    $pirate_hats = [
        ['name' => 'Black Pirate Hat', 'color' => '#2C3E50', 'unlock' => 60],
        ['name' => 'Brown Pirate Hat', 'color' => '#8B4513', 'unlock' => 60],
        ['name' => 'Red Pirate Hat', 'color' => '#E74C3C', 'unlock' => 70],
        ['name' => 'Captain\'s Hat', 'color' => '#2C3E50', 'unlock' => 100],
        ['name' => 'Skull Pirate Hat', 'color' => '#000000', 'unlock' => 150],
        ['name' => 'Admiral Pirate Hat', 'color' => '#34495E', 'unlock' => 200],
    ];

    foreach ($pirate_hats as $hat) {
        $skull = strpos($hat['name'], 'Skull') !== false ? '<circle cx="50" cy="22" r="3" fill="#FFFFFF"/><circle cx="48" cy="21" r="0.8" fill="#000"/><circle cx="52" cy="21" r="0.8" fill="#000"/>' : '';
        $items[] = [
            'slug' => 'hat-pirate-' . strtolower(str_replace(['\'', ' '], ['', '-'], $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => '<path d="M 35 20 L 35 25 L 65 25 L 65 20 Q 50 18 35 20 Z" fill="' . $hat['color'] . '"/><path d="M 45 15 L 50 20 L 55 15" fill="' . $hat['color'] . '"/>' . $skull,
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Cowboy Hats - 8 items
    $cowboy_hats = [
        ['name' => 'Brown Cowboy Hat', 'color' => '#8B4513', 'unlock' => 45],
        ['name' => 'Black Cowboy Hat', 'color' => '#2C3E50', 'unlock' => 45],
        ['name' => 'White Cowboy Hat', 'color' => '#ECF0F1', 'unlock' => 50],
        ['name' => 'Tan Cowboy Hat', 'color' => '#D2B48C', 'unlock' => 50],
        ['name' => 'Red Cowboy Hat', 'color' => '#E74C3C', 'unlock' => 60],
        ['name' => 'Sheriff Cowboy Hat', 'color' => '#8B4513', 'unlock' => 100],
        ['name' => 'Outlaw Cowboy Hat', 'color' => '#000000', 'unlock' => 125],
        ['name' => 'Legendary Cowboy Hat', 'color' => '#FFD700', 'unlock' => 200],
    ];

    foreach ($cowboy_hats as $hat) {
        $star = strpos($hat['name'], 'Sheriff') !== false ? '<circle cx="50" cy="22" r="2.5" fill="#FFD700"/>' : '';
        $items[] = [
            'slug' => 'hat-cowboy-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => '<ellipse cx="50" cy="26" rx="18" ry="3" fill="' . $hat['color'] . '"/><path d="M 38 20 Q 50 15 62 20 L 62 26 L 38 26 Z" fill="' . $hat['color'] . '"/>' . $star,
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Graduation Caps - 8 items
    $grad_caps = [
        ['name' => 'Black Graduation Cap', 'color' => '#000000', 'unlock' => 100, 'metric' => 'books_read'],
        ['name' => 'Blue Graduation Cap', 'color' => '#3498DB', 'unlock' => 100, 'metric' => 'books_read'],
        ['name' => 'Red Graduation Cap', 'color' => '#E74C3C', 'unlock' => 125, 'metric' => 'books_read'],
        ['name' => 'Green Graduation Cap', 'color' => '#27AE60', 'unlock' => 125, 'metric' => 'books_read'],
        ['name' => 'Purple Graduation Cap', 'color' => '#9B59B6', 'unlock' => 150, 'metric' => 'books_read'],
        ['name' => 'Gold Graduation Cap', 'color' => '#FFD700', 'unlock' => 250, 'metric' => 'books_read'],
        ['name' => 'PhD Graduation Cap', 'color' => '#000000', 'unlock' => 500, 'metric' => 'books_read'],
        ['name' => 'Honorary Doctorate Cap', 'color' => '#9B59B6', 'unlock' => 1000, 'metric' => 'books_read'],
    ];

    foreach ($grad_caps as $cap) {
        $items[] = [
            'slug' => 'hat-graduation-' . strtolower(str_replace(' ', '-', $cap['name'])),
            'name' => $cap['name'],
            'category' => 'hat',
            'svg_data' => '<rect x="40" y="20" width="20" height="4" fill="' . $cap['color'] . '"/><path d="M 32 20 L 50 17 L 68 20 L 50 23 Z" fill="' . $cap['color'] . '"/><line x1="68" y1="20" x2="68" y2="28" stroke="' . $cap['color'] . '" stroke-width="1"/><circle cx="68" cy="28" r="1.5" fill="#FFD700"/>',
            'unlock_metric' => $cap['metric'],
            'unlock_value' => $cap['unlock'],
            'unlock_message' => 'Read ' . $cap['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Helmets - 12 items
    $helmets = [
        ['name' => 'Knight Helmet', 'color' => '#95A5A6', 'unlock' => 80],
        ['name' => 'Viking Helmet', 'color' => '#8B4513', 'unlock' => 75],
        ['name' => 'Roman Helmet', 'color' => '#CD7F32', 'unlock' => 85],
        ['name' => 'Spartan Helmet', 'color' => '#CD7F32', 'unlock' => 90],
        ['name' => 'Samurai Helmet', 'color' => '#2C3E50', 'unlock' => 100],
        ['name' => 'Football Helmet', 'color' => '#E74C3C', 'unlock' => 50],
        ['name' => 'Racing Helmet', 'color' => '#E74C3C', 'unlock' => 60],
        ['name' => 'Astronaut Helmet', 'color' => '#FFFFFF', 'unlock' => 150],
        ['name' => 'Motorcycle Helmet', 'color' => '#000000', 'unlock' => 70],
        ['name' => 'Construction Helmet', 'color' => '#F1C40F', 'unlock' => 40],
        ['name' => 'Firefighter Helmet', 'color' => '#E74C3C', 'unlock' => 65],
        ['name' => 'Police Helmet', 'color' => '#3498DB', 'unlock' => 65],
    ];

    foreach ($helmets as $helmet) {
        $visor = in_array($helmet['name'], ['Astronaut Helmet', 'Racing Helmet', 'Motorcycle Helmet']) ? '<path d="M 38 22 L 38 26 Q 50 28 62 26 L 62 22" fill="rgba(255,255,255,0.3)"/>' : '';
        $horns = $helmet['name'] === 'Viking Helmet' ? '<path d="M 35 20 L 30 15 L 35 18" fill="' . $helmet['color'] . '"/><path d="M 65 20 L 70 15 L 65 18" fill="' . $helmet['color'] . '"/>' : '';
        $plume = in_array($helmet['name'], ['Roman Helmet', 'Spartan Helmet']) ? '<path d="M 50 18 L 48 12 L 52 12 Z" fill="#E74C3C"/>' : '';

        $items[] = [
            'slug' => 'hat-helmet-' . strtolower(str_replace(' ', '-', $helmet['name'])),
            'name' => $helmet['name'],
            'category' => 'hat',
            'svg_data' => '<path d="M 35 20 Q 35 15 50 15 Q 65 15 65 20 L 65 28 L 35 28 Z" fill="' . $helmet['color'] . '" stroke="#000" stroke-width="0.5"/>' . $visor . $horns . $plume,
            'unlock_metric' => 'books_read',
            'unlock_value' => $helmet['unlock'],
            'unlock_message' => 'Read ' . $helmet['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Headbands - 10 items
    $headbands = [
        ['name' => 'Red Headband', 'color' => '#E74C3C', 'unlock' => 15],
        ['name' => 'Blue Headband', 'color' => '#3498DB', 'unlock' => 15],
        ['name' => 'Green Headband', 'color' => '#27AE60', 'unlock' => 20],
        ['name' => 'Yellow Headband', 'color' => '#F1C40F', 'unlock' => 20],
        ['name' => 'Purple Headband', 'color' => '#9B59B6', 'unlock' => 25],
        ['name' => 'Black Headband', 'color' => '#2C3E50', 'unlock' => 25],
        ['name' => 'White Headband', 'color' => '#FFFFFF', 'unlock' => 30],
        ['name' => 'Sweatband', 'color' => '#95A5A6', 'unlock' => 35],
        ['name' => 'Ninja Headband', 'color' => '#2C3E50', 'unlock' => 100],
        ['name' => 'Warrior Headband', 'color' => '#E74C3C', 'unlock' => 125],
    ];

    foreach ($headbands as $band) {
        $items[] = [
            'slug' => 'hat-headband-' . strtolower(str_replace(' ', '-', $band['name'])),
            'name' => $band['name'],
            'category' => 'hat',
            'svg_data' => '<rect x="32" y="28" width="36" height="3" rx="1" fill="' . $band['color'] . '" stroke="#000" stroke-width="0.3"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $band['unlock'],
            'unlock_message' => 'Read ' . $band['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Flower Crowns - 10 items
    $flower_crowns = [
        ['name' => 'Rose Flower Crown', 'color' => '#E74C3C', 'unlock' => 30],
        ['name' => 'Daisy Flower Crown', 'color' => '#FFFFFF', 'unlock' => 30],
        ['name' => 'Sunflower Crown', 'color' => '#F1C40F', 'unlock' => 35],
        ['name' => 'Lavender Crown', 'color' => '#9B59B6', 'unlock' => 35],
        ['name' => 'Cherry Blossom Crown', 'color' => '#FFB6C1', 'unlock' => 40],
        ['name' => 'Tulip Crown', 'color' => '#FF69B4', 'unlock' => 40],
        ['name' => 'Wildflower Crown', 'color' => '#FF69B4', 'unlock' => 45],
        ['name' => 'Rainbow Flower Crown', 'color' => '#FF69B4', 'unlock' => 75],
        ['name' => 'Fairy Flower Crown', 'color' => '#FFB6C1', 'unlock' => 100],
        ['name' => 'Enchanted Flower Crown', 'color' => '#9B59B6', 'unlock' => 150],
    ];

    foreach ($flower_crowns as $crown) {
        $items[] = [
            'slug' => 'hat-flowercrown-' . strtolower(str_replace(' ', '-', $crown['name'])),
            'name' => $crown['name'],
            'category' => 'hat',
            'svg_data' => '<circle cx="38" cy="25" r="3" fill="' . $crown['color'] . '"/><circle cx="45" cy="23" r="3" fill="' . $crown['color'] . '"/><circle cx="50" cy="22" r="3" fill="' . $crown['color'] . '"/><circle cx="55" cy="23" r="3" fill="' . $crown['color'] . '"/><circle cx="62" cy="25" r="3" fill="' . $crown['color'] . '"/><path d="M 35 26 Q 50 24 65 26" stroke="#27AE60" stroke-width="1.5" fill="none"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $crown['unlock'],
            'unlock_message' => 'Read ' . $crown['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Seasonal/Holiday Hats - 15 items
    $seasonal = [
        ['name' => 'Santa Hat', 'color' => '#E74C3C', 'unlock' => 25],
        ['name' => 'Elf Hat', 'color' => '#27AE60', 'unlock' => 20],
        ['name' => 'Witch Hat', 'color' => '#000000', 'unlock' => 50],
        ['name' => 'Leprechaun Hat', 'color' => '#27AE60', 'unlock' => 40],
        ['name' => 'Bunny Ears', 'color' => '#FFB6C1', 'unlock' => 30],
        ['name' => 'Turkey Hat', 'color' => '#8B4513', 'unlock' => 35],
        ['name' => 'Pumpkin Hat', 'color' => '#E67E22', 'unlock' => 45],
        ['name' => 'Snowman Hat', 'color' => '#000000', 'unlock' => 30],
        ['name' => 'Reindeer Antlers', 'color' => '#8B4513', 'unlock' => 35],
        ['name' => 'Gingerbread Hat', 'color' => '#8B4513', 'unlock' => 40],
        ['name' => 'New Year Party Hat', 'color' => '#FFD700', 'unlock' => 20],
        ['name' => 'Valentine Heart Hat', 'color' => '#FF69B4', 'unlock' => 25],
        ['name' => 'Easter Egg Hat', 'color' => '#FF69B4', 'unlock' => 30],
        ['name' => 'Independence Fireworks Hat', 'color' => '#E74C3C', 'unlock' => 40],
        ['name' => 'Halloween Spider Hat', 'color' => '#000000', 'unlock' => 50],
    ];

    foreach ($seasonal as $hat) {
        $svg = '<path d="M 35 25 Q 50 15 65 25 L 60 28 Q 50 22 40 28 Z" fill="' . $hat['color'] . '"/>';

        if ($hat['name'] === 'Santa Hat') {
            $svg = '<path d="M 35 28 L 50 12 L 65 28 Z" fill="#E74C3C"/><circle cx="50" cy="12" r="3" fill="#FFFFFF"/><rect x="35" y="28" width="30" height="3" fill="#FFFFFF"/>';
        } elseif ($hat['name'] === 'Witch Hat') {
            $svg = '<path d="M 35 28 L 50 8 L 65 28 Z" fill="#000000"/><ellipse cx="50" cy="28" rx="16" ry="3" fill="#000000"/><rect x="48" y="20" width="4" height="3" fill="#FFD700"/>';
        } elseif ($hat['name'] === 'Bunny Ears') {
            $svg = '<ellipse cx="42" cy="15" rx="3" ry="8" fill="#FFB6C1"/><ellipse cx="58" cy="15" rx="3" ry="8" fill="#FFB6C1"/><ellipse cx="42" cy="15" rx="1.5" ry="6" fill="#FFE4E1"/><ellipse cx="58" cy="15" rx="1.5" ry="6" fill="#FFE4E1"/>';
        } elseif ($hat['name'] === 'Reindeer Antlers') {
            $svg = '<path d="M 38 25 L 35 18 L 32 22 M 35 18 L 33 15" stroke="#8B4513" stroke-width="2" fill="none"/><path d="M 62 25 L 65 18 L 68 22 M 65 18 L 67 15" stroke="#8B4513" stroke-width="2" fill="none"/>';
        }

        $items[] = [
            'slug' => 'hat-seasonal-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Miscellaneous Hats - 20 items
    $misc_hats = [
        ['name' => 'Propeller Beanie', 'color' => '#3498DB', 'unlock' => 35],
        ['name' => 'Fez', 'color' => '#E74C3C', 'unlock' => 45],
        ['name' => 'Beret', 'color' => '#2C3E50', 'unlock' => 40],
        ['name' => 'Sombrero', 'color' => '#F1C40F', 'unlock' => 50],
        ['name' => 'Turban', 'color' => '#9B59B6', 'unlock' => 55],
        ['name' => 'Trilby', 'color' => '#2C3E50', 'unlock' => 60],
        ['name' => 'Fedora', 'color' => '#8B4513', 'unlock' => 65],
        ['name' => 'Bowler Hat', 'color' => '#000000', 'unlock' => 70],
        ['name' => 'Sun Hat', 'color' => '#F1C40F', 'unlock' => 30],
        ['name' => 'Fishing Hat', 'color' => '#27AE60', 'unlock' => 35],
        ['name' => 'Safari Hat', 'color' => '#D2B48C', 'unlock' => 55],
        ['name' => 'Bucket Hat', 'color' => '#95A5A6', 'unlock' => 25],
        ['name' => 'Newsboy Cap', 'color' => '#8B4513', 'unlock' => 45],
        ['name' => 'Flat Cap', 'color' => '#2C3E50', 'unlock' => 50],
        ['name' => 'Deerstalker', 'color' => '#8B4513', 'unlock' => 100],
        ['name' => 'Pork Pie Hat', 'color' => '#000000', 'unlock' => 75],
        ['name' => 'Panama Hat', 'color' => '#F5F5DC', 'unlock' => 60],
        ['name' => 'Boater Hat', 'color' => '#FFFFFF', 'unlock' => 55],
        ['name' => 'Cloche Hat', 'color' => '#9B59B6', 'unlock' => 65],
        ['name' => 'Pillbox Hat', 'color' => '#E74C3C', 'unlock' => 70],
    ];

    foreach ($misc_hats as $hat) {
        $svg = '<ellipse cx="50" cy="24" rx="14" ry="5" fill="' . $hat['color'] . '"/><path d="M 40 19 Q 50 16 60 19 L 60 24 L 40 24 Z" fill="' . $hat['color'] . '"/>';

        if ($hat['name'] === 'Propeller Beanie') {
            $svg = '<path d="M 35 24 Q 50 20 65 24 L 65 28 Q 50 25 35 28 Z" fill="' . $hat['color'] . '"/><rect x="48" y="18" width="4" height="2" fill="#E74C3C"/><line x1="40" y1="19" x2="60" y2="19" stroke="#2C3E50" stroke-width="1"/>';
        } elseif ($hat['name'] === 'Sombrero') {
            $svg = '<ellipse cx="50" cy="28" rx="22" ry="4" fill="' . $hat['color'] . '"/><path d="M 42 20 Q 50 16 58 20 L 58 28 L 42 28 Z" fill="' . $hat['color'] . '"/>';
        }

        $items[] = [
            'slug' => 'hat-misc-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Book-themed Hats - 10 items
    $book_hats = [
        ['name' => 'Book Stack Hat', 'unlock' => 50],
        ['name' => 'Open Book Hat', 'unlock' => 75],
        ['name' => 'Library Card Hat', 'unlock' => 100],
        ['name' => 'Bookmark Hat', 'unlock' => 60],
        ['name' => 'Reading Lamp Hat', 'unlock' => 80],
        ['name' => 'Quill Pen Hat', 'unlock' => 90],
        ['name' => 'Ink Bottle Hat', 'unlock' => 95],
        ['name' => 'Ancient Tome Hat', 'unlock' => 200],
        ['name' => 'Magical Spellbook Hat', 'unlock' => 300],
        ['name' => 'Librarian Hat', 'unlock' => 500],
    ];

    foreach ($book_hats as $hat) {
        $svg = '<rect x="42" y="18" width="16" height="12" rx="1" fill="#8B4513" stroke="#000" stroke-width="0.5"/><rect x="43" y="19" width="14" height="10" fill="#DEB887"/><line x1="50" y1="19" x2="50" y2="29" stroke="#8B4513" stroke-width="0.5"/>';

        $items[] = [
            'slug' => 'hat-book-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Achievement Hats - 15 items (high unlock requirements)
    $achievement_hats = [
        ['name' => 'Bronze Reader Hat', 'unlock' => 100],
        ['name' => 'Silver Reader Hat', 'unlock' => 250],
        ['name' => 'Gold Reader Hat', 'unlock' => 500],
        ['name' => 'Platinum Reader Hat', 'unlock' => 750],
        ['name' => 'Diamond Reader Hat', 'unlock' => 1000],
        ['name' => 'Master Reader Crown', 'unlock' => 1500],
        ['name' => 'Legendary Reader Crown', 'unlock' => 2000],
        ['name' => 'Ultimate Reader Crown', 'unlock' => 3000],
        ['name' => 'Bookworm Crown', 'unlock' => 500],
        ['name' => 'Speed Reader Cap', 'unlock' => 200],
        ['name' => 'Genre Master Hat', 'unlock' => 300],
        ['name' => 'Series Completionist Crown', 'unlock' => 400],
        ['name' => 'Marathon Reader Headband', 'unlock' => 600],
        ['name' => 'Cosmic Reader Halo', 'unlock' => 5000],
        ['name' => 'Infinite Reader Nimbus', 'unlock' => 10000],
    ];

    foreach ($achievement_hats as $hat) {
        $color = '#FFD700';
        if (strpos($hat['name'], 'Bronze') !== false) $color = '#CD7F32';
        if (strpos($hat['name'], 'Silver') !== false) $color = '#C0C0C0';
        if (strpos($hat['name'], 'Platinum') !== false) $color = '#E5E4E2';
        if (strpos($hat['name'], 'Diamond') !== false) $color = '#B9F2FF';

        $svg = '<path d="M 35 20 L 40 15 L 42 20 L 50 13 L 58 20 L 60 15 L 65 20 L 65 25 L 35 25 Z" fill="' . $color . '" stroke="#000" stroke-width="0.5"/><circle cx="40" cy="15" r="2" fill="#FF0000"/><circle cx="50" cy="13" r="2" fill="#FF0000"/><circle cx="60" cy="15" r="2" fill="#FF0000"/>';

        $items[] = [
            'slug' => 'hat-achievement-' . strtolower(str_replace(' ', '-', $hat['name'])),
            'name' => $hat['name'],
            'category' => 'hat',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $hat['unlock'],
            'unlock_message' => 'Read ' . $hat['unlock'] . ' books to unlock this legendary hat!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    return $items;
}

/**
 * ACCESSORIES - ~150 items
 * (Glasses, masks, facial hair, earrings, etc.)
 */
function hs_generate_accessory_items() {
    $items = [];
    $display_order = 0;

    // Reading Glasses - 15 items
    $glasses = [
        ['name' => 'Black Reading Glasses', 'color' => '#000000', 'unlock' => 10],
        ['name' => 'Brown Reading Glasses', 'color' => '#8B4513', 'unlock' => 10],
        ['name' => 'Red Reading Glasses', 'color' => '#E74C3C', 'unlock' => 15],
        ['name' => 'Blue Reading Glasses', 'color' => '#3498DB', 'unlock' => 15],
        ['name' => 'Green Reading Glasses', 'color' => '#27AE60', 'unlock' => 20],
        ['name' => 'Purple Reading Glasses', 'color' => '#9B59B6', 'unlock' => 20],
        ['name' => 'Gold Reading Glasses', 'color' => '#FFD700', 'unlock' => 50],
        ['name' => 'Silver Reading Glasses', 'color' => '#C0C0C0', 'unlock' => 40],
        ['name' => 'Round Glasses', 'color' => '#000000', 'unlock' => 25],
        ['name' => 'Square Glasses', 'color' => '#2C3E50', 'unlock' => 25],
        ['name' => 'Cat Eye Glasses', 'color' => '#E74C3C', 'unlock' => 30],
        ['name' => 'Aviator Glasses', 'color' => '#FFD700', 'unlock' => 35],
        ['name' => 'Hipster Glasses', 'color' => '#8B4513', 'unlock' => 40],
        ['name' => 'Professor Glasses', 'color' => '#2C3E50', 'unlock' => 100],
        ['name' => 'Wise Owl Glasses', 'color' => '#8B4513', 'unlock' => 250],
    ];

    foreach ($glasses as $glass) {
        $items[] = [
            'slug' => 'acc-glasses-' . strtolower(str_replace(' ', '-', $glass['name'])),
            'name' => $glass['name'],
            'category' => 'accessory',
            'svg_data' => '<circle cx="42" cy="50" r="6" fill="none" stroke="' . $glass['color'] . '" stroke-width="2"/><circle cx="58" cy="50" r="6" fill="none" stroke="' . $glass['color'] . '" stroke-width="2"/><line x1="48" y1="50" x2="52" y2="50" stroke="' . $glass['color'] . '" stroke-width="2"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $glass['unlock'],
            'unlock_message' => 'Read ' . $glass['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Sunglasses - 12 items
    $sunglasses = [
        ['name' => 'Black Sunglasses', 'unlock' => 20],
        ['name' => 'Cool Aviators', 'unlock' => 30],
        ['name' => 'Retro Sunglasses', 'unlock' => 35],
        ['name' => 'Sport Sunglasses', 'unlock' => 40],
        ['name' => 'Heart Sunglasses', 'unlock' => 25],
        ['name' => 'Star Sunglasses', 'unlock' => 45],
        ['name' => 'Rainbow Sunglasses', 'unlock' => 50],
        ['name' => 'Neon Sunglasses', 'unlock' => 55],
        ['name' => 'Steampunk Goggles', 'unlock' => 75],
        ['name' => 'Cyberpunk Visor', 'unlock' => 100],
        ['name' => 'Matrix Sunglasses', 'unlock' => 150],
        ['name' => 'Deal With It Sunglasses', 'unlock' => 200],
    ];

    foreach ($sunglasses as $sun) {
        $items[] = [
            'slug' => 'acc-sunglasses-' . strtolower(str_replace(' ', '-', $sun['name'])),
            'name' => $sun['name'],
            'category' => 'accessory',
            'svg_data' => '<rect x="36" y="47" width="12" height="6" rx="2" fill="#000000" opacity="0.8"/><rect x="52" y="47" width="12" height="6" rx="2" fill="#000000" opacity="0.8"/><line x1="48" y1="50" x2="52" y2="50" stroke="#000000" stroke-width="2"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $sun['unlock'],
            'unlock_message' => 'Read ' . $sun['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Monocles - 8 items
    $monocles = [
        ['name' => 'Classic Monocle', 'unlock' => 50],
        ['name' => 'Gold Monocle', 'unlock' => 75],
        ['name' => 'Silver Monocle', 'unlock' => 60],
        ['name' => 'Bronze Monocle', 'unlock' => 55],
        ['name' => 'Gentleman\'s Monocle', 'unlock' => 100],
        ['name' => 'Detective Monocle', 'unlock' => 125],
        ['name' => 'Steampunk Monocle', 'unlock' => 150],
        ['name' => 'Jeweled Monocle', 'unlock' => 200],
    ];

    foreach ($monocles as $monocle) {
        $color = '#000000';
        if (strpos($monocle['name'], 'Gold') !== false) $color = '#FFD700';
        if (strpos($monocle['name'], 'Silver') !== false) $color = '#C0C0C0';
        if (strpos($monocle['name'], 'Bronze') !== false) $color = '#CD7F32';

        $items[] = [
            'slug' => 'acc-monocle-' . strtolower(str_replace(['\'', ' '], ['', '-'], $monocle['name'])),
            'name' => $monocle['name'],
            'category' => 'accessory',
            'svg_data' => '<circle cx="56" cy="50" r="6" fill="none" stroke="' . $color . '" stroke-width="2"/><line x1="56" y1="56" x2="58" y2="62" stroke="' . $color . '" stroke-width="1"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $monocle['unlock'],
            'unlock_message' => 'Read ' . $monocle['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Masks - 15 items
    $masks = [
        ['name' => 'Superhero Mask', 'color' => '#E74C3C', 'unlock' => 40],
        ['name' => 'Ninja Mask', 'color' => '#000000', 'unlock' => 60],
        ['name' => 'Masquerade Mask', 'color' => '#9B59B6', 'unlock' => 50],
        ['name' => 'Zorro Mask', 'color' => '#000000', 'unlock' => 70],
        ['name' => 'Phantom Mask', 'color' => '#FFFFFF', 'unlock' => 80],
        ['name' => 'Mardi Gras Mask', 'color' => '#FFD700', 'unlock' => 55],
        ['name' => 'Venetian Mask', 'color' => '#9B59B6', 'unlock' => 65],
        ['name' => 'Butterfly Mask', 'color' => '#FF69B4', 'unlock' => 45],
        ['name' => 'Cat Mask', 'color' => '#2C3E50', 'unlock' => 35],
        ['name' => 'Fox Mask', 'color' => '#E67E22', 'unlock' => 50],
        ['name' => 'Wolf Mask', 'color' => '#7F8C8D', 'unlock' => 75],
        ['name' => 'Raven Mask', 'color' => '#000000', 'unlock' => 85],
        ['name' => 'Dragon Mask', 'color' => '#E74C3C', 'unlock' => 150],
        ['name' => 'Oni Mask', 'color' => '#E74C3C', 'unlock' => 200],
        ['name' => 'Kitsune Mask', 'color' => '#FFFFFF', 'unlock' => 250],
    ];

    foreach ($masks as $mask) {
        $items[] = [
            'slug' => 'acc-mask-' . strtolower(str_replace(' ', '-', $mask['name'])),
            'name' => $mask['name'],
            'category' => 'accessory',
            'svg_data' => '<path d="M 38 45 Q 50 42 62 45 L 62 52 Q 50 50 38 52 Z" fill="' . $mask['color'] . '" stroke="#000" stroke-width="0.5"/><circle cx="44" cy="48" r="2" fill="#000"/><circle cx="56" cy="48" r="2" fill="#000"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $mask['unlock'],
            'unlock_message' => 'Read ' . $mask['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Mustaches - 20 items
    $mustaches = [
        ['name' => 'Classic Mustache', 'unlock' => 25],
        ['name' => 'Handlebar Mustache', 'unlock' => 30],
        ['name' => 'Pencil Mustache', 'unlock' => 28],
        ['name' => 'Walrus Mustache', 'unlock' => 35],
        ['name' => 'Chevron Mustache', 'unlock' => 32],
        ['name' => 'Fu Manchu Mustache', 'unlock' => 40],
        ['name' => 'Horseshoe Mustache', 'unlock' => 38],
        ['name' => 'English Mustache', 'unlock' => 45],
        ['name' => 'Imperial Mustache', 'unlock' => 50],
        ['name' => 'Dali Mustache', 'unlock' => 60],
        ['name' => 'Toothbrush Mustache', 'unlock' => 42],
        ['name' => 'Pyramid Mustache', 'unlock' => 48],
        ['name' => 'Lampshade Mustache', 'unlock' => 44],
        ['name' => 'Petite Handlebar', 'unlock' => 35],
        ['name' => 'Freestyle Mustache', 'unlock' => 70],
        ['name' => 'Goatee', 'unlock' => 50],
        ['name' => 'Van Dyke', 'unlock' => 55],
        ['name' => 'Soul Patch', 'unlock' => 30],
        ['name' => 'Circle Beard', 'unlock' => 65],
        ['name' => 'Mutton Chops', 'unlock' => 75],
    ];

    foreach ($mustaches as $stache) {
        $items[] = [
            'slug' => 'acc-mustache-' . strtolower(str_replace(' ', '-', $stache['name'])),
            'name' => $stache['name'],
            'category' => 'accessory',
            'svg_data' => '<path d="M 40 55 Q 45 52 50 52 Q 55 52 60 55" stroke="#2C3E50" stroke-width="2.5" fill="none" stroke-linecap="round"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $stache['unlock'],
            'unlock_message' => 'Read ' . $stache['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Beards - 15 items
    $beards = [
        ['name' => 'Short Beard', 'unlock' => 40],
        ['name' => 'Medium Beard', 'unlock' => 50],
        ['name' => 'Long Beard', 'unlock' => 60],
        ['name' => 'Full Beard', 'unlock' => 70],
        ['name' => 'Viking Beard', 'unlock' => 100],
        ['name' => 'Wizard Beard', 'unlock' => 150],
        ['name' => 'Dwarf Beard', 'unlock' => 125],
        ['name' => 'Braided Beard', 'unlock' => 90],
        ['name' => 'Hipster Beard', 'unlock' => 55],
        ['name' => 'Lumberjack Beard', 'unlock' => 80],
        ['name' => 'Santa Beard', 'unlock' => 100],
        ['name' => 'Gandalf Beard', 'unlock' => 250],
        ['name' => 'Dumbledore Beard', 'unlock' => 300],
        ['name' => 'Philosopher Beard', 'unlock' => 200],
        ['name' => 'Sage Beard', 'unlock' => 500],
    ];

    foreach ($beards as $beard) {
        $items[] = [
            'slug' => 'acc-beard-' . strtolower(str_replace(' ', '-', $beard['name'])),
            'name' => $beard['name'],
            'category' => 'accessory',
            'svg_data' => '<path d="M 38 52 Q 40 58 42 65 M 62 52 Q 60 58 58 65 M 42 60 Q 50 62 58 60" stroke="#2C3E50" stroke-width="2" fill="none"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $beard['unlock'],
            'unlock_message' => 'Read ' . $beard['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Earrings - 12 items
    $earrings = [
        ['name' => 'Small Stud Earrings', 'color' => '#FFD700', 'unlock' => 15],
        ['name' => 'Gold Hoop Earrings', 'color' => '#FFD700', 'unlock' => 20],
        ['name' => 'Silver Hoop Earrings', 'color' => '#C0C0C0', 'unlock' => 20],
        ['name' => 'Diamond Stud Earrings', 'color' => '#B9F2FF', 'unlock' => 50],
        ['name' => 'Pearl Earrings', 'color' => '#FFFFFF', 'unlock' => 30],
        ['name' => 'Ruby Earrings', 'color' => '#E74C3C', 'unlock' => 40],
        ['name' => 'Emerald Earrings', 'color' => '#27AE60', 'unlock' => 40],
        ['name' => 'Sapphire Earrings', 'color' => '#3498DB', 'unlock' => 40],
        ['name' => 'Dangle Earrings', 'color' => '#FFD700', 'unlock' => 35],
        ['name' => 'Chandelier Earrings', 'color' => '#FFD700', 'unlock' => 60],
        ['name' => 'Feather Earrings', 'color' => '#8B4513', 'unlock' => 45],
        ['name' => 'Star Earrings', 'color' => '#FFD700', 'unlock' => 50],
    ];

    foreach ($earrings as $earring) {
        $items[] = [
            'slug' => 'acc-earring-' . strtolower(str_replace(' ', '-', $earring['name'])),
            'name' => $earring['name'],
            'category' => 'accessory',
            'svg_data' => '<circle cx="35" cy="50" r="1.5" fill="' . $earring['color'] . '" stroke="#000" stroke-width="0.3"/><circle cx="65" cy="50" r="1.5" fill="' . $earring['color'] . '" stroke="#000" stroke-width="0.3"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $earring['unlock'],
            'unlock_message' => 'Read ' . $earring['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Eye Patches - 8 items
    $eyepatches = [
        ['name' => 'Black Eye Patch', 'unlock' => 50],
        ['name' => 'Brown Leather Eye Patch', 'unlock' => 55],
        ['name' => 'Red Eye Patch', 'unlock' => 60],
        ['name' => 'Pirate Eye Patch', 'unlock' => 70],
        ['name' => 'Skull Eye Patch', 'unlock' => 100],
        ['name' => 'Gold Eye Patch', 'unlock' => 150],
        ['name' => 'Jeweled Eye Patch', 'unlock' => 200],
        ['name' => 'Cybernetic Eye Patch', 'unlock' => 250],
    ];

    foreach ($eyepatches as $patch) {
        $color = '#000000';
        if (strpos($patch['name'], 'Brown') !== false) $color = '#8B4513';
        if (strpos($patch['name'], 'Red') !== false) $color = '#E74C3C';
        if (strpos($patch['name'], 'Gold') !== false) $color = '#FFD700';

        $items[] = [
            'slug' => 'acc-eyepatch-' . strtolower(str_replace(' ', '-', $patch['name'])),
            'name' => $patch['name'],
            'category' => 'accessory',
            'svg_data' => '<ellipse cx="56" cy="50" rx="5" ry="4" fill="' . $color . '"/><line x1="52" y1="48" x2="35" y2="45" stroke="' . $color . '" stroke-width="1"/><line x1="60" y1="48" x2="65" y2="48" stroke="' . $color . '" stroke-width="1"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $patch['unlock'],
            'unlock_message' => 'Read ' . $patch['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Face Paint/Tattoos - 10 items
    $facepaints = [
        ['name' => 'Warrior Paint', 'unlock' => 75],
        ['name' => 'Tribal Tattoo', 'unlock' => 80],
        ['name' => 'Heart Face Paint', 'unlock' => 30],
        ['name' => 'Star Face Paint', 'unlock' => 35],
        ['name' => 'Lightning Bolt Tattoo', 'unlock' => 50],
        ['name' => 'Tear Tattoo', 'unlock' => 60],
        ['name' => 'Face Stripes', 'unlock' => 40],
        ['name' => 'Celtic Tattoo', 'unlock' => 90],
        ['name' => 'Dragon Tattoo', 'unlock' => 150],
        ['name' => 'Mystical Runes', 'unlock' => 200],
    ];

    foreach ($facepaints as $paint) {
        $items[] = [
            'slug' => 'acc-facepaint-' . strtolower(str_replace(' ', '-', $paint['name'])),
            'name' => $paint['name'],
            'category' => 'accessory',
            'svg_data' => '<path d="M 40 48 L 42 50 L 40 52" stroke="#E74C3C" stroke-width="1.5" fill="none"/><path d="M 60 48 L 58 50 L 60 52" stroke="#E74C3C" stroke-width="1.5" fill="none"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $paint['unlock'],
            'unlock_message' => 'Read ' . $paint['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Scars - 6 items
    $scars = [
        ['name' => 'Small Scar', 'unlock' => 40],
        ['name' => 'Eye Scar', 'unlock' => 60],
        ['name' => 'Cheek Scar', 'unlock' => 50],
        ['name' => 'Battle Scar', 'unlock' => 100],
        ['name' => 'Lightning Scar', 'unlock' => 150],
        ['name' => 'Warrior Scars', 'unlock' => 200],
    ];

    foreach ($scars as $scar) {
        $items[] = [
            'slug' => 'acc-scar-' . strtolower(str_replace(' ', '-', $scar['name'])),
            'name' => $scar['name'],
            'category' => 'accessory',
            'svg_data' => '<line x1="54" y1="46" x2="58" y2="50" stroke="#8B4513" stroke-width="1" opacity="0.6"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $scar['unlock'],
            'unlock_message' => 'Read ' . $scar['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Nose Accessories - 5 items
    $noses = [
        ['name' => 'Clown Nose', 'unlock' => 25],
        ['name' => 'Nose Ring', 'unlock' => 45],
        ['name' => 'Pig Nose', 'unlock' => 30],
        ['name' => 'Rudolph Nose', 'unlock' => 35],
        ['name' => 'Nose Piercing', 'unlock' => 50],
    ];

    foreach ($noses as $nose) {
        $items[] = [
            'slug' => 'acc-nose-' . strtolower(str_replace(' ', '-', $nose['name'])),
            'name' => $nose['name'],
            'category' => 'accessory',
            'svg_data' => '<circle cx="50" cy="52" r="2" fill="#E74C3C"/>',
            'unlock_metric' => 'books_read',
            'unlock_value' => $nose['unlock'],
            'unlock_message' => 'Read ' . $nose['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    // Miscellaneous Accessories - 10 items
    $misc = [
        ['name' => 'Headphones', 'unlock' => 40],
        ['name' => 'Earbuds', 'unlock' => 30],
        ['name' => 'Headset', 'unlock' => 50],
        ['name' => 'Bluetooth Earpiece', 'unlock' => 60],
        ['name' => 'Bandage', 'unlock' => 20],
        ['name' => 'Band-Aid', 'unlock' => 15],
        ['name' => 'Freckles', 'unlock' => 10],
        ['name' => 'Dimples', 'unlock' => 25],
        ['name' => 'Blushing Cheeks', 'unlock' => 20],
        ['name' => 'Sparkles', 'unlock' => 75],
    ];

    foreach ($misc as $item) {
        $svg = '<rect x="32" y="48" width="8" height="4" rx="1" fill="#2C3E50"/><rect x="60" y="48" width="8" height="4" rx="1" fill="#2C3E50"/>';

        if ($item['name'] === 'Freckles') {
            $svg = '<circle cx="43" cy="52" r="0.5" fill="#8B4513"/><circle cx="45" cy="53" r="0.5" fill="#8B4513"/><circle cx="57" cy="52" r="0.5" fill="#8B4513"/><circle cx="55" cy="53" r="0.5" fill="#8B4513"/>';
        } elseif ($item['name'] === 'Blushing Cheeks') {
            $svg = '<ellipse cx="40" cy="54" rx="4" ry="2" fill="#FF69B4" opacity="0.4"/><ellipse cx="60" cy="54" rx="4" ry="2" fill="#FF69B4" opacity="0.4"/>';
        }

        $items[] = [
            'slug' => 'acc-misc-' . strtolower(str_replace(' ', '-', $item['name'])),
            'name' => $item['name'],
            'category' => 'accessory',
            'svg_data' => $svg,
            'unlock_metric' => 'books_read',
            'unlock_value' => $item['unlock'],
            'unlock_message' => 'Read ' . $item['unlock'] . ' books to unlock!',
            'is_default' => 0,
            'display_order' => $display_order++
        ];
    }

    return $items;
}

// Include part 2 with backgrounds, shirt patterns, and pants patterns
require_once plugin_dir_path(__FILE__) . 'avatar_item_seeder_part2.php';
