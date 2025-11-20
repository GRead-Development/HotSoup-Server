# Feature Integration Order & Step-by-Step Guide

## 📦 All API Files (Separated by Feature)

```
includes/api/
├── activity_tracking.php    ✓ STANDALONE (Foundation - add first)
├── random_book.php          ✓ STANDALONE (No dependencies)
├── citations.php            ✓ STANDALONE (No dependencies)
├── api_self_test.php        ✓ STANDALONE (No dependencies)
├── custom_lists.php         ✓ STANDALONE (No dependencies)
├── feature_requests.php     ✓ STANDALONE (No dependencies)
├── reading_planner.php      ✓ STANDALONE (No dependencies)
├── milestones.php           ⚠️  REQUIRES: activity_tracking.php
├── chapters.php             ✓ STANDALONE (but uses points system)
└── summaries.php            ✓ STANDALONE (but uses points system)
```

---

## 🎯 Recommended Integration Order

### **Phase 1: Foundation (Do These First)**

#### 1. Activity Tracking ⭐ **START HERE**
- **Why First:** Foundation for milestones and stats
- **File:** `includes/api/activity_tracking.php`
- **Database:** 1 table (`wp_hs_library_activity`)
- **Dependencies:** None
- **Integration Time:** 20 minutes

#### 2. Random Book Selector
- **Why Second:** Easiest feature, immediate value
- **File:** `includes/api/random_book.php`
- **Database:** None (uses existing data)
- **Dependencies:** None
- **Integration Time:** 5 minutes

---

### **Phase 2: Standalone Features (Any Order)**

#### 3. Citations
- **File:** `includes/api/citations.php`
- **Database:** None (uses existing book data)
- **Dependencies:** None
- **Integration Time:** 5 minutes

#### 4. API Self-Test
- **File:** `includes/api/api_self_test.php`
- **Database:** None
- **Dependencies:** None (but benefits from other features being added)
- **Integration Time:** 5 minutes

#### 5. Custom Lists
- **File:** `includes/api/custom_lists.php`
- **Database:** 2 tables (`wp_hs_user_lists`, `wp_hs_list_books`)
- **Dependencies:** None
- **Integration Time:** 10 minutes

#### 6. Feature Requests
- **File:** `includes/api/feature_requests.php`
- **Database:** 2 tables (`wp_hs_feature_requests`, `wp_hs_request_votes`)
- **Dependencies:** None
- **Integration Time:** 10 minutes

#### 7. Reading Planner
- **File:** `includes/api/reading_planner.php`
- **Database:** 2 tables (`wp_hs_reading_plans`, `wp_hs_plan_progress`)
- **Dependencies:** None (but works better with activity tracking)
- **Integration Time:** 15 minutes

---

### **Phase 3: Features Requiring Foundation**

#### 8. Milestones
- **File:** `includes/api/milestones.php`
- **Database:** 1 table (`wp_hs_user_milestones`)
- **Dependencies:** ✅ Activity Tracking (required)
- **Integration Time:** 10 minutes

---

### **Phase 4: Advanced Features (Require Moderation)**

#### 9. Chapters
- **File:** `includes/api/chapters.php`
- **Database:** 1 table (`wp_hs_book_chapters`)
- **Dependencies:** None (but uses points system)
- **Integration Time:** 15 minutes

#### 10. Summaries
- **File:** `includes/api/summaries.php`
- **Database:** 2 tables (`wp_hs_user_summaries`, `wp_hs_summary_votes`)
- **Dependencies:** None (but uses points system)
- **Integration Time:** 20 minutes

---

## 📝 Step-by-Step Integration for Each Feature

### Feature 1: Activity Tracking (START HERE)

**What it does:** Tracks all user library actions with timestamps

**Integration Steps:**

