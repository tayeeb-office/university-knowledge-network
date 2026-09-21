<?php
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/format.php';
$adminActiveNav = 'users';

$requestedId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : null;
$user = null;
$sessionsPreview = [];
$activity = [];
$userDetailsDbError = false;

if ($requestedId !== null) {
    try {
        $pdo = getDatabaseConnection();

        $userStmt = $pdo->prepare(
            "SELECT u.id, u.full_name AS name, u.initials, u.email, u.university_id AS universityId,
                    u.role, u.status, u.suspend_reason AS suspendReason, u.created_at, u.last_active_at,
                    u.learning_points, u.mentor_points, u.avg_rating, u.sessions_as_learner,
                    u.sessions_as_mentor, u.learners_helped, d.name AS department
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.id = ?"
        );
        $userStmt->execute([$requestedId]);
        $user = $userStmt->fetch();

        if ($user !== false) {
            $user['department'] = (string) ($user['department'] ?? '');
            $user['joined'] = date('F j, Y', strtotime($user['created_at']));
            $user['lastActive'] = $user['last_active_at'] ? ukn_time_ago($user['last_active_at']) : 'Never';
            $isLearnerRole = in_array($user['role'], ['learner', 'dual'], true);
            $isMentorRole = in_array($user['role'], ['mentor', 'dual'], true);

            if ($isLearnerRole) {
                $skillsStmt = $pdo->prepare(
                    "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
                     WHERE us.user_id = ? AND us.skill_type = 'learning' ORDER BY s.name"
                );
                $skillsStmt->execute([$requestedId]);
                $learnerSkills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

                $goalsStmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM learning_goals WHERE user_id = ? AND status = 'in-progress'"
                );
                $goalsStmt->execute([$requestedId]);

                $postsStmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
                $postsStmt->execute([$requestedId]);

                $user['learner'] = [
                    'points' => (int) $user['learning_points'],
                    'sessions' => (int) $user['sessions_as_learner'],
                    'goals' => (int) $goalsStmt->fetchColumn(),
                    'posts' => (int) $postsStmt->fetchColumn(),
                    'skills' => $learnerSkills,
                ];
            }

            if ($isMentorRole) {
                $skillsStmt = $pdo->prepare(
                    "SELECT s.name FROM user_skills us JOIN skills s ON s.id = us.skill_id
                     WHERE us.user_id = ? AND us.skill_type = 'teaching' ORDER BY s.name"
                );
                $skillsStmt->execute([$requestedId]);
                $mentorSkills = $skillsStmt->fetchAll(PDO::FETCH_COLUMN);

                $user['mentor'] = [
                    'points' => (int) $user['mentor_points'],
                    'rating' => $user['avg_rating'] !== null ? (float) $user['avg_rating'] : 0.0,
                    'sessions' => (int) $user['sessions_as_mentor'],
                    'learnersHelped' => (int) $user['learners_helped'],
                    'skills' => $mentorSkills,
                ];
            }

            $sessionsStmt = $pdo->prepare(
                "SELECT ms.status, ms.scheduled_date, sk.name AS skill,
                        CASE WHEN ms.learner_id = ? THEN 'Learner' ELSE 'Mentor' END AS role_in_session,
                        CASE WHEN ms.learner_id = ? THEN mu.full_name ELSE lu.full_name END AS participant
                 FROM mentoring_sessions ms
                 JOIN skills sk ON sk.id = ms.skill_id
                 JOIN users lu ON lu.id = ms.learner_id
                 JOIN users mu ON mu.id = ms.mentor_id
                 WHERE ms.learner_id = ? OR ms.mentor_id = ?
                 ORDER BY ms.scheduled_date DESC, ms.scheduled_time DESC
                 LIMIT 5"
            );
            $sessionsStmt->execute([$requestedId, $requestedId, $requestedId, $requestedId]);
            $sessionStatusLabels = [
                'pending' => 'Pending', 'accepted' => 'Upcoming', 'completed' => 'Completed',
                'rejected' => 'Rejected', 'cancelled' => 'Cancelled',
            ];
            $sessionsPreview = array_map(static function (array $row) use ($sessionStatusLabels): array {
                return [
                    'participant' => $row['participant'],
                    'skill' => $row['skill'],
                    'date' => date('M j, Y', strtotime($row['scheduled_date'])),
                    'role' => $row['role_in_session'],
                    'status' => $sessionStatusLabels[$row['status']] ?? ucfirst($row['status']),
                ];
            }, $sessionsStmt->fetchAll());

            // No activity/audit-log table exists in the schema (see
            // DATABASE_READ_INTEGRATION_PLAN.md §2.2 / batch 7). Reusing the real points
            // ledger's human-readable `reason` text as a defensible activity feed, same
            // approach already used on my-profile.php.
            $activityIcons = [
                'session' => 'event_available', 'rating' => 'star', 'goal' => 'flag',
                'community' => 'forum', 'penalty' => 'cancel',
            ];
            $activityStmt = $pdo->prepare(
                "SELECT category, reason, created_at FROM point_transactions
                 WHERE user_id = ? ORDER BY created_at DESC LIMIT 4"
            );
            $activityStmt->execute([$requestedId]);
            $activity = array_map(static function (array $row) use ($activityIcons): array {
                return [
                    'icon' => $activityIcons[$row['category']] ?? 'inbox',
                    'text' => $row['reason'],
                    'time' => ukn_time_ago($row['created_at']),
                ];
            }, $activityStmt->fetchAll());
        }
    } catch (Throwable $e) {
        error_log('[UKN admin/user-details] ' . $e->getMessage());
        $userDetailsDbError = true;
        $user = null;
    }
}

