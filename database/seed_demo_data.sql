-- Supplemental demo data for University Knowledge Network.
--
-- database/seed.sql only populates departments, skill_categories, skills, users and
-- user_settings. Every other feature table (user_skills, sessions, ratings, posts,
-- points, notifications, reports, skill_relations, ...) is left empty, which means most
-- pages will show correct-but-uninteresting empty states until this file is run.
--
-- Run this AFTER schema.sql and seed.sql, against the same database. It only inserts
-- rows (no schema changes) and references the exact user/department/skill ids that
-- seed.sql creates (users 1-17, skills 1-14, departments 1-7, skill_categories 1-7).
--
-- Safe to re-run on a fresh import (drops its own rows first); do NOT run this against
-- a database that already has real user-generated data, since it deletes rows from the
-- tables it seeds.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM reports;
DELETE FROM notifications;
DELETE FROM point_transactions;
DELETE FROM follows;
DELETE FROM saved_posts;
DELETE FROM post_votes;
DELETE FROM comments;
DELETE FROM post_skills;
DELETE FROM posts;
DELETE FROM session_ratings;
DELETE FROM mentoring_sessions;
DELETE FROM learning_goals;
DELETE FROM mentor_availability;
DELETE FROM user_skills;
DELETE FROM skill_relations;
SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- skill_relations (skill-network graph edges)
-- ---------------------------------------------------------------------------
INSERT INTO skill_relations (source_skill_id, target_skill_id, strength, reason) VALUES
(1, 5,  'Strong',  'Python is commonly used for data analysis workflows.'),
(1, 7,  'Medium',  'Backend work in Python often touches database design.'),
(1, 13, 'Strong',  'Python is the primary language for most machine learning coursework.'),
(1, 2,  'Medium',  'Python is frequently paired with MySQL for data-backed apps.'),
(2, 7,  'Strong',  'MySQL is the primary implementation used in database design coursework.'),
(3, 11, 'Strong',  'React is built directly on top of JavaScript.'),
(3, 4,  'Medium',  'React is commonly used to implement UI/UX designs.'),
(4, 11, 'Related', 'Front-end interfaces are usually implemented in JavaScript.'),
(5, 13, 'Strong',  'Data analysis techniques underpin most machine learning workflows.'),
(5, 2,  'Medium',  'Data analysis frequently starts from data stored in MySQL.'),
(6, 12, 'Strong',  'Public speaking and presentation skills directly overlap.'),
(8, 14, 'Strong',  'Arduino projects are a common entry point into embedded systems.'),
(9, 12, 'Related', 'Academic writing and presentation skills both support coursework delivery.');

-- ---------------------------------------------------------------------------
-- user_skills (teaching = mentor offerings, learning = learner enrollments)
-- ---------------------------------------------------------------------------
INSERT INTO user_skills (user_id, skill_id, skill_type, proficiency, progress, sessions_count, avg_rating, primary_mentor_id) VALUES
-- Teaching (mentors)
(1,  7, 'teaching', 'Intermediate', 0, 27,  4.8, NULL),
(2,  1, 'teaching', 'Advanced',     0, 80,  4.9, NULL),
(2,  5, 'teaching', 'Advanced',     0, 30,  4.9, NULL),
(2,  7, 'teaching', 'Intermediate', 0, 17,  4.8, NULL),
(4,  8, 'teaching', 'Advanced',     0, 35,  4.7, NULL),
(4, 14, 'teaching', 'Advanced',     0, 17,  4.7, NULL),
(5,  3, 'teaching', 'Advanced',     0, 40,  4.7, NULL),
(5, 11, 'teaching', 'Advanced',     0, 20,  4.7, NULL),
(5,  4, 'teaching', 'Intermediate', 0, 11,  4.7, NULL),
(6,  6, 'teaching', 'Advanced',     0, 84,  4.8, NULL),
(7,  2, 'teaching', 'Advanced',     0, 60,  4.9, NULL),
(7,  7, 'teaching', 'Advanced',     0, 43,  4.9, NULL),
(8,  9, 'teaching', 'Advanced',     0, 28,  4.6, NULL),
(15, 8, 'teaching', 'Intermediate', 0, 4,   3.9, NULL),
-- Learning
(1,  1, 'learning', 'Advanced',     80, 12, NULL, 2),
(1,  2, 'learning', 'Intermediate', 55, 8,  NULL, 7),
(1,  5, 'learning', 'Intermediate', 60, 10, NULL, 2),
(3,  6, 'learning', 'Beginner',     40, 4,  NULL, 6),
(3, 12, 'learning', 'Beginner',     25, 1,  NULL, NULL),
(5, 13, 'learning', 'Beginner',     20, 1,  NULL, NULL),
(9,  1, 'learning', 'Intermediate', 65, 6,  NULL, 2),
(10, 5, 'learning', 'Intermediate', 58, 5,  NULL, 2),
(10, 1, 'learning', 'Beginner',     30, 2,  NULL, 2),
(11, 9, 'learning', 'Intermediate', 45, 3,  NULL, 8),
(12, 8, 'learning', 'Beginner',     20, 1,  NULL, 4),
(13,10, 'learning', 'Intermediate', 50, 3,  NULL, NULL),
(14,10, 'learning', 'Beginner',     10, 0,  NULL, NULL),
(16, 9, 'learning', 'Beginner',     15, 1,  NULL, 8);

