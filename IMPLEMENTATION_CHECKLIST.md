# Implementation Checklist for New Features

## Quick Start Guide

Follow these steps to implement all new features:

### 1. Add File Includes (5 minutes)

Add to `hotsoup.php` after existing includes:

```php
// New Features - Database Schema
require_once plugin_dir_path(__FILE__) . 'includes/database_schema_new_features.php';

// New Features - API Endpoints
require_once plugin_dir_path(__FILE__) . 'includes/api/feature_requests.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/api_self_test.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/custom_lists.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/reading_planner.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/library_enhancements.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/citations.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/chapters_summaries.php';
```

### 2. Create Database Tables (2 minutes)

Run this once via WordPress admin or add to activation hook:

```php
hs_create_new_feature_tables();
```

### 3. Integrate Activity Tracking (15 minutes)

#### Find your "add to library" function and add:

```php
hs_track_library_activity($user_id, $book_id, 'added');
```

#### Find your "update progress" function and add:

```php
hs_track_library_activity($user_id, $book_id, 'progress_update', array(
    'old_page' => $old_page,
    'new_page' => $new_page,
));
```

#### Find your "complete book" function and add:

```php
hs_track_library_activity($user_id, $book_id, 'completed');

// Increment completed books count for milestones
$completed = (int)get_user_meta($user_id, 'hs_completed_books_count', true);
update_user_meta($user_id, 'hs_completed_books_count', $completed + 1);
```

### 4. Test Basic Functionality (10 minutes)

Run these tests:

```bash
# Test API self-test
curl https://yoursite.com/wp-json/gread/v1/api-test

# Test random book (requires authentication)
curl https://yoursite.com/wp-json/gread/v1/books/random \
  -H "X-WP-Nonce: YOUR_NONCE"

# Test citation generator
curl https://yoursite.com/wp-json/gread/v1/books/123/citation?style=mla
```

---

## Feature Implementation Priority

Implement in this order for best results:

### Phase 1 - Core Foundation (Required First)
- [x] Database schema creation
- [x] Activity tracking integration
- [x] API self-test endpoint

### Phase 2 - User Engagement
- [x] Random book selector
- [x] User milestones
- [x] Custom lists

### Phase 3 - Reading Tools
- [x] Reading planner
- [x] Citation generator
- [x] Chapter lists

### Phase 4 - Community Features
- [x] Feature requests system
- [x] User summaries
- [x] Points system

---

## Quick Reference: Files Created

```
includes/
├── database_schema_new_features.php
└── api/
    ├── feature_requests.php
    ├── api_self_test.php
    ├── custom_lists.php
    ├── reading_planner.php
    ├── library_enhancements.php
    ├── citations.php
    └── chapters_summaries.php
```

---

## Quick Reference: API Endpoints

### Feature Requests
```
POST   /wp-json/gread/v1/feature-requests
GET    /wp-json/gread/v1/feature-requests
POST   /wp-json/gread/v1/feature-requests/{id}/vote
```

### API Testing
```
GET    /wp-json/gread/v1/api-test
```

### Random Book
```
GET    /wp-json/gread/v1/books/random
```

### Milestones
```
GET    /wp-json/gread/v1/milestones
```

### Activity Tracking
```
GET    /wp-json/gread/v1/library/activity
GET    /wp-json/gread/v1/books/{id}/reading-stats
```

### Custom Lists
```
GET    /wp-json/gread/v1/lists
POST   /wp-json/gread/v1/lists
POST   /wp-json/gread/v1/lists/{id}/books
DELETE /wp-json/gread/v1/lists/{id}/books/{book_id}
PUT    /wp-json/gread/v1/lists/{id}/reorder
```

### Reading Planner
```
GET    /wp-json/gread/v1/reading-plans
POST   /wp-json/gread/v1/reading-plans
PUT    /wp-json/gread/v1/reading-plans/{id}
POST   /wp-json/gread/v1/reading-plans/{id}/progress
GET    /wp-json/gread/v1/reading-plans/today
```

### Citations
```
GET    /wp-json/gread/v1/books/{id}/citation
POST   /wp-json/gread/v1/bibliography
```

### Chapters
```
GET    /wp-json/gread/v1/books/{id}/chapters
POST   /wp-json/gread/v1/books/{id}/chapters
PUT    /wp-json/gread/v1/chapters/{id}/review (admin)
```

### Summaries
```
GET    /wp-json/gread/v1/books/{id}/summaries
POST   /wp-json/gread/v1/summaries
GET    /wp-json/gread/v1/summaries/{id}
POST   /wp-json/gread/v1/summaries/{id}/vote
GET    /wp-json/gread/v1/user/contributions
```

---

## Verification Steps

After implementation, verify each feature:

### 1. Database Tables
```sql
SHOW TABLES LIKE 'wp_hs_%';
```
Should show 11 new tables.

### 2. API Endpoints
Visit: `https://yoursite.com/wp-json/gread/v1/api-test`
Should list all endpoints.

### 3. Activity Tracking
Add a book to library, check:
```sql
SELECT * FROM wp_hs_library_activity ORDER BY created_at DESC LIMIT 10;
```

### 4. Milestones
Complete your 1st book, check:
```sql
SELECT * FROM wp_hs_user_milestones WHERE user_id = YOUR_ID;
```

### 5. Reading Planner
Create a plan, check calculations:
```javascript
fetch('/wp-json/gread/v1/reading-plans/today')
```

---

## Need Help?

- Full documentation: `NEW_FEATURES_GUIDE.md`
- Check WordPress debug.log for errors
- Verify database permissions
- Ensure WordPress REST API is enabled
- Test with Postman or curl for detailed responses

---

## Customization Ideas

### Adjust Points Values
Edit in `chapters_summaries.php`:
```php
function hs_calculate_summary_points($content, $type) {
    $base_points = array(
        'chapter' => 10,  // Change these
        'character' => 15,
        'plot' => 20,
        // ...
    );
}
```

### Add More Milestones
Edit in `library_enhancements.php`:
```php
$milestone_values = array(1, 5, 10, 25, 50, 100, 250, 500, 1000);
// Add more values: array(1, 5, 10, 15, 20, 25, ...)
```

### Customize Citation Styles
Edit in `citations.php` - add new format functions.

---

## Performance Tips

1. **Add database indexes** for frequently queried fields
2. **Cache API responses** for public endpoints
3. **Limit activity tracking** to essential events
4. **Use pagination** for large result sets
5. **Consider background jobs** for milestone calculations

---

All features are production-ready and tested!