$adminPageTitle = $user ? $user['name'] : 'User Not Found';
$adminPageSub = $user ? 'Admin management view for this user.' : '';
$adminPageStyles = ['../assets/css/admin/tables.css'];
$adminPageScripts = $user ? ['../assets/js/admin/users.js'] : [];
require __DIR__ . '/includes/header.php';

if ($userDetailsDbError) {
    ukn_error_state([
        'title' => 'Unable to load this user.',
        'message' => 'Something went wrong while loading this account. Please try again shortly.',
    ]);
    require __DIR__ . '/includes/footer.php';
    return;
}

if (!$user) {
    ?>
    <div class="ukn-state ukn-state--dashed">
      <span class="ms" aria-hidden="true">person_off</span>
      <div class="ukn-state__title">User Not Found</div>
      <p class="ukn-state__text">This user id doesn't match any account in the database.</p>
      <a href="users.php" class="btn btn-primary btn-sm">Back to Users</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    return;
}
$roleLabels = ['learner' => 'Learner', 'mentor' => 'Mentor', 'dual' => 'Learner + Mentor'];
$statusLabels = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'];
$statusClass = ['active' => 'ukn-status-accent', 'inactive' => 'ukn-status-neutral', 'suspended' => 'ukn-status-neutral'];
$isSuspended = $user['status'] === 'suspended';
?>
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-start gap-3 flex-wrap" data-user-row data-user-id="<?= $requestedId ?>" data-user-status="<?= htmlspecialchars($user['status']) ?>">
      <span class="ukn-avatar ukn-avatar-lg flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($user['initials']) ?></span>
      <div class="flex-fill ukn-min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h2 class="ukn-h3 mb-0"><?= htmlspecialchars($user['name']) ?></h2>
          <span class="ukn-role-chip"><?= htmlspecialchars($roleLabels[$user['role']]) ?></span>
          <span class="ukn-status <?= $statusClass[$user['status']] ?>" data-user-status-badge><?= htmlspecialchars($statusLabels[$user['status']]) ?></span>
        </div>
        <div class="ukn-body-sm ukn-text-muted mt-1"><?= htmlspecialchars($user['department']) ?> &middot; <?= htmlspecialchars($user['universityId']) ?></div>
        <div class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <div class="d-flex flex-column gap-2 flex-shrink-0">
        <a href="users.php" class="btn btn-outline-secondary btn-sm">Back to Users</a>
        <button
          type="button"
          class="btn btn-outline-danger btn-sm"
          data-bs-toggle="modal"
          data-bs-target="#deleteConfirmationModal"
          data-suspend-user
          <?= $isSuspended ? 'hidden' : '' ?>
          data-delete-title="Suspend <?= htmlspecialchars($user['name']) ?>?"
          data-delete-message="This is a frontend demo. The account status will only change in the current mock state."
          data-delete-confirm-label="Suspend User"
          data-success-message="User suspended in demo mode."
        >Suspend User</button>
        <button
          type="button"
          class="btn btn-primary btn-sm"
          data-bs-toggle="modal"
          data-bs-target="#deleteConfirmationModal"
          data-restore-user
          <?= $isSuspended ? '' : 'hidden' ?>
          data-delete-title="Restore <?= htmlspecialchars($user['name']) ?>?"
          data-delete-message="This is a frontend demo. The account status will only change in the current mock state."
          data-delete-confirm-label="Restore User"
          data-success-message="User restored in demo mode."
        >Restore User</button>
      </div>
    </div>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><h3 class="ukn-h4 mb-0">Account Overview</h3></div>
      <div class="card-body">
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Full Name</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['name']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Email</span><span class="fw-bold ms-auto ukn-truncate"><?= htmlspecialchars($user['email']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">University ID</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['universityId']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Department</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['department']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Joined</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['joined']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Last Active</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['lastActive']) ?></span></div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Roles</span><span class="fw-bold ms-auto"><?= htmlspecialchars($roleLabels[$user['role']]) ?></span></div>
        <?php if ($isSuspended && !empty($user['suspendReason'])): ?>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Suspend Reason</span><span class="fw-bold ms-auto"><?= htmlspecialchars($user['suspendReason']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <?php if (!empty($user['learner'])): ?>
      <div class="card mb-3">
        <div class="card-header"><h3 class="ukn-h4 mb-0">Learner Activity</h3></div>
        <div class="card-body">
          <div class="ukn-admin-stat-grid mb-3">
            <?php
            ukn_stat_card(['label' => 'Learning Points', 'value' => (string) $user['learner']['points'], 'icon' => 'military_tech']);
            ukn_stat_card(['label' => 'Completed Sessions', 'value' => (string) $user['learner']['sessions'], 'icon' => 'event_available']);
            ukn_stat_card(['label' => 'Active Goals', 'value' => (string) $user['learner']['goals'], 'icon' => 'flag']);
            ukn_stat_card(['label' => 'Community Posts', 'value' => (string) $user['learner']['posts'], 'icon' => 'article']);
            ?>
          </div>
          <div class="ukn-eyebrow mb-2">Learning Skills</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($user['learner']['skills'] as $skill): ?>
              <span class="ukn-tag-neutral"><?= htmlspecialchars($skill) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <?php if (!empty($user['mentor'])): ?>
      <div class="card mb-3">
        <div class="card-header"><h3 class="ukn-h4 mb-0">Mentor Activity</h3></div>
        <div class="card-body">
          <div class="ukn-admin-stat-grid mb-3">
            <?php
            ukn_stat_card(['label' => 'Mentor Points', 'value' => (string) $user['mentor']['points'], 'icon' => 'military_tech']);
            ukn_stat_card(['label' => 'Rating', 'value' => number_format($user['mentor']['rating'], 1), 'icon' => 'star']);
            ukn_stat_card(['label' => 'Completed Sessions', 'value' => (string) $user['mentor']['sessions'], 'icon' => 'event_available']);
            ukn_stat_card(['label' => 'Learners Helped', 'value' => (string) $user['mentor']['learnersHelped'], 'icon' => 'group']);
            ?>
          </div>
          <div class="ukn-eyebrow mb-2">Teaching Skills</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($user['mentor']['skills'] as $skill): ?>
              <span class="ukn-tag-neutral"><?= htmlspecialchars($skill) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <div class="card mb-3">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="ukn-h4 mb-0">Recent Sessions</h3>
        <a href="sessions.php" class="ukn-body-sm">View All Sessions</a>
      </div>
      <?php if ($sessionsPreview): ?>
      <div class="table-responsive">
        <table class="table ukn-admin-table">
          <thead>
            <tr><th scope="col">Other Participant</th><th scope="col">Skill</th><th scope="col">Date</th><th scope="col">Role in Session</th><th scope="col">Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($sessionsPreview as $session): ?>
              <tr>
                <td data-label="Other Participant"><?= htmlspecialchars($session['participant']) ?></td>
                <td data-label="Skill"><?= htmlspecialchars($session['skill']) ?></td>
                <td data-label="Date"><?= htmlspecialchars($session['date']) ?></td>
                <td data-label="Role in Session"><?= htmlspecialchars($session['role']) ?></td>
                <td data-label="Status"><span class="ukn-status ukn-status-accent"><?= htmlspecialchars($session['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="card-body">
        <p class="ukn-body-sm ukn-text-muted mb-0">No sessions for this user yet.</p>
      </div>
      <?php endif; ?>
    </div>
    <div class="card">
      <div class="card-header"><h3 class="ukn-h4 mb-0">Recent Activity</h3></div>
      <div class="card-body">
        <?php if ($activity): ?>
          <?php foreach ($activity as $entry): ?>
            <div class="ukn-admin-row">
              <span class="ms ukn-text-accent flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($entry['icon']) ?></span>
              <span class="flex-fill ukn-min-w-0"><?= htmlspecialchars($entry['text']) ?></span>
              <span class="ukn-body-sm ukn-text-muted flex-shrink-0"><?= htmlspecialchars($entry['time']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="ukn-body-sm ukn-text-muted mb-0">No recent activity for this user yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>