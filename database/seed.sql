
SET NAMES utf8mb4;

START TRANSACTION;

SET @pw := '$2y$10$UknDemoSeedPlaceholderHashNotUsableForLogin0000000000';


INSERT INTO departments (id, name, code, status, created_at) VALUES
(1, 'Computer Science',        'CSE',  'active', '2025-01-05 09:00:00'),
(2, 'Electrical Engineering',  'EEE',  'active', '2025-01-05 09:00:00'),
(3, 'Business Administration', 'BBA',  'active', '2025-01-05 09:00:00'),
(4, 'English',                 'ENG',  'active', '2025-01-05 09:00:00'),
(5, 'Economics',               'ECO',  'active', '2025-01-05 09:00:00'),
(6, 'Civil Engineering',       'CE',   'active', '2025-01-05 09:00:00'),
(7, 'Architecture',            'ARCH', 'active', '2025-01-05 09:00:00');


INSERT INTO skill_categories (id, name, description, status, created_at, updated_at) VALUES
(1, 'Programming',   'Programming languages and software development skills.',        'active', '2025-01-05 09:00:00', '2026-09-10 09:00:00'),
(2, 'Data',          'Database, analytics and data-focused skills.',                  'active', '2025-01-05 09:00:00', '2026-09-09 09:00:00'),
(3, 'Design',        'Interface, experience and visual design skills.',               'active', '2025-01-05 09:00:00', '2026-08-28 09:00:00'),
(4, 'Engineering',   'Hardware, electronics and applied engineering skills.',         'active', '2025-01-05 09:00:00', '2026-08-20 09:00:00'),
(5, 'Communication', 'Speaking, presenting and interpersonal communication skills.',  'active', '2025-01-05 09:00:00', '2026-09-01 09:00:00'),
(6, 'Academic',      'Academic research and writing skills.',                         'active', '2025-01-05 09:00:00', '2026-08-15 09:00:00'),
(7, 'Business',      'Marketing, strategy and business-facing skills.',               'active', '2025-01-05 09:00:00', '2026-08-12 09:00:00');


INSERT INTO skills (id, name, slug, category_id, description, about, status, created_at, updated_at) VALUES
(1, 'Python', 'python', 1,
 'A versatile programming language used for software development, automation, data analysis and machine learning.',
 'Python is the most taught skill on the network. Mentors cover everything from first syntax to pandas, scripting and the machine-learning coursework sequence, and most sessions run 45-60 minutes in the Student Union study rooms or online.',
 'active', '2025-01-05 09:00:00', '2026-09-12 09:00:00'),

(2, 'MySQL', 'mysql', 2,
 'A widely used relational database system for storing and querying structured data.',
 'Sessions typically start from writing and querying tables, then move into joins, indexes and the normalization rules that show up most in coursework and interviews.',
 'active', '2025-01-05 09:00:00', '2026-09-10 09:00:00'),

(3, 'React', 'react', 1,
 'A JavaScript library for building interactive user interfaces and single-page applications.',
 'Most mentors assume basic JavaScript and focus on components, state and the mistakes that trip up a first real project - prop drilling, effect dependencies and re-render loops.',
 'active', '2025-01-05 09:00:00', '2026-09-08 09:00:00'),

(4, 'UI/UX Design', 'ui-ux-design', 3,
 'Designing interfaces and experiences that are functional, accessible and easy to use.',
 'Sessions mix short critique of your own screens with the fundamentals - layout, hierarchy, contrast and how to justify a design decision, not just make one.',
 'active', '2025-01-05 09:00:00', '2026-09-06 09:00:00'),

(5, 'Data Analysis', 'data-analysis', 2,
 'Turning raw data into insight using spreadsheets, Python and statistical thinking.',
 'Mentors work through a real dataset with you - cleaning it, asking useful questions of it, and presenting what you found, rather than teaching statistics in the abstract.',
 'active', '2025-01-05 09:00:00', '2026-09-11 09:00:00'),

(6, 'Public Speaking', 'public-speaking', 5,
 'Structuring and delivering talks and presentations with confidence.',
 'Sessions are mostly practice: structuring a short talk, handling nerves, and getting specific feedback on pacing and filler words rather than general advice.',
 'active', '2025-01-05 09:00:00', '2026-09-02 09:00:00'),

(7, 'Database Design', 'database-design', 2,
 'Modeling data relationships and normalizing schemas for reliable, efficient systems.',
 'Covers modeling entities and relationships, normalization, and the trade-offs between a clean schema and a fast query - the part coursework often skips.',
 'active', '2025-01-05 09:00:00', '2026-09-09 09:00:00'),

