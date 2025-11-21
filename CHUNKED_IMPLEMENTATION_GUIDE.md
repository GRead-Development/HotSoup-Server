# Incremental Feature Implementation - Bite-Sized Chunks

## 📂 File Organization

```
includes/
├── activity-tracking.php           ← Core functions (NOT API)
├── random-book.php                 ← Core functions
├── citations.php                   ← Core functions
├── custom-lists.php                ← Core functions
├── milestones.php                  ← Core functions
├── chapters.php                    ← Core functions
├── summaries.php                   ← Core functions
└── api/
    ├── activity-tracking-api.php   ← API endpoints only
    ├── random-book-api.php         ← API endpoints only
    ├── citations-api.php           ← API endpoints only
    ├── custom-lists-api.php        ← API endpoints only
    ├── milestones-api.php          ← API endpoints only
    ├── chapters-api.php            ← API endpoints only
    └── summaries-api.php           ← API endpoints only
```

## 🎯 Implementation Strategy

Each feature is broken into 2-3 small chunks:

**Chunk 1:** Core functions + Database (300-500 LOC)
- Add to `includes/feature-name.php`
- Contains helper functions, database creation
- Can test manually without API

**Chunk 2:** API endpoints (200-300 LOC)
- Add to `includes/api/feature-name-api.php`
- REST API routes only
- Requires Chunk 1

**Chunk 3 (if needed):** Additional helpers (100-200 LOC)

---

## 📋 Features Broken Down

### Feature 1: Activity Tracking
- **Chunk 1.1:** Core tracking function (150 LOC) - `includes/activity-tracking.php`
- **Chunk 1.2:** API endpoints (200 LOC) - `includes/api/activity-tracking-api.php`

### Feature 2: Random Book
- **Chunk 2.1:** Core function (80 LOC) - `includes/random-book.php`
- **Chunk 2.2:** API endpoint (50 LOC) - `includes/api/random-book-api.php`

### Feature 3: Citations
- **Chunk 3.1:** Citation formatters (350 LOC) - `includes/citations.php`
- **Chunk 3.2:** API endpoints (150 LOC) - `includes/api/citations-api.php`

### Feature 4: Custom Lists
- **Chunk 4.1:** Core list functions (300 LOC) - `includes/custom-lists.php`
- **Chunk 4.2:** API endpoints (400 LOC) - `includes/api/custom-lists-api.php`

### Feature 5: Milestones
- **Chunk 5.1:** Core milestone logic (250 LOC) - `includes/milestones.php`
- **Chunk 5.2:** API endpoints (100 LOC) - `includes/api/milestones-api.php`

### Feature 6: Chapters
- **Chunk 6.1:** Core chapter functions (200 LOC) - `includes/chapters.php`
- **Chunk 6.2:** API endpoints (300 LOC) - `includes/api/chapters-api.php`

### Feature 7: Summaries
- **Chunk 7.1:** Core summary functions (400 LOC) - `includes/summaries.php`
- **Chunk 7.2:** API endpoints (500 LOC) - `includes/api/summaries-api.php`

---

## 🚀 Start Here: Activity Tracking (Chunk 1.1)

### What You'll Add: `includes/activity-tracking.php` (~150 LOC)

This file contains:
- ✅ Database table creation
- ✅ Core tracking function `hs_track_library_activity()`
- ✅ Helper functions for activity queries
- ✅ NO API endpoints (those come in Chunk 1.2)

### How to Add:

1. **Create** `includes/activity-tracking.php`
2. **Add to hotsoup.php:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/activity-tracking.php';
   ```
3. **Test manually** by calling the function directly

### After Adding Chunk 1.1:

You can now call:
```php
hs_track_library_activity($user_id, $book_id, 'added');
```

And query:
```php
$activities = hs_get_library_activity_for_user($user_id);
```

---

## Next: Activity Tracking (Chunk 1.2)

### What You'll Add: `includes/api/activity-tracking-api.php` (~200 LOC)

This file contains:
- ✅ REST API route registrations
- ✅ API callback functions
- ✅ Requires Chunk 1.1 to work

### How to Add:

1. **Create** `includes/api/activity-tracking-api.php`
2. **Add to hotsoup.php:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/activity-tracking-api.php';
   ```
3. **Test with curl/Postman**

### After Adding Chunk 1.2:

