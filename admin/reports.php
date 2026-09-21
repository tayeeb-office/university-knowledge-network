<?php
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../backend/helpers/format.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'reports';
$adminPageTitle = 'Reports';
$adminPageSub = 'Review reported users, posts and comments across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css', '../assets/css/admin/reports.css'];
$adminPageScripts = ['../assets/js/admin/reports.js'];
$roleLabels = ['learner' => 'Learner', 'mentor' => 'Mentor', 'dual' => 'Dual Role'];
$userStatusLabels = ['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'];
$reasonLabels = [
    'academic-integrity' => 'Academic Integrity Concern',
    'off-topic' => 'Off-topic',
    'spam' => 'Spam',
    'inappropriate' => 'Inappropriate Content',
    'harassment' => 'Harassment / Conduct',
    'other' => 'Other',
];
$statusLabels = ['pending' => 'Pending', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'];
$statusClass = ['pending' => 'ukn-status-neutral', 'resolved' => 'ukn-status-accent', 'dismissed' => 'ukn-status-neutral'];

function ukn_report_target_key(array $r): string
{
    if ($r['type'] === 'post') {
        return 'post:' . $r['post']['adminId'];
    }
    if ($r['type'] === 'comment') {
        return 'comment:' . $r['comment']['adminId'];
    }
    return 'user:' . $r['user']['id'];
}
$displayPostId = static fn (int $id): string => 'UKN-P-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
$displayCommentId = static fn (int $id): string => 'UKN-C-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);

$reports = [];
$summary = ['total' => 0, 'pending' => 0, 'resolved' => 0, 'dismissed' => 0];
$typeSummary = ['post' => 0, 'comment' => 0, 'user' => 0];
$targetCounts = [];
$reportsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->query(
        "SELECT r.reference_code, r.target_type, r.target_id, r.reason, r.description,
                r.status, r.reviewed_at, r.created_at,
                ru.id AS reporter_id, ru.full_name AS reporter_name, rd.name AS reporter_department,
                rv.full_name AS reviewer_name
         FROM reports r
         JOIN users ru ON ru.id = r.reporter_id
         LEFT JOIN departments rd ON rd.id = ru.department_id
         LEFT JOIN users rv ON rv.id = r.reviewed_by
         ORDER BY r.created_at DESC"
    );
    $rawReports = $stmt->fetchAll();

    $postIds = $commentIds = $userIds = [];
    foreach ($rawReports as $row) {
        if ($row['target_type'] === 'post') {
            $postIds[] = (int) $row['target_id'];
        } elseif ($row['target_type'] === 'comment') {
            $commentIds[] = (int) $row['target_id'];
        } else {
            $userIds[] = (int) $row['target_id'];
        }
    }

    $postsById = [];
    if ($postIds) {
        $placeholders = implode(',', array_fill(0, count(array_unique($postIds)), '?'));
        $ps = $pdo->prepare(
            "SELECT p.id, p.title, p.content, p.status, u.id AS author_id, u.full_name AS author_name
             FROM posts p JOIN users u ON u.id = p.user_id
             WHERE p.id IN ($placeholders)"
        );
        $ps->execute(array_values(array_unique($postIds)));
        foreach ($ps->fetchAll() as $p) {
            $postsById[(int) $p['id']] = $p;
        }
    }

    $commentsById = [];
    if ($commentIds) {
        $placeholders = implode(',', array_fill(0, count(array_unique($commentIds)), '?'));
        $cs = $pdo->prepare(
            "SELECT c.id, c.content, c.status, c.post_id, u.id AS author_id, u.full_name AS author_name, p.title AS post_title
             FROM comments c JOIN users u ON u.id = c.user_id JOIN posts p ON p.id = c.post_id
             WHERE c.id IN ($placeholders)"
        );
        $cs->execute(array_values(array_unique($commentIds)));
        foreach ($cs->fetchAll() as $c) {
            $commentsById[(int) $c['id']] = $c;
        }
    }

    $usersById = [];
    if ($userIds) {
        $placeholders = implode(',', array_fill(0, count(array_unique($userIds)), '?'));
        $us = $pdo->prepare(
            "SELECT u.id, u.full_name AS name, u.role, u.status, d.name AS department
             FROM users u LEFT JOIN departments d ON d.id = u.department_id
             WHERE u.id IN ($placeholders)"
        );
        $us->execute(array_values(array_unique($userIds)));
        foreach ($us->fetchAll() as $u) {
            $usersById[(int) $u['id']] = $u;
        }
    }

    foreach ($rawReports as $row) {
        $targetId = (int) $row['target_id'];
        $type = $row['target_type'];
        $entry = [
            'id' => $row['reference_code'],
            'type' => $type,
            'status' => $row['status'],
            'reporter' => [
                'id' => (int) $row['reporter_id'],
                'name' => $row['reporter_name'],
                'department' => (string) ($row['reporter_department'] ?? ''),
            ],
            'reasonSlug' => $row['reason'],
            'description' => (string) ($row['description'] ?? ''),
            'reportedDisplay' => ukn_time_ago($row['created_at']),
            'reportedIso' => date('Y-m-d', strtotime($row['created_at'])),
        ];
        if ($row['status'] !== 'pending') {
            $entry['decision'] = 'Reviewed by ' . ($row['reviewer_name'] ?? 'an admin') . '. ' .
                ($row['status'] === 'resolved' ? 'Report resolved.' : 'No further action required.');
            $entry['resolvedDisplay'] = $row['reviewed_at'] ? date('M j, Y', strtotime($row['reviewed_at'])) : '';
        }

        if ($type === 'post') {
            $p = $postsById[$targetId] ?? null;
            $entry['post'] = $p ? [
                'adminId' => $displayPostId((int) $p['id']),
                'publicId' => (int) $p['id'],
                'title' => $p['title'],
                'author' => ['id' => (int) $p['author_id'], 'name' => $p['author_name']],
                'status' => $p['status'],
                'excerpt' => ukn_excerpt($p['content'], 160),
            ] : [
                'adminId' => $displayPostId($targetId), 'publicId' => $targetId, 'title' => '(post no longer available)',
                'author' => ['id' => 0, 'name' => '—'], 'status' => 'hidden', 'excerpt' => '',
            ];
        } elseif ($type === 'comment') {
            $c = $commentsById[$targetId] ?? null;
            $entry['comment'] = $c ? [
                'adminId' => $displayCommentId((int) $c['id']),
                'text' => $c['content'],
                'author' => ['id' => (int) $c['author_id'], 'name' => $c['author_name']],
                'postTitle' => $c['post_title'],
                'postId' => (int) $c['post_id'],
                'status' => $c['status'],
            ] : [
                'adminId' => $displayCommentId($targetId), 'text' => '(comment no longer available)',
                'author' => ['id' => 0, 'name' => '—'], 'postTitle' => '', 'postId' => 0, 'status' => 'hidden',
            ];
        } else {
            $u = $usersById[$targetId] ?? null;
            $entry['user'] = $u ? [
                'id' => (int) $u['id'],
                'name' => $u['name'],
                'role' => $roleLabels[$u['role']] ?? ucfirst((string) $u['role']),
                'department' => (string) ($u['department'] ?? ''),
                'status' => $userStatusLabels[$u['status']] ?? ucfirst((string) $u['status']),
            ] : [
                'id' => $targetId, 'name' => '(user no longer available)', 'role' => '—', 'department' => '', 'status' => '—',
            ];
        }

        $reports[] = $entry;
        $summary['total']++;
        if (isset($summary[$row['status']])) {
            $summary[$row['status']]++;
        }
        if (isset($typeSummary[$type])) {
            $typeSummary[$type]++;
        }
    }

    foreach ($reports as $r) {
        $key = ukn_report_target_key($r);
        $targetCounts[$key] = ($targetCounts[$key] ?? 0) + 1;
    }
} catch (Throwable $e) {
    error_log('[UKN admin/reports] ' . $e->getMessage());
    $reportsDbError = true;
    $reports = [];
}
require __DIR__ . '/includes/header.php';
?>
<?php if ($reportsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load reports.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="ukn-admin-stat-grid mb-2">
  <button type="button" class="ukn-admin-stat-btn" data-report-summary-filter="status:">
    <?php ukn_stat_card(['label' => 'Total Reports', 'value' => number_format($summary['total']), 'icon' => 'flag']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-report-stat="pending" data-report-summary-filter="status:pending">
    <?php ukn_stat_card(['label' => 'Pending', 'value' => (string) $summary['pending'], 'icon' => 'hourglass_empty']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-report-stat="resolved" data-report-summary-filter="status:resolved">
    <?php ukn_stat_card(['label' => 'Resolved', 'value' => number_format($summary['resolved']), 'icon' => 'check_circle']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-report-stat="dismissed" data-report-summary-filter="status:dismissed">
    <?php ukn_stat_card(['label' => 'Dismissed', 'value' => (string) $summary['dismissed'], 'icon' => 'block']); ?>
  </button>
</div>
<p class="ukn-body-sm ukn-text-muted mb-4">
  By type: <strong><?= $typeSummary['post'] ?></strong> Post &middot;
  <strong><?= $typeSummary['comment'] ?></strong> Comment &middot;
  <strong><?= $typeSummary['user'] ?></strong> User reports
</p>
<div class="card mb-3">
  <div class="card-body">
    <div class="ukn-admin-filters">
      <div class="ukn-search ukn-admin-filters__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="reportSearchInput" class="ukn-visually-hidden">Search reports by ID, reporter, user or content</label>
        <input type="search" id="reportSearchInput" class="form-control" placeholder="Search reports by ID, reporter, user or content…" data-report-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="reportTypeFilter">Filter by type</label>
      <select id="reportTypeFilter" class="form-select form-select-sm" data-report-filter="type">
        <option value="">All Types</option>
        <option value="post">Post</option>
        <option value="comment">Comment</option>
        <option value="user">User</option>
      </select>
      <label class="ukn-visually-hidden" for="reportStatusFilter">Filter by status</label>
      <select id="reportStatusFilter" class="form-select form-select-sm" data-report-filter="status">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="resolved">Resolved</option>
        <option value="dismissed">Dismissed</option>
      </select>
      <label class="ukn-visually-hidden" for="reportReasonFilter">Filter by reason</label>
      <select id="reportReasonFilter" class="form-select form-select-sm" data-report-filter="reason">
        <option value="">All Reasons</option>
        <?php foreach ($reasonLabels as $slug => $label): ?>
          <option value="<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="ukn-visually-hidden" for="reportDateFilter">Filter by date</label>
      <select id="reportDateFilter" class="form-select form-select-sm" data-report-filter="date">
        <option value="">All Time</option>
        <option value="today">Today</option>
        <option value="week">This Week</option>
        <option value="month">This Month</option>
      </select>
      <label class="ukn-visually-hidden" for="reportSort">Sort reports</label>
      <select id="reportSort" class="form-select form-select-sm" data-report-sort>
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="most-reports">Most Reports on Content</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-report-clear-filters>Clear Filters</button>
    </div>
  </div>
</div>
<p class="ukn-body-sm ukn-text-muted" data-report-result-count role="status"><?= count($reports) ?> reports found</p>
<div class="card">
  <div class="table-responsive">
    <table class="table ukn-admin-table" data-report-table>
      <thead>
        <tr>
          <th scope="col">Report ID</th>
          <th scope="col">Type</th>
          <th scope="col">Reported Entity</th>
          <th scope="col">Reporter</th>
          <th scope="col">Reason</th>
          <th scope="col">Reports</th>
          <th scope="col">Status</th>
          <th scope="col">Reported</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r):
            $reporter = $r['reporter'];
            $contentReports = $targetCounts[ukn_report_target_key($r)];
            $decision = $r['decision'] ?? '';
            $resolvedDisplay = $r['resolvedDisplay'] ?? '';
            $entityLabel = '';
            $postId = $postPublicId = $postTitle = $postAuthorId = $postAuthorName = $postStatus = $postExcerpt = '';
            $commentId = $commentText = $commentAuthorId = $commentAuthorName = $commentPostTitle = $commentPostId = $commentStatus = '';
            $userId = $userName = $userRole = $userDepartment = $userStatus = '';
            if ($r['type'] === 'post') {
                $entityLabel = $r['post']['title'];
                $postId = $r['post']['adminId'];
                $postPublicId = $r['post']['publicId'];
                $postTitle = $r['post']['title'];
                $postAuthorId = $r['post']['author']['id'];
                $postAuthorName = $r['post']['author']['name'];
                $postStatus = $r['post']['status'];
                $postExcerpt = $r['post']['excerpt'];
            } elseif ($r['type'] === 'comment') {
                $entityLabel = $r['comment']['text'];
                $commentId = $r['comment']['adminId'];
                $commentText = $r['comment']['text'];
                $commentAuthorId = $r['comment']['author']['id'];
                $commentAuthorName = $r['comment']['author']['name'];
                $commentPostTitle = $r['comment']['postTitle'];
                $commentPostId = $r['comment']['postId'];
                $commentStatus = $r['comment']['status'];
            } else {
                $entityLabel = $r['user']['name'];
                $userId = $r['user']['id'];
                $userName = $r['user']['name'];
                $userRole = $r['user']['role'];
                $userDepartment = $r['user']['department'];
                $userStatus = $r['user']['status'];
            }
            $searchText = strtolower($r['id'] . ' ' . $reporter['name'] . ' ' . $reasonLabels[$r['reasonSlug']] . ' ' . $entityLabel . ' ' . $r['description']);
        ?>
          <tr
            data-report-row
            data-report-id="<?= htmlspecialchars($r['id']) ?>"
            data-report-type="<?= htmlspecialchars($r['type']) ?>"
            data-report-status="<?= htmlspecialchars($r['status']) ?>"
            data-report-reason-slug="<?= htmlspecialchars($r['reasonSlug']) ?>"
            data-report-search="<?= htmlspecialchars($searchText) ?>"
            data-report-date-sort="<?= (int) strtotime($r['reportedIso']) ?>"
            data-report-date-iso="<?= htmlspecialchars($r['reportedIso']) ?>"
            data-report-reported-display="<?= htmlspecialchars($r['reportedDisplay']) ?>"
            data-report-content-reports="<?= (int) $contentReports ?>"
            data-report-reporter-id="<?= $reporter['id'] ?>"
            data-report-reporter-name="<?= htmlspecialchars($reporter['name']) ?>"
            data-report-reporter-department="<?= htmlspecialchars($reporter['department']) ?>"
            data-report-reason="<?= htmlspecialchars($reasonLabels[$r['reasonSlug']]) ?>"
            data-report-description="<?= htmlspecialchars($r['description']) ?>"
            data-report-decision="<?= htmlspecialchars($decision) ?>"
            data-report-resolved-display="<?= htmlspecialchars($resolvedDisplay) ?>"
            data-report-post-id="<?= htmlspecialchars($postId) ?>"
            data-report-post-public-id="<?= htmlspecialchars((string) $postPublicId) ?>"
            data-report-post-title="<?= htmlspecialchars($postTitle) ?>"
            data-report-post-author-id="<?= htmlspecialchars((string) $postAuthorId) ?>"
            data-report-post-author-name="<?= htmlspecialchars($postAuthorName) ?>"
            data-report-post-status="<?= htmlspecialchars($postStatus) ?>"
            data-report-post-excerpt="<?= htmlspecialchars($postExcerpt) ?>"
            data-report-comment-id="<?= htmlspecialchars($commentId) ?>"
            data-report-comment-text="<?= htmlspecialchars($commentText) ?>"
            data-report-comment-author-id="<?= htmlspecialchars((string) $commentAuthorId) ?>"
            data-report-comment-author-name="<?= htmlspecialchars($commentAuthorName) ?>"
            data-report-comment-post-title="<?= htmlspecialchars($commentPostTitle) ?>"
            data-report-comment-post-id="<?= htmlspecialchars((string) $commentPostId) ?>"
            data-report-comment-status="<?= htmlspecialchars($commentStatus) ?>"
            data-report-user-id="<?= htmlspecialchars((string) $userId) ?>"
            data-report-user-name="<?= htmlspecialchars($userName) ?>"
            data-report-user-role="<?= htmlspecialchars($userRole) ?>"
            data-report-user-department="<?= htmlspecialchars($userDepartment) ?>"
            data-report-user-status="<?= htmlspecialchars($userStatus) ?>"
          >
            <td data-label="Report ID" class="fw-bold"><?= htmlspecialchars($r['id']) ?></td>
            <td data-label="Type"><?= htmlspecialchars(ucfirst($r['type'])) ?></td>
            <td data-label="Reported Entity"><span class="ukn-body-sm<?= $r['type'] === 'comment' ? ' ukn-clamp-2' : ' d-block ukn-truncate' ?>"><?= htmlspecialchars($entityLabel) ?></span></td>
            <td data-label="Reporter"><a href="user-details.php?id=<?= $reporter['id'] ?>" aria-label="View <?= htmlspecialchars($reporter['name']) ?> in Admin"><?= htmlspecialchars($reporter['name']) ?></a></td>
            <td data-label="Reason" class="ukn-body-sm"><?= htmlspecialchars($reasonLabels[$r['reasonSlug']]) ?></td>
            <td data-label="Reports"><?= (int) $contentReports ?> <?= $contentReports === 1 ? 'report' : 'reports' ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$r['status']] ?>" data-report-status-badge><?= htmlspecialchars($statusLabels[$r['status']]) ?></span></td>
            <td data-label="Reported" class="ukn-body-sm ukn-text-muted"><?= htmlspecialchars($r['reportedDisplay']) ?></td>
            <td data-label="Actions">
              <div class="d-flex justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#reportReviewModal" data-report-view aria-label="Review report <?= htmlspecialchars($r['id']) ?>">Review</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-body"<?= $reports ? ' hidden' : '' ?> data-report-empty>
    <?php ukn_empty_state([
        'icon' => 'flag',
        'title' => 'No reports found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-report-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
  <div class="card-body" hidden data-report-empty-pending>
    <?php ukn_empty_state([
        'icon' => 'task_alt',
        'title' => 'No pending reports.',
        'message' => 'There are no reports waiting for review.',
        'dashed' => true,
    ]); ?>
  </div>
  <div class="card-body ukn-admin-pagination" data-report-pagination>
    <span class="ukn-body-sm ukn-text-muted" data-report-pagination-summary></span>
    <div class="d-flex gap-1" data-report-pagination-pages></div>
  </div>
</div>
<?php endif; ?>
<div class="modal fade" id="reportReviewModal" tabindex="-1" aria-labelledby="reportReviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="reportReviewModalLabel">Report Review</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
          <span class="fw-bold" data-report-detail="id">—</span>
          <span class="ukn-status ukn-status-neutral" data-report-detail-status-badge>—</span>
        </div>
        <p class="ukn-body-sm ukn-text-muted mb-3"><span data-report-detail="type">—</span> report &middot; reported <span data-report-detail="reportedDisplay"></span></p>
        <div class="ukn-eyebrow mb-1">Reporter</div>
        <div class="ukn-admin-row">
          <span class="ukn-body-sm ukn-text-muted">Reported by</span>
          <span class="ms-auto text-end"><a href="#" data-report-detail-link="reporter">—</a><span class="d-block ukn-body-sm ukn-text-muted" data-report-detail="reporterDepartment"></span></span>
        </div>
        <div class="ukn-eyebrow mb-1 mt-3">Reported Content</div>
        <div data-report-section="post" hidden>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Post</span><span class="fw-bold ms-auto text-end" data-report-detail="postTitle">—</span></div>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Author</span><span class="ms-auto text-end"><a href="#" data-report-detail-link="postAuthor">—</a></span></div>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Post ID</span><span class="fw-bold ms-auto" data-report-detail="postId">—</span></div>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Status</span><span class="ukn-status ms-auto" data-report-detail-post-status-badge>—</span></div>
          <p class="ukn-body-sm mt-2 mb-0" data-report-detail="postExcerpt"></p>
        </div>
        <div data-report-section="comment" hidden>
          <p class="ukn-body mb-2" data-report-detail="commentText">—</p>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Author</span><span class="ms-auto text-end"><a href="#" data-report-detail-link="commentAuthor">—</a></span></div>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Parent Post</span><span class="fw-bold ms-auto text-end" data-report-detail="commentPost">—</span></div>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Status</span><span class="ukn-status ms-auto" data-report-detail-comment-status-badge>—</span></div>
        </div>
        <div data-report-section="user" hidden>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">User</span><span class="ms-auto text-end"><a href="#" data-report-detail-link="reportedUser">—</a></span></div>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Role</span><span class="fw-bold ms-auto" data-report-detail="userRole">—</span></div>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Department</span><span class="fw-bold ms-auto" data-report-detail="userDepartment">—</span></div>
          <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Account Status</span><span class="ukn-status ms-auto" data-report-detail-user-status-badge>—</span></div>
        </div>
        <p class="ukn-body-sm ukn-text-muted mt-2 mb-0" hidden data-report-detail-multi></p>
        <div class="ukn-eyebrow mb-1 mt-3">Reason</div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Reason</span><span class="fw-bold ms-auto text-end" data-report-detail="reason">—</span></div>
        <p class="ukn-body-sm mt-2 mb-0" data-report-detail="description"></p>
        <div class="ukn-report-decision" hidden data-report-decision-row>
          <div class="ukn-eyebrow mb-1">Decision</div>
          <p class="ukn-body-sm mb-1" data-report-detail="decision"></p>
          <p class="ukn-body-sm ukn-text-muted mb-0">Resolved <span data-report-detail="resolvedDisplay"></span></p>
        </div>
      </div>
      <div class="modal-footer flex-wrap gap-2">
        <div class="d-flex flex-wrap gap-2 me-auto">
          <a href="#" class="btn btn-outline-secondary btn-sm" data-report-detail-link="moderation">—</a>
          <a href="#" class="btn btn-outline-secondary btn-sm" data-report-detail-link="app" hidden>—</a>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-report-dismiss hidden>Dismiss Report</button>
        <button type="button" class="btn btn-primary btn-sm" data-report-resolve hidden>Resolve Report</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>