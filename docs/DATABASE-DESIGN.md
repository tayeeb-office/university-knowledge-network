# UKN Database Design

[← Back to README](../README.md)

This section documents the database as it is defined in `database/schema.sql` — 24 base tables,
42 foreign keys, 27 `CHECK` constraints and one view — and shows where the application uses each
SQL feature.

## ER Diagram

![UKN Entity-Relationship diagram](ER-Diagram.png)

- **Image:** [`ER-Diagram.png`](ER-Diagram.png) (large landscape image; open it at full
  size to read the attributes).
- **Editable source:** [`ER-Diagram.mmd`](ER-Diagram.mmd) (Mermaid `erDiagram`; it can
  be edited and re-rendered with any Mermaid renderer, e.g. the Mermaid Live Editor or
  Mermaid CLI).
- **PNG not yet re-rendered:** the Mermaid source is current: it includes the
  `mentor_applications` table (with its two foreign keys to `users`) and no longer has the removed
  email-verification and password-reset columns on `users`. `ER-Diagram.png` was rendered before
  these changes, so it does not match yet; re-render
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

## Relational Schema

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
- Kept out of `users` so that no general user query can return it; see [Private contact details](../README.md#private-contact-details).

</details>

<details>
<summary><b>24. <code>learner_recommendations</code></b> — 5 columns</summary>

| Column | Type | NOT NULL | Key |
|---|---|:---:|---|
| `id` | `int(10) unsigned` | ✓ | PK |
| `recommender_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `learner_id` | `int(10) unsigned` | ✓ | FK → `users.id` |
| `content` | `varchar(1000)` | ✓ |  |
| `created_at` | `datetime` | ✓ |  |

- **PK:** (id)
- **FK** `recommender_id` → `users(id)` ON DELETE CASCADE; `learner_id` → `users(id)` ON DELETE CASCADE
- No uniqueness on (recommender_id, learner_id): a member may recommend the same learner several times, each as its own row
- **CHECK** `chk_learner_recommendations_not_self`: `recommender_id <> learner_id`; `chk_learner_recommendations_content`: content not blank
- **Indexes:** `idx_learner_recommendations_learner` (learner_id, created_at) — the newest-first list; `idx_learner_recommendations_recommender` (recommender_id, learner_id) — backs the recommender foreign key
- A member's written recommendation for a learner, shown on the learner's profile (Recommendations tab). Not related to mentor recommendations/matching, which store nothing.

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
| users → learner_recommendations (recommender; learner) | 1 : N each | `learner_recommendations.recommender_id`, `learner_id` (several per pair allowed) |
| posts → comments; comments → replies | 1 : N; self 1 : N | `comments.post_id`; `comments.parent_id` |
| sessions / posts / goals → point_transactions | 1 : N (optional) | `related_session_id`, `related_post_id`, `related_goal_id` (nullable) |
| users → reports (reporter, reviewer) | 1 : N | `reports.reporter_id`, `reports.reviewed_by` |
| posts / comments / users → reports (target) | logical, no FK | `reports.target_type` + `target_id` |

## Normalization / 3NF Justification

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
  - This is also listed under [Known Limitations](../README.md#known-limitations).
- `report_count` counts every report filed on the item and is never decremented. Reports are only
  removed when their reporter account is deleted (ON DELETE CASCADE), which the application does
  not offer.

## SQL VIEW

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

## Indexes and Index Rationale

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

### `ft_posts_search` — FULLTEXT (`posts.title`, `posts.content`)

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

### `idx_posts_feed` (`status`, `created_at`)

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

### `idx_points_user_date` (`user_id`, `created_at`)

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

### `idx_points_user_type` (`user_id`, `point_type`)

- **Query:** a member's totals per point type — `WHERE pt.user_id = ? AND pt.point_type = ?`
  (`pages/points/points.php:104`).
- **Column order:** both columns are equality filters. `user_id` is the most selective, so it
  leads.
- **EXPLAIN:** listed in `possible_keys`. On the demo data the optimizer picked
  `idx_points_user_date` (same leading column) instead.

### `idx_notifications_inbox` (`user_id`, `is_read`, `created_at`)

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

### `idx_sessions_mentor_status` (`mentor_id`, `status`) and `idx_sessions_learner_status` (`learner_id`, `status`)

- **Queries:** the Sessions pages —
  `WHERE ms.mentor_id = ? AND ms.status IN ('pending', 'accepted', 'rejected')` and
  `WHERE ms.mentor_id = ? AND ms.status IN ('accepted', 'completed', 'cancelled')`
  (`pages/sessions/sessions.php:43,111`), plus the learner-side equivalents.
- **Column order:** the participant (equality) first, then the lifecycle `status` list. This
  index also serves as the FK index for `mentor_id` / `learner_id`.
- **EXPLAIN:** `key = idx_sessions_mentor_status`, `Using index`.

### Other indexes

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

## Transactions

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

## Joins and Subqueries

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

## DBMS Requirement Matrix

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
