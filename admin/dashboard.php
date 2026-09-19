<?php
/**
 * Admin Dashboard — the first real Admin Panel page. Reached by opening
 * admin/dashboard.php directly (there is no admin/?page=... router — see
 * admin/includes/header.php's own docblock for why). Frontend-only mock
 * data throughout: no database, no real analytics aggregation, no real
 * moderation actions.
 *
 * Mock figures are kept internally consistent rather than copied blindly:
 *   - Active Learners (986) + Active Mentors (214) - Dual-Role (48) =
 *     1,152 unique Total Users (the Users-by-Role chart uses the
 *     mutually-exclusive breakdown of the same three numbers: 938
 *     Learner-only + 166 Mentor-only + 48 Dual-Role = 1,152).
 *   - Session status breakdown (128 + 3,142 + 176 + 36) sums to exactly
 *     the 3,482 Total Sessions stat card.
 *   - Popular Skills reuses pages/skills/skill-details.php's exact
 *     mentor/learner counts (Python 124/340, MySQL 82/214, Data Analysis
 *     91/256, React 76/196).
 *   - Recent/Upcoming Sessions reuses the exact learner/mentor/skill/date
 *     pairings already established in
 *     pages/sessions/session-details.php (ids 101, 201, 203, 205).
 *   - "Recent Users" deliberately uses new/minor names rather than
 *     Nabila Rahman/Rahim Ahmed/Imran Chowdhury — those three already
 *     have deep established histories (hundreds of sessions, years of
 *     points) elsewhere in this project, so presenting them as having
 *     just registered would directly contradict that.
 */
require_once __DIR__ . '/../components/stat-card.php';

$adminActiveNav = 'dashboard';
$adminPageTitle = 'Admin Dashboard';
$adminPageSub = 'Monitor users, skills, sessions and community activity across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/dashboard.css'];
$adminPageScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',
    '../assets/js/admin/dashboard.js',
];

$stats = [
    ['label' => 'Total Users', 'value' => '1,152', 'icon' => 'group', 'helper' => '986 Learners · 214 Mentors · 48 Dual-Role'],
    ['label' => 'Total Skills', 'value' => '76', 'icon' => 'workspaces', 'trend' => 'Up 4 this month'],
    ['label' => 'Total Sessions', 'value' => '3,482', 'icon' => 'event', 'trend' => 'Up 12.2% this month'],
    ['label' => 'Community Posts', 'value' => '1,126', 'icon' => 'article', 'trend' => 'Up 5.1% this month'],
    ['label' => 'Pending Reports', 'value' => '8', 'icon' => 'flag', 'trend' => '2 new this week'],
];

