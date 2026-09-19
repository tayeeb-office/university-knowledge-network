<?php
/**
 * My Profile — main center content only. Routed via
 * index.php?page=my-profile (see index.php's $routes map). The header,
 * left sidebar, contextual right sidebar ($rightSidebarContext = 'profile',
 * set by index.php's $sidebarContextByPage map — already role-aware, see
 * includes/right-sidebar.php's 'profile' case) and footer come from the
 * shell — not from here.
 *
 * This is the signed-in mock user's own profile, so it adapts to whichever
 * role is currently active (includes/left-sidebar.php's same $currentUser
 * role state — not a second role system) rather than existing as two
 * separate learner/mentor profile pages.
 *
 * Frontend-only mock data throughout — no real profile persistence, no
 * database, no backend of any kind.
 */
require_once __DIR__ . '/../../components/stat-card.php';
require_once __DIR__ . '/../../components/goal-card.php';
require_once __DIR__ . '/../../components/post-card.php';

$activeRole = !empty($currentUser['dualRole']) ? ($currentUser['activeRole'] ?? 'learner') : ($currentUser['role'] ?? 'learner');
$isMentor = $activeRole === 'mentor';

$department = 'Computer Science';
$yearOfStudy = $isMentor ? '4th Year' : '3rd Year';
$shortBio = $isMentor
    ? 'Mentor for Python, Machine Learning and Data Analysis.'
    : 'Interested in Python, databases and data analysis.';
$aboutBio = $isMentor
    ? "I'm a Computer Science student who mentors Python, database design and data analysis through project-based sessions. I enjoy helping other students get unstuck on real assignments rather than toy examples."
    : "I'm a Computer Science student interested in Python, databases, and data analysis. I'm currently improving my backend and problem-solving skills.";

$profileStats = $isMentor
    ? [
        ['label' => 'Mentor Points', 'value' => '520', 'icon' => 'military_tech', 'trend' => '+20 this month'],
        ['label' => 'Average Rating', 'value' => '4.8', 'icon' => 'star'],
        ['label' => 'Completed Sessions', 'value' => '27', 'icon' => 'event_available'],
        ['label' => 'Teaching Skills', 'value' => '3', 'icon' => 'school'],
    ]
    : [
        ['label' => 'Learning Points', 'value' => '412', 'icon' => 'military_tech', 'trend' => '+64 this month'],
        ['label' => 'Completed Sessions', 'value' => '18', 'icon' => 'event_available'],
        ['label' => 'Skills Learning', 'value' => '4', 'icon' => 'workspaces'],
        ['label' => 'Current Goals', 'value' => '3', 'icon' => 'flag'],
    ];

$skillsSectionTitle = $isMentor ? 'Teaching Skills' : 'Learning Skills';
$skillsSectionCta = $isMentor ? 'Manage Teaching Skills' : 'Manage Learning Skills';
$skillsSectionHref = ukn_route_href($isMentor ? 'teaching-skills' : 'learning-skills');
$skills = $isMentor
    ? ['Python', 'Database Design', 'Data Analysis']
    : ['Python', 'MySQL', 'Data Analysis', 'Public Speaking'];

$goals = [
    ['title' => 'Learn Python for Data Analysis', 'skill' => 'Python', 'progress' => 65, 'targetDate' => 'December 2026'],
    ['title' => 'Improve Database Design Skills', 'skill' => 'DBMS', 'progress' => 40, 'targetDate' => 'November 2026'],
];

$activity = $isMentor
    ? [
        ['icon' => 'event_available', 'text' => 'Completed a Python session with Nabila Rahman'],
        ['icon' => 'military_tech', 'text' => 'Earned +20 Mentor Points'],
        ['icon' => 'star', 'text' => 'Received a 5-star rating from Sara Khan'],
        ['icon' => 'inbox', 'text' => 'Accepted a new session request from Tanvir Hossain'],
    ]
    : [
        ['icon' => 'event_available', 'text' => 'Completed a Python session with Rahim Ahmed'],
        ['icon' => 'military_tech', 'text' => 'Earned +10 Learning Points'],
        ['icon' => 'bookmark', 'text' => 'Saved "Database Normalization Guide"'],
        ['icon' => 'workspaces', 'text' => 'Added Data Analysis to Learning Skills'],
    ];

$myPost = [
    'id' => 1, 'href' => 'index.php?page=post-details&id=1',
    'author' => $currentUser['name'] ?? 'Nabila Rahman', 'initials' => $currentUser['initials'] ?? 'NR',
    'role' => 'Learner', 'department' => $department, 'time' => '12 min ago',
    'title' => 'Need Help Understanding Database Normalization',
    'excerpt' => "I get 1NF and 2NF but 3NF stops making sense once foreign keys are involved. Does anyone have a simple example that isn't the classic student/course table?",
    'tags' => ['Database', 'MySQL', 'DBMS'], 'score' => 24, 'comments' => 8, 'isOwner' => true,
];
?>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start gap-3 flex-wrap">
      <span class="ukn-avatar ukn-avatar-xl flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($currentUser['initials'] ?? 'NR') ?></span>
      <div class="flex-fill ukn-min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h1 class="ukn-h3 mb-0"><?= htmlspecialchars($currentUser['name'] ?? 'Nabila Rahman') ?></h1>
          <span class="ukn-status ukn-status-accent"><?= htmlspecialchars(ucfirst($activeRole)) ?></span>
        </div>
        <div class="ukn-body-sm mt-1"><?= htmlspecialchars($department) ?> &middot; <?= htmlspecialchars($yearOfStudy) ?></div>
        <p class="ukn-body-sm mt-2 mb-0"><?= htmlspecialchars($shortBio) ?></p>
      </div>
      <div class="flex-shrink-0">
        <a href="<?= htmlspecialchars(ukn_route_href('edit-profile')) ?>" class="btn btn-outline-secondary btn-sm">Edit Profile</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($profileStats as $stat): ?>
    <div class="col-6 col-lg-3"><?php ukn_stat_card($stat); ?></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <h2 class="ukn-h4">About</h2>
        <p class="ukn-body mb-0"><?= htmlspecialchars($aboutBio) ?></p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="ukn-h4 mb-0"><?= htmlspecialchars($skillsSectionTitle) ?></h2>
          <a href="<?= htmlspecialchars($skillsSectionHref) ?>" class="ukn-body-sm"><?= htmlspecialchars($skillsSectionCta) ?></a>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <?php foreach ($skills as $skill): ?>
            <span class="ukn-tag-skill"><?= htmlspecialchars($skill) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!$isMentor): ?>
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">Current Goals</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('learning-goals')) ?>" class="ukn-body-sm">View All Goals</a>
  </div>
  <?php foreach ($goals as $goal): ukn_goal_card($goal); endforeach; ?>
</div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <h2 class="ukn-h4">Recent Activity</h2>
    <?php foreach ($activity as $item): ?>
      <div class="ukn-transaction-row">
        <span class="ms ukn-text-accent" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
        <div class="flex-fill ukn-min-w-0 ukn-nav-text"><?= htmlspecialchars($item['text']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="ukn-h4 mb-0">My Posts</h2>
    <a href="<?= htmlspecialchars(ukn_route_href('my-posts')) ?>" class="ukn-body-sm">View My Posts</a>
  </div>
  <?php ukn_post_card($myPost); ?>
</div>
