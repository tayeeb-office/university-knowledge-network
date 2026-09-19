<?php
/**
 * Admin Reports Management — the review queue for reported Posts,
 * Comments and Users. Reuses admin/includes/users-data.php for every
 * reporter/reported-user identity, and mirrors the exact already-
 * established reported/hidden records from admin/posts.php (UKN-P-0013/
 * 0014/0015) and admin/comments.php (UKN-C-0107/0111/0112) rather than
 * inventing new counts that would disagree with those pages' own
 * "Reports" columns. Each report below is one individual report record
 * (not a grouped-by-content row) — a post/comment reported more than
 * once simply appears as more than one row referencing the same target,
 * with the Review panel's own "reports on this content" note surfacing
 * the grouping for context (see $targetCounts below).
 *
 * There is no admin/report-details.php — "Review" opens one reusable
 * detail modal (#reportReviewModal), same convention as Sessions/Posts/
 * Comments. Resolve/Dismiss are decided directly inside that modal (no
 * second confirmation modal stacked on top of it) and only ever change
 * the REPORT's own status. They deliberately never touch the underlying
 * Post/Comment's visibility or the User's account status — that stays on
 * posts.php/comments.php/user-details.php, which this page only links
 * out to (see assets/js/admin/reports.js's own docblock for why this
 * boundary matters).
 *
 * The 4 status summary cards + Post/Comment/User breakdown line reuse
 * the network-wide totals already established on admin/dashboard.php
 * (Pending Reports: 8), while the table below demonstrates a smaller,
 * honestly-labeled sample of 16 individual report records — the same
 * summary-vs-sample resolution admin/posts.php and admin/comments.php
 * already use for their own Total/Visible/Hidden/Reported cards.
 */
require_once __DIR__ . '/includes/users-data.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/stat-card.php';

$adminActiveNav = 'reports';
$adminPageTitle = 'Reports';
$adminPageSub = 'Review reported users, posts and comments across the network.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css', '../assets/css/admin/reports.css'];
$adminPageScripts = ['../assets/js/admin/reports.js'];

$users = ukn_admin_mock_users();
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

function ukn_admin_report_person(array $users, int $id): array
{
    $u = $users[$id];
    return ['id' => $id, 'name' => $u['name'], 'department' => $u['department']];
}