1. **Add the file to your plugin:**

   In `hotsoup.php`, add:
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/activity_tracking.php';
   ```

2. **The table auto-creates on admin page load** (no manual action needed)

3. **Integrate into your existing code:**

   Find where you **add books to library** and add this line:
   ```php
   // After: update_user_meta($user_id, "book_{$book_id}_in_library", 1);
   hs_track_library_activity($user_id, $book_id, 'added');
   ```

   Find where you **mark books complete** and add:
   ```php
   // After: update_user_meta($user_id, "book_{$book_id}_completed", 1);
   hs_track_library_activity($user_id, $book_id, 'completed');

   // Increment count for milestones (if you'll add milestones later)
   $completed = (int)get_user_meta($user_id, 'hs_completed_books_count', true);
   update_user_meta($user_id, 'hs_completed_books_count', $completed + 1);
   ```

   Find where you **update reading progress** and add:
   ```php
   // After: update_user_meta($user_id, "book_{$book_id}_current_page", $page);
   hs_track_library_activity($user_id, $book_id, 'progress_update', array(
       'old_page' => $old_page,
       'new_page' => $page,
   ));
   ```

4. **Test it:**
   ```bash
   # Add a book to your library via your app
   # Then check:
   curl https://yoursite.com/wp-json/gread/v1/library/activity \
     -H "X-WP-Nonce: YOUR_NONCE"

   # Should return your recent activity
   ```

5. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/library/activity` - View all activity
   - `GET /wp-json/gread/v1/books/{id}/reading-stats` - Get reading stats for a book

---

### Feature 2: Random Book Selector

**What it does:** Picks a random unread book from user's library

**Integration Steps:**

1. **Add the file:**
   ```php
   // In hotsoup.php
   require_once plugin_dir_path(__FILE__) . 'includes/api/random_book.php';
   ```

2. **No database changes needed!**

3. **Test it:**
   ```bash
   curl https://yoursite.com/wp-json/gread/v1/books/random \
     -H "X-WP-Nonce: YOUR_NONCE"
   ```

4. **Add to your frontend:**
   ```html
   <button id="surprise-me-btn">Surprise Me!</button>

   <script>
   document.getElementById('surprise-me-btn').addEventListener('click', function() {
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
   });
   </script>
   ```

5. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/books/random`

---

### Feature 3: Citations

**What it does:** Generates citations in MLA, APA, Chicago, Harvard, BibTeX formats

**Integration Steps:**

1. **Add the file:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/citations.php';
   ```

2. **No database changes needed!**

3. **Test it:**
   ```bash
   # MLA format
   curl https://yoursite.com/wp-json/gread/v1/books/123/citation?style=mla

   # APA format
   curl https://yoursite.com/wp-json/gread/v1/books/123/citation?style=apa
   ```

4. **Add to book page:**
   ```html
   <button id="cite-btn" data-book-id="123">Cite This Book</button>
   <select id="citation-style">
       <option value="mla">MLA</option>
       <option value="apa">APA</option>
       <option value="chicago">Chicago</option>
       <option value="harvard">Harvard</option>
       <option value="bibtex">BibTeX</option>
   </select>
   <textarea id="citation-output" readonly></textarea>

   <script>
   document.getElementById('cite-btn').addEventListener('click', function() {
       const bookId = this.dataset.bookId;
       const style = document.getElementById('citation-style').value;

       fetch(`/wp-json/gread/v1/books/${bookId}/citation?style=${style}`)
           .then(r => r.json())
           .then(data => {
               document.getElementById('citation-output').value = data.citation;
           });
   });
   </script>
   ```

5. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/books/{id}/citation?style={mla|apa|chicago|harvard|bibtex}`
   - `POST /wp-json/gread/v1/bibliography` - Generate bibliography for multiple books

---

### Feature 4: API Self-Test

**What it does:** Lists all API endpoints with documentation and health checks

**Integration Steps:**

1. **Add the file:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/api_self_test.php';
   ```

2. **No database changes needed!**

3. **Test it:**
   ```bash
   # View all endpoints
   curl https://yoursite.com/wp-json/gread/v1/api-test

   # Run health checks
   curl https://yoursite.com/wp-json/gread/v1/api-test?test=true
   ```

4. **Use for development:**
   - Visit `/wp-json/gread/v1/api-test` in browser for full API docs
   - Great for debugging and documentation

5. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/api-test`

---

### Feature 5: Custom Lists

**What it does:** Users create custom lists to organize their books

**Integration Steps:**

1. **Add the file:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/custom_lists.php';
   ```

2. **Tables auto-create on admin page load**

3. **Test it:**
   ```bash
   # Create a list
   curl -X POST https://yoursite.com/wp-json/gread/v1/lists \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE" \
     -d '{"name":"Summer 2024","description":"Books to read this summer"}'

   # Add book to list
   curl -X POST https://yoursite.com/wp-json/gread/v1/lists/1/books \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE" \
     -d '{"book_id":123}'
   ```

4. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/lists` - Get all user's lists
   - `POST /wp-json/gread/v1/lists` - Create list
   - `GET /wp-json/gread/v1/lists/{id}` - Get single list
   - `PUT /wp-json/gread/v1/lists/{id}` - Update list
   - `DELETE /wp-json/gread/v1/lists/{id}` - Delete list
   - `POST /wp-json/gread/v1/lists/{id}/books` - Add book to list
   - `DELETE /wp-json/gread/v1/lists/{id}/books/{book_id}` - Remove book
   - `PUT /wp-json/gread/v1/lists/{id}/reorder` - Reorder books

---

### Feature 6: Feature Requests

**What it does:** Users submit feature requests and vote on them

**Integration Steps:**

1. **Add the file:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/feature_requests.php';
   ```

2. **Tables auto-create on admin page load**

3. **Test it:**
   ```bash
   # Submit a request
   curl -X POST https://yoursite.com/wp-json/gread/v1/feature-requests \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE" \
     -d '{"type":"feature","title":"Dark mode","description":"Add dark mode to reading interface"}'

   # Vote for request
   curl -X POST https://yoursite.com/wp-json/gread/v1/feature-requests/1/vote \
     -H "X-WP-Nonce: YOUR_NONCE"
   ```

4. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/feature-requests`
   - `POST /wp-json/gread/v1/feature-requests`
   - `GET /wp-json/gread/v1/feature-requests/{id}`
   - `PUT /wp-json/gread/v1/feature-requests/{id}` (admin only)
   - `DELETE /wp-json/gread/v1/feature-requests/{id}` (admin only)
   - `POST /wp-json/gread/v1/feature-requests/{id}/vote`
   - `DELETE /wp-json/gread/v1/feature-requests/{id}/vote`

---

### Feature 7: Reading Planner

**What it does:** Set target dates, calculates pages per day, adjusts based on progress

**Integration Steps:**

1. **Add the file:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/reading_planner.php';
   ```

2. **Tables auto-create on admin page load**

3. **Test it:**
   ```bash
   # Create a plan
   curl -X POST https://yoursite.com/wp-json/gread/v1/reading-plans \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE" \
     -d '{"book_id":123,"target_date":"2024-12-31"}'

   # Record progress
   curl -X POST https://yoursite.com/wp-json/gread/v1/reading-plans/1/progress \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE" \
     -d '{"current_page":150}'
   ```

4. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/reading-plans`
   - `POST /wp-json/gread/v1/reading-plans`
   - `GET /wp-json/gread/v1/reading-plans/{id}`
   - `PUT /wp-json/gread/v1/reading-plans/{id}`
   - `DELETE /wp-json/gread/v1/reading-plans/{id}`
   - `POST /wp-json/gread/v1/reading-plans/{id}/progress`
   - `GET /wp-json/gread/v1/reading-plans/today`

---

### Feature 8: Milestones

**What it does:** Auto-awards achievements (1st book, 5th, 10th, etc.)

**⚠️ Requires:** Activity Tracking to be installed first

**Integration Steps:**

1. **Add the file:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/milestones.php';
   ```

2. **Table auto-creates on admin page load**

3. **Automatic tracking:** Milestones are automatically checked when `hs_track_library_activity()` is called (from Activity Tracking feature)

4. **Make sure you're tracking book counts:**
   ```php
   // When book is completed:
   $completed = (int)get_user_meta($user_id, 'hs_completed_books_count', true);
   update_user_meta($user_id, 'hs_completed_books_count', $completed + 1);

   // When book is added:
   $added = (int)get_user_meta($user_id, 'hs_books_added_count', true);
   update_user_meta($user_id, 'hs_books_added_count', $added + 1);
   ```

5. **Test it:**
   ```bash
   # Complete your 1st, 5th, or 10th book, then:
   curl https://yoursite.com/wp-json/gread/v1/milestones \
     -H "X-WP-Nonce: YOUR_NONCE"
   ```

6. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/milestones`

---

### Feature 9: Chapters

**What it does:** Users submit chapter lists for books, earn points

**Integration Steps:**

1. **Add the file:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/chapters.php';
   ```

2. **Table auto-creates on admin page load**

3. **Test it:**
   ```bash
   # Submit chapters
   curl -X POST https://yoursite.com/wp-json/gread/v1/books/123/chapters \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE" \
     -d '{
       "chapters": [
         {"chapter_number":1,"chapter_title":"Introduction","start_page":1,"end_page":20},
         {"chapter_number":2,"chapter_title":"Chapter Two","start_page":21,"end_page":45}
       ]
     }'
   ```

