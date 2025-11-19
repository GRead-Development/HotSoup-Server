# Avatar Customization System

## Overview

The Avatar Customization System allows users to create personalized 2D SVG avatars with customizable colors and unlockable items. Avatars are displayed on user profiles and accessible via REST API for mobile apps (iOS and Android).

## Features

- ✅ **2D SVG-based avatars** - Lightweight, scalable, and easy to render on any platform
- ✅ **Base customization** - Body color (RGB wheel), gender, shirt color, pants color
- ✅ **Unlockable items** - Hats, accessories, backgrounds, and patterns
- ✅ **Integration with achievements** - Unlock items by reading books, earning points, etc.
- ✅ **REST API** - Full API for mobile apps (Swift iOS, Flutter Android)
- ✅ **BuddyPress integration** - Avatars display on user profiles
- ✅ **Admin interface** - Manage unlockable items easily

## Quick Start

### For Administrators

1. **Activate the plugin** (if not already activated)
   - The avatar system tables will be created automatically

2. **Access the Avatar Manager**
   - Go to WordPress Admin → Avatar Manager
   - Here you can create and manage unlockable avatar items

3. **Create avatar items**
   - Click "Add New Avatar Item"
   - Fill in the details:
     - Name: Display name (e.g., "Baseball Cap")
     - Slug: Unique identifier (e.g., "hat-baseball")
     - Category: hat, accessory, background, shirt_pattern, or pants_pattern
     - SVG Data: The SVG code for the item (see below for examples)
     - Unlock Metric: points, books_read, pages_read, etc.
     - Unlock Value: Threshold to unlock (e.g., 10 books)

### For Users

