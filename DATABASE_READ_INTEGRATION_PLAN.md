# Database Read-Integration Plan (Phase 0 Audit)

Scope: replace hardcoded/mock PHP data with **read-only** PDO queries against the existing
`database/schema.sql` schema, for the public `pages/` app and, where safe, the `admin/` mock
tables. No auth, sessions, role switching, or write/CRUD operations are covered here — those
belong to other team members' tasks. This document is the Phase 0 deliverable: **no project
files were modified to produce it.**

## 0. How this document was built

Every `pages/*.php` file, every reusable `components/*.php` render function, `includes/right-sidebar.php`,
and every `admin/*.php` file (except `admin/settings.php`, which has no DB-backed feature) was read in
full. Component function signatures (the exact array keys each `ukn_xxx()` helper expects) were read
directly from `components/*.php` so the SQL aliases below can target them precisely.

**Correction note:** during research, a few sub-passes were briefed with a *partial* table list and
consequently flagged some fields as "no DB mapping" that the real schema already supports. These are
corrected below (search for **"CORRECTED"**). The tables that resolve most of them: `user_skills`
(learner/mentor↔skill link with `skill_type` ENUM('learning','teaching')), `session_ratings`,
`learning_goals`, `mentor_availability`, `post_skills`, and `mentoring_sessions` — all of which exist
in `database/schema.sql` and were simply omitted from a couple of the narrower research briefs.

## 1. Global conventions to apply everywhere

- **Current-user placeholder:** Per the task's restriction, no session/auth work is done here. Every
  query that needs "the logged-in user" (my posts, my sessions, my points, my goals, follow/save/vote
  state, etc.) will use a **single named constant**, e.g. `const UKN_DEMO_USER_ID = 1;` defined once
  (recommend in a small new read-only helper, or inline near the top of each page, clearly commented
  `// TODO(auth): replace with real session user id`). This keeps every page compatible with the future
  auth team's work — they only need to replace one constant/lookup per page, not re-derive queries.
- **Role casing:** `users.role` enum is `learner|mentor|dual` (lowercase). Mock data displays
  `'Learner'|'Mentor'` (capitalized). Map with `ucfirst()` in PHP after fetch; `dual` has no defined
  mock rendering today — recommend rendering the page's *active* context role (e.g. "Mentor" on a
  mentor-dashboard) rather than inventing a "Dual" chip, to match existing card component behavior.
- **Relative time ("12 min ago")** and **excerpt truncation** are always PHP-side post-processing of
  `created_at` / `content` — never stored columns. A single small helper (e.g. `ukn_time_ago()`,
  `ukn_excerpt()`) should be added once and reused, not duplicated per page.
- **Empty states:** every list query must check for zero rows and render the page's existing
  `ukn_empty_state([...])` call (already present in most pages for the mock "no results" case) instead
  of an empty loop.
- **Error handling:** wrap PDO calls in `try { ... } catch (Throwable $e) { error_log(...); ukn_error_state([...]); }`
  per page/section — never echo `$e->getMessage()` to the page. `backend/config/database.php`'s
  `getDatabaseConnection()` already throws a generic `RuntimeException('Database connection failed.')`
  on connect failure (message is safe to surface as-is); per-query exceptions should be caught locally
  around each fetch so one failed panel doesn't blank the whole page.
- **Security:** every query below is written as a parameterized PDO prepared statement (`?` or named
  placeholders). `$_GET['id']`, `$_GET['q']`, `$_GET['from']` are always bound as parameters, never
  concatenated. This matches the existing convention in `backend/models/User.php`.

---

## 2. Per-page mapping

Legend for the **Status** column: 🟢 ready to convert now (no current-user dependency, or only a
trivial placeholder-id dependency) · 🟡 convert with a documented demo-user-id placeholder · 🔴 do not
convert yet (see §4).

### 2.1 Home / Community Feed

| Page | Mock source | Tables | Component reused | Status |
|---|---|---|---|---|
| `pages/home.php` | `$communityPosts` (8 hardcoded posts) | `posts` JOIN `users` JOIN `departments`; `post_skills` JOIN `skills`; `posts.vote_score`/`comment_count` (denormalized, trust them) | `ukn_post_card()` | 🟡 (feed itself 🟢; `following`/`voteState`/`isOwner` per-row flags 🔴) |
| `pages/community/post-details.php` | `$posts[1]`, `$comments` (nested replies) | `posts`, `comments` (self-join via `parent_id`), `users`, `departments`, `post_skills`+`skills` | `ukn_post_card()` (detail variant) + plain PHP `foreach` for comments (confirmed: comments are PHP-rendered on load, not JS-built) | 🟡 (`saved`/`isOwner` 🔴) |
| `pages/community/my-posts.php` | `$myPosts` (5 posts, all "mine") | `posts` WHERE `user_id = :demoUserId`, joined as above | `ukn_post_card()` | 🟡 (whose posts = current-user filter) |
| `pages/community/saved-posts.php` | `$savedPosts` (4 posts) | `saved_posts` JOIN `posts` JOIN `users`/`departments` WHERE `saved_posts.user_id = :demoUserId` | `ukn_post_card()` | 🟡 |