-- ---------------------------------------------------------------------------
-- mentor_availability (0 = Sunday .. 6 = Saturday, per DAYOFWEEK()-1 convention)
-- ---------------------------------------------------------------------------
INSERT INTO mentor_availability (user_id, day_of_week, start_time, end_time, is_enabled) VALUES
(2, 1, '18:00:00', '20:00:00', 1),
(2, 3, '18:00:00', '20:00:00', 1),
(4, 2, '19:00:00', '21:00:00', 1),
(5, 4, '17:00:00', '19:00:00', 1),
(6, 6, '10:00:00', '12:00:00', 1),
(7, 1, '16:00:00', '18:00:00', 1),
(7, 4, '16:00:00', '18:00:00', 1),
(8, 3, '15:00:00', '17:00:00', 1),
(15,5, '18:00:00', '19:00:00', 0);

-- ---------------------------------------------------------------------------
-- learning_goals
-- ---------------------------------------------------------------------------
INSERT INTO learning_goals (id, user_id, skill_id, title, progress, target_date, status, completed_at) VALUES
(1, 1,  1, 'Finish Python Basics',                  100, '2026-06-01', 'completed', '2026-06-01 12:00:00'),
(2, 1,  5, 'Learn Python for Data Analysis',          65, '2026-12-15', 'in-progress', NULL),
(3, 1,  2, 'Master MySQL Joins',                      40, '2026-11-01', 'in-progress', NULL),
(4, 3,  6, 'Improve Public Speaking for Seminars',     40, '2026-10-20', 'in-progress', NULL),
(5, 9,  1, 'Get comfortable with Python syntax',       65, '2026-11-10', 'in-progress', NULL),
(6, 10, 5, 'Learn Data Analysis fundamentals',         58, '2026-11-20', 'in-progress', NULL),
(7, 11, 9, 'Improve academic essay structure',         45, '2026-10-30', 'in-progress', NULL);

-- ---------------------------------------------------------------------------
-- mentoring_sessions
-- ---------------------------------------------------------------------------
INSERT INTO mentoring_sessions
    (id, reference_code, learner_id, mentor_id, skill_id, scheduled_date, scheduled_time,
     duration_minutes, status, request_message, cancel_reason, requested_at, responded_at, completed_at) VALUES
