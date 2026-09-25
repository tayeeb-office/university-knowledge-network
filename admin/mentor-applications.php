<?php
require_once __DIR__ . '/../backend/helpers/auth.php';
requireAdmin();
require_once __DIR__ . '/../backend/helpers/csrf.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../backend/helpers/format.php';
require_once __DIR__ . '/../backend/helpers/mentor-applications.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'mentor-applications';
$adminPageTitle = 'Mentor Applications';
$adminPageSub = 'Review learners who asked to become mentors. Approved accounts keep learner access and gain Mentor mode.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];

$statusFilters = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'];
$statusFilter = (isset($_GET['status']) && is_string($_GET['status']) && isset($statusFilters[$_GET['status']])) ? $_GET['status'] : 'pending';
$statusClass = ['pending' => 'ukn-status-warning', 'approved' => 'ukn-status-success', 'rejected' => 'ukn-status-neutral'];
$roleLabels = ['learner' => 'Learner', 'dual' => 'Learner & Mentor'];
$reviewerId = (int) getCurrentUser()['id'];
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$applications = [];
$dbError = false;

try {
    $pdo = getDatabaseConnection();
    foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM mentor_applications GROUP BY status')->fetchAll() as $row) {
        $counts[$row['status']] = (int) $row['c'];
    }
    // One query for the whole list (no per-row lookups): applicant, department, skill counts
    // and the reviewer's name. Never selects password, token or verification hashes.
    $stmt = $pdo->prepare(
        "SELECT a.id, a.user_id, a.status, a.application_message, a.requested_at, a.reviewed_at, a.admin_note,
                u.full_name, u.initials, u.role, u.is_admin, u.status AS account_status,
                u.email_verified_at IS NOT NULL AS verified, u.year_of_study, u.learning_points,
                u.sessions_as_learner, d.name AS department,
                (SELECT COUNT(*) FROM user_skills us WHERE us.user_id = u.id AND us.skill_type = 'learning') AS learning_skills,
                (SELECT COUNT(*) FROM user_skills us WHERE us.user_id = u.id AND us.skill_type = 'teaching') AS teaching_skills,
                r.full_name AS reviewer_name
         FROM mentor_applications a
         JOIN users u ON u.id = a.user_id
         LEFT JOIN departments d ON d.id = u.department_id
         LEFT JOIN users r ON r.id = a.reviewed_by
         WHERE (? = 'all' OR a.status = ?)
         ORDER BY a.status = 'pending' DESC, a.requested_at DESC, a.id DESC
         LIMIT 200"
    );
    $stmt->execute([$statusFilter, $statusFilter]);
    $applications = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[UKN admin mentor-applications] ' . $e->getMessage());
    $dbError = true;
}
$formatTime = static fn (?string $ts): string => $ts ? date('M j, Y g:i A', strtotime($ts)) : '';