**Core feed query:**
```sql
SELECT p.id, p.title, p.content, p.vote_score AS score, p.comment_count AS comments,
       p.created_at, u.id AS author_id, u.full_name AS author, u.initials, u.role,
       d.name AS department
FROM posts p
JOIN users u ON u.id = p.user_id
LEFT JOIN departments d ON d.id = u.department_id
WHERE p.status = 'visible'
ORDER BY p.created_at DESC
LIMIT ?, ?;
```
Tags (per post, or batched with `GROUP_CONCAT`/a second IN-query):
```sql
SELECT ps.post_id, s.name
FROM post_skills ps JOIN skills s ON s.id = ps.skill_id
WHERE ps.post_id IN (...);
```
Comments (post-details.php), split top-level/replies in PHP by `parent_id`:
```sql
SELECT c.id, c.parent_id, c.content, c.created_at, u.id AS user_id, u.full_name AS author,
       u.initials, u.role
FROM comments c JOIN users u ON u.id = c.user_id
WHERE c.post_id = ? AND c.status = 'visible'
ORDER BY c.created_at ASC;
```
**Per-viewer fields NOT wired (🔴, document only):** `voteState` (needs `post_votes` + current user),
`saved` (`saved_posts` + current user), `following` (`follows` + current user), `isOwner`
(`posts.user_id === current user`). These keys should simply be **omitted from the array passed to
`ukn_post_card()`** — the component already defaults them (`voteState=0`, `saved=false`,
`following=false`, `isOwner=true`) via its `$post += [...]` merge, so leaving them out is safe and
requires no component change.

**Gap (real):** `role` casing (see §1). No other real gaps here — `post_skills`, `comments.parent_id`,
and `posts.vote_score`/`comment_count` fully cover the mock shape.

---

### 2.2 Profiles

