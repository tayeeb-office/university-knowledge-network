<?php
/**
 * Shared Admin mock user dataset — required by BOTH admin/users.php and
 * admin/user-details.php so the two pages can never drift into two
 * different records for the same id (see admin/user-details.php's own
 * docblock for why that matters — the same reasoning that put this
 * behind one function instead of two copy-pasted arrays).
 *
 * Frontend-only mock data. Ids match the convention already established
 * across the user-facing app (Nabila Rahman = 1, Rahim Ahmed = 2).
 * Known characters' points/rating/sessions/department are copied from
 * their already-established pages rather than the numbers this prompt's
 * own examples suggested where those conflicted:
 *   - Imran Chowdhury: 318 Learning Points (learner-profile.php,
 *     already reused by Search/Notifications/Leaderboard), not the 378
 *     this prompt's own text suggested.
 *   - Hasan Mahmud: 365 Mentor Points / 4.7 rating / 52 sessions
 *     (mentor-profile.php, already reused by Leaderboard), not the
 *     445/4.8 this prompt's own text suggested.
 * Ayesha Rahman's Dual-Role split keeps her already-established Mentor
 * side (React, 368 pts/4.7/71 sessions, find-mentors.php) and adds a
 * plausible Learner side (UI/UX Design) that isn't established
 * anywhere else, since no earlier prompt ever gave her one.
 */