(1,  'UKN-S-1001', 1,  2, 1, '2026-09-28', '19:00:00', 60, 'pending',   'Would like help with pandas basics before the midterm.', NULL, '2026-09-20 10:00:00', NULL, NULL),
(2,  'UKN-S-1002', 9,  2, 1, '2026-09-24', '18:00:00', 60, 'accepted',  'Could we go over list comprehensions?', NULL, '2026-09-18 09:00:00', '2026-09-18 12:00:00', NULL),
(3,  'UKN-S-1003', 10, 2, 5, '2026-09-26', '19:00:00', 45, 'accepted',  NULL, NULL, '2026-09-19 09:00:00', '2026-09-19 11:00:00', NULL),
(4,  'UKN-S-1004', 1,  7, 2, '2026-08-15', '16:00:00', 60, 'completed', NULL, NULL, '2026-08-10 09:00:00', '2026-08-10 10:00:00', '2026-08-15 17:00:00'),
(5,  'UKN-S-1005', 3,  6, 6, '2026-08-01', '10:00:00', 45, 'completed', NULL, NULL, '2026-07-28 09:00:00', '2026-07-28 10:00:00', '2026-08-01 10:45:00'),
(6,  'UKN-S-1006', 12, 4, 8, '2026-09-10', '19:00:00', 90, 'completed', NULL, NULL, '2026-09-05 09:00:00', '2026-09-05 12:00:00', '2026-09-10 20:30:00'),
(7,  'UKN-S-1007', 11, 8, 9, '2026-07-20', '15:00:00', 60, 'completed', NULL, NULL, '2026-07-15 09:00:00', '2026-07-15 10:00:00', '2026-07-20 16:00:00'),
(8,  'UKN-S-1008', 1,  2, 5, '2026-07-01', '19:00:00', 60, 'completed', NULL, NULL, '2026-06-27 09:00:00', '2026-06-27 10:00:00', '2026-07-01 20:00:00'),
(9,  'UKN-S-1009', 9,  2, 1, '2026-09-06', '18:00:00', 60, 'rejected',  'Hoping to cover error handling.', NULL, '2026-09-03 09:00:00', '2026-09-03 13:00:00', NULL),
(10, 'UKN-S-1010', 10, 2, 1, '2026-08-20', '18:00:00', 60, 'cancelled', NULL, 'Learner had a scheduling conflict.', '2026-08-14 09:00:00', '2026-08-14 10:00:00', NULL),
(11, 'UKN-S-1011', 1,  5, 3, '2026-09-29', '17:00:00', 60, 'pending',   'Want to learn React basics for a class project.', NULL, '2026-09-21 08:00:00', NULL, NULL),
(12, 'UKN-S-1012', 14, 8, 9, '2026-09-27', '15:00:00', 45, 'pending',   NULL, NULL, '2026-09-20 08:00:00', NULL, NULL),
(13, 'UKN-S-1013', 3,  8, 9, '2026-06-10', '15:00:00', 60, 'completed', NULL, NULL, '2026-06-05 09:00:00', '2026-06-05 10:00:00', '2026-06-10 16:00:00'),
(14, 'UKN-S-1014', 12, 15,8, '2026-06-01', '18:00:00', 60, 'completed', NULL, NULL, '2026-05-27 09:00:00', '2026-05-27 10:00:00', '2026-06-01 19:00:00'),
(15, 'UKN-S-1015', 9,  1, 7, '2026-06-15', '18:00:00', 60, 'completed', NULL, NULL, '2026-06-10 09:00:00', '2026-06-10 10:00:00', '2026-06-15 19:00:00');

-- ---------------------------------------------------------------------------
-- session_ratings (only some completed sessions are rated, to exercise both states)
-- ---------------------------------------------------------------------------
INSERT INTO session_ratings (session_id, reviewer_id, mentor_id, skill_id, overall, teaching, communication, helpfulness, review, created_at) VALUES
(4,  1, 7, 2, 5, 5, 5, 4, 'Farhan explained joins really clearly with real examples.', '2026-08-15 17:20:00'),
(5,  3, 6, 6, 5, 5, 4, 5, 'Sara gave specific feedback on my pacing that actually helped.', '2026-08-01 11:00:00'),
(6, 12, 4, 8, 4, 4, 4, 4, 'Good hands-on session, would have liked more time on debouncing.', '2026-09-10 20:45:00'),
(8,  1, 2, 5, 5, 5, 5, 5, 'Rahim is an excellent mentor, made pandas finally click.', '2026-07-01 20:15:00'),
(13, 3, 8, 9, 4, 4, 5, 4, 'Helped me restructure my essay introduction.', '2026-06-10 16:10:00'),
(15, 9, 1, 7, 5, 5, 4, 5, 'Nabila made database design approachable, great with beginners despite being a peer.', '2026-06-15 19:15:00');