| Page | Mock source | Tables | Component | Status |
|---|---|---|---|---|
| `pages/profile/my-profile.php` | `$department`,`$yearOfStudy`,`$shortBio`,`$pointsStatByRole`,`$profileStats`,`$skills`,`$goals`,`$activity`,`$myPost` | `users`,`departments`,`user_skills`+`skills`,`learning_goals`,`posts` | `ukn_stat_card()`,`ukn_goal_card()`,`ukn_post_card()` | 🟡 (whole page is "my profile") |
| `pages/profile/edit-profile.php` | prefill only, no submit implemented here | `users`,`departments`,`user_skills`+`skills` | plain form fields | 🟡 (prefill only — **do not wire the submit/action**) |
| `pages/profile/learner-profile.php` | `$learners[$_GET['id']]` | `users` WHERE `role IN ('learner','dual')`,`departments`,`user_skills`+`skills`,`learning_goals`,`posts` | `ukn_goal_card()`,`ukn_post_card()` | 🟢 (viewing someone else's profile by id needs no current-user; only the "Follow" button state is 🔴) |
| `pages/profile/mentor-profile.php` | `$mentors[$_GET['id']]` | `users` WHERE `role IN ('mentor','dual')`,`departments`,`user_skills`(`skill_type='teaching'`),`mentor_availability`,`session_ratings`+`users`(reviewer)+`skills` | `ukn_rating_item()` | 🟢 (public mentor profile; only "Request Session" button's pre-fill is static) |
| `pages/skills/skills.php` | `$categories`,`$skills` | `skill_categories`,`skills`,`user_skills` (COUNT per skill/type) | `ukn_skill_card()` (directory variant) | 🟢 (feed itself; `learningState`/`teachingState` toggle 🔴) |
| `pages/skills/learning-skills.php` | `$summaryStats`,`$learningSkills` | `user_skills`(`skill_type='learning'`)+`skills`,`users.sessions_as_learner`,`learning_goals` | `ukn_skill_card()` (learning variant) | 🟡 |
| `pages/skills/teaching-skills.php` | `$summaryStats`,`$teachingSkills` | `user_skills`(`skill_type='teaching'`)+`skills`,`users.sessions_as_mentor`,`users.avg_rating`,`users.mentor_points` | `ukn_skill_card()` (teaching variant) | 🟡 |
| `pages/skills/skill-details.php` | `$skills[$_GET['id']]`,`$categoryTopics`,`$mentorPool` | `skills`+`skill_categories`,`user_skills`(counts),`mentoring_sessions`(completed count),`posts`+`post_skills`,`skill_relations`+`skills`(target),`user_skills`(`skill_type='teaching'`)+`users` ranked by `avg_rating` | `ukn_mentor_card()`,`ukn_post_card()` | 🟢 (public page; only per-viewer "add to learning/teaching" toggle 🔴) |

**Representative queries:**
```sql
-- Learner/mentor profile by id
SELECT u.id, u.full_name, u.initials, u.year_of_study, u.bio, u.headline, u.avatar_path,
       u.avg_rating, u.total_reviews, u.learning_points, u.mentor_points,
       u.sessions_as_learner, u.sessions_as_mentor, u.learners_helped,
       d.name AS department
FROM users u LEFT JOIN departments d ON d.id = u.department_id
WHERE u.id = ? AND u.status = 'active';

-- Skills for a user (learning or teaching)
SELECT s.id, s.name, us.proficiency, us.progress, us.sessions_count, us.avg_rating,
       us.primary_mentor_id
FROM user_skills us JOIN skills s ON s.id = us.skill_id
WHERE us.user_id = ? AND us.skill_type = ?;

-- Skill directory with counts
SELECT s.id, s.name, s.description, sc.name AS category,
       (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'teaching') AS mentors,
       (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'learning') AS learners
FROM skills s JOIN skill_categories sc ON sc.id = s.category_id
WHERE s.status = 'active'
ORDER BY s.name;

-- Mentor's teaching skills ranked (skill-details.php "top mentors for this skill")
SELECT u.id, u.full_name, u.initials, u.avg_rating, u.mentor_points, u.sessions_as_mentor,
       d.name AS department
FROM user_skills us JOIN users u ON u.id = us.user_id
LEFT JOIN departments d ON d.id = u.department_id
WHERE us.skill_id = ? AND us.skill_type = 'teaching'
ORDER BY u.avg_rating DESC LIMIT 6;

-- Mentor reviews (mentor-profile.php)
SELECT sr.overall, sr.teaching, sr.communication, sr.helpfulness, sr.review, sr.created_at,
       ur.full_name AS reviewer, ur.initials, sk.name AS skill
FROM session_ratings sr
JOIN users ur ON ur.id = sr.reviewer_id
LEFT JOIN skills sk ON sk.id = sr.skill_id
WHERE sr.mentor_id = ?
ORDER BY sr.created_at DESC LIMIT 10;
```

**Real gaps:**
- `my-profile.php`'s `$activity` (recent-activity feed) — **no activity/audit-log table exists.**
  Recommend either omitting the "Recent Activity" panel for now, or synthesizing a small ad-hoc feed
  from 2–3 existing tables (latest `session_ratings` received, latest completed `mentoring_sessions`,
  latest `posts`) unioned and sorted by timestamp in PHP — document this as a simplification, not a
  full activity log.
- Points "trend" strings ("+64 this month") are computable via `SUM(point_transactions.amount) WHERE
  created_at >= start_of_month` — **not a gap**, just needs the aggregation query, corrected from an
  earlier note.
- `mentor.availabilityStatus` ("Available This Week") has no stored column — derive it from
  `mentor_availability` (`EXISTS` an enabled slot in the current week); document as a computed/derived
  value, not a stored fact.

---

### 2.3 Learning Goals & Mentor Availability

| Page | Mock source | Tables | Component | Status |
|---|---|---|---|---|
| `pages/learning/learning-goals.php` | `$activeGoals`,`$completedGoals`,`$skillOptions` | `learning_goals`+`skills` | `ukn_goal_card()` | 🟡 |
| `pages/learning/availability.php` | `$week`,`$upcomingSessions`,`$bookedHours`,`$pendingRequestCount` | `mentor_availability`; `mentoring_sessions`+`users`+`skills` for the upcoming list | `ukn_session_card()` | 🟡 |

```sql
-- Goals
SELECT lg.id, lg.title, s.name AS skill, lg.progress, lg.target_date, lg.status
FROM learning_goals lg LEFT JOIN skills s ON s.id = lg.skill_id
WHERE lg.user_id = ? AND lg.status = ?
ORDER BY lg.target_date ASC;

-- Mentor's weekly availability
SELECT day_of_week, start_time, end_time
FROM mentor_availability
WHERE user_id = ? AND is_enabled = 1
ORDER BY day_of_week, start_time;
```
**CORRECTED:** `$upcomingSessions` / `$bookedHours` / `$pendingRequestCount` on `availability.php` DO
map cleanly to `mentoring_sessions` (an earlier research pass was briefed without this table and
wrongly flagged it as a schema gap):
```sql
-- Upcoming sessions for a mentor
SELECT ms.id, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes, ms.status,
       l.full_name AS learner, l.initials, sk.name AS skill
FROM mentoring_sessions ms
JOIN users l ON l.id = ms.learner_id
JOIN skills sk ON sk.id = ms.skill_id
WHERE ms.mentor_id = ? AND ms.status = 'accepted' AND ms.scheduled_date >= CURDATE()
ORDER BY ms.scheduled_date, ms.scheduled_time;

-- Booked hours this week / pending request count
SELECT COALESCE(SUM(duration_minutes),0)/60 FROM mentoring_sessions
WHERE mentor_id = ? AND status='accepted' AND scheduled_date BETWEEN ? AND ?;
SELECT COUNT(*) FROM mentoring_sessions WHERE mentor_id = ? AND status = 'pending';
```
**Real decisions needed (not blocking):**
- `day_of_week` numbering convention (0=Sunday vs 0=Monday) must be picked and documented once —
  recommend MySQL's own `DAYOFWEEK()-1` convention (0=Sunday) for consistency with SQL date functions.
- A day with zero enabled rows displays as "disabled" in the mock's per-day toggle — recommend
  inferring this from row absence rather than adding a new per-day column (no schema change needed).

---

### 2.4 Mentor Discovery & Recommendations

| Page | Mock source | Tables | Component | Status |
|---|---|---|---|---|
| `pages/mentors/find-mentors.php` | `$mentors` (8 rows) + filter option lists | `users`(`role IN('mentor','dual')`)+`departments`+`user_skills`(`skill_type='teaching'`)+`skills`+`mentor_availability` | `ukn_mentor_card()` | 🟢 (fully public directory — no current-user dependency at all) |
| `pages/mentors/recommendations.php` | `$recommendedMentors` (4 rows incl. `match`,`matchLabel`) | same base query, `ORDER BY avg_rating DESC, sessions_as_mentor DESC LIMIT 4` | `ukn_mentor_card(..., ['variant'=>'recommendation'])` | 🟢 for the base list; `match`/`matchLabel` documented as a simplification (see below) |

```sql
SELECT u.id, u.full_name AS name, u.initials, u.avg_rating AS rating,
       u.sessions_as_mentor AS sessions, u.mentor_points AS points, d.name AS department
FROM users u JOIN departments d ON d.id = u.department_id
WHERE u.role IN ('mentor','dual') AND u.status = 'active'
ORDER BY u.avg_rating DESC, u.sessions_as_mentor DESC;

-- per-mentor teaching skills (primarySkill = highest sessions_count; rest = otherSkills)
SELECT s.name, us.sessions_count, us.proficiency
FROM user_skills us JOIN skills s ON s.id = us.skill_id
WHERE us.user_id = ? AND us.skill_type = 'teaching'
ORDER BY us.sessions_count DESC, us.proficiency DESC;
```

**Filters (find-mentors.php):** search (name/skill LIKE), skill (exact, via `user_skills` EXISTS),
department (exact), minimum rating (`avg_rating >= ?`), availability bucket (`EXISTS` in
`mentor_availability` for "this week" / specific weekend days) — all straightforward `WHERE`/`EXISTS`
additions to the base query above, all parameterized.

**Documented limitation (recommendations.php):** there is no scoring engine or `match` column in the
schema. This task will **not invent one**. Recommendation: run the same "top-rated mentors" query
(optionally narrowed to mentors teaching skills the demo user has in `user_skills` where
`skill_type='learning'`, once a current-user id is available) and pass `match => null, matchLabel =>
null` — both keys are optional in `ukn_mentor_card()` and the component already skips rendering the
match badge when `match` is null. This must be called out to whoever built the "match %" UI so
expectations are set correctly — it is not this task's job to fabricate a percentage.

**No real gaps** on this page group once the corrected mapping above is used.

---

### 2.5 Sessions

| Page | Mock source | Tables | Component | Status |
|---|---|---|---|---|
| `pages/sessions/sessions.php` | `$requests` / `$sessions` (branches on `$sessionView`) | `mentoring_sessions`+`users`(counterparty)+`skills`+`session_ratings`(for rating status) | `ukn_session_card()` | 🟡 (both branches are "my sessions") |
| `pages/sessions/session-details.php` | `$sessions[$_GET['id']]` | same, single row + both parties' `users` rows | `ukn_rating_item()` implicitly via rating block | 🟡 (must also check the demo user is a participant — see access-control note) |

```sql
-- "My sessions" (learner view) / "Learner requests" (mentor view)
SELECT ms.id, ms.reference_code, ms.scheduled_date, ms.scheduled_time, ms.duration_minutes,
       ms.status, ms.request_message, ms.cancel_reason,
       u2.full_name AS counterparty, u2.initials AS counterparty_initials, u2.id AS counterparty_id,
       sk.name AS skill,
       sr.overall AS rating_value
FROM mentoring_sessions ms
JOIN users u2 ON u2.id = IF(ms.mentor_id = :uid, ms.learner_id, ms.mentor_id)
JOIN skills sk ON sk.id = ms.skill_id
LEFT JOIN session_ratings sr ON sr.session_id = ms.id
WHERE (ms.learner_id = :uid OR ms.mentor_id = :uid)
  AND (:uid = ms.mentor_id OR ms.status <> 'pending')  -- learners never see mentor-only pending-request queue as "requests"
ORDER BY ms.scheduled_date DESC, ms.scheduled_time DESC;

-- Single session (with access check)
SELECT ms.*, sk.name AS skill_name,
       ul.full_name AS learner_name, ul.initials AS learner_initials,
       um.full_name AS mentor_name, um.initials AS mentor_initials,
       um.avg_rating, um.sessions_as_mentor, um.mentor_points,
       sr.overall AS rating_value
FROM mentoring_sessions ms
JOIN users ul ON ul.id = ms.learner_id
JOIN users um ON um.id = ms.mentor_id
JOIN skills sk ON sk.id = ms.skill_id
LEFT JOIN session_ratings sr ON sr.session_id = ms.id
WHERE ms.id = ? AND (ms.learner_id = :uid OR ms.mentor_id = :uid);
```
**Status label mapping:** DB enum is `pending|accepted|completed|rejected|cancelled`; UI additionally
shows "upcoming" = `status='accepted' AND scheduled_date >= CURDATE()`. Map this in PHP after fetch,
do not add a new enum value.

**`ratingStatus`/`ratingValue`:** purely presence-of-row in `session_ratings` (`UNIQUE(session_id)`) —
`'rated'` if a row exists, else `'unrated'` for completed sessions; not applicable otherwise.

**Access control note:** even though this is a read-only task, `session-details.php` must still filter
`WHERE ms.id = ? AND (learner_id = :uid OR mentor_id = :uid)` rather than trusting `?id=` alone — this
prevents an IDOR-style "any session id shows anyone's private session" issue once real ids replace the
mock's small fixed set. Use the demo-user-id placeholder for `:uid` exactly as elsewhere; this is not
new authorization logic, just a correct `WHERE` clause on an existing filter column.

---

### 2.6 Ratings

| Page | Mock source | Tables | Component | Status |
|---|---|---|---|---|
| `pages/ratings/ratings.php` | `$overall`,`$breakdown`,`$distribution`,`$reviews` | `session_ratings`+`users`(reviewer)+`skills`,`users.avg_rating/total_reviews/sessions_as_mentor` | `ukn_rating_item()` | 🟡 (reviews *received* by current mentor) |

```sql
SELECT AVG(overall) AS overall, AVG(teaching) AS teaching, AVG(communication) AS communication,
       AVG(helpfulness) AS helpfulness
FROM session_ratings WHERE mentor_id = ?;

SELECT ROUND(overall) AS star, COUNT(*) AS n
FROM session_ratings WHERE mentor_id = ? GROUP BY star;

SELECT sr.overall, sr.teaching, sr.communication, sr.helpfulness, sr.review, sr.created_at,
       ur.full_name AS reviewer, ur.initials, sk.name AS skill
FROM session_ratings sr
JOIN users ur ON ur.id = sr.reviewer_id
LEFT JOIN skills sk ON sk.id = sr.skill_id
WHERE sr.mentor_id = ?
ORDER BY sr.created_at DESC;
```
No real gaps.

---

### 2.7 Points / Leaderboard

| Page | Mock source | Tables | Component | Status |
|---|---|---|---|---|
| `pages/points/points.php` | `$pointStats`,`$learnerTransactions`/`$mentorTransactions` | `users.learning_points/mentor_points`,`point_transactions` | `ukn_point_transaction()` | 🟡 |
| `pages/leaderboard/leaderboard.php` | `$learners`,`$mentors`,`$contributors` (3 tabs) | `users`(ranked),`point_transactions`(`point_type='community'`, aggregated) | `ukn_leaderboard_row()` | 🟢 (genuinely global/public ranking; `isCurrentUser` flag only is 🟡) |

```sql
-- Points ledger (my transactions)
SELECT amount, point_type, category, reason, related_session_id, related_post_id,
       related_goal_id, created_at
FROM point_transactions
WHERE user_id = ? AND point_type = ?
ORDER BY created_at DESC;

-- Monthly delta ("+64 this month")
SELECT COALESCE(SUM(amount),0) FROM point_transactions
WHERE user_id = ? AND point_type = ? AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01');

-- Leaderboard: learners
SELECT u.id, u.full_name, u.initials, u.learning_points AS points, u.sessions_as_learner AS sessions,
       d.name AS category
FROM users u LEFT JOIN departments d ON d.id = u.department_id
WHERE u.role IN ('learner','dual') ORDER BY u.learning_points DESC LIMIT 20;

-- Leaderboard: mentors
SELECT u.id, u.full_name, u.initials, u.mentor_points AS points, u.avg_rating AS rating,
       u.sessions_as_mentor AS sessions, d.name AS category
FROM users u LEFT JOIN departments d ON d.id = u.department_id
WHERE u.role IN ('mentor','dual') ORDER BY u.mentor_points DESC, u.avg_rating DESC LIMIT 20;

-- Leaderboard: contributors (community points aggregated live — no cached column exists)
SELECT u.id, u.full_name, u.initials, d.name AS category,
       SUM(pt.amount) AS points, COUNT(*) AS post_count
FROM point_transactions pt JOIN users u ON u.id = pt.user_id
LEFT JOIN departments d ON d.id = u.department_id
WHERE pt.point_type = 'community'
GROUP BY u.id ORDER BY points DESC LIMIT 20;
```
**Real gap (minor, non-blocking):** `rankChange` (week-over-week rank delta shown as ▲/▼) has no
historical snapshot table anywhere in the schema. **Recommendation: pass `rankChange => null` (the
component already handles this — it simply omits the arrow) rather than fabricating a delta.** If this
becomes a real requirement later, it needs a new small ranking-snapshot table plus a scheduled job —
out of scope here.

**session label on point_transactions:** `related_session_id`/`related_post_id`/`related_goal_id` are
mutually-exclusive-by-category FKs; resolving the human-readable "session"/"post title"/"goal title"
label needs a small per-category `switch` in PHP with 3 tiny lookup queries (or one query with
`LEFT JOIN`s to all three and `COALESCE`), not a schema change.

---

### 2.8 Notifications

| Page | Mock source | Tables | Component | Status |
|---|---|---|---|---|
| `pages/notifications/notifications.php` | `$notifications` | `notifications` | `ukn_notification_item()` | 🟡 (read-only listing; **do not** implement mark-read) |

```sql
SELECT id, type, icon, message, link_url, is_read, created_at
FROM notifications
WHERE user_id = ?
ORDER BY created_at DESC;
```
`kind` = map `type` enum to display label in PHP (`session→Session`, `community→Community`,
`rating→Rating`, `system→System`). `unread` = `!is_read`. No real gaps.

---

### 2.9 Search

| Page | Mock source | Tables | Component | Status |
|---|---|---|---|---|
| `pages/search/search-results.php` | `$mockPosts`,`$mockSkills`,`$mockMentors`,`$mockLearners` | `posts`(FULLTEXT),`skills`+`skill_categories`+`user_skills`,`users`+`departments`+`user_skills` | `ukn_search_result_item()` | 🟢 (fully public; only per-row `following`/`learningState`/`teachingState` toggles are 🔴) |

Run as **separate parameterized queries per result type** (not a UNION — different column shapes),
then merge/rank in PHP exactly as the existing mock code already does:
```sql
-- Skills
SELECT s.id, s.name, sc.name AS category,
       (SELECT COUNT(*) FROM user_skills WHERE skill_id=s.id AND skill_type='teaching') AS mentors,
       (SELECT COUNT(*) FROM user_skills WHERE skill_id=s.id AND skill_type='learning') AS learners
FROM skills s JOIN skill_categories sc ON sc.id = s.category_id
WHERE s.name LIKE CONCAT('%', ?, '%') ORDER BY (s.name = ?) DESC, s.name LIMIT 20;

-- Mentors
SELECT u.id, u.full_name, u.initials, u.avg_rating, u.mentor_points, d.name AS department
FROM users u LEFT JOIN departments d ON d.id = u.department_id
WHERE u.role IN ('mentor','dual') AND u.full_name LIKE CONCAT('%', ?, '%') LIMIT 20;

-- Learners
SELECT u.id, u.full_name, u.learning_points, d.name AS department
FROM users u LEFT JOIN departments d ON d.id = u.department_id
WHERE u.role IN ('learner','dual') AND u.full_name LIKE CONCAT('%', ?, '%') LIMIT 20;

-- Posts (FULLTEXT — matches the schema's ft_posts_search index)
SELECT p.id, p.title, p.user_id, u.full_name AS author, p.vote_score, p.comment_count
FROM posts p JOIN users u ON u.id = p.user_id
WHERE p.status='visible' AND MATCH(p.title, p.content) AGAINST (? IN NATURAL LANGUAGE MODE)
LIMIT 20;
```
**CORRECTED:** an earlier research pass, briefed without `departments`/`comments`/`post_skills` in its
table list, flagged "department", "comments count", and "points" as schema gaps for this page — all
three are real columns/tables (`departments.name`, `posts.comment_count`, `users.mentor_points` /
`learning_points`) and require no schema change.

---

### 2.10 Skill Network (Cytoscape.js)

| Page | Mock source | Tables | Notes |
|---|---|---|---|
| `pages/network/skill-network.php` | `$nodes`,`$edges` (JSON in `data-network-nodes`/`data-network-edges` attributes) | `skills`+`skill_categories`,`skill_relations`,`user_skills`(counts),`mentoring_sessions`(completed count per skill) | 🟢 fully public |

Confirmed (from reading `assets/js/pages/network.js`): the JS remaps flat JSON into Cytoscape's
`{data:{...}}` element format itself (`id: 'skill-'+n.id`, computes `size` client-side from
`mentors+learners`) — **no JS changes are needed**, only the PHP-side `$nodes`/`$edges` arrays need to
be populated from real queries before `json_encode()`, keeping the existing enrichment loop
(`related`, `skillDetailsHref`, `findMentorsHref`, `isCurrent`) untouched.

```sql
-- Nodes
SELECT s.id, s.name, sc.name AS category, s.description,
       (SELECT COUNT(*) FROM user_skills WHERE skill_id=s.id AND skill_type='teaching') AS mentors,
       (SELECT COUNT(*) FROM user_skills WHERE skill_id=s.id AND skill_type='learning') AS learners,
       (SELECT COUNT(*) FROM mentoring_sessions WHERE skill_id=s.id AND status='completed') AS sessions
FROM skills s JOIN skill_categories sc ON sc.id = s.category_id
WHERE s.status = 'active';

-- Edges
SELECT source_skill_id AS source, target_skill_id AS target, strength, reason
FROM skill_relations;
```
**CORRECTED:** an earlier research pass, briefed without `skill_categories`/`mentoring_sessions` in its
table list, flagged "category", "sessions count", and the `skills.status` filter as schema gaps — all
three exist in the real schema (`skill_categories.name`, `mentoring_sessions.status='completed'` count,
and `skills.status ENUM('active','inactive')`). No real gap here. `isCurrent` (whether the *viewing*
user has this skill) remains 🔴 (current-user dependent).

---

### 2.11 Admin (read-only data replacement only — auth explicitly out of scope)

**Reconfirmed:** no file in `admin/` has any auth guard (`requireAdmin`, session check, or similar) —
this is a pre-existing condition, **not addressed by this task**, and must be documented clearly in the
final report so nobody mistakes "mock data replaced" for "admin panel secured."

| Page | Mock source | Tables | Status |
|---|---|---|---|
| `admin/dashboard.php` | inline `$stats`,`$roleBreakdown`,`$sessionActivity`,`$sessionStatus`,`$recentUsers`,`$recentSessions`,`$popularSkills`,`$pendingReports`,`$recentActivity` | `users`,`skills`,`mentoring_sessions`,`posts`,`reports`,`user_skills` | 🟢 except `$recentActivity` (real gap, see below) |
| `admin/users.php` | `ukn_admin_mock_users()` | `users`+`departments`,`learning_goals`(count),`posts`(count) | 🟢 |
| `admin/user-details.php` | same + `ukn_admin_user_sessions()`,`ukn_admin_user_activity()` | `users`+`departments`,`user_skills`+`skills`,`mentoring_sessions`+`skills` | 🟢 except the activity feed (real gap) |
| `admin/departments.php` | `ukn_admin_mock_departments()` | `departments`,`users`(counts by department/role) | 🟢 |
| `admin/skill-categories.php` | `ukn_admin_mock_categories()` | `skill_categories`,`skills`(count per category) | 🟢 |
| `admin/skills.php` | `ukn_admin_mock_skills()` | `skills`+`skill_categories`,`user_skills`(counts) | 🟢 |
| `admin/sessions.php` | inline `$sessions` | `mentoring_sessions`+`users`(both parties)+`skills`+`session_ratings` | 🟢 |
| `admin/posts.php` | inline `$posts` | `posts`+`users`,`post_skills`+`skills`,`reports`(for reportReasons) | 🟢 |
| `admin/comments.php` | inline `$comments` | `comments`(self-join for parent/replies)+`users`+`posts`,`reports` | 🟢 |
| `admin/reports.php` | inline `$reports` + hardcoded `$summary`/`$typeSummary` | `reports`+`users`(reporter), conditional join to `posts`/`comments`/`users` by `target_type` | 🟢 (and **fixes the known stat/data mismatch bug**, see below) |

**CORRECTED (important):** an earlier research pass on `admin/` was briefed without `user_skills`,
`session_ratings`, `learning_goals`, and `post_skills` in its table list, and consequently reported
"no per-user-skill association table" as *the single largest recurring gap* across `dashboard.php`
(popularSkills), `user-details.php` (skill tag lists, "Active Goals" stat), and `skills.php`
(mentors/learners columns), and separately flagged `sessions.php`'s per-session `rating` field and
`posts.php`'s `tags` as gaps. **None of these are real gaps** — `user_skills` (mentor/learner↔skill
counts), `learning_goals` (goals count), `session_ratings` (1:1 per-session rating via `session_id`),
and `post_skills` (post tags) all exist and resolve every one of these cleanly, using the same query
patterns already shown in §2.2/§2.5/§2.1 above.

```sql
-- dashboard.php stat cards (also reusable, verbatim, in users.php/sessions.php/posts.php/comments.php/reports.php stat cards — single source of truth fixes the reports.php mismatch bug)
SELECT role, COUNT(*) FROM users GROUP BY role;
SELECT status, COUNT(*) FROM mentoring_sessions GROUP BY status;
SELECT status, COUNT(*) FROM reports GROUP BY status;
SELECT target_type, COUNT(*) FROM reports GROUP BY target_type;

-- popular skills (dashboard.php)
SELECT s.name,
       (SELECT COUNT(*) FROM user_skills WHERE skill_id=s.id AND skill_type='learning') AS learners,
       (SELECT COUNT(*) FROM user_skills WHERE skill_id=s.id AND skill_type='teaching') AS mentors
FROM skills s ORDER BY learners DESC LIMIT 5;

-- reports.php full row + conditional target join
SELECT r.id, r.reference_code, r.target_type, r.status, r.reason, r.description,
       r.reviewed_by, r.reviewed_at, r.created_at,
       ur.full_name AS reporter_name,
       tp.id AS post_id, tp.title AS post_title, tp.status AS post_status,
       tc.id AS comment_id, tc.content AS comment_text, tc.status AS comment_status,
       tu.id AS user_target_id, tu.full_name AS user_target_name
FROM reports r
JOIN users ur ON ur.id = r.reporter_id
LEFT JOIN posts tp ON r.target_type='post' AND tp.id = r.target_id
LEFT JOIN comments tc ON r.target_type='comment' AND tc.id = r.target_id
LEFT JOIN users tu ON r.target_type='user' AND tu.id = r.target_id
ORDER BY r.created_at DESC;
```

**Genuinely real admin gaps:**
- **`recentActivity`** (dashboard.php) and **`ukn_admin_user_activity()`** (user-details.php) — no
  activity/audit-log table exists. Recommend either dropping these panels for now, or building a small
  ad-hoc "recent events" feed by unioning a few real tables (new users, new sessions, new reports)
  ordered by timestamp — document clearly as a simplification, not a full audit log.
- **`decision`/resolution-notes free text** on `reports.php` — `reports` only has `reviewed_by` +
  `reviewed_at`, no free-text resolution column. Recommend showing "Resolved by {reviewer} on {date}"
  instead of inventing decision text, or note this as a nice-to-have schema addition **for the CRUD
  team to decide on later** (not something to add unilaterally in this read-only task).
- **`reports.php`'s known stat/data mismatch** (hardcoded `$summary`/`$typeSummary` totals not matching
  the 16-row mock array): converting **both** the stat cards and the table rows to the same
  `GROUP BY status`/`GROUP BY target_type` queries above resolves this automatically — this is the
  recommended fix, not a manual number edit.