$reports = [
    // ---- Pending ----
    ['id' => 'UKN-R-0201', 'type' => 'post', 'status' => 'pending', 'reporter' => 1, 'reasonSlug' => 'academic-integrity',
        'description' => 'The post appears to encourage sharing completed assignment work.', 'reportedDisplay' => '22 min ago', 'reportedIso' => '2026-09-14',
        'post' => ['adminId' => 'UKN-P-0014', 'publicId' => 1, 'title' => 'Anyone Want to Exchange Completed Assignment Files?', 'author' => 14, 'status' => 'visible', 'excerpt' => 'Looking to trade completed assignments from last semester to save time.']],
    ['id' => 'UKN-R-0202', 'type' => 'comment', 'status' => 'pending', 'reporter' => 5, 'reasonSlug' => 'inappropriate',
        'description' => "This comment doesn't add anything constructive and points back to the reported assignment-sharing post.", 'reportedDisplay' => '48 min ago', 'reportedIso' => '2026-09-14',
        'comment' => ['adminId' => 'UKN-C-0111', 'text' => 'This is useless. Just send me the completed assignment.', 'author' => 14, 'status' => 'visible', 'postTitle' => 'Need Help Understanding Database Normalization', 'postId' => 1]],
    ['id' => 'UKN-R-0203', 'type' => 'post', 'status' => 'pending', 'reporter' => 2, 'reasonSlug' => 'off-topic',
        'description' => 'Not related to any specific skill or mentoring topic.', 'reportedDisplay' => '1 hr ago', 'reportedIso' => '2026-09-14',
        'post' => ['adminId' => 'UKN-P-0014', 'publicId' => 1, 'title' => 'Anyone Want to Exchange Completed Assignment Files?', 'author' => 14, 'status' => 'visible', 'excerpt' => 'Looking to trade completed assignments from last semester to save time.']],
    ['id' => 'UKN-R-0204', 'type' => 'user', 'status' => 'pending', 'reporter' => 9, 'reasonSlug' => 'spam',
        'description' => 'Has posted unrelated promotional links in multiple comment threads.', 'reportedDisplay' => '3 hr ago', 'reportedIso' => '2026-09-14',
        'user' => ['id' => 12]],
    ['id' => 'UKN-R-0205', 'type' => 'post', 'status' => 'pending', 'reporter' => 11, 'reasonSlug' => 'spam',
        'description' => 'Looks like a coordinated attempt to trade completed coursework.', 'reportedDisplay' => '5 hr ago', 'reportedIso' => '2026-09-14',
        'post' => ['adminId' => 'UKN-P-0014', 'publicId' => 1, 'title' => 'Anyone Want to Exchange Completed Assignment Files?', 'author' => 14, 'status' => 'visible', 'excerpt' => 'Looking to trade completed assignments from last semester to save time.']],
    ['id' => 'UKN-R-0206', 'type' => 'comment', 'status' => 'pending', 'reporter' => 7, 'reasonSlug' => 'academic-integrity',
        'description' => 'Encourages other students to get the answer instead of doing the work.', 'reportedDisplay' => '6 hr ago', 'reportedIso' => '2026-09-14',
        'comment' => ['adminId' => 'UKN-C-0111', 'text' => 'This is useless. Just send me the completed assignment.', 'author' => 14, 'status' => 'visible', 'postTitle' => 'Need Help Understanding Database Normalization', 'postId' => 1]],
    ['id' => 'UKN-R-0207', 'type' => 'post', 'status' => 'pending', 'reporter' => 8, 'reasonSlug' => 'off-topic',
        'description' => 'Very similar to another existing post asking the same question.', 'reportedDisplay' => 'Sep 13, 2026', 'reportedIso' => '2026-09-13',
        'post' => ['adminId' => 'UKN-P-0013', 'publicId' => 3, 'title' => 'Best Resources for Learning MySQL Joins?', 'author' => 5, 'status' => 'visible', 'excerpt' => 'Looking for practice problems that go beyond simple INNER JOIN examples.']],

    // ---- Resolved ----
    ['id' => 'UKN-R-0208', 'type' => 'post', 'status' => 'resolved', 'reporter' => 10, 'reasonSlug' => 'spam',
        'description' => 'Promoting a personal side project unrelated to coursework or mentoring.', 'reportedDisplay' => 'Sep 12, 2026', 'reportedIso' => '2026-09-12',
        'decision' => 'Content hidden', 'resolvedDisplay' => 'Sep 12, 2026',
        'post' => ['adminId' => 'UKN-P-0015', 'publicId' => 1, 'title' => 'Check Out My New Side Project Website!', 'author' => 15, 'status' => 'hidden', 'excerpt' => 'Promoting a personal side project unrelated to coursework or mentoring.']],
    ['id' => 'UKN-R-0209', 'type' => 'post', 'status' => 'resolved', 'reporter' => 9, 'reasonSlug' => 'spam',
        'description' => 'Same project link posted across multiple discussion threads.', 'reportedDisplay' => 'Sep 12, 2026', 'reportedIso' => '2026-09-12',
        'decision' => 'Content hidden', 'resolvedDisplay' => 'Sep 12, 2026',
        'post' => ['adminId' => 'UKN-P-0015', 'publicId' => 1, 'title' => 'Check Out My New Side Project Website!', 'author' => 15, 'status' => 'hidden', 'excerpt' => 'Promoting a personal side project unrelated to coursework or mentoring.']],
    ['id' => 'UKN-R-0210', 'type' => 'comment', 'status' => 'resolved', 'reporter' => 8, 'reasonSlug' => 'spam',
        'description' => 'Offering to exchange completed assignment files.', 'reportedDisplay' => 'Sep 13, 2026', 'reportedIso' => '2026-09-13',
        'decision' => 'Content hidden', 'resolvedDisplay' => 'Sep 13, 2026',
        'comment' => ['adminId' => 'UKN-C-0112', 'text' => "DM me, I have last semester's files too.", 'author' => 15, 'status' => 'hidden', 'postTitle' => 'Anyone Want to Exchange Completed Assignment Files?', 'postId' => 1]],
    ['id' => 'UKN-R-0211', 'type' => 'comment', 'status' => 'resolved', 'reporter' => 11, 'reasonSlug' => 'academic-integrity',
        'description' => 'Encourages sharing of completed assignment files.', 'reportedDisplay' => 'Sep 13, 2026', 'reportedIso' => '2026-09-13',
        'decision' => 'Content hidden', 'resolvedDisplay' => 'Sep 13, 2026',
        'comment' => ['adminId' => 'UKN-C-0112', 'text' => "DM me, I have last semester's files too.", 'author' => 15, 'status' => 'hidden', 'postTitle' => 'Anyone Want to Exchange Completed Assignment Files?', 'postId' => 1]],
    ['id' => 'UKN-R-0212', 'type' => 'user', 'status' => 'resolved', 'reporter' => 7, 'reasonSlug' => 'spam',
        'description' => 'Multiple pieces of content promoted outside projects and encouraged assignment sharing.', 'reportedDisplay' => 'Sep 12, 2026', 'reportedIso' => '2026-09-12',
        'decision' => 'User reviewed and suspended for policy violation.', 'resolvedDisplay' => 'Sep 12, 2026',
        'user' => ['id' => 15]],
    ['id' => 'UKN-R-0213', 'type' => 'post', 'status' => 'resolved', 'reporter' => 6, 'reasonSlug' => 'off-topic',
        'description' => 'Seemed unrelated to a specific skill or mentoring request.', 'reportedDisplay' => 'Sep 13, 2026', 'reportedIso' => '2026-09-13',
        'decision' => 'Manual review completed — a reminder about posting guidelines was shared with the author.', 'resolvedDisplay' => 'Sep 13, 2026',
        'post' => ['adminId' => 'UKN-P-0005', 'publicId' => 5, 'title' => 'How Do You Improve Academic Presentation Skills?', 'author' => 3, 'status' => 'visible', 'excerpt' => 'My seminar presentations feel flat even when the research is solid.']],

    // ---- Dismissed ----
    ['id' => 'UKN-R-0214', 'type' => 'post', 'status' => 'dismissed', 'reporter' => 9, 'reasonSlug' => 'off-topic',
        'description' => 'Seemed unrelated to a specific skill.', 'reportedDisplay' => 'Sep 13, 2026', 'reportedIso' => '2026-09-13',
        'decision' => 'No action required — the post was relevant to the discussion after review.', 'resolvedDisplay' => 'Sep 13, 2026',
        'post' => ['adminId' => 'UKN-P-0003', 'publicId' => 3, 'title' => 'Looking for a Public Speaking Practice Partner', 'author' => 6, 'status' => 'visible', 'excerpt' => 'Preparing for a case competition presentation and would love a few practice run-throughs.']],
    ['id' => 'UKN-R-0215', 'type' => 'comment', 'status' => 'dismissed', 'reporter' => 11, 'reasonSlug' => 'off-topic',
        'description' => 'Seemed like a duplicate reply.', 'reportedDisplay' => 'Sep 13, 2026', 'reportedIso' => '2026-09-13',
        'decision' => 'No violation found.', 'resolvedDisplay' => 'Sep 13, 2026',
        'comment' => ['adminId' => 'UKN-C-0107', 'text' => "I'd be interested in practicing together — I have a presentation coming up too.", 'author' => 3, 'status' => 'visible', 'postTitle' => 'Looking for a Public Speaking Practice Partner', 'postId' => 3]],
    ['id' => 'UKN-R-0216', 'type' => 'user', 'status' => 'dismissed', 'reporter' => 10, 'reasonSlug' => 'spam',
        'description' => 'Suspected inactive/spam account.', 'reportedDisplay' => 'Sep 13, 2026', 'reportedIso' => '2026-09-13',
        'decision' => 'Account reviewed — no policy violation found; account appears simply inactive.', 'resolvedDisplay' => 'Sep 13, 2026',
        'user' => ['id' => 13]],
];

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

