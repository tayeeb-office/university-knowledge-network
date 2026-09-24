<?php
require_once __DIR__ . '/components/post-card.php';
require_once __DIR__ . '/components/mentor-card.php';
require_once __DIR__ . '/components/learner-card.php';
require_once __DIR__ . '/components/skill-card.php';
require_once __DIR__ . '/components/session-card.php';
require_once __DIR__ . '/components/stat-card.php';
require_once __DIR__ . '/components/goal-card.php';
require_once __DIR__ . '/components/point-transaction.php';
require_once __DIR__ . '/components/rating-item.php';
require_once __DIR__ . '/components/notification-item.php';
require_once __DIR__ . '/components/leaderboard-row.php';
require_once __DIR__ . '/components/search-result-item.php';
require_once __DIR__ . '/components/loading-state.php';
require_once __DIR__ . '/components/empty-state.php';
require_once __DIR__ . '/components/error-state.php';
require_once __DIR__ . '/components/success-state.php';
$currentUser = [
    'loggedIn' => true, 'role' => 'learner', 'dualRole' => true, 'activeRole' => 'learner',
    'name' => 'Nabila Rahman', 'initials' => 'NR', 'meta' => 'Learner · Computer Science',
];
$activeNav = 'home';
$showRightSidebar = false;
$pageTitle = '[DEV] Component Preview · University Knowledge Network';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20,300,0,0" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/typography.css">
  <link rel="stylesheet" href="assets/css/layout.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/forms.css">
  <link rel="stylesheet" href="assets/css/grunge.css">
  <link rel="stylesheet" href="assets/css/utilities.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
  <?php include __DIR__ . '/includes/header.php'; ?>
  <div class="alert alert-warning mb-4">
    <span class="ms" aria-hidden="true">construction</span>
    <div><strong>Development-only preview.</strong> Not a real page, not in the route whitelist, not linked from navigation. Delete once real pages exercise these components directly.</div>
  </div>
  <div class="ukn-page-header">
    <div>
      <h1>Component Library Preview</h1>
      <p class="ukn-page-header__sub">Every reusable card/state component, in its documented states, for visual QA only.</p>
    </div>
  </div>
  <h2 class="ukn-eyebrow mb-3">01 — Post Card (default / voted / saved)</h2>
  <?php
  ukn_post_card([
      'id' => 1, 'href' => 'index.php?page=post-details',
      'author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'department' => 'Computer Science', 'time' => '5 hours ago',
      'title' => 'Need Help Understanding Database Normalization',
      'excerpt' => 'I get 1NF and 2NF but 3NF stops making sense the moment a table has two candidate keys. Anyone mentoring on this before Thursday?',
      'tags' => ['MySQL', 'Database', '3NF'], 'score' => 96, 'voteState' => 0, 'comments' => 24, 'saved' => false,
  ]);
  ukn_post_card([
      'id' => 2, 'href' => 'index.php?page=post-details',
      'author' => 'Tanvir Hossain', 'initials' => 'TH', 'role' => 'Learner', 'department' => 'Computer Science', 'time' => '1 day ago',
      'title' => 'Things I Learned While Building My First React Project',
      'excerpt' => 'Component state was the part that broke my brain. Six mistakes, in the order I made them.',
      'tags' => ['React', 'JavaScript'], 'score' => 215, 'voteState' => 1, 'comments' => 38, 'saved' => true,
  ]);
  ?>
  <h2 class="ukn-eyebrow mb-3 mt-4">02 — Mentor Card (normal / recommendation)</h2>
  <div class="ukn-grid-2">
    <?php
    ukn_mentor_card([
        'name' => 'Rahim Ahmed', 'initials' => 'RA', 'department' => 'Computer Science', 'primarySkill' => 'Python',
        'otherSkills' => ['Machine Learning', 'Data Structures'], 'rating' => 4.9, 'sessions' => 127, 'points' => 520,
        'availability' => 'Available Wed, Sat evenings', 'profileHref' => 'index.php?page=mentor-profile',
    ]);
    ukn_mentor_card([
        'name' => 'Sara Khan', 'initials' => 'SK', 'department' => 'Business Administration', 'primarySkill' => 'UI/UX Design',
        'otherSkills' => ['Figma', 'User Research'], 'rating' => 4.8, 'sessions' => 47, 'points' => 410,
        'availability' => 'Available Fri mornings', 'profileHref' => 'index.php?page=mentor-profile',
        'match' => 98, 'matchLabel' => 'Best Match',
    ], ['variant' => 'recommendation']);
    ?>
  </div>
  <h2 class="ukn-eyebrow mb-3 mt-4">03 — Learner Card (normal / follow state)</h2>
  <div class="ukn-grid-2">
    <?php
    ukn_learner_card([
        'name' => 'Nabila Rahman', 'initials' => 'NR', 'department' => 'Computer Science',
        'skills' => ['React', 'JavaScript'], 'points' => 412, 'sessions' => 18, 'profileHref' => 'index.php?page=learner-profile',
    ]);
    ukn_learner_card([
        'name' => 'Sara Khan', 'initials' => 'SK', 'department' => 'Business Administration',
        'skills' => ['UI/UX Design'], 'points' => 366, 'sessions' => 12, 'profileHref' => 'index.php?page=learner-profile',
        'following' => false,
    ]);
    ?>
  </div>
  <h2 class="ukn-eyebrow mb-3 mt-4">04 — Skill Card (normal / selected-action state)</h2>
  <div class="ukn-grid-3">
    <?php
    ukn_skill_card(['name' => 'Python', 'category' => 'Programming', 'mentors' => 124, 'learners' => 340, 'href' => 'index.php?page=skill-details']);
    ukn_skill_card(['name' => 'React', 'category' => 'Programming', 'mentors' => 61, 'learners' => 210, 'href' => 'index.php?page=skill-details', 'learningState' => 'add']);
    ukn_skill_card(['name' => 'UI/UX Design', 'category' => 'Design', 'mentors' => 38, 'learners' => 155, 'href' => 'index.php?page=skill-details', 'learningState' => 'added', 'teachingState' => 'add']);
    ?>
  </div>
  <h2 class="ukn-eyebrow mb-3 mt-4">05 — Session Card (pending / upcoming / completed / cancelled)</h2>
  <?php
  ukn_session_card(['counterparty' => 'Sara Khan', 'counterpartyInitials' => 'SK', 'skill' => 'Python', 'day' => '18', 'month' => 'Sep', 'time' => 'Thu 5:00pm', 'duration' => '60 min', 'status' => 'pending', 'message' => 'Could we go over pandas merge and groupby before Friday\'s deadline?', 'detailsHref' => 'index.php?page=session-details']);
  ukn_session_card(['counterparty' => 'Rahim Ahmed', 'counterpartyInitials' => 'RA', 'skill' => 'Python', 'day' => '17', 'month' => 'Sep', 'time' => 'Wed 6:00pm', 'duration' => '60 min', 'status' => 'upcoming', 'detailsHref' => 'index.php?page=session-details']);
  ukn_session_card(['counterparty' => 'Tanvir Hossain', 'counterpartyInitials' => 'TH', 'skill' => 'JavaScript', 'day' => '09', 'month' => 'Sep', 'time' => 'Tue 4:00pm', 'duration' => '60 min', 'status' => 'completed', 'detailsHref' => 'index.php?page=session-details']);
  ukn_session_card(['counterparty' => 'Hasan Mahmud', 'counterpartyInitials' => 'HM', 'skill' => 'Data Analysis', 'day' => '05', 'month' => 'Sep', 'time' => 'Fri 3:00pm', 'duration' => '45 min', 'status' => 'cancelled', 'detailsHref' => 'index.php?page=session-details']);
  ?>
  <h2 class="ukn-eyebrow mb-3 mt-4">06 — Stat Card (multiple value types)</h2>
  <div class="ukn-grid-4 mb-4">
    <?php
    ukn_stat_card(['label' => 'Learning Points', 'value' => '412', 'icon' => 'military_tech', 'trend' => '+64 this month']);
    ukn_stat_card(['label' => 'Upcoming Sessions', 'value' => '2', 'icon' => 'event']);
    ukn_stat_card(['label' => 'Average Rating', 'value' => '4.9', 'icon' => 'star', 'helper' => 'from 57 reviews']);
    ukn_stat_card(['label' => 'Pending Requests', 'value' => '3', 'icon' => 'inbox']);
    ?>
  </div>
  <h2 class="ukn-eyebrow mb-3 mt-4">07 — Learning Goal Card (progress)</h2>
  <?php
  ukn_goal_card(['title' => 'Learn Python for Data Analysis', 'skill' => 'Python', 'progress' => 65, 'targetDate' => 'December 2026', 'status' => 'in-progress']);
  ukn_goal_card(['title' => 'Build a React portfolio project', 'skill' => 'React', 'progress' => 100, 'targetDate' => 'August 2026', 'status' => 'completed']);
  ?>
  <h2 class="ukn-eyebrow mb-3 mt-4">08 — Point Transaction (positive display)</h2>
  <div class="card mb-4"><div class="card-body">
    <?php
    ukn_point_transaction(['amount' => 10, 'type' => 'Learning Points', 'reason' => 'Completed Python Session', 'session' => 'S-1039', 'date' => 'Sep 12', 'icon' => 'event_available']);
    ukn_point_transaction(['amount' => 12, 'type' => 'Community Points', 'reason' => 'Post upvoted ×12', 'date' => 'Sep 10', 'icon' => 'arrow_upward']);
    ukn_point_transaction(['amount' => 40, 'type' => 'Learning Points', 'reason' => 'Goal completed', 'date' => 'Sep 5', 'icon' => 'flag']);
    ?>
  </div></div>
  <h2 class="ukn-eyebrow mb-3 mt-4">09 — Rating Item (stars/review)</h2>
  <?php
  ukn_rating_item(['reviewer' => 'Sara Khan', 'initials' => 'SK', 'overall' => 5, 'teaching' => 5, 'communication' => 5, 'helpfulness' => 4, 'review' => 'Explained pandas merges so clearly I finally understood the assignment.', 'date' => 'Sep 10', 'skill' => 'Python']);
  ?>
  <h2 class="ukn-eyebrow mb-3 mt-4">10 — Notification Item (read / unread)</h2>
  <div class="card mb-4">
    <?php
    ukn_notification_item(['icon' => 'event_available', 'text' => 'Rahim Ahmed accepted your Python session request.', 'time' => '2 min ago', 'kind' => 'Session', 'unread' => true]);
    ukn_notification_item(['icon' => 'check_circle', 'text' => 'Your JavaScript mentoring session has been completed.', 'time' => 'Yesterday', 'kind' => 'Session', 'unread' => false]);
    ?>
  </div>
  <h2 class="ukn-eyebrow mb-3 mt-4">11 — Leaderboard Row (normal + Top 3)</h2>
  <?php
  ukn_leaderboard_row(['rank' => 1, 'name' => 'Rahim Ahmed', 'initials' => 'RA', 'category' => 'Computer Science', 'points' => 520, 'rating' => 4.9, 'sessions' => 127]);
  ukn_leaderboard_row(['rank' => 2, 'name' => 'Sara Khan', 'initials' => 'SK', 'category' => 'Business Administration', 'points' => 410]);
  ukn_leaderboard_row(['rank' => 3, 'name' => 'Hasan Mahmud', 'initials' => 'HM', 'category' => 'Electrical Engineering', 'points' => 365]);
  ukn_leaderboard_row(['rank' => 12, 'name' => 'Nabila Rahman', 'initials' => 'NR', 'category' => 'Computer Science', 'points' => 412]);
  ?>

  <h2 class="ukn-eyebrow mb-3 mt-4">12 — Search Result Item (Skill / Mentor / Learner / Post)</h2>
  <?php
  ukn_search_result_item(['type' => 'skill', 'title' => 'Python', 'meta' => '124 mentors · 340 learners', 'href' => 'index.php?page=skill-details']);
  ukn_search_result_item(['type' => 'mentor', 'title' => 'Rahim Ahmed', 'meta' => 'Computer Science · teaches Python', 'href' => 'index.php?page=mentor-profile']);
  ukn_search_result_item(['type' => 'learner', 'title' => 'Sara Khan', 'meta' => 'Business Administration', 'href' => 'index.php?page=learner-profile']);
  ukn_search_result_item(['type' => 'post', 'title' => 'Need Help Understanding Database Normalization', 'meta' => 'Nabila Rahman · 96 points · 24 comments', 'href' => 'index.php?page=post-details']);
  ?>
  <h2 class="ukn-eyebrow mb-3 mt-4">13-16 — UI States: Loading / Empty / Error / Success</h2>
  <?php ukn_loading_spinner('Loading…'); ?>
  <div class="ukn-grid-2 mt-3">
    <?php
    ukn_loading_skeleton('Loading mentors…');
    ukn_empty_state(['icon' => 'person_search', 'title' => 'No mentors found', 'message' => 'Try removing the availability filter, or ask in the community feed.', 'action' => ['label' => 'Clear filters', 'href' => '#'], 'dashed' => true]);
    ukn_error_state(['title' => 'Unable to load mentors', 'message' => 'The feed did not respond. Your draft posts are safe.', 'action' => ['label' => 'Retry', 'href' => '#']]);
    ukn_success_state(['message' => 'Session request sent successfully.', 'detail' => 'Rahim usually replies within a day. You will get a notification either way.']);
    ?>
  </div>
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
  <script src="assets/js/core/theme.js"></script>
  <script src="assets/js/core/dropdown.js"></script>
  <script src="assets/js/core/role-switch.js"></script>
  <script src="assets/js/core/validation.js"></script>
  <script src="assets/js/core/toast.js"></script>
  <script src="assets/js/core/modal.js"></script>
  <script src="assets/js/core/sidebar.js"></script>
  <script src="assets/js/core/mobile-nav.js"></script>
</body>
</html>