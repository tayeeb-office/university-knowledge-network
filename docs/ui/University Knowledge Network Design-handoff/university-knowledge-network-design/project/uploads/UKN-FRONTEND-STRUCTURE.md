# University Knowledge Network
## Frontend File Structure

**Project:** University Knowledge Network (UKN)  
**Purpose:** Frontend UI/UX design and developer handoff reference  
**Frontend Stack:** HTML5 + CSS3 + Bootstrap 5 + Vanilla JavaScript  
**Template Layer:** PHP  
**Visualization:** Chart.js + Cytoscape.js  

---

# 1. Project Structure

```text
university-knowledge-network/
│
├── index.php
├── README.md
│
├── pages/
│   ├── home.php
│   │
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   │
│   ├── dashboard/
│   │   ├── learner-dashboard.php
│   │   └── mentor-dashboard.php
│   │
│   ├── profile/
│   │   ├── my-profile.php
│   │   ├── edit-profile.php
│   │   ├── learner-profile.php
│   │   └── mentor-profile.php
│   │
│   ├── skills/
│   │   ├── skills.php
│   │   ├── learning-skills.php
│   │   ├── teaching-skills.php
│   │   └── skill-details.php
│   │
│   ├── learning/
│   │   ├── learning-goals.php
│   │   └── availability.php
│   │
│   ├── mentors/
│   │   ├── find-mentors.php
│   │   └── recommendations.php
│   │
│   ├── sessions/
│   │   ├── sessions.php
│   │   └── session-details.php
│   │
│   ├── points/
│   │   └── points.php
│   │
│   ├── ratings/
│   │   └── ratings.php
│   │
│   ├── community/
│   │   ├── post-details.php
│   │   ├── my-posts.php
│   │   └── saved-posts.php
│   │
│   ├── notifications/
│   │   └── notifications.php
│   │
│   ├── search/
│   │   └── search-results.php
│   │
│   ├── leaderboard/
│   │   └── leaderboard.php
│   │
│   ├── network/
│   │   └── skill-network.php
│   │
│   ├── settings/
│   │   └── settings.php
│   │
│   └── errors/
│       ├── 403.php
│       ├── 404.php
│       └── 500.php
│
├── includes/
│   ├── header.php
│   ├── left-sidebar.php
│   ├── right-sidebar.php
│   ├── mobile-nav.php
│   ├── profile-dropdown.php
│   ├── notification-dropdown.php
│   ├── footer.php
│   │
│   ├── modals/
│   │   ├── create-post-modal.php
│   │   ├── edit-post-modal.php
│   │   ├── session-request-modal.php
│   │   ├── rating-modal.php
│   │   ├── delete-confirmation-modal.php
│   │   └── role-switch-modal.php
│   │
│   └── components/
│       ├── post-card.php
│       ├── mentor-card.php
│       ├── learner-card.php
│       ├── skill-card.php
│       ├── session-card.php
│       ├── stat-card.php
│       ├── goal-card.php
│       ├── point-transaction.php
│       ├── rating-item.php
│       ├── notification-item.php
│       ├── leaderboard-row.php
│       ├── search-result-item.php
│       ├── loading-state.php
│       ├── empty-state.php
│       ├── error-state.php
│       └── success-state.php
│
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── user-details.php
│   ├── departments.php
│   ├── skill-categories.php
│   ├── skills.php
│   ├── sessions.php
│   ├── posts.php
│   ├── comments.php
│   ├── reports.php
│   └── settings.php
│
├── assets/
│   ├── css/
│   │   ├── variables.css
│   │   ├── base.css
│   │   ├── typography.css
│   │   ├── layout.css
│   │   ├── components.css
│   │   ├── forms.css
│   │   ├── utilities.css
│   │   ├── grunge.css
│   │   ├── theme.css
│   │   ├── responsive.css
│   │   │
│   │   ├── pages/
│   │   │   ├── home.css
│   │   │   ├── auth.css
│   │   │   ├── dashboard.css
│   │   │   ├── profile.css
│   │   │   ├── skills.css
│   │   │   ├── learning.css
│   │   │   ├── mentors.css
│   │   │   ├── sessions.css
│   │   │   ├── points.css
│   │   │   ├── ratings.css
│   │   │   ├── community.css
│   │   │   ├── notifications.css
│   │   │   ├── search.css
│   │   │   ├── leaderboard.css
│   │   │   ├── network.css
│   │   │   └── settings.css
│   │   │
│   │   └── admin/
│   │       ├── admin-layout.css
│   │       ├── dashboard.css
│   │       ├── tables.css
│   │       ├── forms.css
│   │       └── reports.css
│   │
│   ├── js/
│   │   ├── app.js
│   │   ├── config.js
│   │   ├── mock-data.js
│   │   │
│   │   ├── core/
│   │   │   ├── theme.js
│   │   │   ├── sidebar.js
│   │   │   ├── mobile-nav.js
│   │   │   ├── dropdown.js
│   │   │   ├── modal.js
│   │   │   ├── toast.js
│   │   │   └── validation.js
│   │   │
│   │   ├── components/
│   │   │   ├── post-card.js
│   │   │   ├── mentor-card.js
│   │   │   ├── session-card.js
│   │   │   ├── voting.js
│   │   │   ├── comments.js
│   │   │   ├── save-post.js
│   │   │   ├── follow.js
│   │   │   └── notifications.js
│   │   │
│   │   ├── pages/
│   │   │   ├── home.js
│   │   │   ├── auth.js
│   │   │   ├── dashboard.js
│   │   │   ├── profile.js
│   │   │   ├── skills.js
│   │   │   ├── learning.js
│   │   │   ├── mentors.js
│   │   │   ├── sessions.js
│   │   │   ├── points.js
│   │   │   ├── ratings.js
│   │   │   ├── community.js
│   │   │   ├── search.js
│   │   │   ├── leaderboard.js
│   │   │   └── network.js
│   │   │
│   │   └── admin/
│   │       ├── dashboard.js
│   │       ├── users.js
│   │       ├── skills.js
│   │       ├── moderation.js
│   │       └── reports.js
│   │
│   ├── images/
│   │   ├── logo/
│   │   ├── avatars/
│   │   ├── posts/
│   │   ├── placeholders/
│   │   └── illustrations/
│   │
│   ├── icons/
│   │   ├── navigation/
│   │   ├── actions/
│   │   ├── status/
│   │   └── social/
│   │
│   └── textures/
│       ├── grunge-subtle.png
│       ├── grunge-medium.png
│       ├── grunge-dark.png
│       └── scratches.png
│
├── uploads/
│   ├── profiles/
│   ├── posts/
│   └── certificates/
│
└── docs/
    ├── ui/
    ├── screenshots/
    └── references/
```

