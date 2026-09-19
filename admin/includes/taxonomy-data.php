<?php
/**
 * Shared Admin mock dataset for Departments, Skill Categories and Skills
 * — required by admin/departments.php, admin/skill-categories.php and
 * admin/skills.php so all three agree on the same names/counts (Skills'
 * own category selector reuses ukn_admin_mock_categories() directly
 * rather than keeping a second hardcoded category list, and each
 * category's displayed Skill Count is COUNTED from this same skills
 * array, never a separately hand-typed number).
 *
 * Skill ids/mentor/learner counts are copied verbatim from the already-
 * established pages/skills/skill-details.php (ids 1-10) and
 * pages/network/skill-network.php (JavaScript=11, Presentation Skills=12,
 * Embedded Systems=14 — id 13/Machine Learning is deliberately skipped
 * here: Skill Network treats it as an optional extra node, but this
 * prompt's own 7-category/13-skill breakdown never includes it, so
 * Admin's catalogue simply doesn't have a row for it yet rather than
 * inventing a 14th skill that contradicts the given category counts).
 *
 * Frontend-only mock data. No database, no real taxonomy persistence —
 * Add/Edit/Activate/Deactivate in the three admin pages only ever mutate
 * this same in-memory list for the current page view (assets/js/admin/
 * skills.js), and everything resets on refresh.
 */
if (!function_exists('ukn_admin_mock_departments')) {
    function ukn_admin_mock_departments(): array
    {
        return [
            1 => ['name' => 'Computer Science', 'code' => 'CSE', 'users' => 534, 'learners' => 421, 'mentors' => 96, 'status' => 'active'],
            2 => ['name' => 'Electrical Engineering', 'code' => 'EEE', 'users' => 248, 'learners' => 199, 'mentors' => 42, 'status' => 'active'],
            3 => ['name' => 'Business Administration', 'code' => 'BBA', 'users' => 196, 'learners' => 161, 'mentors' => 31, 'status' => 'active'],
            4 => ['name' => 'English', 'code' => 'ENG', 'users' => 154, 'learners' => 128, 'mentors' => 22, 'status' => 'active'],
            5 => ['name' => 'Economics', 'code' => 'ECO', 'users' => 116, 'learners' => 91, 'mentors' => 23, 'status' => 'active'],
        ];
    }
}

if (!function_exists('ukn_admin_mock_categories')) {
    function ukn_admin_mock_categories(): array
    {
        return [
            1 => ['name' => 'Programming', 'description' => 'Programming languages and software development skills.', 'status' => 'active', 'updated' => 'Sep 10, 2026'],
            2 => ['name' => 'Data', 'description' => 'Database, analytics and data-focused skills.', 'status' => 'active', 'updated' => 'Sep 9, 2026'],
            3 => ['name' => 'Design', 'description' => 'Interface, experience and visual design skills.', 'status' => 'active', 'updated' => 'Aug 28, 2026'],
            4 => ['name' => 'Engineering', 'description' => 'Hardware, electronics and applied engineering skills.', 'status' => 'active', 'updated' => 'Aug 20, 2026'],
            5 => ['name' => 'Communication', 'description' => 'Speaking, presenting and interpersonal communication skills.', 'status' => 'active', 'updated' => 'Sep 1, 2026'],
            6 => ['name' => 'Academic', 'description' => 'Academic research and writing skills.', 'status' => 'active', 'updated' => 'Aug 15, 2026'],
            7 => ['name' => 'Business', 'description' => 'Marketing, strategy and business-facing skills.', 'status' => 'active', 'updated' => 'Aug 12, 2026'],
        ];
    }
}

if (!function_exists('ukn_admin_mock_skills')) {
    function ukn_admin_mock_skills(): array
    {
        return [
            1 => ['name' => 'Python', 'categoryId' => 1, 'description' => 'A versatile programming language used for software development, automation and data analysis.', 'mentors' => 124, 'learners' => 340, 'status' => 'active', 'updated' => 'Sep 12, 2026'],
            2 => ['name' => 'MySQL', 'categoryId' => 2, 'description' => 'A widely used relational database system for storing and querying structured data.', 'mentors' => 82, 'learners' => 214, 'status' => 'active', 'updated' => 'Sep 10, 2026'],
            3 => ['name' => 'React', 'categoryId' => 1, 'description' => 'A JavaScript library for building interactive user interfaces and single-page applications.', 'mentors' => 76, 'learners' => 196, 'status' => 'active', 'updated' => 'Sep 8, 2026'],
            4 => ['name' => 'UI/UX Design', 'categoryId' => 3, 'description' => 'Designing interfaces and experiences that are functional, accessible and easy to use.', 'mentors' => 68, 'learners' => 173, 'status' => 'active', 'updated' => 'Sep 6, 2026'],
            5 => ['name' => 'Data Analysis', 'categoryId' => 2, 'description' => 'Turning raw data into insight using spreadsheets, Python and statistical thinking.', 'mentors' => 91, 'learners' => 256, 'status' => 'active', 'updated' => 'Sep 11, 2026'],
            6 => ['name' => 'Public Speaking', 'categoryId' => 5, 'description' => 'Structuring and delivering talks and presentations with confidence.', 'mentors' => 54, 'learners' => 161, 'status' => 'active', 'updated' => 'Sep 2, 2026'],
            7 => ['name' => 'Database Design', 'categoryId' => 2, 'description' => 'Modeling data relationships and normalizing schemas for reliable, efficient systems.', 'mentors' => 63, 'learners' => 147, 'status' => 'active', 'updated' => 'Sep 9, 2026'],
            8 => ['name' => 'Arduino', 'categoryId' => 4, 'description' => 'Building and programming microcontroller projects, from sensors to simple robotics.', 'mentors' => 47, 'learners' => 128, 'status' => 'active', 'updated' => 'Aug 22, 2026'],
            9 => ['name' => 'Academic Writing', 'categoryId' => 6, 'description' => 'Structuring essays, reports and citations for university-level coursework.', 'mentors' => 39, 'learners' => 104, 'status' => 'active', 'updated' => 'Aug 15, 2026'],
            10 => ['name' => 'Digital Marketing', 'categoryId' => 7, 'description' => 'Reaching an audience through social media, content and basic campaign analytics.', 'mentors' => 44, 'learners' => 121, 'status' => 'active', 'updated' => 'Aug 12, 2026'],
            11 => ['name' => 'JavaScript', 'categoryId' => 1, 'description' => 'The core scripting language of the web, used alongside React for interactive interfaces.', 'mentors' => 58, 'learners' => 162, 'status' => 'active', 'updated' => 'Sep 7, 2026'],
            12 => ['name' => 'Presentation Skills', 'categoryId' => 5, 'description' => "Turning a talk's structure into a confident, well-paced delivery in front of an audience.", 'mentors' => 31, 'learners' => 89, 'status' => 'active', 'updated' => 'Sep 3, 2026'],
            14 => ['name' => 'Embedded Systems', 'categoryId' => 4, 'description' => 'Programming the hardware side of microcontroller projects — sensors, timing and low-level control.', 'mentors' => 22, 'learners' => 58, 'status' => 'active', 'updated' => 'Aug 21, 2026'],
        ];
    }
}