-- ---------------------------------------------------------------------------
-- posts + post_skills
-- ---------------------------------------------------------------------------
INSERT INTO posts (id, user_id, title, content, vote_score, comment_count, report_count, status, created_at) VALUES
(1,  9,  'Need Help Understanding Database Normalization',
     'I keep mixing up 2NF and 3NF when I try to apply them to my own schema. Does anyone have a simple way of checking whether a table is actually normalized, beyond just repeating the textbook definitions?',
     24, 2, 0, 'visible', '2026-09-20 09:12:00'),
(2,  2,  'A Simple Way to Start Learning Python for Data Analysis',
     'Skip the theory-heavy courses at first. Start with pandas on a dataset you actually care about — it clicks a lot faster than notebooks full of print statements.',
     48, 1, 0, 'visible', '2026-09-19 14:00:00'),
(3,  3,  'Python List Comprehension Confusion',
     'I understand the basic syntax but nested comprehensions with a condition still take me a full minute to read. Any mental model that made this click for you?',
     21, 0, 1, 'visible', '2026-09-18 10:30:00'),
(4,  6,  'Which Library Should I Learn for Data Analysis?',
     'Trying to decide between going deep on pandas first or splitting time with numpy and matplotlib from the start. What order actually worked for you?',
     17, 0, 0, 'visible', '2026-09-17 08:45:00'),
(5,  1,  'Things I Learned While Building My First React Project',
     'Prop drilling got out of hand fast. useContext and lifting state up earlier than I thought I needed to would have saved a lot of rework.',
     32, 0, 0, 'visible', '2026-09-16 19:00:00'),
(6,  5,  'Best Resources for Learning UI/UX Design',
     'A few free resources that actually helped: the Refactoring UI book, and just critiquing five apps you use every day for layout and hierarchy.',
     19, 0, 0, 'visible', '2026-09-15 11:20:00'),
(7,  10, 'How I Started Learning Python for Data Analysis',
     'I started from a spreadsheet I already understood and rebuilt it in pandas column by column. Much less abstract than a generic tutorial dataset.',
     15, 0, 0, 'visible', '2026-09-14 16:00:00'),
(8,  11, 'Struggling With Academic Essay Structure — Any Tips?',
     'I can write individual paragraphs fine but my overall argument structure across a full essay tends to wander. How do you outline before you start writing?',
     9,  0, 0, 'visible', '2026-09-13 13:10:00'),
(9,  4,  'Common Beginner Mistakes in Arduino Wiring',
     'Forgetting a pull-down resistor and not debouncing a button are the two mistakes that cost new students the most debugging time.',
     27, 0, 1, 'visible', '2026-09-12 09:30:00'),
(10, 7,  'Why Indexing Matters More Than You Think in MySQL',
     'A query that felt instant on 200 rows can crawl on 200,000 without the right index. Worth learning EXPLAIN early, not after something is already slow.',
     36, 0, 0, 'visible', '2026-09-11 15:45:00');

INSERT INTO post_skills (post_id, skill_id) VALUES
(1, 2), (1, 7),
(2, 1), (2, 5),
(3, 1),
(4, 1), (4, 5),
(5, 3), (5, 11),
(6, 4),
(7, 1), (7, 5),
(8, 9),
(9, 8),
(10, 2), (10, 7);

-- ---------------------------------------------------------------------------
-- comments (post 1 has a top-level comment + one reply; post 2 has one comment)
-- ---------------------------------------------------------------------------
INSERT INTO comments (id, post_id, user_id, parent_id, content, report_count, status, created_at) VALUES
(1, 1, 2, NULL, 'Normalization really clicks once you practice with a real messy dataset — happy to look at your schema if you post it.', 1, 'visible', '2026-09-20 10:05:00'),
(2, 1, 9, 1,    'Thanks, I''ll share my ERD tomorrow!', 0, 'visible', '2026-09-20 10:40:00'),
(3, 2, 3, NULL, 'This matches my experience — pandas before matplotlib.', 0, 'visible', '2026-09-19 15:00:00');