if (!function_exists('ukn_admin_mock_users')) {
    function ukn_admin_mock_users(): array
    {
        return [
            1 => [
                'name' => 'Nabila Rahman', 'initials' => 'NR', 'email' => 'nabila.rahman@university.edu',
                'universityId' => 'UKN-2026-0142', 'role' => 'learner', 'department' => 'Computer Science',
                'status' => 'active', 'joined' => 'January 12, 2026', 'joinedSort' => '2026-01-12', 'lastActive' => '12 min ago',
                'learner' => ['points' => 412, 'sessions' => 18, 'skills' => ['Python', 'MySQL', 'Data Analysis'], 'goals' => 3, 'posts' => 12],
                'mentor' => null,
            ],
            2 => [
                'name' => 'Rahim Ahmed', 'initials' => 'RA', 'email' => 'rahim.ahmed@university.edu',
                'universityId' => 'UKN-2025-0874', 'role' => 'mentor', 'department' => 'Computer Science',
                'status' => 'active', 'joined' => 'September 18, 2025', 'joinedSort' => '2025-09-18', 'lastActive' => '2 hr ago',
                'learner' => null,
                'mentor' => ['points' => 520, 'rating' => 4.9, 'sessions' => 127, 'skills' => ['Python', 'Data Analysis', 'Database Design'], 'learnersHelped' => 84],
            ],
            3 => [
                'name' => 'Imran Chowdhury', 'initials' => 'IC', 'email' => 'imran.chowdhury@university.edu',
                'universityId' => 'UKN-2026-0201', 'role' => 'learner', 'department' => 'English',
                'status' => 'active', 'joined' => 'February 3, 2026', 'joinedSort' => '2026-02-03', 'lastActive' => '1 day ago',
                'learner' => ['points' => 318, 'sessions' => 14, 'skills' => ['Public Speaking', 'Presentation'], 'goals' => 1, 'posts' => 4],
                'mentor' => null,
            ],
            4 => [
                'name' => 'Hasan Mahmud', 'initials' => 'HM', 'email' => 'hasan.mahmud@university.edu',
                'universityId' => 'UKN-2025-0653', 'role' => 'mentor', 'department' => 'Electrical Engineering',
                'status' => 'active', 'joined' => 'August 5, 2025', 'joinedSort' => '2025-08-05', 'lastActive' => '3 hr ago',
                'learner' => null,
                'mentor' => ['points' => 365, 'rating' => 4.7, 'sessions' => 52, 'skills' => ['Arduino', 'Embedded Systems', 'C++'], 'learnersHelped' => 38],
            ],
            5 => [
                'name' => 'Ayesha Rahman', 'initials' => 'AR', 'email' => 'ayesha.rahman@university.edu',
                'universityId' => 'UKN-2025-0961', 'role' => 'dual', 'department' => 'Computer Science',
                'status' => 'active', 'joined' => 'October 22, 2025', 'joinedSort' => '2025-10-22', 'lastActive' => '40 min ago',
                'learner' => ['points' => 184, 'sessions' => 7, 'skills' => ['UI/UX Design'], 'goals' => 1, 'posts' => 3],
                'mentor' => ['points' => 368, 'rating' => 4.7, 'sessions' => 71, 'skills' => ['React', 'JavaScript', 'UI/UX Design'], 'learnersHelped' => 46],
            ],
            6 => [
                'name' => 'Sara Khan', 'initials' => 'SK', 'email' => 'sara.khan@university.edu',
                'universityId' => 'UKN-2025-0512', 'role' => 'mentor', 'department' => 'Business Administration',
                'status' => 'active', 'joined' => 'June 14, 2025', 'joinedSort' => '2025-06-14', 'lastActive' => '5 hr ago',
                'learner' => null,
                'mentor' => ['points' => 410, 'rating' => 4.8, 'sessions' => 84, 'skills' => ['Public Speaking'], 'learnersHelped' => 55],
            ],
            7 => [
                'name' => 'Farhan Kabir', 'initials' => 'FK', 'email' => 'farhan.kabir@university.edu',
                'universityId' => 'UKN-2025-0388', 'role' => 'mentor', 'department' => 'Computer Science',
                'status' => 'active', 'joined' => 'April 2, 2025', 'joinedSort' => '2025-04-02', 'lastActive' => '20 min ago',
                'learner' => null,
                'mentor' => ['points' => 462, 'rating' => 4.9, 'sessions' => 103, 'skills' => ['MySQL', 'Database Design'], 'learnersHelped' => 67],
            ],
            8 => [
                'name' => 'Nusrat Jahan', 'initials' => 'NJ', 'email' => 'nusrat.jahan@university.edu',
                'universityId' => 'UKN-2025-1102', 'role' => 'mentor', 'department' => 'English',
                'status' => 'active', 'joined' => 'December 9, 2025', 'joinedSort' => '2025-12-09', 'lastActive' => '1 day ago',
                'learner' => null,
                'mentor' => ['points' => 240, 'rating' => 4.6, 'sessions' => 28, 'skills' => ['Academic Writing'], 'learnersHelped' => 21],
            ],
            9 => [
                'name' => 'Mahi Noor', 'initials' => 'MN', 'email' => 'mahi.noor@university.edu',
                'universityId' => 'UKN-2026-0057', 'role' => 'learner', 'department' => 'Computer Science',
                'status' => 'active', 'joined' => 'March 30, 2026', 'joinedSort' => '2026-03-30', 'lastActive' => '2 hr ago',
                'learner' => ['points' => 356, 'sessions' => 16, 'skills' => ['Python'], 'goals' => 2, 'posts' => 6],
                'mentor' => null,
            ],
            10 => [
                'name' => 'Tanvir Hossain', 'initials' => 'TH', 'email' => 'tanvir.hossain@university.edu',
                'universityId' => 'UKN-2026-0089', 'role' => 'learner', 'department' => 'Electrical Engineering',
                'status' => 'active', 'joined' => 'February 18, 2026', 'joinedSort' => '2026-02-18', 'lastActive' => '6 hr ago',
                'learner' => ['points' => 331, 'sessions' => 15, 'skills' => ['Data Analysis', 'Python'], 'goals' => 2, 'posts' => 5],
                'mentor' => null,
            ],
            11 => [
                'name' => 'Sabrina Ali', 'initials' => 'SA', 'email' => 'sabrina.ali@university.edu',
                'universityId' => 'UKN-2026-0333', 'role' => 'learner', 'department' => 'English',
                'status' => 'active', 'joined' => 'May 27, 2026', 'joinedSort' => '2026-05-27', 'lastActive' => '3 day ago',
                'learner' => ['points' => 264, 'sessions' => 12, 'skills' => ['Academic Writing'], 'goals' => 1, 'posts' => 2],
                'mentor' => null,
            ],
            12 => [
                'name' => 'Adil Hasan', 'initials' => 'AH', 'email' => 'adil.hasan@university.edu',
                'universityId' => 'UKN-2026-0410', 'role' => 'learner', 'department' => 'Electrical Engineering',
                'status' => 'active', 'joined' => 'July 9, 2026', 'joinedSort' => '2026-07-09', 'lastActive' => '4 day ago',
                'learner' => ['points' => 241, 'sessions' => 10, 'skills' => ['Arduino'], 'goals' => 1, 'posts' => 1],
                'mentor' => null,
            ],
            13 => [
                'name' => 'Maliha Islam', 'initials' => 'MI', 'email' => 'maliha.islam@university.edu',
                'universityId' => 'UKN-2026-0475', 'role' => 'learner', 'department' => 'Business Administration',
                'status' => 'inactive', 'joined' => 'April 21, 2026', 'joinedSort' => '2026-04-21', 'lastActive' => '3 weeks ago',
                'learner' => ['points' => 309, 'sessions' => 14, 'skills' => ['Digital Marketing'], 'goals' => 0, 'posts' => 3],
                'mentor' => null,
            ],
            14 => [
                'name' => 'Tanjim Rahman', 'initials' => 'TR', 'email' => 'tanjim.rahman@university.edu',
                'universityId' => 'UKN-2026-0512', 'role' => 'learner', 'department' => 'Business Administration',
                'status' => 'active', 'joined' => 'September 13, 2026', 'joinedSort' => '2026-09-13', 'lastActive' => '18 min ago',
                'learner' => ['points' => 45, 'sessions' => 1, 'skills' => ['Digital Marketing'], 'goals' => 1, 'posts' => 0],
                'mentor' => null,
            ],
            15 => [
                'name' => 'Kamrul Hasan', 'initials' => 'KH', 'email' => 'kamrul.hasan@university.edu',
                'universityId' => 'UKN-2026-0498', 'role' => 'mentor', 'department' => 'Electrical Engineering',
                'status' => 'suspended', 'joined' => 'September 12, 2026', 'joinedSort' => '2026-09-12', 'lastActive' => '2 day ago',
                'suspendReason' => 'Policy violation',
                'learner' => null,
                'mentor' => ['points' => 60, 'rating' => 3.9, 'sessions' => 4, 'skills' => ['Arduino'], 'learnersHelped' => 3],
            ],
            16 => [
                'name' => 'Farhana Islam', 'initials' => 'FI', 'email' => 'farhana.islam@university.edu',
                'universityId' => 'UKN-2026-0501', 'role' => 'learner', 'department' => 'English',
                'status' => 'suspended', 'joined' => 'September 13, 2026', 'joinedSort' => '2026-09-13', 'lastActive' => '5 day ago',
                'suspendReason' => 'Spam',
                'learner' => ['points' => 20, 'sessions' => 1, 'skills' => ['Academic Writing'], 'goals' => 0, 'posts' => 1],
                'mentor' => null,
            ],
        ];
    }
}
