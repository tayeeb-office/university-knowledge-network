# University Knowledge Network (UKN)

A campus peer-learning and mentoring platform: students learn skills from other students,
mentors teach what they know, and everyone earns points, shares knowledge in a community feed
and discovers skills and people across the university.

Built with PHP, MySQL/MariaDB (PDO), Bootstrap 5 and vanilla JavaScript, and designed to run on
a local XAMPP stack.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Database](#database)
- [Database Design](#database-design)
  - [ER Diagram](#er-diagram)
  - [Relational Schema](#relational-schema)
  - [Normalization / 3NF Justification](#normalization--3nf-justification)
  - [SQL VIEW](#sql-view)
  - [Indexes and Index Rationale](#indexes-and-index-rationale)
  - [Transactions](#transactions)
  - [Joins and Subqueries](#joins-and-subqueries)
  - [DBMS Requirement Matrix](#dbms-requirement-matrix)
- [Authentication Flow](#authentication-flow)
- [Roles](#roles)
- [Mentoring Workflow](#mentoring-workflow)
- [Community Workflow](#community-workflow)
- [Admin](#admin)
- [Security](#security)
- [Security Headers](#security-headers)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Project](#running-the-project)
- [Git Workflow](#git-workflow)
- [Testing](#testing)
- [Project Completion Status](#project-completion-status)
- [Known Limitations](#known-limitations)
- [Production Deployment Notes](#production-deployment-notes)
- [Project Scope](#project-scope)
- [Credits](#credits)

---

## Overview

UKN connects students who want to learn with students who can teach:

- **Learners** list the skills they want to learn, set learning goals, find and get matched with
  mentors, request mentoring sessions and rate them afterwards.
- **Mentors** list the skills they teach, publish their weekly availability, accept or reject
  session requests and run sessions.
- A single account can hold **both roles** and switch between them.
- A **community** feed lets members post questions and knowledge, comment, reply, vote, save
  posts and follow each other, with **notifications** for activity on their content.
- **Discovery** features — search and a points leaderboard — help members find skills, mentors
  and discussions.
- An **admin panel** manages departments, skill categories, skills and users, and moderates posts,
  comments and reports.

---

## Key Features

### Authentication
- Registration with server-side validation (name, university email, university ID, department,
  mobile number, password rules, terms acceptance) and duplicate email / university ID checks.
- The mobile number is private (see [Private contact details](#private-contact-details)).
- Email verification with a single-use, expiring token (stored only as a SHA-256 hash).
  Unverified accounts cannot log in; a new link can be requested.
- Login with password hashing (`password_hash` / `password_verify`), a generic error message
  for unknown email *and* wrong password, and equalised timing for both cases.
- Login rate limiting (see [Security](#security)).
- Forgot Password / Reset Password: a single-use reset link (token stored only as a SHA-256 hash,
  valid for 60 minutes) is emailed to active, verified accounts only; the request form always
  gives the same answer, so it does not reveal which emails have accounts. A reset signs out
  every existing session of that account.
- Logout that destroys the server-side session.
- The current user is re-read from the database on every request, so changes to status, role
  or admin rights take effect on the user's next request.

### Roles & Authorization
- Every new account is a **Learner**. A learner applies to become a mentor (*Apply to Become a
  Mentor* in the profile menu); an admin approves or rejects the application. Approved accounts
  become **Learner & Mentor** — they keep full learner access and gain Mentor mode. There is no
  mentor-only account.
- Admin rights are a separate flag, independent of learner/mentor capability.
- Server-side Learner ↔ Mentor mode switching for Learner & Mentor accounts (the active mode is
  always checked against the database).
- Route guards for every page and every action (guest / logged-in / learner / mentor / admin).

### Profiles
- My Profile, Edit Profile (name, department, year of study, bio, skills, profile photo) and
  public learner / mentor profile pages.
- Profile photo: JPG or PNG, at least 200×200 px, up to 2 MB, checked on the server (real content
  type, image size, complete file). It is stored in `uploads/profiles/` under a random name and
  `users.avatar_path` holds only `profiles/<name>`; replacing or removing it deletes the old file
  after the database update. The photo is shown in the header, account menu, mobile menu, My
  Profile, Edit Profile, the public learner/mentor profile and the admin header; everywhere
  else (and when there is no photo) the initials avatar is used.

### Skills & Learning Goals
- Skill directory with categories and skill detail pages.
- Learners manage their learning skills and mentors their teaching skills, each with a
  proficiency level.
- Learning goals: create, edit, update progress, complete and delete.

### Mentor Availability
- Mentors set weekly availability (up to four time slots per day) with overlap and time-range
  validation.

### Mentor Recommendation
- Recommended mentors for a learner, scored 0–100 from real data:
  skill match (40), availability (20), rating (20), experience (10) and mentor points (10).
  Factors without data for a mentor are left out and the rest renormalised.

### Mentoring Sessions
- Learners request sessions (mentor, skill, date, time, duration of 30/45/60 minutes, message);
  a request needs a stored mobile number, which only that session's mentor can see.
- Mentors accept or reject pending requests; either side can cancel as allowed by the lifecycle;
  the mentor marks a session completed once it has started.

### Ratings & Points
- A learner can rate a completed session once (overall plus optional teaching, communication
  and helpfulness scores, and a review).
- Points are recorded in a points ledger (`point_transactions`):
  completed session — learner **+25** learning points, mentor **+30** mentor points;
  late cancellation (an accepted session cancelled less than 6 hours before it starts) —
  **−10** for the person who cancels.

### Community
- Create posts with optional skill tags; edit and delete your own posts.
- Share a post from its Share menu: copy its link, share it to WhatsApp, Facebook, LinkedIn or X,
  or use the device's native share sheet where the browser supports it.
- Comments and one level of replies; edit and delete your own comments.
- Report a post, comment or reply written by someone else (reason + optional details) for admin review.
- Up/down voting (clicking the same vote again removes it) and saved posts.
- Follow / unfollow members.

### Notifications
- Generated for comments on your posts, replies to your comments and new followers,
  respecting the notification preferences stored for each member (the Settings page cannot
  change them yet — see [Known Limitations](#known-limitations)).
- Opening a notification marks it read and navigates to its target; "mark all as read".

### Search & Discovery
- Global search across skills, mentors, learners and posts; posts are searched through a
  MySQL `FULLTEXT` index, with escaped `LIKE` matching for short terms, names and skill tags.

### Leaderboard
- Top Learners, Top Mentors and Community Contributors, computed from the points ledger,
  with **This Week / This Month / All Time** periods, deterministic tie-breaking and the
  current member's own standing.

### Administration
- Seven admin areas: departments, skill categories, skills, user management, post moderation,
  comment moderation and report management (see [Admin](#admin)).
- Admin dashboard with platform statistics (Chart.js).

### Security
- CSRF protection, server-side validation, authorization and ownership checks, prepared
  statements, transactions, output escaping, login rate limiting, security headers and
  direct-access protection (see [Security](#security)).

---

## Technology Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, **Bootstrap 5.3** (CSS + JS bundle), vanilla JavaScript |
| Charts | **Chart.js 4** (admin dashboard) |
| Fonts / icons | Google Fonts (incl. Material Symbols) |
| Backend | **PHP 8** (developed and tested on PHP 8.2), server-rendered pages + form POST actions |
| Database | **MariaDB / MySQL** via **PDO** (developed and tested on MariaDB 10.4, InnoDB, `utf8mb4`) |
| Web server | Apache (XAMPP) with `.htaccess` support |

Bootstrap and Chart.js are loaded from the jsDelivr CDN; there is no build step
and no package manager.

---

## Project Structure

```text
university-knowledge-network-frontend/
├── index.php                  Front controller: route whitelist, route guards, page layout
├── .htaccess                  Security headers and web-access rules
├── admin/                     Admin panel pages (dashboard, users, departments, …)
│   └── includes/              Admin layout (header/footer) and shared admin modal
├── backend/
│   ├── auth/                  Register, login, logout, resend verification, role switch,
│   │                          password reset (request link, set new password)
│   ├── mentor-applications/   Apply to become a mentor
│   ├── profile/ skills/ goals/ availability/
│   ├── sessions/              Request, accept, reject, complete, cancel, rate
│   ├── posts/ comments/ follows/ notifications/ reports/
│   ├── admin/                 Admin actions (departments, skill-categories, skills, users,
│   │                          mentor-applications, posts, comments, reports)
│   ├── helpers/               Shared logic: auth, session, CSRF, validation, actions,
│   │                          community, sessions/points, recommendations, search,
│   │                          leaderboard, login throttling, error handling, mail, …
│   │                          PHPMailer/ = bundled PHPMailer 6.12.0 (SMTP transport)
│   ├── models/                User model
│   ├── config/                Database connection and environment-driven app settings
│   └── test-db.php            Command-line-only database connectivity check
├── pages/                     Page templates rendered by index.php (auth, dashboard, profile,
│                              skills, learning, mentors, sessions, community, notifications,
│                              search, leaderboard, points, ratings, settings, errors)
├── components/                Reusable render functions (post card, mentor card, session card, …)
├── includes/                  Shared layout: header, sidebars, dropdowns, mobile nav, footer
├── modals/                    Bootstrap modals (create/edit post, session request, rating, …)
├── assets/
│   ├── css/                   Design system, layout, components, page and admin styles
│   ├── js/                    Core scripts, page scripts, admin scripts
│   └── icons/ images/ textures/
├── database/
│   ├── schema.sql             Full schema (drops and recreates all tables and the view)
│   ├── seed.sql               Base reference data (departments, categories, skills, users)
│   ├── seed_demo_data.sql     Optional demo content for every feature table
│   └── patches/               Idempotent upgrade patches for existing databases
├── storage/mail/              Development mail log (not web-accessible)
├── uploads/                   Upload folders (profile photos in profiles/; scripts blocked)
├── docs/                      ER diagram (ER-Diagram.png + Mermaid source), design hand-off,
│                              references and screenshots (not web-accessible)
├── component-preview.dev.php  Development-only static component preview (mock data)
└── DATABASE_READ_INTEGRATION_PLAN.md   Historical planning document
```

Pages are served through `index.php?page=<route>`; state-changing actions are plain HTML forms
that `POST` to the matching file under `backend/`, which redirects back with a flash message.

---

## Database

- **Engine / charset:** InnoDB, `utf8mb4` / `utf8mb4_unicode_ci` for every table.
- **SQL mode:** the application connection enables `STRICT_TRANS_TABLES`, so values that do not
  fit a column raise an error (and roll back) instead of being silently truncated.
- **Tables (23):**

| Area | Tables |
|---|---|
| People | `users`, `user_settings`, `user_private_contacts`, `departments`, `mentor_applications` |
| Skills | `skill_categories`, `skills`, `skill_relations`, `user_skills` |
| Learning & mentoring | `learning_goals`, `mentor_availability`, `mentoring_sessions`, `session_ratings` |
| Points | `point_transactions` |
| Community | `posts`, `post_skills`, `comments`, `post_votes`, `saved_posts`, `follows` |
| Notifications & moderation | `notifications`, `reports` |
| Security | `login_attempts` |

- **View (1):** `mentor_rating_summary` — per-mentor average rating and review count computed
  from `session_ratings` (see [SQL VIEW](#sql-view)).
- **Relationships:** users belong to a department; skills belong to a category; users link to
  skills through `user_skills` (`learning` / `teaching`); sessions link a learner, a mentor and
  a skill; posts, comments (with parent/reply links), votes, saves and follows link to users and
  posts.
- **Integrity:** foreign keys with deliberate `CASCADE` / `SET NULL` / `RESTRICT` rules,
  unique keys (e.g. one vote per user per post, one rating per session, one report per reporter
  per target), and `CHECK` constraints (e.g. rating ranges, session duration, no self-follow,
  a reason required for suspensions and cancellations).
- **Transactions:** every multi-step write (registration, profile + skills, session lifecycle
  with points, ratings, comments and counters, votes, follows with notifications, admin
  moderation and report review) runs in a single transaction.
- **Points:** the `point_transactions` ledger is the source of truth for leaderboards and
  recommendations.
- **Patches:** `database/patches/` contains additive, idempotent scripts for upgrading an existing
  database. `schema.sql` already includes all of them, so a fresh install does not need them.
  An existing database gets the view with
  `mysql -u <db-user> -p ukn_database < database/patches/add-mentor-rating-summary-view.sql`
  (safe to run more than once). Password reset needs
  `mysql -u <db-user> -p ukn_database < database/patches/add-password-reset.sql` on a database
  created before it existed, and mentor applications need
  `mysql -u <db-user> -p ukn_database < database/patches/simplify-user-roles-and-add-mentor-applications.sql`
  (it also converts any old mentor-only account to Learner & Mentor). Private mobile numbers need
  `mysql -u <db-user> -p ukn_database < database/patches/add-user-private-contacts.sql`; existing
  accounts get no number, and each user adds one in Settings before requesting a session.
- **Design documentation:** ER diagram, relational schema, 3NF justification, index rationale and
  SQL feature coverage are in [Database Design](#database-design).

---

## Database Design

This section documents the database as it is defined in `database/schema.sql` — 23 base tables,
40 foreign keys, 25 `CHECK` constraints and one view — and shows where the application uses each
SQL feature.

### ER Diagram

![UKN Entity-Relationship diagram](docs/ER-Diagram.png)

- **Image:** [`docs/ER-Diagram.png`](docs/ER-Diagram.png) (large landscape image; open it at full
  size to read the attributes).
- **Editable source:** [`docs/ER-Diagram.mmd`](docs/ER-Diagram.mmd) (Mermaid `erDiagram`; it can
  be edited and re-rendered with any Mermaid renderer, e.g. the Mermaid Live Editor or
  Mermaid CLI).
- **PNG not yet re-rendered:** the Mermaid source is current and includes the password-reset
  columns on `users` and the `mentor_applications` table (with its two foreign keys to `users`).
  `ER-Diagram.png` was rendered before these were added, so it does not show them yet; re-render
  it from the `.mmd` with a Mermaid renderer. Both are also listed in the
  [Relational Schema](#relational-schema).

How to read it:

- Every table appears with every column, its data type, and `PK` / `FK` / `UK` markers (`UK` =
  part of a `UNIQUE` key).
- Each solid line is one real foreign key, labelled with the FK column. The diagram was generated
  from the foreign keys of a database built from `schema.sql`, so no relationship is invented.
- Crow's-foot cardinalities follow the constraints:
  - parent side: `||` = exactly one (FK is `NOT NULL`), `|o` = zero or one (FK is nullable);
  - child side: `o{` = zero or many, `o|` = zero or one (the FK is also the child's primary key
    or a single-column `UNIQUE` key, e.g. `user_settings.user_id`,
    `session_ratings.session_id`).
- Junction (many-to-many) tables: `post_skills` (posts ↔ skills), `post_votes` and
  `saved_posts` (users ↔ posts), `follows` (users ↔ users), `skill_relations`
  (skills ↔ skills), and `user_skills` (users ↔ skills, with `skill_type` = learning/teaching and
  its own attributes).
- `mentoring_sessions` links a learner, a mentor and a skill (three FKs, two of them to `users`);
  `comments.parent_id` is a self-reference (reply → parent comment).
- **Dashed lines** are the only relationships *without* a foreign key: `reports.target_type` +
  `reports.target_id` point to a post, a comment or a user depending on `target_type`. A single
  column cannot carry an FK to three tables, and reports must stay traceable even after their
  target is deleted, so this reference is validated by the application instead.
- `login_attempts` (login rate limiting, keyed by a hashed throttle key) has no relationships.
- The view `mentor_rating_summary` stores no data and is therefore not drawn as an entity (see
  [SQL VIEW](#sql-view)).

### Relational Schema

Notation: **PK** primary key, **FK** foreign key, **UNIQUE** unique key, ✓ = `NOT NULL`.
All tables are InnoDB with `utf8mb4_unicode_ci`. Every surrogate `id` is `AUTO_INCREMENT`.
Expand a table to see its columns and constraints (generated from the database's
`information_schema`, so it matches `schema.sql` exactly).

<details>
<summary><b>1. <code>departments</code></b> — 6 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `smallint(5) unsigned` | ✓ | PK |
| `name` | `varchar(100)` | ✓ | UNIQUE |
| `code` | `varchar(10)` | ✓ | UNIQUE |
| `status` | `enum('active','inactive')` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **UNIQUE** `uq_departments_code` (code)
- **UNIQUE** `uq_departments_name` (name)
- **Indexes:** `idx_departments_status` (status)

</details>

<details>
<summary><b>2. <code>users</code></b> — 30 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `full_name` | `varchar(120)` | ✓ |  |
| `initials` | `varchar(4)` | ✓ |  |
| `email` | `varchar(160)` | ✓ | UNIQUE |
| `university_id` | `varchar(30)` | ✓ | UNIQUE |
| `password_hash` | `varchar(255)` | ✓ |  |
| `email_verified_at` | `datetime` |  |  |
| `verification_token_hash` | `char(64)` |  | UNIQUE |
| `verification_expires_at` | `datetime` |  |  |
| `password_reset_token_hash` | `char(64)` |  | UNIQUE |
| `password_reset_expires_at` | `datetime` |  |  |
| `department_id` | `smallint(5) unsigned` |  | FK → `departments.id` |
| `role` | `enum('learner','dual')` | ✓ |  |
| `is_admin` | `tinyint(1)` | ✓ |  |
| `year_of_study` | `enum('1st Year','2nd Year','3rd Year','4th Year')` |  |  |
| `headline` | `varchar(150)` |  |  |
| `bio` | `text` |  |  |
| `avatar_path` | `varchar(255)` |  |  |
| `status` | `enum('active','inactive','suspended')` | ✓ |  |
| `suspend_reason` | `varchar(255)` |  |  |
| `learning_points` | `int(11)` | ✓ |  |
| `mentor_points` | `int(11)` | ✓ |  |
| `avg_rating` | `decimal(2,1)` |  |  |
| `total_reviews` | `int(10) unsigned` | ✓ |  |
| `sessions_as_learner` | `int(10) unsigned` | ✓ |  |
| `sessions_as_mentor` | `int(10) unsigned` | ✓ |  |
| `learners_helped` | `int(10) unsigned` | ✓ |  |
| `last_active_at` | `datetime` |  |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `department_id` → `departments(id)` ON DELETE SET NULL
- **UNIQUE** `uq_users_email` (email)
- **UNIQUE** `uq_users_university_id` (university_id)
- **UNIQUE** `uq_users_verification_token` (verification_token_hash)
- **UNIQUE** `uq_users_password_reset_token` (password_reset_token_hash)
- **CHECK** `chk_users_avg_rating`: `avg_rating is null or avg_rating >= 1.0 and avg_rating <= 5.0`
- **CHECK** `chk_users_suspend_reason`: `status <> 'suspended' or suspend_reason is not null`
- **CHECK** `chk_users_role`: `role in ('learner','dual')` — also rejects an invalid role (e.g. `'mentor'`) from a non-strict SQL session, which would otherwise store the ENUM's empty value
- **Indexes:** `idx_users_department` (department_id); `idx_users_full_name` (full_name); `idx_users_learning_points` (learning_points); `idx_users_mentor_points` (mentor_points); `idx_users_role_status` (role, status)

</details>

<details>
<summary><b>3. <code>user_settings</code></b> — 10 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `user_id` | `int(10) unsigned` | ✓ | PK, FK → `users.id` |
| `notify_session_updates` | `tinyint(1)` | ✓ |  |
| `notify_session_reminders` | `tinyint(1)` | ✓ |  |
| `notify_community_replies` | `tinyint(1)` | ✓ |  |
| `notify_follow_activity` | `tinyint(1)` | ✓ |  |
| `notify_learner_requests` | `tinyint(1)` | ✓ |  |
| `notify_rating_received` | `tinyint(1)` | ✓ |  |
| `profile_visibility` | `enum('public','members','private')` | ✓ |  |
| `theme` | `enum('light','dark','system')` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (user_id)
- **FK** `user_id` → `users(id)` ON DELETE CASCADE

</details>

<details>
<summary><b>4. <code>skill_categories</code></b> — 6 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `tinyint(3) unsigned` | ✓ | PK |
| `name` | `varchar(60)` | ✓ | UNIQUE |
| `description` | `varchar(255)` | ✓ |  |
| `status` | `enum('active','inactive')` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **UNIQUE** `uq_skill_categories_name` (name)
- **Indexes:** `idx_skill_categories_status` (status)

</details>

<details>
<summary><b>5. <code>skills</code></b> — 9 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `smallint(5) unsigned` | ✓ | PK |
| `name` | `varchar(80)` | ✓ | UNIQUE |
| `slug` | `varchar(80)` | ✓ | UNIQUE |
| `category_id` | `tinyint(3) unsigned` | ✓ | FK → `skill_categories.id` |
| `description` | `varchar(255)` | ✓ |  |
| `about` | `text` |  |  |
| `status` | `enum('active','inactive')` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `category_id` → `skill_categories(id)` ON DELETE RESTRICT
- **UNIQUE** `uq_skills_name` (name)
- **UNIQUE** `uq_skills_slug` (slug)
- **Indexes:** `idx_skills_category` (category_id); `idx_skills_status` (status)

</details>

<details>
<summary><b>6. <code>skill_relations</code></b> — 6 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `source_skill_id` | `smallint(5) unsigned` | ✓ | FK → `skills.id` |
| `target_skill_id` | `smallint(5) unsigned` | ✓ | FK → `skills.id` |
| `strength` | `enum('Strong','Medium','Related')` | ✓ |  |
| `reason` | `varchar(255)` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `source_skill_id` → `skills(id)` ON DELETE CASCADE
- **FK** `target_skill_id` → `skills(id)` ON DELETE CASCADE
- **UNIQUE** `uq_skill_relations_pair` (source_skill_id, target_skill_id)
- **CHECK** `chk_skill_relations_not_self`: `source_skill_id <> target_skill_id`
- **Indexes:** `idx_skill_relations_target` (target_skill_id)

</details>

<details>
<summary><b>7. <code>user_skills</code></b> — 11 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `user_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `skill_id` | `smallint(5) unsigned` | ✓ | FK → `skills.id` |
| `skill_type` | `enum('learning','teaching')` | ✓ |  |
| `proficiency` | `enum('Beginner','Intermediate','Advanced')` |  |  |
| `progress` | `tinyint(3) unsigned` | ✓ |  |
| `sessions_count` | `int(10) unsigned` | ✓ |  |
| `avg_rating` | `decimal(2,1)` |  |  |
| `primary_mentor_id` | `int(10) unsigned` |  | FK → `users.id` |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `primary_mentor_id` → `users(id)` ON DELETE SET NULL
- **FK** `skill_id` → `skills(id)` ON DELETE CASCADE
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **UNIQUE** `uq_user_skills` (user_id, skill_id, skill_type)
- **CHECK** `chk_user_skills_mentor_only_on_learning`: `primary_mentor_id is null or skill_type = 'learning'`
- **CHECK** `chk_user_skills_progress`: `progress between 0 and 100`
- **CHECK** `chk_user_skills_rating`: `avg_rating is null or avg_rating >= 1.0 and avg_rating <= 5.0`
- **Indexes:** `idx_user_skills_mentor` (primary_mentor_id); `idx_user_skills_skill_type` (skill_id, skill_type)

</details>

<details>
<summary><b>8. <code>mentor_availability</code></b> — 8 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `user_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `day_of_week` | `tinyint(3) unsigned` | ✓ |  |
| `start_time` | `time` | ✓ |  |
| `end_time` | `time` | ✓ |  |
| `is_enabled` | `tinyint(1)` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **UNIQUE** `uq_mentor_availability_slot` (user_id, day_of_week, start_time)
- **CHECK** `chk_mentor_availability_day`: `day_of_week between 0 and 6`
- **CHECK** `chk_mentor_availability_range`: `end_time > start_time`
- **Indexes:** `idx_mentor_availability_day` (day_of_week, is_enabled)

</details>

<details>
<summary><b>9. <code>learning_goals</code></b> — 11 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `user_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `skill_id` | `smallint(5) unsigned` |  | FK → `skills.id` |
| `title` | `varchar(160)` | ✓ |  |
| `description` | `varchar(500)` |  |  |
| `progress` | `tinyint(3) unsigned` | ✓ |  |
| `target_date` | `date` |  |  |
| `status` | `enum('in-progress','completed')` | ✓ |  |
| `completed_at` | `datetime` |  |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `skill_id` → `skills(id)` ON DELETE SET NULL
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **CHECK** `chk_learning_goals_completed`: `status <> 'completed' or progress = 100`
- **CHECK** `chk_learning_goals_progress`: `progress between 0 and 100`
- **Indexes:** `idx_learning_goals_skill` (skill_id); `idx_learning_goals_user_status` (user_id, status)

</details>

<details>
<summary><b>10. <code>mentoring_sessions</code></b> — 16 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `reference_code` | `varchar(20)` | ✓ | UNIQUE |
| `learner_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `mentor_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `skill_id` | `smallint(5) unsigned` | ✓ | FK → `skills.id` |
| `scheduled_date` | `date` | ✓ |  |
| `scheduled_time` | `time` | ✓ |  |
| `duration_minutes` | `smallint(5) unsigned` | ✓ |  |
| `status` | `enum('pending','accepted','completed','rejected','cancelled')` | ✓ |  |
| `request_message` | `text` |  |  |
| `cancel_reason` | `varchar(255)` |  |  |
| `requested_at` | `datetime` | ✓ |  |
| `responded_at` | `datetime` |  |  |
| `completed_at` | `datetime` |  |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `learner_id` → `users(id)` ON DELETE CASCADE
- **FK** `mentor_id` → `users(id)` ON DELETE CASCADE
- **FK** `skill_id` → `skills(id)` ON DELETE RESTRICT
- **UNIQUE** `uq_mentoring_sessions_reference` (reference_code)
- **CHECK** `chk_sessions_cancel_reason`: `status <> 'cancelled' or cancel_reason is not null`
- **CHECK** `chk_sessions_duration`: `duration_minutes between 15 and 240`
- **CHECK** `chk_sessions_not_self`: `learner_id <> mentor_id`
- **Indexes:** `idx_sessions_date` (scheduled_date); `idx_sessions_learner_status` (learner_id, status); `idx_sessions_mentor_status` (mentor_id, status); `idx_sessions_skill` (skill_id)

</details>

<details>
<summary><b>11. <code>session_ratings</code></b> — 11 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `session_id` | `int(10) unsigned` | ✓ | FK → `mentoring_sessions.id`, UNIQUE |
| `reviewer_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `mentor_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `skill_id` | `smallint(5) unsigned` |  | FK → `skills.id` |
| `overall` | `tinyint(3) unsigned` | ✓ |  |
| `teaching` | `tinyint(3) unsigned` |  |  |
| `communication` | `tinyint(3) unsigned` |  |  |
| `helpfulness` | `tinyint(3) unsigned` |  |  |
| `review` | `text` |  |  |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `mentor_id` → `users(id)` ON DELETE CASCADE
- **FK** `reviewer_id` → `users(id)` ON DELETE CASCADE
- **FK** `session_id` → `mentoring_sessions(id)` ON DELETE CASCADE
- **FK** `skill_id` → `skills(id)` ON DELETE SET NULL
- **UNIQUE** `uq_session_ratings_session` (session_id)
- **CHECK** `chk_session_ratings_communication`: `communication is null or communication between 1 and 5`
- **CHECK** `chk_session_ratings_helpfulness`: `helpfulness is null or helpfulness between 1 and 5`
- **CHECK** `chk_session_ratings_not_self`: `reviewer_id <> mentor_id`
- **CHECK** `chk_session_ratings_overall`: `overall between 1 and 5`
- **CHECK** `chk_session_ratings_teaching`: `teaching is null or teaching between 1 and 5`
- **Indexes:** `idx_session_ratings_mentor` (mentor_id, created_at); `idx_session_ratings_reviewer` (reviewer_id); `idx_session_ratings_skill` (skill_id)

</details>

<details>
<summary><b>12. <code>posts</code></b> — 10 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `user_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `title` | `varchar(160)` | ✓ |  |
| `content` | `text` | ✓ |  |
| `vote_score` | `int(11)` | ✓ |  |
| `comment_count` | `int(10) unsigned` | ✓ |  |
| `report_count` | `int(10) unsigned` | ✓ |  |
| `status` | `enum('visible','hidden')` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **Indexes:** `ft_posts_search` FULLTEXT (title, content); `idx_posts_author` (user_id, created_at); `idx_posts_feed` (status, created_at); `idx_posts_popular` (status, vote_score)

</details>

<details>
<summary><b>13. <code>post_skills</code></b> — 2 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `post_id` | `int(10) unsigned` | ✓ | PK, FK → `posts.id` |
| `skill_id` | `smallint(5) unsigned` | ✓ | PK, FK → `skills.id` |

- **PK:** (post_id, skill_id)
- **FK** `post_id` → `posts(id)` ON DELETE CASCADE
- **FK** `skill_id` → `skills(id)` ON DELETE CASCADE
- **Indexes:** `idx_post_skills_skill` (skill_id)

</details>

<details>
<summary><b>14. <code>comments</code></b> — 9 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `post_id` | `int(10) unsigned` | ✓ | FK → `posts.id` |
| `user_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `parent_id` | `int(10) unsigned` |  | FK → `comments.id` |
| `content` | `text` | ✓ |  |
| `report_count` | `int(10) unsigned` | ✓ |  |
| `status` | `enum('visible','hidden')` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `parent_id` → `comments(id)` ON DELETE CASCADE
- **FK** `post_id` → `posts(id)` ON DELETE CASCADE
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **Indexes:** `idx_comments_parent` (parent_id); `idx_comments_post` (post_id, created_at); `idx_comments_user` (user_id)

</details>

<details>
<summary><b>15. <code>post_votes</code></b> — 4 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `user_id` | `int(10) unsigned` | ✓ | PK, FK → `users.id` |
| `post_id` | `int(10) unsigned` | ✓ | PK, FK → `posts.id` |
| `value` | `tinyint(4)` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (user_id, post_id)
- **FK** `post_id` → `posts(id)` ON DELETE CASCADE
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **CHECK** `chk_post_votes_value`: `value in (-1,1)`
- **Indexes:** `idx_post_votes_post` (post_id)

</details>

<details>
<summary><b>16. <code>saved_posts</code></b> — 3 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `user_id` | `int(10) unsigned` | ✓ | PK, FK → `users.id` |
| `post_id` | `int(10) unsigned` | ✓ | PK, FK → `posts.id` |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (user_id, post_id)
- **FK** `post_id` → `posts(id)` ON DELETE CASCADE
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **Indexes:** `idx_saved_posts_post` (post_id)

</details>

<details>
<summary><b>17. <code>follows</code></b> — 3 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `follower_id` | `int(10) unsigned` | ✓ | PK, FK → `users.id` |
| `following_id` | `int(10) unsigned` | ✓ | PK, FK → `users.id` |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (follower_id, following_id)
- **FK** `follower_id` → `users(id)` ON DELETE CASCADE
- **FK** `following_id` → `users(id)` ON DELETE CASCADE
- **CHECK** `chk_follows_not_self`: `follower_id <> following_id`
- **Indexes:** `idx_follows_following` (following_id)

</details>

<details>
<summary><b>18. <code>point_transactions</code></b> — 10 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `bigint(20) unsigned` | ✓ | PK |
| `user_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `amount` | `int(11)` | ✓ |  |
| `point_type` | `enum('learning','mentor','community')` | ✓ |  |
| `category` | `enum('session','goal','rating','community','penalty')` | ✓ |  |
| `reason` | `varchar(160)` | ✓ |  |
| `related_session_id` | `int(10) unsigned` |  | FK → `mentoring_sessions.id` |
| `related_post_id` | `int(10) unsigned` |  | FK → `posts.id` |
| `related_goal_id` | `int(10) unsigned` |  | FK → `learning_goals.id` |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `related_goal_id` → `learning_goals(id)` ON DELETE SET NULL
- **FK** `related_post_id` → `posts(id)` ON DELETE SET NULL
- **FK** `related_session_id` → `mentoring_sessions(id)` ON DELETE SET NULL
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **CHECK** `chk_points_amount_nonzero`: `amount <> 0`
- **Indexes:** `fk_points_goal` (related_goal_id); `fk_points_post` (related_post_id); `fk_points_session` (related_session_id); `idx_points_category` (category); `idx_points_user_date` (user_id, created_at); `idx_points_user_type` (user_id, point_type)

</details>

<details>
<summary><b>19. <code>notifications</code></b> — 8 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `bigint(20) unsigned` | ✓ | PK |
| `user_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `type` | `enum('session','community','rating','system')` | ✓ |  |
| `icon` | `varchar(40)` | ✓ |  |
| `message` | `varchar(255)` | ✓ |  |
| `link_url` | `varchar(255)` |  |  |
| `is_read` | `tinyint(1)` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **Indexes:** `idx_notifications_inbox` (user_id, is_read, created_at); `idx_notifications_type` (user_id, type)

</details>

<details>
<summary><b>20. <code>reports</code></b> — 11 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `reference_code` | `varchar(20)` | ✓ | UNIQUE |
| `reporter_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `target_type` | `enum('post','comment','user')` | ✓ |  |
| `target_id` | `int(10) unsigned` | ✓ |  |
| `reason` | `enum('academic-integrity','off-topic','spam','inappropriate','harassment','other')` | ✓ |  |
| `description` | `text` |  |  |
| `status` | `enum('pending','resolved','dismissed')` | ✓ |  |
| `reviewed_by` | `int(10) unsigned` |  | FK → `users.id` |
| `reviewed_at` | `datetime` |  |  |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `reporter_id` → `users(id)` ON DELETE CASCADE
- **FK** `reviewed_by` → `users(id)` ON DELETE SET NULL
- **UNIQUE** `uq_reports_one_per_reporter` (reporter_id, target_type, target_id)
- **UNIQUE** `uq_reports_reference` (reference_code)
- **CHECK** `chk_reports_reviewed`: `status = 'pending' or reviewed_at is not null`
- **Indexes:** `fk_reports_reviewer` (reviewed_by); `idx_reports_queue` (status, created_at); `idx_reports_target` (target_type, target_id)

</details>

<details>
<summary><b>21. <code>login_attempts</code></b> — 4 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `throttle_key` | `char(64)` | ✓ | PK |
| `attempts` | `int(10) unsigned` | ✓ |  |
| `window_started_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (throttle_key)
- **Indexes:** `idx_login_attempts_window` (window_started_at)

</details>

<details>
<summary><b>22. <code>mentor_applications</code></b> — 9 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `user_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `status` | `enum('pending','approved','rejected')` | ✓ |  |
| `application_message` | `varchar(1000)` |  |  |
| `requested_at` | `datetime` | ✓ |  |
| `reviewed_at` | `datetime` |  |  |
| `reviewed_by` | `int(10) unsigned` |  | FK → `users.id` |
| `admin_note` | `varchar(255)` |  |  |
| `pending_user_id` | `int(10) unsigned` (generated: `user_id` while pending, else NULL) |  | UNIQUE |

- **PK:** (id)
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **FK** `reviewed_by` → `users(id)` ON DELETE SET NULL
- **UNIQUE** `uq_mentor_applications_pending` (pending_user_id) — at most one *pending* application per user
  (MariaDB 10.4 has no partial unique index, so a stored generated column carries the rule)
- **CHECK** `chk_mentor_applications_reviewed`: `status = 'pending' or reviewed_at is not null`
- **Indexes:** `fk_mentor_applications_reviewer` (reviewed_by); `idx_mentor_applications_queue` (status, requested_at); `idx_mentor_applications_user` (user_id, requested_at)

</details>

<details>
<summary><b>23. <code>user_private_contacts</code></b> — 4 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `user_id` | `int(10) unsigned` | ✓ | PK, FK → `users.id` |
| `mobile_number` | `varchar(20)` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |
| `updated_at` | `datetime` | ✓ |  |

- **PK:** (user_id)
- **FK** `user_id` → `users(id)` ON DELETE CASCADE
- **CHECK** `chk_user_private_contacts_mobile`: `mobile_number` matches `^[+]8801[3-9][0-9]{8}$` (Bangladesh mobile, E.164)
- Not unique: a shared/family number may belong to more than one account.
- Kept out of `users` so that no general user query can return it; see [Private contact details](#private-contact-details).

</details>

**View:** `mentor_rating_summary(mentor_id, avg_rating, total_reviews)` — derived from
`session_ratings`; see [SQL VIEW](#sql-view).

Summary of the relationships:

| Relationship | Type | Implemented by |
|---|---|---|
| departments → users | 1 : N (optional) | `users.department_id` (nullable, `ON DELETE SET NULL`) |
| users → user_settings | 1 : 0..1 | `user_settings.user_id` is both PK and FK |
| users → user_private_contacts | 1 : 0..1 | `user_private_contacts.user_id` is both PK and FK (accounts from before this table have no row) |
| skill_categories → skills | 1 : N | `skills.category_id` |
| skills ↔ skills | M : N | `skill_relations(source_skill_id, target_skill_id)` |
| users ↔ skills | M : N with attributes | `user_skills(user_id, skill_id, skill_type)` |
| users → mentor_availability, learning_goals, posts, comments, notifications, point_transactions | 1 : N | `user_id` FK in each table |
| users (learner) / users (mentor) / skills → mentoring_sessions | 1 : N each | `learner_id`, `mentor_id`, `skill_id` |
| mentoring_sessions → session_ratings | 1 : 0..1 | `session_ratings.session_id` `UNIQUE` |
| users → mentor_applications (applicant; reviewing admin) | 1 : N each | `mentor_applications.user_id`; `reviewed_by` (nullable, `ON DELETE SET NULL`) |
| posts ↔ skills | M : N | `post_skills(post_id, skill_id)` |
| users ↔ posts (votes, saves) | M : N | `post_votes(user_id, post_id)`, `saved_posts(user_id, post_id)` |
| users ↔ users (follows) | M : N | `follows(follower_id, following_id)` |
| posts → comments; comments → replies | 1 : N; self 1 : N | `comments.post_id`; `comments.parent_id` |
| sessions / posts / goals → point_transactions | 1 : N (optional) | `related_session_id`, `related_post_id`, `related_goal_id` (nullable) |
| users → reports (reporter, reviewer) | 1 : N | `reports.reporter_id`, `reports.reviewed_by` |
| posts / comments / users → reports (target) | logical, no FK | `reports.target_type` + `target_id` |

### Normalization / 3NF Justification

**First normal form (atomic values, no repeating groups).**
- Every column holds one value; there are no comma-separated lists or arrays.
- Multi-valued facts are stored as rows in their own tables instead of repeated columns:
  - skill tags of a post → `post_skills`;
  - a user's skills → `user_skills`;
  - weekly time slots → one `mentor_availability` row per slot;
  - votes, saves and follows → `post_votes`, `saved_posts`, `follows`;
  - related skills → `skill_relations`;
  - a user's mentor applications (with their review history) → `mentor_applications`, not
    repeated "application status" columns on `users`.
- The `notify_*` columns of `user_settings` are not a repeating group: each is a different,
  fixed preference of a single user (one row per user).

**Second normal form (no partial dependencies).**
- Most tables have a single-column surrogate key (`id`). A partial dependency is impossible for
  those keys.
- The tables with a composite primary key are `post_skills`, `post_votes`, `saved_posts` and
  `follows`. Their only non-key columns (`value`, `created_at`) describe the pair as a whole,
  for example *this* user's vote on *this* post.
- Tables whose natural key is composite also keep that key as a `UNIQUE` constraint, and their
  attributes depend on the whole key:
  - `user_skills` (`user_id`, `skill_id`, `skill_type`): proficiency and progress belong to one
    user, one skill and one direction;
  - `skill_relations` (`source_skill_id`, `target_skill_id`): strength and reason describe the
    pair;
  - `mentor_availability` (`user_id`, `day_of_week`, `start_time`).

**Third normal form (no transitive dependencies).**
- Descriptive facts about an entity are stored only in that entity's table and referenced by key.
  For example:
  - a user row holds `department_id`, not the department's name;
  - a skill holds `category_id`, not the category's name;
  - posts, comments and sessions hold `user_id` / `learner_id` / `mentor_id`, not user names;
  - point balances are not stored in sessions or posts.
- Entities (users, skills, posts, sessions, …) are kept separate from relationships (the junction
  tables above), so a relationship can be added or removed without changing either entity.
- `reports.target_type` + `target_id` does not introduce a transitive dependency. It is a
  polymorphic reference without an FK (see [ER Diagram](#er-diagram)); what it gives up is
  referential integrity, not normalization.

**Derived and cached columns — controlled denormalization.** The schema deliberately keeps a few
values that can be derived from other data. They are **not** 3NF-pure. They exist so that
frequently shown numbers (profile cards, feed cards, mentor cards) do not need an aggregate query
per row. Each one has a single, known source and a defined writer:

| Column(s) | Derived from | Maintained by | Why it is kept |
|---|---|---|---|
| `users.initials` | `users.full_name` | `uknDeriveInitials()` on every write of the name (`backend/auth/register.php:81`, `backend/profile/update.php:74`) | Avatar placeholder text shown on almost every page |
| `users.learning_points`, `users.mentor_points` | `SUM(point_transactions.amount)` per type | `uknAwardPoints()` inserts the ledger row and increments the column in the same transaction (`backend/helpers/sessions.php:68-72`) | Quick display of a member's totals |
| `users.avg_rating`, `users.total_reviews` | `session_ratings` of the mentor | Running-average update in the rating transaction (`backend/sessions/rate.php:58-62`) | Shown on mentor cards and profiles |
| `users.sessions_as_learner`, `sessions_as_mentor`, `learners_helped` | completed `mentoring_sessions` | Incremented in the completion transaction; `learners_helped` only for the first completed session with that learner (`backend/sessions/complete.php:52-53`) | Profile and dashboard statistics |
| `user_skills.sessions_count` | completed sessions for that skill | Same completion transaction (`backend/sessions/complete.php:55`) | Mentor's per-skill experience |
| `user_skills.avg_rating` | `session_ratings` for that mentor and skill | Recomputed with a correlated subquery in the rating transaction (`backend/sessions/rate.php:65-67`) | Per-skill rating on teaching skills |
| `posts.vote_score` | `SUM(post_votes.value)` | Delta update while the post row is locked (`SELECT … FOR UPDATE`, `backend/posts/vote.php:22,39`) | Feed ordering and display |
| `posts.comment_count` | visible `comments` of the post | +1 on create (`backend/comments/create.php:52`); full recount by `uknRecountPostComments()` (`backend/helpers/community.php:21-30`) after deletes and moderation | Feed cards |
| `session_ratings.reviewer_id`, `mentor_id`, `skill_id` | the rated `mentoring_sessions` row | Copied from the session when the rating is inserted (`backend/sessions/rate.php:46-53`); a session's learner, mentor and skill are never updated after it is created | Lets ratings be filtered by mentor/skill (`idx_session_ratings_mentor`) and aggregated by the view without joining sessions |
| `mentoring_sessions.reference_code` | the session `id` (`UKN-S-` + 1000 + id) | Set once, right after the insert (`backend/sessions/request.php:76`) | Human-readable, unique reference |
| `skills.slug` | `skills.name` (plus a `-2`, `-3` … suffix on collision) | Admin skill create/update (`backend/helpers/admin-taxonomy.php`) | Stable, unique URL key |
| `posts.report_count`, `comments.report_count` | `reports` for that target (any status) | +1 in the report transaction (`backend/reports/create.php`); reviewing a report does not change it | Displayed in the admin moderation lists |

Honest notes on consistency:
- Every application writer updates the cached value **in the same transaction** as the source
  row, so a failure rolls back both.
- `users.learning_points` / `mentor_points` and `users.avg_rating` in the **seed data** were set by
  hand and do not match the ledger/ratings for the demo accounts.
  - For this reason the leaderboard, search and recommendations aggregate the source tables
    (`point_transactions`, `session_ratings`, via the view) instead of these columns.
  - This is also listed under [Known Limitations](#known-limitations).
- `report_count` counts every report filed on the item and is never decremented. Reports are only
  removed when their reporter account is deleted (ON DELETE CASCADE), which the application does
  not offer.

### SQL VIEW

| | |
|---|---|
| **Name** | `mentor_rating_summary` |
| **Columns** | `mentor_id`, `avg_rating` (`ROUND(AVG(overall), 1)`), `total_reviews` (`COUNT(*)`) |
| **Underlying table** | `session_ratings` (one row per mentor that has at least one rating) |
| **Definition** | `database/schema.sql` — created after all tables (and dropped first on re-import) |
| **Upgrade patch** | `database/patches/add-mentor-rating-summary-view.sql` (idempotent `CREATE OR REPLACE`) |
| **Security** | `SQL SECURITY INVOKER` — runs with the privileges of the querying account |

```sql
CREATE SQL SECURITY INVOKER VIEW mentor_rating_summary AS
SELECT sr.mentor_id,
       ROUND(AVG(sr.overall), 1) AS avg_rating,
       COUNT(*)                  AS total_reviews
FROM session_ratings sr
GROUP BY sr.mentor_id;
```

**Where the application uses it:**
- **Leaderboard → Top Mentors** (`index.php?page=leaderboard`, and the leaderboard widget in the
  right sidebar): `uknLeaderboardRows()` in `backend/helpers/leaderboard.php` joins
  `LEFT JOIN mentor_rating_summary r ON r.mentor_id = u.id` to show each mentor's rating.
- **Search → Mentors** (`index.php?page=search`): the mentor query in
  `pages/search/search-results.php` joins the same view, shows `r.avg_rating` and orders by it.

**Why a view:**
- Both queries previously repeated the same inline derived table
  `(SELECT mentor_id, ROUND(AVG(overall), 1) … GROUP BY mentor_id)`. The view defines that
  aggregation once.
- It is computed from the real `session_ratings` rows every time it is queried, so it can never
  go stale, unlike the cached `users.avg_rating`.
- The switch did not change any output. The leaderboard results (all types and periods) and the
  search results were compared before and after the change and were identical.

### Indexes and Index Rationale

**Primary, unique and foreign-key indexes.**
- Every primary key and `UNIQUE` key is backed by an index. These enforce the business rules,
  for example one vote per user per post, one rating per session, or unique email and
  university ID.
- InnoDB also needs an index on every foreign-key column. Many of the composite indexes below
  lead with an FK column so they serve both purposes.

The remaining indexes were chosen for specific queries.

The notes below describe the intent of each index. The "EXPLAIN" remarks are what the optimizer
actually chose on the small demo dataset. With only a handful of rows, the optimizer may prefer a
full table scan even when a suitable index exists; that is expected and says nothing about
behavior on larger data. No performance measurements were made, so none are claimed.

#### `ft_posts_search` — FULLTEXT (`posts.title`, `posts.content`)

- **Query:** `MATCH(title, content) AGAINST (? IN BOOLEAN MODE)` in the post branch of global
  search (`pages/search/search-results.php`, *Posts* results). The same expression is also used
  to rank the results by relevance.
- **Why FULLTEXT:** a `LIKE '%term%'` search must read every post's title and content, because a
  leading wildcard cannot use a B-tree index. A FULLTEXT index is an inverted word index: it
  finds the posts that contain the searched words directly, supports prefix terms (`pyth*`) in
  boolean mode, and returns a relevance score.
- **What it accelerates:** keyword search over post titles and bodies. Very short terms and
  stopwords are not indexed by FULLTEXT, so for those the code falls back to an escaped `LIKE`.
- **EXPLAIN:** `type = fulltext`, `key = ft_posts_search`.

#### `idx_posts_feed` (`status`, `created_at`)

- **Query:** the home feed —
  `WHERE p.status = 'visible' ORDER BY p.created_at DESC LIMIT 20` (`pages/home.php`).
- **Column order:** `status` is filtered by equality, so it comes first. Within one status the
  entries are already ordered by `created_at`, so the newest visible posts can be read in index
  order and the scan can stop after 20 rows, avoiding a sort of all posts.
- **Problem addressed:** without it, every feed load would have to scan and sort all posts,
  including hidden ones. The index is intended to reduce the amount of data the database must
  scan and sort as the number of posts grows.
- **EXPLAIN:** listed in `possible_keys`. With the 10 demo posts, the optimizer currently prefers
  a full scan plus sort.

#### `idx_points_user_date` (`user_id`, `created_at`)

- **Query:** the Points page history and period totals — `WHERE user_id = ? AND created_at >= ? AND
  created_at < ?` (`pages/points/points.php:65`) and
  `… AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')` (`pages/points/points.php:42`).
- **Column order:** equality on `user_id` first, then a range on `created_at`. A range column must
  come last so that the index can narrow to one member's rows and then to the date range inside
  them.
- **Problem addressed:** `point_transactions` is an append-only ledger and is the table most
  likely to grow fastest. The index is intended to stop per-member history and period queries
  from reading every member's entries.
- **EXPLAIN:** `key = idx_points_user_date`, `type = ref`, `Using index condition` (the date range
  is checked inside the index).

#### `idx_points_user_type` (`user_id`, `point_type`)

- **Query:** a member's totals per point type — `WHERE pt.user_id = ? AND pt.point_type = ?`
  (`pages/points/points.php:104`).
- **Column order:** both columns are equality filters. `user_id` is the most selective, so it
  leads.
- **EXPLAIN:** listed in `possible_keys`. On the demo data the optimizer picked
  `idx_points_user_date` (same leading column) instead.

#### `idx_notifications_inbox` (`user_id`, `is_read`, `created_at`)

- **Queries:**
  - the unread badge — `SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0`
    (`includes/notification-dropdown.php:43`, `index.php:201`);
  - the inbox lists — `WHERE user_id = ? ORDER BY created_at DESC`
    (`pages/notifications/notifications.php:18`, `includes/notification-dropdown.php:19`).
- **Column order:**
  - the badge counter runs on every page for logged-in members and filters on
    `user_id` + `is_read`, so those two lead and it can be answered from the index alone;
  - `created_at` comes last for ordering newest-first within a member's unread (or read)
    notifications.
- **EXPLAIN:**
  - unread count: `key = idx_notifications_inbox`, `ref = const,const`, `Using index` (index-only);
  - inbox list: the same index for the `user_id` lookup. The final sort is still done separately,
    because the list is not filtered by `is_read`.

#### `idx_sessions_mentor_status` (`mentor_id`, `status`) and `idx_sessions_learner_status` (`learner_id`, `status`)

- **Queries:** the Sessions pages —
  `WHERE ms.mentor_id = ? AND ms.status IN ('pending', 'accepted', 'rejected')` and
  `WHERE ms.mentor_id = ? AND ms.status IN ('accepted', 'completed', 'cancelled')`
  (`pages/sessions/sessions.php:43,111`), plus the learner-side equivalents.
- **Column order:** the participant (equality) first, then the lifecycle `status` list. This
  index also serves as the FK index for `mentor_id` / `learner_id`.
- **EXPLAIN:** `key = idx_sessions_mentor_status`, `Using index`.

#### Other indexes

| Index | Columns | Supports |
|---|---|---|
| `idx_posts_author` | `user_id`, `created_at` | "My Posts" (`WHERE p.user_id = ? … ORDER BY p.created_at DESC`) |
| `idx_posts_popular` | `status`, `vote_score` | Visible posts ordered by score |
| `idx_comments_post` | `post_id`, `created_at` | A post's comments in chronological order |
| `idx_session_ratings_mentor` | `mentor_id`, `created_at` | A mentor's ratings (and the `GROUP BY mentor_id` of the view) |
| `idx_user_skills_skill_type` | `skill_id`, `skill_type` | Mentors/learners of a skill (skill pages, recommendations) |
| `idx_learning_goals_user_status` | `user_id`, `status` | A member's active / completed goals |
| `idx_reports_queue` | `status`, `created_at` | Admin report queue (pending first, by date) |
| `idx_reports_target` | `target_type`, `target_id` | Finding the reports for one post/comment/user |
| `idx_login_attempts_window` | `window_started_at` | Purging expired rate-limit rows (`DELETE … WHERE window_started_at < …`) |

### Transactions

Every multi-statement write runs inside `beginTransaction()` … `commit()`. A `catch` block calls
`rollBack()`, so either all of its statements are saved or none are.

Example — **completing a mentoring session** (`backend/sessions/complete.php`):

1. `beginTransaction()` (line 19) and a locked read of the session.
2. Conditional `UPDATE mentoring_sessions SET status = 'completed' … WHERE id = ? AND status =
   'accepted'` (line 41). If a concurrent request already completed it, nothing is changed.
3. Insert the learner's +25 and the mentor's +30 ledger rows and update the cached point totals
   (`uknAwardPoints`, lines 47–49).
4. Update the session counters on `users` and `user_skills` (lines 52–55).
5. `commit()` (line 58); on any error `rollBack()` (line 61), leaving the session and all
   counters unchanged.

Other transactions:
- registration;
- profile + skills update;
- session request, accept, reject and cancel (with the late-cancel penalty);
- rating (the rating plus the cached averages);
- post create/edit (post + `post_skills`);
- comments and counters;
- votes (with `SELECT … FOR UPDATE` row locking);
- follows with notifications;
- admin moderation and report review.

The test suites proved the rollbacks by injecting failures with temporary database triggers.

### Joins and Subqueries

| Technique | Example (file:line) |
|---|---|
| INNER JOIN | Ledger ⋈ users in the leaderboard: `FROM point_transactions pt JOIN users u ON u.id = pt.user_id` (`backend/helpers/leaderboard.php:41-42`) |
| LEFT JOIN | Optional department and view rating: `LEFT JOIN departments d …`, `LEFT JOIN mentor_rating_summary r …` (`backend/helpers/leaderboard.php:65,68`) |
| Self-join | Comment → parent comment when recounting visible comments (`backend/helpers/community.php:26`) |
| Multi-table join | Home feed: posts ⋈ users ⟕ departments (`pages/home.php:19-21`) |
| Derived table (subquery in `FROM`) | Mentor points per user in search: `LEFT JOIN (SELECT user_id, SUM(amount) … GROUP BY user_id) lp` (`pages/search/search-results.php:140-141`) |
| Nested / scalar subquery | Leaderboard rank: `(SELECT COUNT(*) FROM (SELECT … GROUP BY … HAVING total > 0) t WHERE t.total > me.total …) + 1` (`backend/helpers/leaderboard.php:96-103`) |
| Correlated `EXISTS` | Mentors who teach a matching skill (`pages/search/search-results.php:144-145`) |
| Correlated subquery in `UPDATE` | Per-skill average rating (`backend/sessions/rate.php:65-67`) |
| `UNION ALL` | Merging post matches by text, author and tag (`pages/search/search-results.php:74-82`) |

### DBMS Requirement Matrix

| Requirement | Used in (feature) | Evidence |
|---|---|---|
| **SELECT** | Global search, home feed, every page | `pages/search/search-results.php:72-88`, `pages/home.php:15-25` |
| **INSERT** | Create post with skill tags | `backend/posts/create.php:17,20` |
| **UPDATE** | Complete a learning goal | `backend/goals/complete.php:15` |
| **DELETE** | Delete a learning goal (owner only) | `backend/goals/delete.php:14` |
| **Aggregate functions** | Leaderboard `SUM`/`COUNT`; the view's `AVG`/`COUNT` | `backend/helpers/leaderboard.php:60-61,71`; `database/schema.sql` (view) |
| **GROUP BY** | Leaderboard per member; view per mentor | `backend/helpers/leaderboard.php:73`; `database/schema.sql` (view) |
| **HAVING** | Leaderboard ranks only positive totals (`HAVING points > 0`) | `backend/helpers/leaderboard.php:74,98` |
| **INNER JOIN** | Leaderboard ledger ⋈ users; home feed posts ⋈ users | `backend/helpers/leaderboard.php:42`, `pages/home.php:20` |
| **LEFT JOIN** | Leaderboard/search mentor rating via the view; departments | `backend/helpers/leaderboard.php:65,68`, `pages/search/search-results.php:139` |
| **Subquery** | Leaderboard rank (nested), search `EXISTS` (correlated), rating `UPDATE` (correlated) | `backend/helpers/leaderboard.php:96-103`, `pages/search/search-results.php:144`, `backend/sessions/rate.php:66` |
| **VIEW** | `mentor_rating_summary`, used by Leaderboard (Top Mentors) and Search (Mentors) | `database/schema.sql`, `database/patches/add-mentor-rating-summary-view.sql`, `backend/helpers/leaderboard.php:65`, `pages/search/search-results.php:139` |
| **Transaction** | Session completion with points and counters | `backend/sessions/complete.php:19,58,61` |
| **Index** | FULLTEXT post search; composite feed, ledger, inbox and session indexes | `database/schema.sql:392,394,519,551,305` |

---

## Authentication Flow

```text
Register ──► verification email (token, 24 h) ──► Verify email ──► Log in ──► Session ──► Log out
```

1. **Register** — validated server-side; the account is created unverified. The account, its
   settings row and its private mobile number are written in one transaction.
2. **Verify** — the emailed link contains a single-use token; only its SHA-256 hash is stored.
   In development, emails are written to `storage/mail/` instead of being sent.
3. **Log in** — CSRF-protected; rate limited; generic error for unknown email or wrong
   password. Account state (unverified / not active) is only revealed after the correct password.
4. **Session** — a new session ID is issued at login; the cookie is `HttpOnly`,
   `SameSite=Lax` and `Secure` when served over HTTPS. The session holds the user ID, role
   information, the CSRF token, short-lived flash messages and a SHA-256 fingerprint of the
   current password hash (server-side only) — never the password or the password hash itself.
5. **Current user** — loaded from the database on each request; deleted, suspended, inactive or
   unverified users are signed out automatically, and so is any session whose password
   fingerprint no longer matches (the password was changed or reset since that login).
6. **Log out** — POST with CSRF token; the server-side session is destroyed.

```text
Forgot Password ──► reset email (token, 60 min) ──► Reset Password form ──► new password ──► Log in
```

- **Request** (`index.php?page=forgot-password`, POST `backend/auth/request-password-reset.php`)
  — CSRF-protected; the same message for every email. Only active, verified accounts get a link,
  at most one per account per 60 seconds and at most 10 requests per client IP per 15 minutes.
  A new request replaces the previous link. If the email cannot be sent the new token is withdrawn.
- **Reset** (`index.php?page=reset-password&token=…`, POST `backend/auth/reset-password.php`) —
  opening the link only shows the form (email scanners cannot use it up); posting it checks the
  token, its expiry and that the account is still active and verified, applies the registration
  password rules, stores a new `PASSWORD_DEFAULT` hash and clears the token in one atomic update.
  The user is not logged in automatically; all existing sessions of the account end.

---

## Roles

| Account | Stored as | Capabilities |
|---|---|---|
| **Learner** | `users.role = 'learner'` (every new registration) | Learner mode: learning skills, learning goals, mentor recommendations, session requests, ratings. Can apply to become a mentor. |
| **Learner & Mentor** | `users.role = 'dual'` (after an approved mentor application) | Everything a learner can do **plus** Mentor mode: teaching skills, availability, learner requests (accept/reject), completing sessions, ratings received. The user switches the *active mode* (Learner ↔ Mentor) from the profile menu; pages and actions follow the active mode. |
| **Admin** | `users.is_admin = 1` (separate flag) | Access to `admin/` (reachable from *Admin Panel* in the profile menu), in any mode. Independent of learner/mentor capability: an admin account is a Learner or a Learner & Mentor like any other. |

The active mode (`learner` / `mentor`) lives only in the server-side session and is re-checked
against `users.role` on every request; `admin` is never a mode, and admin access is checked
separately. Every login starts in Learner mode.

**Becoming a mentor** — a Learner opens *Apply to Become a Mentor* in the profile menu
(`index.php?page=mentor-application`) and explains why they want to mentor (1–1000 characters).
While the application waits, the menu shows *Mentor Application Pending* and no second
application can be sent. An admin approves or rejects it in **Admin → Mentor Applications**.
Approval turns the account into Learner & Mentor (nothing else about it changes) and the mode
switch appears on the user's next page; rejection leaves the account a Learner, and the learner
may apply again. Every application is kept as history.

Guests can browse the home feed, skills, public learner/mentor profiles, post details, the
mentor directory and search. Any logged-in member can additionally use their profile, sessions,
points, saved/own posts, notifications and the leaderboard, and can post,
comment, vote, save and follow. Mode switching is a server-side action that only allows modes
the account actually has.

---

## Mentoring Workflow

```text
pending ──accept (mentor)──► accepted ──complete (mentor, after start)──► completed ──► rated (learner, once)
   │                            │
   ├──reject (mentor)──► rejected
   ├──cancel (learner)─► cancelled
                                └──cancel (learner or mentor)──► cancelled
```

- `rejected`, `cancelled` and `completed` are final.
- Completing a session awards the learner +25 and the mentor +30 points (once, even if
  submitted concurrently).
- Cancelling an accepted session less than 6 hours before it starts costs the canceller 10 points.
- Only the participants of a session can view or act on it.

### Private contact details

- **Collected:** every new registration asks for a mobile number (*“Your mobile number is private
  and will only be shared with the mentor you request a session with.”*). Accepted forms are
  `01XXXXXXXXX`, `8801XXXXXXXXX` and `+8801XXXXXXXXX` (spaces/hyphens ignored); it is validated
  on the server and stored only as `+8801XXXXXXXXX`.
- **Stored:** in `user_private_contacts` (one row per user), not in `users`, so profile, search,
  leaderboard, mentor-list, community, notification and admin queries cannot return it. Only
  `backend/helpers/private-contacts.php` reads the table.
- **Owner:** views, adds and changes their number in **Settings → Account → Mobile Number**
  (POST + CSRF, always the signed-in user's own row). Accounts created before this feature have
  no number and must add one there before they can request a mentoring session; the request is
  refused with *“Add your mobile number in Settings before requesting a mentoring session.”*
- **Mentor:** sees the learner's number (*Learner Contact*, a `tel:` link) only on **Session
  Details** of a session where they are the mentor, and only while it is `pending` or `accepted`.
  The query checks the session id, `mentor_id` = the signed-in user and the status, and takes the
  learner from that session row, so changing ids in the URL or form reveals nothing. It is hidden
  once the session is `completed`, `rejected` or `cancelled`, and it never appears in session
  lists, requests, profiles or admin pages. Admin rights alone do not reveal it; the learner's own
  session view does not repeat it.
- **Not encrypted at rest:** mobile numbers are access-controlled at the application level but
  remain visible to database administrators (the project has no key-management system to hold an
  encryption key). Numbers are never written to the application's logs or messages.

---

## Community Workflow

- **Posts** — create with a title, content and optional skill tags (none, or up to 10); the
  author can edit or delete their own post. Tags are picked from the existing skills; a skill
  name still typed in the field (not yet confirmed with Enter or comma) is added when the form is
  submitted. Every tag must match an existing skill — unknown names are rejected and no new
  skills are created.
- **Sharing** — every post card, including Post Details, has a Share menu with *Copy link*,
  *WhatsApp*, *Facebook*, *LinkedIn* and *X*, plus *More options…* (the native share sheet) only
  where the browser supports it. Only the post title and its public Post Details link are shared;
  the link is built from `UKN_APP_URL`, so it works for others only once that is a public URL.
  Guests can open shared post links.
- **Comments & replies** — comment on a post or reply to a comment (one reply level); authors
  can edit/delete their own comments.
- **Reporting** — logged-in members can report a post (Post Details) or someone else's comment or
  reply: a reason from the `reports.reason` list plus optional details (up to 500 characters).
  The report is stored in `reports` (`target_type` + `target_id`, reporter = the session user,
  status `pending`) and the item's `report_count` goes up in the same transaction. Only visible
  content can be reported, never your own, and each member can report an item once. Reporting
  never hides or changes the content itself — that is an admin decision (see [Admin](#admin)).
- **Voting** — upvote / downvote a post; repeating the same vote removes it.
- **Saving** — save / unsave posts to a personal "Saved Posts" list.
- **Following** — follow / unfollow other members.
- **Notifications** — comment, reply and new-follower notifications, each respecting the
  recipient's stored notification preferences; opening one marks it read.

Hidden (moderated) posts and comments disappear from every community view; comment counts only
include comments the community can see.

---

## Admin

The admin panel lives in `admin/` and requires a logged-in user whose account has admin rights
in the database (`users.is_admin`). Admins reach it from **Admin Panel** in the profile menu (also in
the mobile navigation), whichever Learner/Mentor mode they are in.

| Area | What admins can do |
|---|---|
| **Departments** | Create, edit, activate/deactivate; delete only departments with no members |
| **Skill Categories** | Create, edit, activate/deactivate; delete only empty categories |
| **Skills** | Create, edit, activate/deactivate; delete only skills nothing refers to |
| **Users** | View users and details (Learner / Learner & Mentor, plus an Admin badge); suspend (with a required reason) and restore |
| **Mentor Applications** | Pending / Approved / Rejected / All; see the applicant's department, year, points, sessions, skills and message; approve (account becomes Learner & Mentor) or reject, with an optional note. An admin cannot review their own application, an applicant who is no longer active or verified cannot be approved, and an application can only be decided once — if two admins decide at the same moment, the second gets "already reviewed". |
| **Posts** | Review, hide and restore posts (nothing is deleted) |
| **Comments** | Review, hide and restore comments (replies under a hidden comment are hidden too) |
| **Reports** | Filter by type, status (Pending / Resolved / Dismissed), reason and date; resolve or dismiss a pending report; when resolving, optionally hide the reported post/comment or suspend the reported user in the same step. A report is decided once (`pending` → `resolved` or `dismissed`, recording the admin and time); a second decision — including one made at the same moment by another admin — gets "already reviewed". Dismissing never changes the content. |

Moderation never deletes content: hidden items are restorable, reports stay traceable even if
their target is later removed, and an admin cannot suspend their own account.

---

## Security

Implemented in the application (Phases I and J):

| Area | Protection |
|---|---|
| CSRF | Every state-changing action requires a per-session CSRF token; missing, empty, invalid or array tokens change nothing |
| HTTP methods | Action endpoints accept `POST` only (`GET` → 405) |
| Validation | Server-side validation of every field: types, lengths, enums, dates/times, IDs, array tampering, control characters and invalid UTF-8 |
| Authorization | Route guards for guest / logged-in / learner / mentor / admin; roles and admin rights are read from the database on every request |
| Ownership | Ownership is enforced in SQL; other users' resources behave as "not found"; posted identity fields (`user_id`, `role`, `is_admin`, …) are ignored |
| SQL | Prepared statements with bound parameters throughout |
| Integrity | Foreign keys, unique keys, `CHECK` constraints, strict SQL mode, transactions with rollback |
| XSS | Context-aware output escaping (HTML text, attributes, `data-*`, JSON embedded in HTML); JavaScript builds HTML only from escaped values |
| Sessions | Session ID regenerated at login, strict session mode, `HttpOnly` + `SameSite=Lax` cookies, full session destruction at logout |
| Login rate limiting | Attempts are counted per account + source, per account and per source in the database (hashed keys, works across sessions and parallel requests); throttling is temporary and gives the same response for existing and non-existing accounts |
| Error handling | Users only see generic error messages; with `UKN_ENV=production`, PHP errors are never displayed and uncaught errors show a generic 500 page while details go to the error log |
| Direct access | Repository metadata, database files, documentation and include-only PHP folders are not web-accessible; directory listing is disabled |
| Uploads | Profile photos only: JPG/PNG checked by content type, `getimagesize()`, minimum size and file completeness, max 2 MB, saved under a random server-generated name (the uploaded name is never used); scripts placed in `uploads/` can never execute |

---

## Security Headers

Set for every response by the root `.htaccess` (requires Apache `mod_headers`):

| Header | Value / purpose |
|---|---|
| `Content-Security-Policy` | Scripts only from the site and jsDelivr (no inline scripts or `eval`); styles from the site, jsDelivr and Google Fonts; no plugins; forms post only to the site; no framing |
| `X-Frame-Options` | `DENY` (clickjacking protection) |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | Camera, microphone, geolocation, payment and USB disabled |
| `X-Powered-By` | Removed |

**Not enabled:** `Strict-Transport-Security` (HSTS). The local setup runs over plain HTTP; HSTS
should only be added once the site is served over HTTPS (see
[Production Deployment Notes](#production-deployment-notes)).

---

## Installation

### Requirements
- **XAMPP** with Apache, MariaDB/MySQL and **PHP 8** (developed on PHP 8.2 / MariaDB 10.4), or an
  equivalent Apache + PHP 8 + MySQL/MariaDB stack.
- **Git**, to clone the repository.
- Apache with `.htaccess` overrides allowed (`AllowOverride All`) and **`mod_headers`** enabled —
  both are the XAMPP defaults for `htdocs`.
- Internet access in the browser (Bootstrap, Chart.js and fonts load from CDNs).

### Steps

The examples use the default XAMPP location `C:\xampp`; adjust the drive/folder if XAMPP is
installed elsewhere.

1. **Install XAMPP.**
2. **Install Git.**
3. **Start Apache and MySQL** from the XAMPP Control Panel.
4. **Clone the repository into XAMPP's `htdocs` folder.** The folder name becomes the URL path, so
   clone into `university-knowledge-network-frontend` to match the URLs used in this README:

   ```bash
   cd C:\xampp\htdocs
   git clone <repository-url> university-knowledge-network-frontend
   ```

   The project is then at `C:\xampp\htdocs\university-knowledge-network-frontend\`. The
   application works out its own base URL, so another folder name also works (the URLs change
   accordingly). No Apache `Alias` is needed when the project is inside `htdocs`; a custom `Alias`
   pointing to a folder elsewhere is optional and must allow `.htaccess` overrides
   (`AllowOverride All`).
5. **Create the database** (the code expects the name `ukn_database`), e.g. in phpMyAdmin
   (`http://localhost/phpmyadmin` → *SQL*):

   ```sql
   CREATE DATABASE ukn_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

6. **Import the schema, then the seed data**, in exactly this order (phpMyAdmin → select
   `ukn_database` → *Import*, or the `mysql` command-line client from the project folder):

   ```bash
   mysql -u <db-user> -p ukn_database < database/schema.sql
   mysql -u <db-user> -p ukn_database < database/seed.sql
   mysql -u <db-user> -p ukn_database < database/seed_demo_data.sql   # optional demo content
   ```

   If `mysql` is not on your `PATH`, use XAMPP's client, e.g. `C:\xampp\mysql\bin\mysql.exe`.
   `seed_demo_data.sql` fills the feature tables with demo sessions, posts, points and so on.
   It deletes the rows it seeds first, so never run it against a database with real user data.

7. **Configure the database connection** in `backend/config/database.php` (host, database name,
   user and password — see [Configuration](#configuration)).
8. **Configure mail** (optional). Nothing is needed for local development: verification and
   password-reset emails are written to `storage/mail/`. To send real emails, see
   [Gmail SMTP setup](#gmail-smtp-setup-optional).
9. **Open the application:** `http://localhost/university-knowledge-network-frontend/`
10. **Create your first account:** register, then open the verification link from the newest file
    in `storage/mail/` (development mail log).
    The seeded demo users cannot log in — they are unverified and have a placeholder password
    hash; they exist to populate the demo data.
11. **Grant admin rights** (optional) to your verified account directly in the database:

    ```sql
    UPDATE users SET is_admin = 1 WHERE email = '<your-email>';
    ```

---

## Configuration

**Database connection** — set in `backend/config/database.php`:

```text
host     = <database host, e.g. localhost>
dbname   = ukn_database
username = <database user>
password = <database password>
```

The repository ships with XAMPP's local defaults (`localhost`, `root`, empty password). Change
them to match your machine if needed. Use a dedicated database user with a strong password
outside local development.

**Environment variables** (read with `getenv`; with Apache use `SetEnv`) — all optional locally:

| Variable | Purpose | Default |
|---|---|---|
| `UKN_APP_URL` | Absolute base URL used in emailed links (e.g. `https://ukn.example.edu`). **Required in production.** | derived from the request |
| `UKN_MAIL_TRANSPORT` | `log` writes emails to `storage/mail/`; `mail` sends them with PHP `mail()`; `smtp` sends them through an authenticated SMTP server (e.g. Gmail) with the bundled PHPMailer | `log` |
| `UKN_MAIL_FROM` | Sender address for outgoing email (for Gmail: the Gmail address itself) | `no-reply@localhost` |
| `UKN_MAIL_FROM_NAME` | Sender display name | `University Knowledge Network` |
| `UKN_MAIL_HOST` | SMTP server (`smtp` transport only), e.g. `smtp.gmail.com` | — |
| `UKN_MAIL_PORT` | SMTP port; STARTTLS with certificate verification is always used | `587` |
| `UKN_MAIL_USERNAME` | SMTP login (for Gmail: the Gmail address) | — |
| `UKN_MAIL_PASSWORD` | SMTP password (for Gmail: an **App Password**, never the account password). Set it only in the server configuration, never in the repository | — |
| `UKN_ENV` | `production` hides PHP errors and shows a generic error page | development behaviour |

**Development mail log:** with the default `UKN_MAIL_TRANSPORT=log`, every email (registration
verification, resend, password reset) is written as a `.eml` file to `storage/mail/` instead of
being sent; the newest file contains the latest link. The folder is not web-accessible, and its
`.gitignore` keeps generated mail files out of Git.

> **Do not commit SMTP credentials, passwords, API keys or other secrets to GitHub.** Mail
> settings belong in the Apache configuration on each machine (`SetEnv`), never in project files
> such as `backend/config/app.php` or the project's `.htaccess`, which are part of the repository.

### Gmail SMTP setup (optional)

`UKN_MAIL_TRANSPORT=log` remains the default development/test transport (emails go to
`storage/mail/`). To deliver emails to real inboxes:

1. Turn on **2-Step Verification** for the Gmail account (Google Account → Security).
2. Create an **App Password** (Google Account → Security → 2-Step Verification → App passwords).
   Never use the normal Gmail password.
3. Put the App Password only into Apache `SetEnv` — never into the repository.
4. Add a `<Directory>` block for the project to XAMPP's `apache\conf\httpd.conf` (outside the
   repository), or add the `SetEnv` lines to the project's existing block if you use an `Alias`.
   Placeholders are shown; use the project's real location and URL:

   ```apache
   <Directory "C:/xampp/htdocs/university-knowledge-network-frontend">
       SetEnv UKN_MAIL_TRANSPORT smtp
       SetEnv UKN_MAIL_HOST smtp.gmail.com
       SetEnv UKN_MAIL_PORT 587
       SetEnv UKN_MAIL_USERNAME your-email@example.com
       SetEnv UKN_MAIL_PASSWORD your-app-password
       SetEnv UKN_MAIL_FROM your-email@example.com
       SetEnv UKN_MAIL_FROM_NAME "University Knowledge Network"
       SetEnv UKN_APP_URL http://localhost/university-knowledge-network-frontend
   </Directory>
   ```

   `UKN_MAIL_FROM` should match the authenticated Gmail account (Gmail rewrites other senders).
   `UKN_APP_URL` must be the URL the application is opened at; emailed links are built from it.
5. Restart Apache.
6. Register with a real email address.
7. Check the inbox (and the spam folder).
8. Click the verification link.
9. Log in.

If sending fails, the user sees a generic message, the account stays unverified and "Resend
verification email" can be used; the reason is written to the PHP error log without the
password, recipient or verification link. PHPMailer 6.12.0 is bundled in `backend/helpers/PHPMailer/`
(LGPL-2.1, see its `LICENSE`); `backend/helpers/` is not web-accessible.

---

## Running the Project

- Application: `http://localhost/university-knowledge-network-frontend/` (entry point
  `index.php`; pages are addressed as `index.php?page=<route>`, e.g. `index.php?page=login`). With
  a different folder name, replace `university-knowledge-network-frontend` accordingly.
- Admin panel: `http://localhost/university-knowledge-network-frontend/admin/dashboard.php`
  (admin accounts only; there is no admin link in the main navigation).
- Development emails (verification and password-reset links): newest file in `storage/mail/`
  (`log` transport; with `smtp` they arrive in the recipient's inbox instead).
- Database connectivity check (command line only), run from the project folder:
  `php backend/test-db.php`. If `php` is not on your `PATH`, use XAMPP's bundled PHP instead:

  ```bat
  C:\xampp\php\php.exe backend\test-db.php
  ```

  The web application itself does not need PHP on the `PATH`.

---

## Git Workflow

First-time setup (see [Installation](#installation) for where to clone):

```bash
git clone <repository-url> university-knowledge-network-frontend
cd university-knowledge-network-frontend
```

Get the latest changes later:

```bash
git pull
```

Contributing a change:

```bash
git status
git add .
git commit -m "Describe the change"
git push
```

Check `git status` before committing. **Never commit** local credentials, SMTP or database
passwords, App Passwords, API keys, generated mail logs (`storage/mail/*.eml`), database dumps
with real data or other secrets. Mail settings stay in the Apache configuration outside the
repository (`SetEnv`). `backend/config/database.php` is tracked with XAMPP's default local
credentials; if you change it for your machine, do not commit a real password.

---

## Testing

The project was verified phase by phase with automated end-to-end test suites (Bash + `curl` +
the MySQL client, driving the running application). The Phase J suite also used headless Chrome
to confirm that the Content-Security-Policy does not break any page. The suites create their own
test accounts and data, fault-inject database failures with temporary triggers to prove
rollbacks, and restore the database to its seed state afterwards.

> **Note:** these test scripts were run in the development environment and are **not included
> in this repository**. The results below are the verified results reported for each phase.

| Suite | Scope | Result |
|---|---|---|
| Phase A | Registration, email verification, login, logout, current user | 81 / 81 |
| Phase B | Roles, role switching, route guards | 56 / 56 |
| Phase C | Profile, learning/teaching skills, goals, availability | 83 / 83 |
| Step 28 | Mentor recommendations | 40 / 40 |
| Phase E | Session lifecycle, ratings, points | 69 / 69 |
| Phase F | Posts, comments, votes, saves, follows, notifications | 74 / 74 |
| Phase G | Search, leaderboard (and the skill network of Step 44, since removed) | 75 / 75 |
| Phase H | Admin CRUD, user management, moderation, reports | 190 / 190 |
| Phase I | CSRF, validation, authorization/IDOR, integrity/concurrency, XSS | 53 / 53 |
| Phase J | Rate limiting, sessions, headers/CSP, disclosure, enumeration | 47 / 47 |

Static checks at the end of Phase J: **PHP lint 155/155**, **JavaScript syntax 39/39**,
**CSS 32/32** files with balanced braces.

Note on Phase F: the 74 / 74 result was recorded when related skills were mandatory. Related
skills are now optional by design, so one older Phase F assertion (a post without skills is
rejected) no longer applies to the current behavior.

---

## Project Completion Status

| Phase | Steps | Area | Status |
|---|---:|---|---|
| A | 15–19 | Authentication | Complete |
| B | 20–22 | Authorization & roles | Complete |
| C | 23–27 | Profile, skills, goals, availability | Complete |
| D | 28 | Mentor recommendation | Complete |
| E | 29–33 | Mentoring sessions, ratings, points | Complete |
| F | 34–41 | Community & notifications | Complete |
| G | 42–44 | Search, leaderboard (the Step 44 skill network was later removed) | Complete |
| H | 45–51 | Administration | Complete |
| I | 52–56 | Security & data integrity | Complete |
| J | Additional hardening | Production hardening & final security audit | Complete |
| K | DBMS course requirements | SQL VIEW (used by Leaderboard and Search), ER diagram, relational schema, 3NF and index documentation | Complete |

Phase K deliverables: [SQL VIEW](#sql-view) (defined in [`database/schema.sql`](database/schema.sql)
and [`database/patches/add-mentor-rating-summary-view.sql`](database/patches/add-mentor-rating-summary-view.sql)),
ER diagram ([`docs/ER-Diagram.png`](docs/ER-Diagram.png), source
[`docs/ER-Diagram.mmd`](docs/ER-Diagram.mmd)), [Relational Schema](#relational-schema),
[Normalization / 3NF Justification](#normalization--3nf-justification) and
[Indexes and Index Rationale](#indexes-and-index-rationale).

**The original defined roadmap ends at Step 56. Phase J is additional production
hardening/security audit; Phase K completes the DBMS course deliverables (one SQL view and the
database design documentation) without adding application features. No Step 57+ feature has
been defined or implemented.**

Phase J added, on top of Phase I: login rate limiting, protection of repository/database/source
files and internal PHP folders, security headers, production error handling, upload execution
protection, and audits of information disclosure, authentication/session security and database
security.

---

## Known Limitations

- **Settings page** — apart from the mobile number, displays account, appearance, notification
  and privacy options but does not save them (no backend yet). The change-password dialog is likewise not connected to a backend.
- **Profile photos** — shown only where the signed-in user's or a profile page's avatar is
  displayed; cards, comments, lists and search still use initials. Images are validated but not
  re-encoded (the PHP GD extension is not enabled).
- **Reports** — members can report posts and comments, not users (user reports exist only in the
  demo data). A reviewed report cannot be reopened, and the reporter is not notified of the
  decision.
- **Notifications** — generated for community activity (comments, replies, new followers) only,
  not for session events or mentor-application decisions (the applicant sees the result in the
  profile menu and on the application page).
- **Community points** — the leaderboard's Community Contributors view shows community points
  from the ledger, but no application action currently awards community points.
- **Admin Sessions page** and **admin Settings page** — read-only/demonstration screens; the
  session "Cancel" action and the settings toggles there do not persist changes.
- **Denormalised profile statistics** — some profile/dashboard figures (e.g. users' point totals
  and average rating columns) come from seeded counter columns, while leaderboards and
  recommendations use the ledger and ratings tables; for the demo seed accounts these can differ.
- **Registration** tells a visitor when an email or university ID is already registered.
- **Mobile numbers** — Bangladesh mobile numbers only; stored unencrypted (visible to database
  administrators, see [Private contact details](#private-contact-details)).
- **Content-Security-Policy** still allows inline *styles* (`'unsafe-inline'` in `style-src`),
  because some templates use `style=""` attributes; CDN resources have no Subresource Integrity
  attributes.
- The access rules and headers rely on Apache `.htaccess`; other web servers need equivalent rules.

---

## Production Deployment Notes

The repository is configured for **local development**. Before a public deployment:

| Item | Status / action |
|---|---|
| HTTPS | Not configured. Serve the site over HTTPS (the session cookie then becomes `Secure` automatically). |
| HSTS | Not enabled. Add `Strict-Transport-Security` only after HTTPS works everywhere. |
| `UKN_ENV=production` | Set with `SetEnv` so PHP errors are hidden and logged. |
| `UKN_APP_URL` | Set to the public HTTPS URL used in emails. |
| Mail | Set `UKN_MAIL_TRANSPORT=smtp` (with the `UKN_MAIL_*` SMTP settings) or `mail` (PHP mail/SMTP in php.ini); keep `storage/` and `backend/helpers/` non-public. |
| Database account | Use a dedicated, least-privilege user with a strong password (not a root account). |
| Apache | `ServerTokens Prod`, `ServerSignature Off` (server config, outside the project). |
| PHP | `expose_php = Off`, `display_errors = Off`, `log_errors = On` in `php.ini`. |
| Reverse proxy | Login rate limiting uses the connecting IP; behind a proxy/load balancer, trusted-proxy handling must be configured first. |
| Session lifetime | Idle expiry follows PHP's session settings; no absolute session timeout is defined — decide on a policy. |
| Rate-limit thresholds | 5 attempts per account + source, 20 per account and 50 failures per source, per 15 minutes — review for your environment. |
| `component-preview.dev.php` | Development-only preview with static mock data; consider removing or blocking it in production. |

---

## Project Scope

**Complete:** all features of Phases A–I (Steps 15–56) listed above, plus the Phase J production
hardening and security audit and the Phase K database design deliverables.

**Outside the current defined scope:** everything listed under
[Known Limitations](#known-limitations) and any feature beyond Step 56. No further phase is
currently defined.

---

## Credits

Author, course and institution details are not recorded in this repository; add them here as
appropriate. Design references and the original UI hand-off are kept in `docs/`.

**Group contribution:** group member names and each member's individual contribution are not
recorded in this repository (the Git history does not document them either). The group must
provide this information separately, for example in the course submission or by adding it to
this section.