$targetCounts = [];
foreach ($reports as $r) {
    $key = ukn_report_target_key($r);
    $targetCounts[$key] = ($targetCounts[$key] ?? 0) + 1;
}

$statusLabels = ['pending' => 'Pending', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'];
$statusClass = ['pending' => 'ukn-status-neutral', 'resolved' => 'ukn-status-accent', 'dismissed' => 'ukn-status-neutral'];

// Platform-wide totals — kept in sync with admin/dashboard.php's own
// "Pending Reports: 8" card. 8 + 49 + 7 = 64. 31 + 21 + 12 = 64.
$summary = ['total' => 64, 'pending' => 8, 'resolved' => 49, 'dismissed' => 7];
$typeSummary = ['post' => 31, 'comment' => 21, 'user' => 12];

require __DIR__ . '/includes/header.php';
?>
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
            $reporter = ukn_admin_report_person($users, $r['reporter']);
            $contentReports = $targetCounts[ukn_report_target_key($r)];
            $decision = $r['decision'] ?? '';
            $resolvedDisplay = $r['resolvedDisplay'] ?? '';

            $entityLabel = '';
            $postId = $postPublicId = $postTitle = $postAuthorId = $postAuthorName = $postStatus = $postExcerpt = '';
            $commentId = $commentText = $commentAuthorId = $commentAuthorName = $commentPostTitle = $commentPostId = $commentStatus = '';
            $userId = $userName = $userRole = $userDepartment = $userStatus = '';

            if ($r['type'] === 'post') {
                $entityLabel = $r['post']['title'];
                $author = ukn_admin_report_person($users, $r['post']['author']);
                $postId = $r['post']['adminId'];
                $postPublicId = $r['post']['publicId'];
                $postTitle = $r['post']['title'];
                $postAuthorId = $author['id'];
                $postAuthorName = $author['name'];
                $postStatus = $r['post']['status'];
                $postExcerpt = $r['post']['excerpt'];
            } elseif ($r['type'] === 'comment') {
                $entityLabel = $r['comment']['text'];
                $author = ukn_admin_report_person($users, $r['comment']['author']);
                $commentId = $r['comment']['adminId'];
                $commentText = $r['comment']['text'];
                $commentAuthorId = $author['id'];
                $commentAuthorName = $author['name'];
                $commentPostTitle = $r['comment']['postTitle'];
                $commentPostId = $r['comment']['postId'];
                $commentStatus = $r['comment']['status'];
            } else {
                $target = $users[$r['user']['id']];
                $entityLabel = $target['name'];
                $userId = $r['user']['id'];
                $userName = $target['name'];
                $userRole = $roleLabels[$target['role']];
                $userDepartment = $target['department'];
                $userStatus = $userStatusLabels[$target['status']];
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
            <td data-label="Reported Entity" class="ukn-body-sm <?= $r['type'] === 'comment' ? 'ukn-clamp-2' : 'ukn-truncate' ?>"><?= htmlspecialchars($entityLabel) ?></td>
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

  <div class="card-body" hidden data-report-empty>
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