(8, 'Arduino', 'arduino', 4,
 'Building and programming microcontroller projects, from sensors to simple robotics.',
 'Hands-on sessions with real boards and components - wiring, debouncing, sensors and the debugging habits that save the most time on a first project.',
 'active', '2025-01-05 09:00:00', '2026-08-22 09:00:00'),

(9, 'Academic Writing', 'academic-writing', 6,
 'Structuring essays, reports and citations for university-level coursework.',
 'Mentors help structure arguments, tighten paragraphs and get citations right for the specific style your department expects.',
 'active', '2025-01-05 09:00:00', '2026-08-15 09:00:00'),

(10, 'Digital Marketing', 'digital-marketing', 7,
 'Reaching an audience through social media, content and basic campaign analytics.',
 'Sessions cover the basics of reaching an audience - content planning, social platforms and reading enough analytics to know if something worked.',
 'active', '2025-01-05 09:00:00', '2026-08-12 09:00:00'),

(11, 'JavaScript', 'javascript', 1,
 'The core scripting language of the web, used alongside React for interactive interfaces.',
 NULL,
 'active', '2025-01-05 09:00:00', '2026-09-07 09:00:00'),

(12, 'Presentation Skills', 'presentation-skills', 5,
 'Turning a talk''s structure into a confident, well-paced delivery in front of an audience.',
 NULL,
 'active', '2025-01-05 09:00:00', '2026-09-03 09:00:00'),

(13, 'Machine Learning', 'machine-learning', 2,
 'Using data and Python to build models that recognize patterns and make predictions.',
 NULL,
 'active', '2025-01-05 09:00:00', '2026-09-04 09:00:00'),

(14, 'Embedded Systems', 'embedded-systems', 4,
 'Programming the hardware side of microcontroller projects - sensors, timing and low-level control.',
 NULL,
 'active', '2025-01-05 09:00:00', '2026-08-21 09:00:00');


INSERT INTO users
    (id, full_name, initials, email, university_id, password_hash,
     department_id, role, is_admin, year_of_study, headline, bio,
     status, suspend_reason,
     learning_points, mentor_points, avg_rating, total_reviews,
     sessions_as_learner, sessions_as_mentor, learners_helped,
     last_active_at, created_at)
VALUES

(1, 'Nabila Rahman', 'NR', 'nabila.rahman@university.edu', 'UKN-2026-0142', @pw,
 1, 'dual', 0, '3rd Year', NULL,
 'I''m a Computer Science student interested in Python, databases, and data analysis. I''m currently improving my backend and problem-solving skills.',
 'active', NULL,
 412, 520, 4.8, 42, 18, 27, 0,
 '2026-09-14 09:48:00', '2026-01-12 09:00:00'),

(2, 'Rahim Ahmed', 'RA', 'rahim.ahmed@university.edu', 'UKN-2025-0874', @pw,
 1, 'dual', 0, '4th Year', 'Python & Data Analysis Mentor',
 'I help students learn Python, database design and practical data analysis through project-based sessions.',
 'active', NULL,
 0, 520, 4.9, 42, 0, 127, 84,
 '2026-09-14 08:00:00', '2025-09-18 09:00:00'),

(3, 'Imran Chowdhury', 'IC', 'imran.chowdhury@university.edu', 'UKN-2026-0201', @pw,
 4, 'learner', 0, '2nd Year', NULL,
 'Second-year English student working on academic presentation skills for seminars and case competitions. Learning by doing, not just reading about it.',
 'active', NULL,
 318, 0, NULL, 0, 14, 0, 0,
 '2026-09-13 10:00:00', '2026-02-03 09:00:00'),

(4, 'Hasan Mahmud', 'HM', 'hasan.mahmud@university.edu', 'UKN-2025-0653', @pw,
 2, 'dual', 0, '4th Year', 'Arduino & Embedded Systems Mentor',
 'I mentor students building their first Arduino projects, focusing on wiring, debouncing and practical debugging over pure theory.',
 'active', NULL,
 0, 365, 4.7, 21, 0, 52, 38,
 '2026-09-14 07:00:00', '2025-08-05 09:00:00'),

(5, 'Ayesha Rahman', 'AR', 'ayesha.rahman@university.edu', 'UKN-2025-0961', @pw,
 1, 'dual', 0, NULL, NULL, NULL,
 'active', NULL,
 184, 368, 4.7, 0, 7, 71, 46,
 '2026-09-14 09:20:00', '2025-10-22 09:00:00'),

(6, 'Sara Khan', 'SK', 'sara.khan@university.edu', 'UKN-2025-0512', @pw,
 3, 'dual', 0, NULL, NULL, NULL,
 'active', NULL,
 0, 410, 4.8, 0, 0, 84, 55,
 '2026-09-14 05:00:00', '2025-06-14 09:00:00'),