---

## 3. Existing components confirmed reusable as-is (no changes needed)

`ukn_post_card`, `ukn_mentor_card`, `ukn_learner_card`, `ukn_skill_card`, `ukn_session_card`,
`ukn_stat_card`, `ukn_goal_card`, `ukn_point_transaction`, `ukn_rating_item`, `ukn_notification_item`,
`ukn_leaderboard_row`, `ukn_search_result_item`, `ukn_empty_state`, `ukn_error_state`,
`ukn_loading_skeleton`/`ukn_loading_spinner`, `ukn_success_state`. Every one of these already merges
sane defaults for optional keys (`$x += [...]`), so passing a real-data array with only the keys we can
populate (omitting per-viewer 🔴 keys) is safe and requires zero component edits.

## 4. Pages that should NOT be modified yet

| Page | Reason |
|---|---|
| `pages/auth/login.php`, `pages/auth/register.php` | Auth team's task; forms have no `action`/`method` by design |
| `backend/auth/login.php`, `backend/auth/logout.php` | Empty stubs — auth team's task |
| `pages/profile/edit-profile.php` (submit), `modals/*.php` (all 7), any "Save"/"Delete"/"Accept"/"Reject"/"Cancel"/"Vote"/"Follow"/"Save post" action | Write/CRUD operations — future team members' task |
| `includes/left-sidebar.php`, `includes/mobile-nav.php`, `includes/profile-dropdown.php`, `includes/header.php`'s `$currentUser` block | Session/current-user rendering — auth team's task; do not touch `$currentUser` injection in `index.php` |
| `pages/settings/settings.php` | Entirely account-preference toggles with no read-only display value worth wiring (all are write-only prefs); leave as-is |
| `admin/settings.php` | Pure UI preferences, no DB-backed feature at all |
| Anything requiring `notifications` mark-read, rating submission, point awarding, or session state transitions | Explicitly future team members' tasks per the brief |