-- ---------------------------------------------------------------------------
-- post_votes / saved_posts / follows
-- ---------------------------------------------------------------------------
INSERT INTO post_votes (user_id, post_id, value) VALUES
(1, 1, 1), (5, 1, 1), (9, 2, 1), (10, 2, 1), (1, 5, 1), (2, 5, 1);

INSERT INTO saved_posts (user_id, post_id) VALUES
(1, 2), (1, 9), (9, 1);

INSERT INTO follows (follower_id, following_id) VALUES
(1, 2), (9, 2), (10, 2), (1, 5);

-- ---------------------------------------------------------------------------
-- point_transactions
-- ---------------------------------------------------------------------------
INSERT INTO point_transactions
    (user_id, amount, point_type, category, reason, related_session_id, related_post_id, related_goal_id, created_at) VALUES
(1,  20, 'learning', 'session', 'Completed Data Analysis session with Rahim Ahmed', 8,  NULL, NULL, '2026-07-01 20:15:00'),
(1,  15, 'mentor',   'session', 'Completed Database Design mentoring session with Mahi Noor', 15, NULL, NULL, '2026-06-15 19:15:00'),
(1,  10, 'learning', 'goal',    'Completed learning goal: Finish Python Basics', NULL, NULL, 1, '2026-06-01 12:00:00'),
(1,   5, 'community', 'community', 'Post "Things I Learned While Building My First React Project" reached 25+ upvotes', NULL, 5, NULL, '2026-09-10 09:00:00'),
(2,  22, 'mentor',   'session', 'Completed Data Analysis mentoring session with Nabila Rahman', 8, NULL, NULL, '2026-07-01 20:15:00'),
(2,   5, 'mentor',   'rating',  'Received a 5-star rating from Nabila Rahman', 8, NULL, NULL, '2026-07-01 20:20:00'),
(7,  20, 'mentor',   'session', 'Completed MySQL mentoring session with Nabila Rahman', 4, NULL, NULL, '2026-08-15 17:15:00'),
(7,   5, 'mentor',   'rating',  'Received a 5-star rating from Nabila Rahman', 4, NULL, NULL, '2026-08-15 17:20:00'),
(6,  15, 'mentor',   'session', 'Completed Public Speaking session with Imran Chowdhury', 5, NULL, NULL, '2026-08-01 11:00:00'),
(6,   5, 'mentor',   'rating',  'Received a 5-star rating from Imran Chowdhury', 5, NULL, NULL, '2026-08-01 11:05:00'),
(4,  20, 'mentor',   'session', 'Completed Arduino mentoring session with Adil Hasan', 6, NULL, NULL, '2026-09-10 20:45:00'),
(4,   4, 'mentor',   'rating',  'Received a 4-star rating from Adil Hasan', 6, NULL, NULL, '2026-09-10 20:50:00'),
(8,  18, 'mentor',   'session', 'Completed Academic Writing session with Sabrina Ali', 7, NULL, NULL, '2026-07-20 16:15:00'),
(8,  18, 'mentor',   'session', 'Completed Academic Writing session with Imran Chowdhury', 13, NULL, NULL, '2026-06-10 16:10:00'),
(8,   4, 'mentor',   'rating',  'Received a 4-star rating from Imran Chowdhury', 13, NULL, NULL, '2026-06-10 16:15:00'),
(9,  15, 'learning', 'session', 'Completed Database Design session with Nabila Rahman', 15, NULL, NULL, '2026-06-15 19:15:00'),
(3,  12, 'learning', 'session', 'Completed Public Speaking session with Sara Khan', 5, NULL, NULL, '2026-08-01 11:00:00'),
(3,  10, 'learning', 'session', 'Completed Academic Writing session with Nusrat Jahan', 13, NULL, NULL, '2026-06-10 16:10:00'),
(12, 14, 'learning', 'session', 'Completed Arduino session with Hasan Mahmud', 6, NULL, NULL, '2026-09-10 20:45:00'),
(11, 10, 'learning', 'session', 'Completed Academic Writing session with Nusrat Jahan', 7, NULL, NULL, '2026-07-20 16:15:00'),
(15, -20, 'mentor',  'penalty', 'Point deduction for policy violation', NULL, NULL, NULL, '2026-09-12 10:00:00');

