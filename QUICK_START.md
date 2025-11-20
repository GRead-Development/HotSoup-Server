# Quick Start - Add Your First Feature in 5 Minutes!

## 🚀 Start Here: Activity Tracking

This is the **easiest feature to add** and it's the **foundation** for other features like milestones.

### Step 1: Add One Line to hotsoup.php

Open `hotsoup.php` and add this line after your other `require_once` statements:

```php
require_once plugin_dir_path(__FILE__) . 'includes/api/activity_tracking.php';
```

### Step 2: That's It! (Database Auto-Creates)

The table will be created automatically the next time you visit your WordPress admin dashboard.

### Step 3: Integrate Into Your Existing Code

Find your existing function that **adds books to library** and add one line:

**Before:**
```php
function add_book_to_library($user_id, $book_id) {
    update_user_meta($user_id, "book_{$book_id}_in_library", 1);
    // Your other code...
}
```

**After:**
```php
function add_book_to_library($user_id, $book_id) {
    update_user_meta($user_id, "book_{$book_id}_in_library", 1);
    hs_track_library_activity($user_id, $book_id, 'added'); // ← ADD THIS LINE
    // Your other code...
}
```

Find your function that **marks books as complete** and add:

**Before:**
```php
function mark_book_complete($user_id, $book_id) {
    update_user_meta($user_id, "book_{$book_id}_completed", 1);
    // Your other code...
}
```

**After:**
```php
function mark_book_complete($user_id, $book_id) {
    update_user_meta($user_id, "book_{$book_id}_completed", 1);
    hs_track_library_activity($user_id, $book_id, 'completed'); // ← ADD THIS LINE

    // Optional: Track count for milestones (if you'll add that later)
    $completed = (int)get_user_meta($user_id, 'hs_completed_books_count', true);
    update_user_meta($user_id, 'hs_completed_books_count', $completed + 1);
    // Your other code...
}
```

### Step 4: Test It!

1. Add a book to your library through your app
2. Then run this in terminal or browser:

```bash
curl https://yoursite.com/wp-json/gread/v1/library/activity \
  -H "X-WP-Nonce: YOUR_NONCE"
```

Or visit in browser (while logged in):
```
https://yoursite.com/wp-json/gread/v1/library/activity
```

You should see your recent library activity with timestamps!

---

## ✅ Done! What's Next?

### Add Your Second Feature: Random Book (Takes 2 Minutes)

1. Add to `hotsoup.php`:
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/random_book.php';
   ```

2. Test it:
   ```
   https://yoursite.com/wp-json/gread/v1/books/random
   ```

3. Add a "Surprise Me!" button to your UI:
   ```html
   <button onclick="getRandomBook()">Surprise Me!</button>

   <script>
   function getRandomBook() {
       fetch('/wp-json/gread/v1/books/random', {
           headers: { 'X-WP-Nonce': wpApiSettings.nonce }
       })
       .then(r => r.json())
       .then(data => {
           if (data.success) {
               alert(data.message);
               window.location.href = `/book/${data.book.id}`;
           }
       });
   }
   </script>
   ```

### Add Your Third Feature: Citations (Takes 2 Minutes)

1. Add to `hotsoup.php`:
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/citations.php';
   ```

2. Test it:
   ```
   https://yoursite.com/wp-json/gread/v1/books/123/citation?style=mla
   ```

3. Add to your book page:
   ```html
   <button onclick="getCitation(123, 'mla')">Get MLA Citation</button>

   <script>
   function getCitation(bookId, style) {
       fetch(`/wp-json/gread/v1/books/${bookId}/citation?style=${style}`)
           .then(r => r.json())
           .then(data => {
               alert(data.citation);
               // Or display in a modal, copy to clipboard, etc.
           });
   }
   </script>
   ```

---

## 📚 Full Feature List

See `FEATURE_INTEGRATION_ORDER.md` for complete step-by-step instructions for all 10 features:

1. ✅ **Activity Tracking** (you just did this!)
2. ⭐ **Random Book Selector** (do this next - 2 min)
3. ⭐ **Citations** (do this third - 2 min)
4. **API Self-Test**
5. **Custom Lists**
6. **Feature Requests**
7. **Reading Planner**
8. **Milestones** (requires Activity Tracking)
9. **Chapters**
10. **Summaries**

---

## 🆘 Need Help?

**Tables not creating?**
- Visit your WordPress admin dashboard once (this triggers table creation)
- Check phpMyAdmin for `wp_hs_library_activity` table

**Activity not tracking?**
- Make sure you added the `hs_track_library_activity()` line
- Check that `$user_id` and `$book_id` are valid integers
- Enable WP_DEBUG and check debug.log

**Want to see all available endpoints?**
```
https://yoursite.com/wp-json/gread/v1/api-test
```

---

## 🎯 Quick Reference

### All Files You Can Add (One at a Time)

```
includes/api/
├── activity_tracking.php   ← START HERE (foundation)
├── random_book.php         ← THEN THIS (no dependencies)
├── citations.php           ← THEN THIS (no dependencies)
├── api_self_test.php       ← Any time
├── custom_lists.php        ← Any time
├── feature_requests.php    ← Any time
├── reading_planner.php     ← Any time
├── milestones.php          ← After activity_tracking.php
├── chapters.php            ← Any time
└── summaries.php           ← Any time
```

### Function Reference

```php
// Track an activity (use this everywhere!)
hs_track_library_activity($user_id, $book_id, 'added');
hs_track_library_activity($user_id, $book_id, 'started');
hs_track_library_activity($user_id, $book_id, 'completed');
hs_track_library_activity($user_id, $book_id, 'removed');
hs_track_library_activity($user_id, $book_id, 'progress_update', array(
    'old_page' => 10,
    'new_page' => 25,
));
```

---

## 🎉 You're Ready!

Start with activity tracking, then add features one at a time. Each feature is completely independent (except milestones which needs activity tracking).

Happy coding! 🚀