You can now access:
```
GET /wp-json/gread/v1/library/activity
GET /wp-json/gread/v1/books/{id}/reading-stats
```

---

## 📊 Complete Implementation Order

| Step | Chunk | File | LOC | Time |
|------|-------|------|-----|------|
| 1 | 1.1 | includes/activity-tracking.php | 150 | 10 min |
| 2 | 1.2 | includes/api/activity-tracking-api.php | 200 | 10 min |
| 3 | 2.1 | includes/random-book.php | 80 | 5 min |
| 4 | 2.2 | includes/api/random-book-api.php | 50 | 5 min |
| 5 | 3.1 | includes/citations.php | 350 | 15 min |
| 6 | 3.2 | includes/api/citations-api.php | 150 | 10 min |
| 7 | 4.1 | includes/custom-lists.php | 300 | 15 min |
| 8 | 4.2 | includes/api/custom-lists-api.php | 400 | 20 min |
| 9 | 5.1 | includes/milestones.php | 250 | 15 min |
| 10 | 5.2 | includes/api/milestones-api.php | 100 | 5 min |
| 11 | 6.1 | includes/chapters.php | 200 | 10 min |
| 12 | 6.2 | includes/api/chapters-api.php | 300 | 15 min |
| 13 | 7.1 | includes/summaries.php | 400 | 20 min |
| 14 | 7.2 | includes/api/summaries-api.php | 500 | 25 min |

**Total:** 14 small chunks, ~180 minutes total

---

## 🎯 Benefits of This Approach

1. **Small Commits:** Each chunk is a small, testable commit
2. **Progressive Testing:** Test core functions before API
3. **No Breaking Changes:** Add features without disrupting existing code
4. **Easy Debugging:** Know exactly which chunk caused an issue
5. **Flexible:** Skip API if you only need core functions

---

## 📁 Detailed File Structure

```
hotsoup-server/
├── hotsoup.php
├── includes/
│   ├── activity-tracking.php          [Chunk 1.1] Core functions
│   ├── random-book.php                [Chunk 2.1] Core functions
│   ├── citations.php                  [Chunk 3.1] Core functions
│   ├── custom-lists.php               [Chunk 4.1] Core functions
│   ├── milestones.php                 [Chunk 5.1] Core functions
│   ├── chapters.php                   [Chunk 6.1] Core functions
│   ├── summaries.php                  [Chunk 7.1] Core functions
│   └── api/
│       ├── activity-tracking-api.php  [Chunk 1.2] API routes
│       ├── random-book-api.php        [Chunk 2.2] API routes
│       ├── citations-api.php          [Chunk 3.2] API routes
│       ├── custom-lists-api.php       [Chunk 4.2] API routes
│       ├── milestones-api.php         [Chunk 5.2] API routes
│       ├── chapters-api.php           [Chunk 6.2] API routes
│       └── summaries-api.php          [Chunk 7.2] API routes
```

---

## ✅ Testing Each Chunk

### After Core Functions (Chunk X.1):
```php
// Test in theme's functions.php or plugin
$result = hs_your_function($param);
var_dump($result);
```

### After API Endpoints (Chunk X.2):
```bash
curl https://yoursite.com/wp-json/gread/v1/endpoint
```

---

## 🔧 How to Add to hotsoup.php

Add in this order:

```php
// Core Functions (Add These First)
require_once plugin_dir_path(__FILE__) . 'includes/activity-tracking.php';
require_once plugin_dir_path(__FILE__) . 'includes/random-book.php';
require_once plugin_dir_path(__FILE__) . 'includes/citations.php';
require_once plugin_dir_path(__FILE__) . 'includes/custom-lists.php';
require_once plugin_dir_path(__FILE__) . 'includes/milestones.php';
require_once plugin_dir_path(__FILE__) . 'includes/chapters.php';
require_once plugin_dir_path(__FILE__) . 'includes/summaries.php';

// API Endpoints (Add After Core)
require_once plugin_dir_path(__FILE__) . 'includes/api/activity-tracking-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/random-book-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/citations-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/custom-lists-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/milestones-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/chapters-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/summaries-api.php';
```

---

## 📖 What's Next?

Ready to start? I'll create the first chunk for you:

**Chunk 1.1: Activity Tracking Core Functions**
- File: `includes/activity-tracking.php`
- 150 LOC
- Database table + core functions
- No API dependencies

Let me know and I'll create all the chunked files for you!