4. **Admin approval workflow:**
   - Chapters submitted as "pending"
   - Admin approves via `PUT /wp-json/gread/v1/chapters/{id}/review`
   - Points awarded on approval

5. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/books/{id}/chapters`
   - `POST /wp-json/gread/v1/books/{id}/chapters`
   - `PUT /wp-json/gread/v1/chapters/{id}/review` (admin only)

---

### Feature 10: Summaries

**What it does:** Users submit summaries, vote on quality, earn points

**Integration Steps:**

1. **Add the file:**
   ```php
   require_once plugin_dir_path(__FILE__) . 'includes/api/summaries.php';
   ```

2. **Tables auto-create on admin page load**

3. **Test it:**
   ```bash
   # Submit a summary
   curl -X POST https://yoursite.com/wp-json/gread/v1/summaries \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE" \
     -d '{
       "book_id":123,
       "summary_type":"character",
       "title":"Holden Caulfield Analysis",
       "content":"Holden is a complex character... (at least 50 characters)",
       "character_name":"Holden Caulfield"
     }'

   # Vote on summary
   curl -X POST https://yoursite.com/wp-json/gread/v1/summaries/1/vote \
     -H "Content-Type: application/json" \
     -H "X-WP-Nonce: YOUR_NONCE" \
     -d '{"vote_type":"helpful"}'
   ```

4. **Admin approval workflow:**
   - Summaries submitted as "pending"
   - Admin reviews via `PUT /wp-json/gread/v1/summaries/{id}/review`
   - Points awarded based on quality score

5. **API Endpoints Added:**
   - `GET /wp-json/gread/v1/books/{id}/summaries`
   - `POST /wp-json/gread/v1/summaries`
   - `GET /wp-json/gread/v1/summaries/{id}`
   - `POST /wp-json/gread/v1/summaries/{id}/vote`
   - `PUT /wp-json/gread/v1/summaries/{id}/review` (admin only)
   - `GET /wp-json/gread/v1/user/contributions`

---

## 🗑️ Removing Old Combined Files

Once you've added all the separate files, you can delete:

```bash
# These are no longer needed:
rm includes/api/library_enhancements.php
rm includes/api/chapters_summaries.php
rm includes/database_schema_new_features.php
```

**Note:** Keep the documentation files:
- `NEW_FEATURES_GUIDE.md`
- `IMPLEMENTATION_CHECKLIST.md`
- `FEATURE_INTEGRATION_ORDER.md` (this file)

---

## ✅ Testing Checklist

After each feature:

- [ ] File included in hotsoup.php
- [ ] Database tables created (check via phpMyAdmin)
- [ ] API endpoint accessible (test with curl or Postman)
- [ ] No PHP errors in debug.log
- [ ] Feature works as expected

---

## 🚨 Common Issues & Solutions

**Tables not creating:**
- Check database user has CREATE TABLE permission
- Check WordPress debug.log for errors
- Manually run the `hs_create_*_table()` function from WordPress admin

**Activity tracking not firing:**
- Make sure you added `hs_track_library_activity()` calls
- Check that user_id and book_id are valid integers
- Enable WP_DEBUG and check debug.log

**Milestones not awarding:**
- Ensure Activity Tracking is installed first
- Check that you're updating `hs_completed_books_count` user meta
- Verify the hook is registered

**Points not awarded:**
- Check that admin is approving submissions
- Verify `hs_contribution_points` user meta exists
- Check debug.log for errors

---

## 📊 Database Table Summary

| Feature | Tables Created | Count |
|---------|---------------|-------|
| Activity Tracking | `wp_hs_library_activity` | 1 |
| Random Book | (none - uses existing data) | 0 |
| Citations | (none - uses existing data) | 0 |
| API Self-Test | (none) | 0 |
| Custom Lists | `wp_hs_user_lists`, `wp_hs_list_books` | 2 |
| Feature Requests | `wp_hs_feature_requests`, `wp_hs_request_votes` | 2 |
| Reading Planner | `wp_hs_reading_plans`, `wp_hs_plan_progress` | 2 |
| Milestones | `wp_hs_user_milestones` | 1 |
| Chapters | `wp_hs_book_chapters` | 1 |
| Summaries | `wp_hs_user_summaries`, `wp_hs_summary_votes` | 2 |
| **TOTAL** | | **11** |

---

## 🎉 You're Done!

Each feature is now standalone and can be added incrementally. Start with Activity Tracking, then add features one at a time in the order above.