1. **Access avatar customization**
   - Go to your profile → Settings → Avatar
   - Customize your avatar using the color pickers
   - Select unlockable items (if you've unlocked them)
   - Click "Save Avatar"

2. **View your avatar**
   - Your avatar will appear on your profile page
   - It's also accessible via the API for mobile apps

### For Developers

See `AVATAR_API.md` for complete API documentation.

**Quick example:**

```javascript
// Get user's avatar (SVG)
GET /wp-json/gread/v1/avatar/123

// Get customization UI data
GET /wp-json/gread/v1/avatar/customization/full

// Update avatar
POST /wp-json/gread/v1/avatar/customization
{
  "body_color": "#FFFFFF",
  "gender": "male",
  "shirt_color": "#4A90E2",
  "pants_color": "#2C3E50",
  "equipped_items": [3, 7]
}
```

## Creating Avatar Items (SVG Examples)

### Baseball Cap
```xml
<path d="M 30 25 Q 50 20 70 25 L 70 30 Q 50 27 30 30 Z" fill="#FF5733"/>
<ellipse cx="50" cy="28" rx="15" ry="3" fill="#CC4422"/>
```

### Reading Glasses
```xml
<g>
  <circle cx="35" cy="50" r="8" fill="none" stroke="#333" stroke-width="2"/>
  <circle cx="65" cy="50" r="8" fill="none" stroke="#333" stroke-width="2"/>
  <line x1="43" y1="50" x2="57" y2="50" stroke="#333" stroke-width="2"/>
</g>
```

### Crown
```xml
<path d="M 35 20 L 40 15 L 42 20 L 50 13 L 58 20 L 60 15 L 65 20 L 65 25 L 35 25 Z" fill="#FFD700" stroke="#DAA520" stroke-width="1"/>
<circle cx="40" cy="15" r="2" fill="#FF0000"/>
<circle cx="50" cy="13" r="2" fill="#FF0000"/>
<circle cx="60" cy="15" r="2" fill="#FF0000"/>
```

### Top Hat
```xml
<rect x="40" y="20" width="20" height="10" rx="1" fill="#000000"/>
<ellipse cx="50" cy="30" rx="15" ry="3" fill="#000000"/>
<ellipse cx="50" cy="20" rx="10" ry="2" fill="#1a1a1a"/>
```

### Book Background
```xml
<rect width="100" height="100" fill="#F0E68C"/>
<g opacity="0.3">
  <rect x="10" y="15" width="15" height="20" rx="1" fill="#8B4513"/>
  <rect x="70" y="60" width="15" height="20" rx="1" fill="#8B4513"/>
  <rect x="30" y="70" width="12" height="16" rx="1" fill="#A0522D"/>
</g>
```

### Shirt Stripes Pattern
```xml
<g opacity="0.5">
  <line x1="40" y1="50" x2="60" y2="50" stroke="#FFFFFF" stroke-width="2"/>
  <line x1="40" y1="54" x2="60" y2="54" stroke="#FFFFFF" stroke-width="2"/>
  <line x1="40" y1="58" x2="60" y2="58" stroke="#FFFFFF" stroke-width="2"/>
  <line x1="40" y1="62" x2="60" y2="62" stroke="#FFFFFF" stroke-width="2"/>
</g>
```

**Important SVG Notes:**
- Use coordinates relative to 100x100 viewbox
- Don't include `<svg>` wrapper tags
- Test your SVG in the admin panel preview
- Keep file size small for better performance

## Database Schema

### Avatar Items Table (`wp_hs_avatar_items`)

| Field | Type | Description |
|-------|------|-------------|
| id | mediumint(9) | Primary key |
| slug | varchar(50) | Unique identifier |
| name | varchar(100) | Display name |
| category | varchar(50) | hat, accessory, background, etc. |
| svg_data | text | SVG markup |
| unlock_metric | varchar(50) | Metric to check for unlock |
| unlock_value | int(11) | Threshold value |
| unlock_message | text | Message shown when locked |
| is_default | tinyint(1) | Always unlocked |
| display_order | int(11) | Sort order |

### User Meta Fields

| Meta Key | Description | Example |
|----------|-------------|---------|
| hs_avatar_body_color | Body color (hex) | #FFFFFF |
| hs_avatar_gender | Gender | male/female |
| hs_avatar_shirt_color | Shirt color (hex) | #4A90E2 |
| hs_avatar_pants_color | Pants color (hex) | #2C3E50 |
| hs_avatar_equipped_items | Equipped items (JSON) | [3, 7, 12] |

## REST API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/gread/v1/avatar/{user_id}` | GET | Get user's avatar SVG |
| `/gread/v1/avatar/customization` | GET | Get current user's settings |
| `/gread/v1/avatar/customization` | POST | Update avatar |
| `/gread/v1/avatar/items` | GET | Get available items |
| `/gread/v1/avatar/customization/full` | GET | Get all data (mobile-optimized) |
| `/gread/v1/avatar/{user_id}/data` | GET | Get user's avatar data |

## File Structure

```
HotSoup-Server/
├── includes/
│   ├── avatar_generator.php      # SVG generation logic
│   ├── avatar_profile.php        # BuddyPress integration
│   ├── admin/
│   │   └── avatar_manager.php    # Admin interface
│   └── api/
│       └── avatar.php            # REST API endpoints
├── js/
│   └── avatar-customizer.js      # Frontend JavaScript
├── css/
│   ├── avatar-customizer.css     # Customization UI styles
│   └── avatar-display.css        # Avatar display styles
└── docs/
    ├── AVATAR_SYSTEM_README.md   # This file
    └── AVATAR_API.md             # API documentation
```

## How It Works

### Avatar Generation Flow

1. User customizes avatar (colors, gender, items)
2. Settings saved to user meta
3. When avatar is requested:
   - System retrieves user's customization from database
   - Generates SVG markup dynamically
   - Layers: Background → Body → Shirt → Pants → Face → Accessories → Hat
   - Returns SVG string

### Unlock System

Items are unlocked based on user metrics:

```php
// Example: Baseball cap unlocks after reading 5 books
[
  'unlock_metric' => 'books_read',
  'unlock_value' => 5
]

// System checks:
$books_read = get_user_meta($user_id, 'hs_completed_books_count', true);
if ($books_read >= 5) {
  // Item is unlocked
}
```

### Mobile App Integration

Mobile apps can use the REST API to:

1. Fetch user's current avatar customization
2. Display available items (with lock status)
3. Build a customization UI
4. Save updates back to the server
5. Render SVG avatars

See `AVATAR_API.md` for implementation examples in Swift and Flutter.

## Customization Categories

### Available Categories

1. **Hat** - Headwear items (caps, crowns, etc.)
2. **Accessory** - Face accessories (glasses, masks, etc.)
3. **Background** - Background patterns/colors
4. **Shirt Pattern** - Overlays for shirts
5. **Pants Pattern** - Overlays for pants

You can easily add more categories by modifying the admin interface.

## Tips for Admins

1. **Start simple** - Create a few basic items first
2. **Balance unlocks** - Don't make everything too easy or too hard
3. **Test SVG rendering** - Preview items before publishing
4. **Use achievements** - Tie special items to achievements
5. **Seasonal items** - Create limited-time items for events

## Troubleshooting

### Avatar not displaying

- Check if user has initialized avatar (happens automatically on user registration)
- Verify avatar endpoints are accessible
- Check browser console for errors

### Items not unlocking

- Verify unlock metrics are set correctly
- Check user stats (books_read, points, etc.)
- Use Achievement Debug tool to manually trigger checks

### SVG rendering issues

- Validate SVG syntax
- Ensure coordinates fit within 100x100 viewbox
- Test in admin preview before deploying

## Future Enhancements

Potential additions to the system:

- [ ] Animated avatars (SVG animations)
- [ ] More body types
- [ ] Face customization (eyes, mouth, nose)
- [ ] Hair styles
- [ ] Pets/companions
- [ ] Seasonal themes
- [ ] Avatar frames/borders
- [ ] Export as PNG

## Support

For issues or questions:
- Check the documentation
- File a GitHub issue
- Contact the development team

---

**Enjoy creating your custom avatar!** 🎨