require __DIR__ . '/includes/header.php';
?>
<?php if ($dbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load mentor applications.',
      'message' => 'Something went wrong while loading applications. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="ukn-admin-stat-grid mb-4">
  <?php
  ukn_stat_card(['label' => 'Pending', 'value' => number_format($counts['pending']), 'icon' => 'hourglass_top']);
  ukn_stat_card(['label' => 'Approved', 'value' => number_format($counts['approved']), 'icon' => 'check_circle']);
  ukn_stat_card(['label' => 'Rejected', 'value' => number_format($counts['rejected']), 'icon' => 'cancel']);
  ?>
</div>
<nav class="nav nav-tabs mb-3" aria-label="Filter applications by status">
  <?php foreach ($statusFilters as $key => $label): ?>
    <a class="nav-link<?= $statusFilter === $key ? ' active' : '' ?>" href="mentor-applications.php?status=<?= $key ?>"<?= $statusFilter === $key ? ' aria-current="page"' : '' ?>>
      <?= htmlspecialchars($label) ?><?= $key !== 'all' ? ' (' . (int) $counts[$key] . ')' : '' ?>
    </a>
  <?php endforeach; ?>
</nav>
<?php if (empty($applications)): ?>
  <?php ukn_empty_state([
      'icon' => 'volunteer_activism',
      'title' => $statusFilter === 'pending' ? 'No applications waiting for review.' : 'No applications to show.',
      'message' => 'Learners apply from their profile menu with "Apply to Become a Mentor".',
  ]); ?>
<?php else: ?>
  <?php foreach ($applications as $app):
      $isOwn = (int) $app['user_id'] === $reviewerId;
      $accountOk = $app['account_status'] === 'active' && (int) $app['verified'] === 1 && $app['role'] === 'learner';
  ?>
    <div class="card mb-3" data-mentor-application="<?= (int) $app['id'] ?>" data-application-status="<?= htmlspecialchars($app['status']) ?>">
      <div class="card-body">
        <div class="d-flex align-items-start gap-3 flex-wrap">
          <span class="ukn-avatar" aria-hidden="true"><?= htmlspecialchars($app['initials']) ?></span>
          <div class="flex-fill ukn-min-w-0">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <a href="user-details.php?id=<?= (int) $app['user_id'] ?>" class="fw-bold text-body"><?= htmlspecialchars($app['full_name']) ?></a>
              <span class="ukn-role-chip"><?= htmlspecialchars($roleLabels[$app['role']] ?? ucfirst((string) $app['role'])) ?></span>
              <?php if (!empty($app['is_admin'])): ?><span class="ukn-status ukn-status-accent">Admin</span><?php endif; ?>
              <span class="ukn-status <?= $statusClass[$app['status']] ?>"><?= htmlspecialchars(ucfirst($app['status'])) ?></span>
              <?php if ($app['account_status'] !== 'active'): ?><span class="ukn-status ukn-status-danger">Account <?= htmlspecialchars($app['account_status']) ?></span><?php endif; ?>
              <?php if ((int) $app['verified'] !== 1): ?><span class="ukn-status ukn-status-danger">Email not verified</span><?php endif; ?>
            </div>
            <div class="ukn-body-sm ukn-text-muted mt-1">
              <?= htmlspecialchars(implode(' · ', array_filter([(string) $app['department'], (string) $app['year_of_study']]))) ?>
            </div>
            <div class="ukn-body-sm mt-1">
              <?= (int) $app['learning_points'] ?> learning points · <?= (int) $app['sessions_as_learner'] ?> sessions as learner ·
              <?= (int) $app['learning_skills'] ?> learning skills · <?= (int) $app['teaching_skills'] ?> teaching skills
            </div>
            <div class="ukn-eyebrow mt-3 mb-1">Why they want to mentor</div>
            <p class="ukn-body ukn-prose mb-2"><?= nl2br(htmlspecialchars((string) $app['application_message'])) ?></p>
            <div class="ukn-body-sm ukn-text-muted">Requested <?= htmlspecialchars($formatTime($app['requested_at'])) ?></div>
            <?php if ($app['status'] !== 'pending'): ?>
              <div class="ukn-body-sm ukn-text-muted" data-application-review>
                <?= htmlspecialchars(ucfirst($app['status'])) ?> <?= htmlspecialchars($formatTime($app['reviewed_at'])) ?>
                by <?= htmlspecialchars($app['reviewer_name'] ?? 'a former admin') ?>
                <?php if ($app['admin_note'] !== null && $app['admin_note'] !== ''): ?>
                  — note: <?= htmlspecialchars($app['admin_note']) ?>
                <?php endif; ?>
              </div>
            <?php elseif ($isOwn): ?>
              <p class="ukn-body-sm mt-2 mb-0" data-application-own>This is your own application; another admin must review it.</p>
            <?php else: ?>
              <form action="../backend/admin/mentor-applications/review.php" method="post" class="d-flex flex-wrap align-items-end gap-2 mt-3" data-application-review-form>
                <?= csrfField() ?>
                <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
                <input type="hidden" name="return_to" value="<?= htmlspecialchars(uknBaseUrl() . '/admin/mentor-applications.php?status=' . $statusFilter) ?>">
                <div class="flex-fill">
                  <label class="form-label ukn-body-sm mb-1" for="appNote<?= (int) $app['id'] ?>">Note (optional)</label>
                  <input type="text" class="form-control form-control-sm" id="appNote<?= (int) $app['id'] ?>" name="admin_note" maxlength="<?= UKN_MENTOR_APPLICATION_NOTE_MAX ?>">
                </div>
                <button type="submit" name="decision" value="rejected" class="btn btn-outline-secondary btn-sm">Reject</button>
                <button type="submit" name="decision" value="approved" class="btn btn-primary btn-sm"<?= $accountOk ? '' : ' disabled title="Only active, verified learner accounts can be approved."' ?>>Approve</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