## 5. Missing seed data / schema limitations (real, confirmed against the actual schema)

1. **No activity/audit-log table** — blocks a fully real "Recent Activity" feed on `my-profile.php`,
   `admin/dashboard.php`, and `admin/user-details.php`. Not a blocking issue for the rest of the task;
   recommend a documented simplification (small synthesized feed or an omitted panel), not a schema change.
2. **No historical leaderboard-rank snapshot table** — blocks a real `rankChange` (▲/▼) value on
   `leaderboard.php`. Recommend passing `null` (component already handles it).
3. **No `match`/scoring column or engine** for `recommendations.php` — by design, this task will not
   invent a scoring formula; use a simple "top-rated mentors" query and pass `match => null`.
4. **No free-text resolution/decision column on `reports`** — only `reviewed_by`/`reviewed_at` exist.
   Minor cosmetic gap on `admin/reports.php`'s decision text; not blocking.
5. **`seed.sql` coverage:** seed data exists for `departments`, `skill_categories`, `skills` (confirmed
   read). It was **not confirmed during this audit** whether `seed.sql` also seeds `users`,
   `user_skills`, `mentoring_sessions`, `session_ratings`, `posts`, `comments`, `learning_goals`,
   `mentor_availability`, `point_transactions`, `notifications`, or `reports` — **Phase 1 must verify
   this before Phase 2 implementation**, since several pages above (home feed, sessions, points,
   leaderboard, admin tables) will render empty (correctly, via empty-states) rather than
   "successfully tested" if that data isn't actually seeded. This is the single most important
   open question before implementation begins.