-- ---------------------------------------------------------------------------
-- notifications
-- ---------------------------------------------------------------------------
INSERT INTO notifications (user_id, type, icon, message, link_url, is_read, created_at) VALUES
(1,  'session',   'event_available', 'Your React session request with Ayesha Rahman is awaiting a response.', 'index.php?page=session-details&id=11', 0, '2026-09-21 08:05:00'),
(9,  'community',  'chat_bubble',    'Rahim Ahmed commented on your post "Need Help Understanding Database Normalization."', 'index.php?page=post-details&id=1', 0, '2026-09-20 10:05:00'),
(2,  'rating',     'star',           'You received a 5-star rating for Data Analysis.', 'index.php?page=ratings', 1, '2026-07-01 20:20:00'),
(7,  'rating',     'star',           'You received a 5-star rating for MySQL.', 'index.php?page=ratings', 0, '2026-08-15 17:20:00'),
(1,  'rating',     'star',           'You received a 5-star rating for Database Design.', 'index.php?page=ratings', 1, '2026-06-15 19:15:00'),
(10, 'session',    'event_available','Your Data Analysis session request with Rahim Ahmed was accepted.', 'index.php?page=session-details&id=3', 1, '2026-09-19 11:00:00'),
(9,  'session',    'event_busy',     'Your Python session request with Rahim Ahmed was declined.', 'index.php?page=session-details&id=9', 1, '2026-09-03 13:00:00'),
(17, 'system',     'info',           'Welcome to University Knowledge Network.', NULL, 1, '2025-01-05 09:00:00');

-- ---------------------------------------------------------------------------
-- reports
-- ---------------------------------------------------------------------------
INSERT INTO reports (reference_code, reporter_id, target_type, target_id, reason, description, status, reviewed_by, reviewed_at, created_at) VALUES
('UKN-R-0001', 9,  'post',    3,  'off-topic',           'This looks unrelated to the skill tag it was posted under.', 'pending',   NULL, NULL,                 '2026-09-20 12:00:00'),
('UKN-R-0002', 11, 'comment', 1,  'spam',                'Possible unrelated promotional reply.',                    'resolved',  17,   '2026-09-20 15:00:00', '2026-09-20 12:30:00'),
('UKN-R-0003', 10, 'user',    15, 'harassment',          'Reported after a policy violation in a mentoring session.', 'resolved',  17,   '2026-09-12 11:00:00', '2026-09-12 10:30:00'),
('UKN-R-0004', 12, 'post',    9,  'academic-integrity',  'Possibly copied wiring diagram without credit.',           'dismissed', 17,   '2026-09-13 09:00:00', '2026-09-12 18:00:00');

COMMIT;

-- ---------------------------------------------------------------------------
-- Verification summary
-- ---------------------------------------------------------------------------
SELECT 'skill_relations' AS table_name, COUNT(*) AS rows_seeded FROM skill_relations
UNION ALL SELECT 'user_skills',         COUNT(*) FROM user_skills
UNION ALL SELECT 'mentor_availability', COUNT(*) FROM mentor_availability
UNION ALL SELECT 'learning_goals',      COUNT(*) FROM learning_goals
UNION ALL SELECT 'mentoring_sessions',  COUNT(*) FROM mentoring_sessions
UNION ALL SELECT 'session_ratings',     COUNT(*) FROM session_ratings
UNION ALL SELECT 'posts',               COUNT(*) FROM posts
UNION ALL SELECT 'post_skills',         COUNT(*) FROM post_skills
UNION ALL SELECT 'comments',            COUNT(*) FROM comments
UNION ALL SELECT 'post_votes',          COUNT(*) FROM post_votes
UNION ALL SELECT 'saved_posts',         COUNT(*) FROM saved_posts
UNION ALL SELECT 'follows',             COUNT(*) FROM follows
UNION ALL SELECT 'point_transactions',  COUNT(*) FROM point_transactions
UNION ALL SELECT 'notifications',       COUNT(*) FROM notifications
UNION ALL SELECT 'reports',             COUNT(*) FROM reports;