---

# 2. Page Architecture Rules

## Page vs Component vs Modal

Use the following rule throughout the project:

- **Major screen / route = Page**
- **Reusable content block = Component**
- **Small action or form = Modal**
- **Different status of the same feature = Tab / Filter where practical**

Examples:

- `mentor-profile.php` = Page
- `mentor-card.php` = Component
- `session-request-modal.php` = Modal
- Session Requests / Upcoming / Completed = Tabs inside `sessions.php`

---

# 3. Main Application Shell

All normal application pages should follow the same shell:

```text
HEADER
────────────────────────────────────────────────────────────
LEFT SIDEBAR    |    MAIN CONTENT    |    RIGHT SIDEBAR
```

Responsibilities:

- `header.php` = global top navigation
- `left-sidebar.php` = role-aware navigation
- `right-sidebar.php` = contextual information
- `mobile-nav.php` = mobile/offcanvas navigation
- Main page files = page-specific content only

Do not duplicate the full header/sidebar markup in every page.

---

# 4. Home Page Rule

`pages/home.php` is the main:

**Home + Community Feed**

Do not create a separate generic `community.php` landing page.

---

# 5. Sessions Rule

Use:

```text
pages/sessions/sessions.php
pages/sessions/session-details.php
```

`sessions.php` should visually support:

- Requests
- Upcoming
- Completed
- Cancelled

through tabs or filters.

---

# 6. Points Rule

Use one main page:

```text
pages/points/points.php
```

It should contain:

- Learning Points
- Mentor Points
- Total Points
- Point summary
- Recent transactions
- Transaction history

---

# 7. Rating Rule

Use:

```text
pages/ratings/ratings.php
```

For submitting a rating use:

```text
includes/modals/rating-modal.php
```

Do not create a separate full rating submission page unless a later requirement specifically needs one.

---

# 8. Post Creation and Editing

Do not create full pages for creating/editing posts.

Use:

```text
includes/modals/create-post-modal.php
includes/modals/edit-post-modal.php
```

The Create button should normally be available from the global header for logged-in users.

---

# 9. Responsive Structure

## Desktop

- Global header
- Permanent role-aware left sidebar
- Main page content
- Contextual right sidebar

## Mobile

- Compact top header
- Search row
- Left navigation becomes offcanvas
- No permanent right sidebar
- Contextual content moves into the main page when useful

Target breakpoints/screens:

- 320px
- 360px
- 390px
- 430px
- 576px
- 768px
- 992px
- 1200px
- 1400px+

---

# 10. Frontend Development Rule

During the frontend-only phase:

- PHP files may be used as reusable templates/includes.
- Do not add real database queries yet.
- Use realistic mock/demo data.
- Keep components API-ready for later PHP/MySQL integration.
- Avoid duplicating HTML/CSS/JavaScript across pages.
- Keep reusable components consistent across the application.

Later integration will replace mock data with:

```text
JavaScript Fetch
        ↓
PHP API
        ↓
PDO
        ↓
MySQL
        ↓
JSON
        ↓
Frontend UI
```

---

# 11. Design-to-Code Workflow

For each page/component:

```text
Requirement
    ↓
Claude Design
    ↓
Design Review
    ↓
Claude Code in VS Code
    ↓
Responsive Check
    ↓
Interaction Check
    ↓
Frontend QA
    ↓
Git Commit
```

The design and code should always map back to this file structure.