$roleBreakdown = ['labels' => ['Learner only', 'Mentor only', 'Dual-Role'], 'values' => [938, 166, 48]];
$sessionActivity = ['labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 'values' => [42, 51, 47, 63, 58, 74, 69]];
$sessionStatus = [
    ['label' => 'Upcoming', 'value' => 128],
    ['label' => 'Completed', 'value' => 3142],
    ['label' => 'Cancelled', 'value' => 176],
    ['label' => 'Pending', 'value' => 36],
];

$recentUsers = [
    ['name' => 'Tanjim Rahman', 'initials' => 'TR', 'role' => 'Learner', 'department' => 'Business Administration', 'joined' => 'Sep 13, 2026', 'status' => 'Active'],
    ['name' => 'Farhana Islam', 'initials' => 'FI', 'role' => 'Learner', 'department' => 'English', 'joined' => 'Sep 13, 2026', 'status' => 'Active'],
    ['name' => 'Kamrul Hasan', 'initials' => 'KH', 'role' => 'Mentor', 'department' => 'Electrical Engineering', 'joined' => 'Sep 12, 2026', 'status' => 'Active'],
    ['name' => 'Sabrina Ali', 'initials' => 'SA', 'role' => 'Learner', 'department' => 'English', 'joined' => 'Sep 12, 2026', 'status' => 'Active'],
    ['name' => 'Adil Hasan', 'initials' => 'AH', 'role' => 'Learner', 'department' => 'Electrical Engineering', 'joined' => 'Sep 11, 2026', 'status' => 'Active'],
];

$recentSessions = [
    ['learner' => 'Mahi Noor', 'mentor' => 'Nabila Rahman', 'skill' => 'Python', 'date' => 'Sep 18, 2026', 'status' => 'Pending'],
    ['learner' => 'Nabila Rahman', 'mentor' => 'Rahim Ahmed', 'skill' => 'Python', 'date' => 'Sep 18, 2026', 'status' => 'Upcoming'],
    ['learner' => 'Nabila Rahman', 'mentor' => 'Rahim Ahmed', 'skill' => 'Python', 'date' => 'Sep 8, 2026', 'status' => 'Completed'],
    ['learner' => 'Nabila Rahman', 'mentor' => 'Hasan Mahmud', 'skill' => 'Arduino', 'date' => 'Aug 28, 2026', 'status' => 'Cancelled'],
];

$popularSkills = [
    ['name' => 'Python', 'learners' => 340, 'mentors' => 124],
    ['name' => 'Data Analysis', 'learners' => 256, 'mentors' => 91],
    ['name' => 'MySQL', 'learners' => 214, 'mentors' => 82],
    ['name' => 'React', 'learners' => 196, 'mentors' => 76],
];

$pendingReports = [
    ['type' => 'Post', 'reason' => 'Off-topic / spam', 'content' => 'Looking for paid assignment help', 'time' => '22 min ago'],
    ['type' => 'Comment', 'reason' => 'Inappropriate language', 'content' => 'Comment on "Best Resources for Learning MySQL Joins?"', 'time' => '1 hr ago'],
    ['type' => 'User Conduct', 'reason' => 'Repeated late cancellations', 'content' => 'Reported user: Sara Khan', 'time' => '3 hr ago'],
];

$recentActivity = [
    ['icon' => 'person_add', 'text' => 'Tanjim Rahman registered as a new Learner.', 'time' => '18 min ago'],
    ['icon' => 'event_available', 'text' => 'Rahim Ahmed completed a Python mentoring session.', 'time' => '35 min ago'],
    ['icon' => 'flag', 'text' => 'A community post was reported for review.', 'time' => '22 min ago'],
    ['icon' => 'category', 'text' => 'New skill category added: Business.', 'time' => '2 hr ago'],
    ['icon' => 'star', 'text' => 'Sara Khan received a 5-star mentoring rating.', 'time' => '4 hr ago'],
];

$statusClass = [
    'Active' => 'ukn-status-accent',
    'Upcoming' => 'ukn-status-accent',
    'Pending' => 'ukn-status-neutral',
    'Completed' => 'ukn-status-accent',
    'Cancelled' => 'ukn-status-neutral',
];

require __DIR__ . '/includes/header.php';
?>
<div class="ukn-admin-stat-grid mb-4">
  <?php foreach ($stats as $stat): ukn_stat_card($stat); endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><h2 class="ukn-h4 mb-0">Users by Role</h2></div>
      <div class="card-body">
        <div class="ukn-admin-chart-wrap ukn-admin-chart-wrap--sm">
          <canvas
            data-admin-chart="doughnut"
            data-chart-labels="<?= htmlspecialchars(json_encode($roleBreakdown['labels'])) ?>"
            data-chart-values="<?= htmlspecialchars(json_encode($roleBreakdown['values'])) ?>"
            role="img"
            aria-label="User role breakdown: 938 Learner only, 166 Mentor only, 48 Dual-Role"
          ></canvas>
        </div>
        <p class="ukn-body-sm ukn-text-muted mb-0">Each user counted once — 1,152 total. Dual-role users are shown separately, not double-counted in Learner or Mentor.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header"><h2 class="ukn-h4 mb-0">Sessions Over Time</h2></div>
      <div class="card-body">
        <div class="ukn-admin-chart-wrap">
          <canvas
            data-admin-chart="bar"
            data-chart-labels="<?= htmlspecialchars(json_encode($sessionActivity['labels'])) ?>"
            data-chart-values="<?= htmlspecialchars(json_encode($sessionActivity['values'])) ?>"
            data-chart-dataset-label="Sessions"
            role="img"
            aria-label="Sessions held per day this week: Mon 42, Tue 51, Wed 47, Thu 63, Fri 58, Sat 74, Sun 69"
          ></canvas>
        </div>
        <div class="ukn-admin-inline-stats mt-3">
          <?php foreach ($sessionStatus as $row): ?>
            <div><span class="ukn-eyebrow d-block"><?= htmlspecialchars($row['label']) ?></span><span class="fw-bold"><?= number_format($row['value']) ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h2 class="ukn-h4 mb-0">Recent Users</h2>
    <a href="users.php" class="ukn-body-sm">View All Users</a>
  </div>
  <div class="table-responsive">
    <table class="table ukn-admin-table">
      <thead>
        <tr><th scope="col">User</th><th scope="col">Role</th><th scope="col">Department</th><th scope="col">Joined</th><th scope="col">Status</th><th scope="col">Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentUsers as $i => $user): ?>
          <tr>
            <td data-label="User">
              <div class="d-flex align-items-center gap-2">
                <span class="ukn-avatar flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($user['initials']) ?></span>
                <span><?= htmlspecialchars($user['name']) ?></span>
              </div>
            </td>
            <td data-label="Role"><?= htmlspecialchars($user['role']) ?></td>
            <td data-label="Department"><?= htmlspecialchars($user['department']) ?></td>
            <td data-label="Joined"><?= htmlspecialchars($user['joined']) ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$user['status']] ?? 'ukn-status-neutral' ?>"><?= htmlspecialchars($user['status']) ?></span></td>
            <td data-label="Action"><a href="user-details.php?id=<?= $i + 1 ?>" class="btn btn-outline-secondary btn-sm">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="ukn-h4 mb-0">Recent &amp; Upcoming Sessions</h2>
        <a href="sessions.php" class="ukn-body-sm">View All Sessions</a>
      </div>
      <div class="table-responsive">
        <table class="table ukn-admin-table">
          <thead>
            <tr><th scope="col">Learner</th><th scope="col">Mentor</th><th scope="col">Skill</th><th scope="col">Date</th><th scope="col">Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentSessions as $session): ?>
              <tr>
                <td data-label="Learner"><?= htmlspecialchars($session['learner']) ?></td>
                <td data-label="Mentor"><?= htmlspecialchars($session['mentor']) ?></td>
                <td data-label="Skill"><?= htmlspecialchars($session['skill']) ?></td>
                <td data-label="Date"><?= htmlspecialchars($session['date']) ?></td>
                <td data-label="Status"><span class="ukn-status <?= $statusClass[$session['status']] ?? 'ukn-status-neutral' ?>"><?= htmlspecialchars($session['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="ukn-h4 mb-0">Popular Skills</h2>
        <a href="skills.php" class="ukn-body-sm">Manage Skills</a>
      </div>
      <div class="card-body">
        <?php foreach ($popularSkills as $skill): ?>
          <div class="ukn-admin-row">
            <span class="fw-bold flex-fill ukn-min-w-0 ukn-truncate"><?= htmlspecialchars($skill['name']) ?></span>
            <span class="ukn-body-sm ukn-text-muted flex-shrink-0"><?= number_format($skill['learners']) ?> Learners</span>
            <span class="ukn-body-sm ukn-text-muted flex-shrink-0"><?= number_format($skill['mentors']) ?> Mentors</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="ukn-h4 mb-0">Pending Reports</h2>
        <a href="reports.php" class="ukn-body-sm">View All Reports</a>
      </div>
      <div class="card-body">
        <?php foreach ($pendingReports as $report): ?>
          <div class="ukn-admin-row ukn-admin-row--wrap">
            <div class="flex-fill ukn-min-w-0">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="ukn-role-chip"><?= htmlspecialchars($report['type']) ?></span>
                <span class="fw-bold"><?= htmlspecialchars($report['reason']) ?></span>
              </div>
              <div class="ukn-body-sm ukn-text-muted ukn-truncate"><?= htmlspecialchars($report['content']) ?></div>
              <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($report['time']) ?> &middot; <span class="ukn-status ukn-status-neutral">Pending</span></div>
            </div>
            <a href="reports.php" class="btn btn-outline-secondary btn-sm flex-shrink-0">Review</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h2 class="ukn-h4 mb-0">Recent Activity</h2></div>
      <div class="card-body">
        <?php foreach ($recentActivity as $activity): ?>
          <div class="ukn-admin-row">
            <span class="ms ukn-text-accent flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($activity['icon']) ?></span>
            <span class="flex-fill ukn-min-w-0"><?= htmlspecialchars($activity['text']) ?></span>
            <span class="ukn-body-sm ukn-text-muted flex-shrink-0"><?= htmlspecialchars($activity['time']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2 class="ukn-h4 mb-0">Quick Actions</h2></div>
  <div class="card-body d-flex flex-wrap gap-2">
    <a href="users.php" class="btn btn-outline-secondary btn-sm"><span class="ms" aria-hidden="true">group</span> Manage Users</a>
    <a href="skills.php" class="btn btn-outline-secondary btn-sm"><span class="ms" aria-hidden="true">workspaces</span> Manage Skills</a>
    <a href="reports.php" class="btn btn-outline-secondary btn-sm"><span class="ms" aria-hidden="true">flag</span> Review Reports</a>
    <a href="sessions.php" class="btn btn-outline-secondary btn-sm"><span class="ms" aria-hidden="true">event</span> View Sessions</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