(7, 'Farhan Kabir', 'FK', 'farhan.kabir@university.edu', 'UKN-2025-0388', @pw,
 1, 'dual', 0, NULL, NULL, NULL,
 'active', NULL,
 0, 462, 4.9, 0, 0, 103, 67,
 '2026-09-14 09:40:00', '2025-04-02 09:00:00'),

(8, 'Nusrat Jahan', 'NJ', 'nusrat.jahan@university.edu', 'UKN-2025-1102', @pw,
 4, 'dual', 0, NULL, NULL, NULL,
 'active', NULL,
 0, 240, 4.6, 0, 0, 28, 21,
 '2026-09-13 10:00:00', '2025-12-09 09:00:00'),

(9, 'Mahi Noor', 'MN', 'mahi.noor@university.edu', 'UKN-2026-0057', @pw,
 1, 'learner', 0, NULL, NULL, NULL,
 'active', NULL,
 356, 0, NULL, 0, 16, 0, 0,
 '2026-09-14 08:00:00', '2026-03-30 09:00:00'),

(10, 'Tanvir Hossain', 'TH', 'tanvir.hossain@university.edu', 'UKN-2026-0089', @pw,
 2, 'learner', 0, NULL, NULL, NULL,
 'active', NULL,
 331, 0, NULL, 0, 15, 0, 0,
 '2026-09-14 04:00:00', '2026-02-18 09:00:00'),

(11, 'Sabrina Ali', 'SA', 'sabrina.ali@university.edu', 'UKN-2026-0333', @pw,
 4, 'learner', 0, NULL, NULL, NULL,
 'active', NULL,
 264, 0, NULL, 0, 12, 0, 0,
 '2026-09-11 10:00:00', '2026-05-27 09:00:00'),

(12, 'Adil Hasan', 'AH', 'adil.hasan@university.edu', 'UKN-2026-0410', @pw,
 2, 'learner', 0, NULL, NULL, NULL,
 'active', NULL,
 241, 0, NULL, 0, 10, 0, 0,
 '2026-09-10 10:00:00', '2026-07-09 09:00:00'),

(13, 'Maliha Islam', 'MI', 'maliha.islam@university.edu', 'UKN-2026-0475', @pw,
 3, 'learner', 0, NULL, NULL, NULL,
 'inactive', NULL,
 309, 0, NULL, 0, 14, 0, 0,
 '2026-08-24 10:00:00', '2026-04-21 09:00:00'),

(14, 'Tanjim Rahman', 'TR', 'tanjim.rahman@university.edu', 'UKN-2026-0512', @pw,
 3, 'learner', 0, NULL, NULL, NULL,
 'active', NULL,
 45, 0, NULL, 0, 1, 0, 0,
 '2026-09-14 09:42:00', '2026-09-13 09:00:00'),

(15, 'Kamrul Hasan', 'KH', 'kamrul.hasan@university.edu', 'UKN-2026-0498', @pw,
 2, 'dual', 0, NULL, NULL, NULL,
 'suspended', 'Policy violation',
 0, 60, 3.9, 0, 0, 4, 3,
 '2026-09-12 10:00:00', '2026-09-12 09:00:00'),

(16, 'Farhana Islam', 'FI', 'farhana.islam@university.edu', 'UKN-2026-0501', @pw,
 4, 'learner', 0, NULL, NULL, NULL,
 'suspended', 'Spam',
 20, 0, NULL, 0, 1, 0, 0,
 '2026-09-09 10:00:00', '2026-09-13 09:00:00'),

(17, 'Admin User', 'AU', 'admin@university.edu', 'UKN-ADMIN-0001', @pw,
 NULL, 'learner', 1, NULL, NULL, NULL,
 'active', NULL,
 0, 0, NULL, 0, 0, 0, 0,
 '2026-09-14 09:00:00', '2025-01-05 09:00:00');


INSERT INTO user_settings (user_id)
SELECT id FROM users;


COMMIT;


SELECT 'departments'      AS table_name, COUNT(*) AS rows_seeded FROM departments
UNION ALL SELECT 'skill_categories', COUNT(*) FROM skill_categories
UNION ALL SELECT 'skills',           COUNT(*) FROM skills
UNION ALL SELECT 'users',            COUNT(*) FROM users
UNION ALL SELECT 'user_settings',    COUNT(*) FROM user_settings;

SELECT c.name AS category, COUNT(s.id) AS skill_count
FROM skill_categories c
LEFT JOIN skills s ON s.category_id = c.id
GROUP BY c.id, c.name
ORDER BY skill_count DESC, c.name;

SELECT COALESCE(d.name, '(none)') AS department, u.role, COUNT(*) AS users
FROM users u
LEFT JOIN departments d ON d.id = u.department_id
GROUP BY d.name, u.role
ORDER BY department, u.role;
