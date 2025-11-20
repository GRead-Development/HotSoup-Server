# HotSoup Reading App - New Features Implementation Guide

This guide provides comprehensive documentation for implementing all the new features requested for the HotSoup Reading App.

## Table of Contents

1. [Installation & Setup](#installation--setup)
2. [Feature Request & Issue Reporting System](#1-feature-request--issue-reporting-system)
3. [API Self-Test System](#2-api-self-test-system)
4. [Timestamp Tracking for All User Actions](#3-timestamp-tracking-for-all-user-actions)
5. [Random Book Selector](#4-random-book-selector)
6. [Custom Lists System](#5-custom-lists-system)
7. [User Milestones Tracking](#6-user-milestones-tracking)
8. [Book Addition & Completion Tracking](#7-book-addition--completion-tracking)
9. [Reading Planner with Adaptive Pages](#8-reading-planner-with-adaptive-pages)
10. [Citation & Bibliography Generator](#9-citation--bibliography-generator)
11. [Chapter List Submission](#10-chapter-list-submission)
12. [User Summaries with Points System](#11-user-summaries-with-points-system)

---

## Installation & Setup

### Step 1: Include New Files in Your Plugin

Add these lines to your main plugin file (`hotsoup.php`), after the existing includes:

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

### Step 2: Create Database Tables

The database tables will be created automatically when an admin visits the WordPress admin dashboard. To manually trigger creation, add this to your plugin activation hook:

```php
register_activation_hook(__FILE__, 'hs_create_new_feature_tables');
```

Or run this once in your theme's functions.php:
```php
if (function_exists('hs_create_new_feature_tables')) {
    hs_create_new_feature_tables();
}
```

### Step 3: Integrate Activity Tracking

To enable automatic activity tracking, you need to modify your existing library functions. Find your "add to library" function and add:

```php
// After successfully adding a book to library
hs_track_library_activity($user_id, $book_id, 'added', array(
    'source' => 'manual', // or 'import', 'recommendation', etc.
));
```

For progress updates:
```php
// After updating reading progress
hs_track_library_activity($user_id, $book_id, 'progress_update', array(
    'old_page' => $old_page,
    'new_page' => $new_page,
));
```

For book completion:
```php
// When marking a book as completed
hs_track_library_activity($user_id, $book_id, 'completed', array(
    'total_pages' => $total_pages,
    'completion_date' => current_time('mysql'),
));
```

---

## 1. Feature Request & Issue Reporting System

### Overview
Allows users to submit feature requests, bug reports, and issues directly through the API. Includes voting system and admin management.

### API Endpoints

#### Create Feature Request
```
POST /wp-json/gread/v1/feature-requests
```

**Parameters:**
- `type` (required): 'feature', 'issue', 'bug', or 'improvement'
- `title` (required): Title of the request
- `description` (required): Detailed description

**Example:**
```javascript
fetch('/wp-json/gread/v1/feature-requests', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        type: 'feature',
        title: 'Dark mode support',
        description: 'Please add a dark mode option to the reading interface'
    })
});
```

#### Get All Feature Requests
```
GET /wp-json/gread/v1/feature-requests?type=feature&status=open&sort=votes
```

**Parameters:**
- `type`: Filter by type
- `status`: Filter by status (open, in_progress, resolved, closed, rejected)
- `sort`: 'votes', 'recent', or 'oldest'
- `page`: Page number (default: 1)
- `per_page`: Results per page (default: 20)

#### Vote for Feature Request
```
POST /wp-json/gread/v1/feature-requests/{id}/vote
```

#### Update Feature Request (Admin Only)
```
PUT /wp-json/gread/v1/feature-requests/{id}
```

**Parameters:**
- `status`: 'open', 'in_progress', 'resolved', 'closed', 'rejected'
- `priority`: 'low', 'medium', 'high', 'critical'
- `admin_notes`: Internal notes

---

## 2. API Self-Test System

### Overview
Provides comprehensive API documentation and health checks for all endpoints.

### API Endpoint

```
GET /wp-json/gread/v1/api-test?test=true
```

**Parameters:**
- `test` (optional): If true, runs actual connectivity tests
- `endpoint` (optional): Test specific endpoint only

**Response includes:**
- List of all API endpoints
- Method, parameters, and return values for each
- Example usage
- Current status (working/error)

**Example:**
```javascript
fetch('/wp-json/gread/v1/api-test?test=true')
    .then(response => response.json())
    .then(data => {
        console.log(`Total endpoints: ${data.total_endpoints}`);
        data.categories.forEach(category => {
            console.log(`${category.name}: ${category.endpoints.length} endpoints`);
        });
    });
```

---

## 3. Timestamp Tracking for All User Actions

### Overview
All user actions related to books are now automatically timestamped and stored in the `wp_hs_library_activity` table.

### Tracked Activities
- `added`: When a book is added to library
- `started`: When user starts reading
- `completed`: When user finishes a book
- `removed`: When user removes a book
- `progress_update`: When reading progress is updated

### API Endpoint

```
GET /wp-json/gread/v1/library/activity?activity_type=completed&limit=50
```

**Parameters:**
- `book_id`: Filter by specific book
- `activity_type`: Filter by activity type
- `limit`: Number of results (default: 50)

### Get Book Reading Stats

```
GET /wp-json/gread/v1/books/{book_id}/reading-stats
```

**Returns:**
- When book was added
- When reading started
- When completed
- Total reading duration
- Number of progress updates

**Example:**
```javascript
fetch('/wp-json/gread/v1/books/123/reading-stats')
    .then(response => response.json())
    .then(data => {
        console.log(`Started: ${data.stats.started_at}`);
        console.log(`Completed: ${data.stats.completed_at}`);
        console.log(`Duration: ${data.stats.reading_duration.formatted}`);
    });
```

---

## 4. Random Book Selector

### Overview
Selects a random unread book from the user's library.

### API Endpoint

```
GET /wp-json/gread/v1/books/random
```

**Response:**
```json
{
    "success": true,
    "book": {
        "id": 123,
        "title": "The Great Gatsby",
        "author": "F. Scott Fitzgerald",
        "total_pages": 180,
        "current_page": 0,
        "progress_percentage": 0
    },
    "message": "How about reading The Great Gatsby?"
}
```

**Example:**
```javascript
fetch('/wp-json/gread/v1/books/random', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Redirect to book page or display in UI
        }
    });
```

---

## 5. Custom Lists System

### Overview
Users can create custom lists to organize their book collection in any way they want.

### API Endpoints

#### Create a List
```
POST /wp-json/gread/v1/lists
```

**Parameters:**
- `name` (required): List name
- `description`: List description
- `is_public`: Boolean, whether list is public
- `sort_order`: 'custom', 'title', 'author', 'date_added', 'publication_year'

**Example:**
```javascript
fetch('/wp-json/gread/v1/lists', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        name: 'Summer Reading 2024',
        description: 'Books I want to read this summer',
        is_public: false,
        sort_order: 'custom'
    })
});
```

#### Get All User Lists
```
GET /wp-json/gread/v1/lists?include_books=true
```

#### Add Book to List
```
POST /wp-json/gread/v1/lists/{list_id}/books
```

**Parameters:**
- `book_id` (required): Book to add
- `position`: Optional position in list

#### Remove Book from List
```
DELETE /wp-json/gread/v1/lists/{list_id}/books/{book_id}
```

#### Reorder Books in List
```
PUT /wp-json/gread/v1/lists/{list_id}/reorder
```

**Parameters:**
- `book_order`: Array of book IDs in desired order

**Example:**
```javascript
fetch('/wp-json/gread/v1/lists/1/reorder', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        book_order: [45, 23, 67, 89, 12] // New order
    })
});
```

---

## 6. User Milestones Tracking

### Overview
Automatically tracks and awards milestones for user achievements.

### Milestone Types

**Books Completed:**
- 1st book: "First Book Completed"
- 5 books: "Book Worm"
- 10 books: "Avid Reader"
- 25 books: "Reading Enthusiast"
- 50 books: "Half Century Reader"
- 100 books: "Century Club"
- 250 books: "Reading Legend"
- 500 books: "Master Reader"
- 1000 books: "Reading Titan"

**Books Added:**
- 1 book: "Library Started"
- 10 books: "Growing Collection"
- 50 books: "Book Collector"
- 100 books: "Library Builder"
- 500 books: "Bibliophile"

### API Endpoint

```
GET /wp-json/gread/v1/milestones?type=books_completed
```

**Parameters:**
- `type`: Filter by milestone type

**Response:**
```json
{
    "success": true,
    "milestones": [
        {
            "id": 1,
            "milestone_type": "books_completed",
            "milestone_value": 10,
            "achieved_at": "2024-01-15 10:30:00",
            "metadata": {
                "book_id": 123,
                "milestone_name": "Avid Reader"
            }
        }
    ],
    "total_milestones": 1
}
```

### How It Works

Milestones are automatically checked and awarded when:
- A user completes a book
- A user adds a book to their library

No manual intervention needed!

---

## 7. Book Addition & Completion Tracking

### Overview
All book additions and completions are automatically timestamped and tracked.

### Automatic Tracking

When you call `hs_track_library_activity()`, the system:
1. Records the activity with timestamp
2. Checks for milestone achievements
3. Updates user statistics
4. Triggers any related actions

### Integration Example

In your existing "mark book as completed" function:

```php
function mark_book_completed($user_id, $book_id) {
    // Your existing code...
    update_user_meta($user_id, "book_{$book_id}_completed", 1);

    // Add this line to track activity
    hs_track_library_activity($user_id, $book_id, 'completed', array(
        'completion_date' => current_time('mysql'),
        'total_pages' => get_post_meta($book_id, 'nop', true),
    ));

    // Increment completed books count
    $completed = (int)get_user_meta($user_id, 'hs_completed_books_count', true);
    update_user_meta($user_id, 'hs_completed_books_count', $completed + 1);
}
```

---

## 8. Reading Planner with Adaptive Pages

### Overview
Allows users to set target dates for finishing books and automatically calculates daily page requirements. Adjusts based on actual progress.

### API Endpoints

#### Create Reading Plan
```
POST /wp-json/gread/v1/reading-plans
```

**Parameters:**
- `book_id` (required): Book to plan
- `target_date` (required): When you want to finish (YYYY-MM-DD)
- `start_date`: When you'll start (defaults to today)

**Example:**
```javascript
fetch('/wp-json/gread/v1/reading-plans', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        book_id: 123,
        target_date: '2024-12-31'
    })
})
    .then(response => response.json())
    .then(data => {
        console.log(`Read ${data.pages_per_day} pages per day to finish on time!`);
    });
```

#### Get All Reading Plans
```
GET /wp-json/gread/v1/reading-plans?status=active
```

#### Update Target Date
```
PUT /wp-json/gread/v1/reading-plans/{plan_id}
```

**Parameters:**
- `target_date`: New target date
- `status`: 'active', 'completed', 'paused', 'cancelled'

**Example - Push back deadline:**
```javascript
fetch('/wp-json/gread/v1/reading-plans/1', {
    method: 'PUT',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        target_date: '2025-01-15' // Push back 2 weeks
    })
})
    .then(response => response.json())
    .then(data => {
        console.log(`New goal: ${data.new_pages_per_day} pages/day`);
    });
```

#### Record Progress
```
POST /wp-json/gread/v1/reading-plans/{plan_id}/progress
```

**Parameters:**
- `current_page` (required): Page you're on now

**Adaptive Behavior:**
The system automatically:
- Calculates if you're on track
- Adjusts daily page requirements based on actual progress
- Increases pages/day if you're behind
- Decreases pages/day if you're ahead

**Example:**
```javascript
fetch('/wp-json/gread/v1/reading-plans/1/progress', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        current_page: 150
    })
})
    .then(response => response.json())
    .then(data => {
        if (data.is_on_track) {
            console.log('Great progress!');
        } else {
            console.log(`Behind schedule. Read ${data.new_pages_per_day} pages/day to catch up.`);
        }
    });
```

#### Get Today's Reading Goals
```
GET /wp-json/gread/v1/reading-plans/today
```

**Response:**
```json
{
    "success": true,
    "date": "2024-11-20",
    "total_pages_today": 45,
    "goals": [
        {
            "plan_id": 1,
            "book_title": "The Great Gatsby",
            "pages_to_read_today": 25,
            "current_page": 50,
            "target_date": "2024-12-01",
            "is_on_track": true
        },
        {
            "plan_id": 2,
            "book_title": "1984",
            "pages_to_read_today": 20,
            "current_page": 100,
            "target_date": "2024-12-15",
            "is_on_track": false
        }
    ]
}
```

---

## 9. Citation & Bibliography Generator

### Overview
Generates properly formatted citations in multiple academic styles.

### Supported Styles
- MLA (Modern Language Association)
- APA (American Psychological Association)
- Chicago
- Harvard
- BibTeX

### API Endpoints

#### Get Single Book Citation
```
GET /wp-json/gread/v1/books/{book_id}/citation?style=mla
```

**Parameters:**
- `style`: 'mla', 'apa', 'chicago', 'harvard', or 'bibtex'

**Example:**
```javascript
fetch('/wp-json/gread/v1/books/123/citation?style=mla')
    .then(response => response.json())
    .then(data => {
        console.log(data.citation);
        // Output: Fitzgerald, F. Scott. _The Great Gatsby_. Scribner, 1925.
    });
```

#### Generate Bibliography
```
POST /wp-json/gread/v1/bibliography
```

**Parameters:**
- `book_ids` (required): Array of book IDs
- `style`: Citation style (default: 'mla')
- `sort`: 'alphabetical' or 'order'

**Example:**
```javascript
fetch('/wp-json/gread/v1/bibliography', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        book_ids: [123, 456, 789],
        style: 'apa',
        sort: 'alphabetical'
    })
})
    .then(response => response.json())
    .then(data => {
        console.log(data.formatted_bibliography);
        // Copy to clipboard or display
        navigator.clipboard.writeText(data.formatted_bibliography);
    });
```

### Citation Formats

**MLA:**
```
Fitzgerald, F. Scott. _The Great Gatsby_. Scribner, 1925.
```

**APA:**
```
Fitzgerald, F. S. (1925). _The Great Gatsby_. Scribner.
```

**Chicago:**
```
Fitzgerald, F. Scott. _The Great Gatsby_. New York: Scribner, 1925.
```

**BibTeX:**
```
@book{fitzgerald1925,
  author = {F. Scott Fitzgerald},
  title = {The Great Gatsby},
  publisher = {Scribner},
  year = {1925},
  isbn = {978-0743273565}
}
```

---

## 10. Chapter List Submission

### Overview
Users can submit chapter lists for books, earning points for approved submissions.

### API Endpoints

#### Get Book Chapters
```
GET /wp-json/gread/v1/books/{book_id}/chapters?status=approved
```

**Parameters:**
- `status`: 'approved', 'pending', or 'all'

#### Submit Chapters
```
POST /wp-json/gread/v1/books/{book_id}/chapters
```

**Parameters:**
- `chapters` (required): Array of chapter objects

**Chapter Object:**
- `chapter_number` (required): Chapter number
- `chapter_title` (required): Chapter title
- `start_page`: Starting page (optional)
- `end_page`: Ending page (optional)

**Example:**
```javascript
fetch('/wp-json/gread/v1/books/123/chapters', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        chapters: [
            {
                chapter_number: 1,
                chapter_title: 'In which we are introduced',
                start_page: 1,
                end_page: 25
            },
            {
                chapter_number: 2,
                chapter_title: 'The valley of ashes',
                start_page: 26,
                end_page: 45
            }
        ]
    })
})
    .then(response => response.json())
    .then(data => {
        console.log(`Submitted ${data.chapters_submitted} chapters`);
        console.log(`Pending points: ${data.pending_points}`);
    });
```

#### Approve/Reject Chapters (Admin Only)
```
PUT /wp-json/gread/v1/chapters/{chapter_id}/review
```

**Parameters:**
- `status`: 'approved' or 'rejected'

### Points System
- 5 points per chapter submitted
- Points awarded only after admin approval

---

## 11. User Summaries with Points System

### Overview
Users can submit summaries of various types and earn points. Other users can vote on summary quality.

### Summary Types
- `chapter`: Summary of a specific chapter
- `character`: Character analysis
- `plot`: Plot summary
- `theme`: Theme analysis
- `overall`: Overall book summary

### API Endpoints

#### Get Book Summaries
```
GET /wp-json/gread/v1/books/{book_id}/summaries?type=chapter&status=approved
```

**Parameters:**
- `type`: Filter by summary type
- `status`: 'approved', 'pending', or 'all'

#### Submit Summary
```
POST /wp-json/gread/v1/summaries
```

**Parameters:**
- `book_id` (required): Book ID
- `summary_type` (required): Type of summary
- `title` (required): Summary title
- `content` (required): Summary content (min 50 characters)
- `chapter_id`: Required if type is 'chapter'
- `character_name`: Required if type is 'character'

**Example:**
```javascript
fetch('/wp-json/gread/v1/summaries', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        book_id: 123,
        summary_type: 'character',
        title: 'Jay Gatsby - A Man of Mystery',
        content: 'Jay Gatsby is the titular character... [lengthy analysis]',
        character_name: 'Jay Gatsby'
    })
})
    .then(response => response.json())
    .then(data => {
        console.log(`Summary submitted! Pending points: ${data.pending_points}`);
    });
```

#### Vote on Summary
```
POST /wp-json/gread/v1/summaries/{summary_id}/vote
```

**Parameters:**
- `vote_type`: 'helpful' or 'not_helpful'

**Example:**
```javascript
fetch('/wp-json/gread/v1/summaries/1/vote', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        vote_type: 'helpful'
    })
});
```

#### Get User Contributions
```
GET /wp-json/gread/v1/user/contributions
```

**Response:**
```json
{
    "success": true,
    "stats": {
        "total_points": 450,
        "summaries_submitted": 15,
        "summaries_approved": 12,
        "total_summary_views": 2500,
        "total_helpful_votes": 180,
        "chapters_submitted": 3,
        "chapters_approved": 3
    },
    "recent_summaries": [...]
}
```

### Points System

**Base Points by Type:**
- Chapter summary: 10 points
- Character summary: 15 points
- Plot summary: 20 points
- Theme analysis: 25 points
- Overall summary: 30 points

**Bonuses:**
- +5 points for 200+ words
- +10 points for 500+ words
- +5-20 points for high quality (admin-rated)
- +2 points per helpful vote from other users

**Quality Score Bonuses:**
- 4.5+ stars: +20 points
- 4.0+ stars: +10 points
- 3.5+ stars: +5 points

---

## Frontend Integration Examples

### Display User's Reading Goals Dashboard

```javascript
// Get today's reading goals
fetch('/wp-json/gread/v1/reading-plans/today', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
    .then(response => response.json())
    .then(data => {
        const goalsHtml = data.goals.map(goal => `
            <div class="reading-goal ${goal.is_on_track ? 'on-track' : 'behind'}">
                <h3>${goal.book_title}</h3>
                <p>Read ${goal.pages_to_read_today} pages today</p>
                <p>Current page: ${goal.current_page}</p>
                <p>Due: ${goal.target_date}</p>
                <span class="status">${goal.is_on_track ? '✓ On Track' : '⚠ Behind'}</span>
            </div>
        `).join('');

        document.getElementById('reading-goals').innerHTML = goalsHtml;
    });
```

### Create Bibliography Generator Widget

```javascript
function generateBibliography(bookIds, style = 'mla') {
    fetch('/wp-json/gread/v1/bibliography', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            book_ids: bookIds,
            style: style,
            sort: 'alphabetical'
        })
    })
        .then(response => response.json())
        .then(data => {
            const textarea = document.getElementById('bibliography-output');
            textarea.value = data.formatted_bibliography;

            // Add copy button
            document.getElementById('copy-btn').onclick = () => {
                navigator.clipboard.writeText(data.formatted_bibliography);
                alert('Bibliography copied to clipboard!');
            };
        });
}
```

### Milestone Achievements Display

```javascript
fetch('/wp-json/gread/v1/milestones', {
    headers: {
        'X-WP-Nonce': wpApiSettings.nonce
    }
})
    .then(response => response.json())
    .then(data => {
        const milestonesHtml = data.milestones.map(m => `
            <div class="milestone">
                <span class="badge">🏆</span>
                <h4>${m.metadata.milestone_name}</h4>
                <p>Achieved on ${new Date(m.achieved_at).toLocaleDateString()}</p>
            </div>
        `).join('');

        document.getElementById('milestones').innerHTML = milestonesHtml;
    });
```

---

## Database Schema Reference

### New Tables Created

1. **wp_hs_feature_requests** - Feature requests and issues
2. **wp_hs_request_votes** - Votes on feature requests
3. **wp_hs_user_lists** - User custom lists
4. **wp_hs_list_books** - Books in lists
5. **wp_hs_user_milestones** - User milestone achievements
6. **wp_hs_library_activity** - All library activities with timestamps
7. **wp_hs_reading_plans** - Reading plans
8. **wp_hs_plan_progress** - Reading plan progress history
9. **wp_hs_book_chapters** - User-submitted chapter lists
10. **wp_hs_user_summaries** - User-submitted summaries
11. **wp_hs_summary_votes** - Votes on summaries

---

## Testing Checklist

- [ ] Database tables created successfully
- [ ] Feature requests can be created and voted on
- [ ] API self-test endpoint returns all endpoints
- [ ] Activity tracking fires on book add/complete
- [ ] Random book selector returns unread books
- [ ] Lists can be created and books added
- [ ] Milestones awarded automatically
- [ ] Reading plans calculate pages per day correctly
- [ ] Reading plan adjusts when target date changes
- [ ] Citations generated in all styles
- [ ] Chapters can be submitted and approved
- [ ] Summaries can be submitted and voted on
- [ ] Points awarded correctly for contributions

---

## Support and Troubleshooting

### Common Issues

**Database tables not created:**
- Check that the database user has CREATE TABLE permissions
- Manually call `hs_create_new_feature_tables()` from WordPress admin

**Activity tracking not working:**
- Ensure you've added the `hs_track_library_activity()` calls to your existing code
- Check that user_id and book_id are valid

**Points not awarded:**
- Ensure submissions are being approved by admins
- Check that `hs_contribution_points` user meta exists

**Reading planner calculations off:**
- Verify book has `nop` (number of pages) meta field
- Check that dates are in correct format (YYYY-MM-DD)

### Debug Mode

Add this to wp-config.php for debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check debug.log for any errors in the wp-content directory.

---

## Credits

Developed for HotSoup Reading App
Version: 1.0
Last Updated: November 2024