6. **`mentor_availability.day_of_week` numbering convention** is not pinned down anywhere (schema only
   says `TINYINT 0–6`) — must be fixed by convention (recommend `DAYOFWEEK()-1`, i.e. 0=Sunday) before
   writing the availability query, and documented in code.
7. **`database/patches/remove-comment-voting.sql`** confirms comment-level voting was removed from the
   design — no `comments.vote_score` column exists (correctly matches the mock, which never shows
   comment vote counts).

## 6. Recommended implementation order

Ordered by (a) zero/lowest current-user dependency first, (b) highest confidence in DB coverage,
(c) simplest queries first, so each step is independently testable before moving on:

1. **Skills directory & skill details** (`skills.php`, `skill-details.php`) — fully public, simple joins.
2. **Mentor discovery** (`find-mentors.php`) — fully public, no current-user dependency at all.
3. **Skill network graph** (`skill-network.php`) — fully public, isolated (only touches one `<div>`'s data attributes).
4. **Learner/mentor public profiles** (`learner-profile.php`, `mentor-profile.php`) — public, `?id=`-keyed, good IDOR-safety practice case.
5. **Search results** (`search-results.php`) — public, exercises FULLTEXT + multiple query types.
6. **Leaderboard** (`leaderboard.php`) — public ranking, good aggregation practice.
7. **Home feed & post-details** (`home.php`, `post-details.php`) — introduces the demo-user-id placeholder pattern for the first time (for `following`/`isOwner` *omission*, not wiring).
8. **My-posts / saved-posts / my-profile / learning-skills / teaching-skills / learning-goals / availability / sessions / session-details / ratings / points / notifications** — all demo-user-id-scoped, same pattern repeated.
9. **Admin read-only tables** (`dashboard.php` → `users.php`/`departments.php`/`skill-categories.php`/`skills.php` → `sessions.php`/`posts.php`/`comments.php`/`reports.php`) — last, since it's a separate app surface and explicitly still unauthenticated; converting it does not change that risk, so it carries no urgency relative to the public app.

Recommend **pausing for a go/no-go check-in after step 3 or 4** (first fully public pages converted) to
confirm the query/component pattern reads correctly against your actual local `ukn_database` before
repeating it across the remaining ~25 pages.
