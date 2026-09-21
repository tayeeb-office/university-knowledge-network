<?php
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'comments';
$adminPageTitle = 'Comments';
$adminPageSub = 'Review community comments, replies and moderation status.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/moderation.js'];
$statusLabels = ['visible' => 'Visible', 'hidden' => 'Hidden'];
$statusClass = ['visible' => 'ukn-status-accent', 'hidden' => 'ukn-status-neutral'];
$reasonLabels = [
    'academic-integrity' => 'Academic integrity concern', 'off-topic' => 'Off-topic',
    'spam' => 'Spam', 'inappropriate' => 'Inappropriate', 'harassment' => 'Harassment', 'other' => 'Other',
];
$displayId = static fn (int $id): string => 'UKN-C-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);

$comments = [];
$commentsById = [];
$totalCount = 0;
$visibleCount = 0;
$hiddenCount = 0;
$reportedCount = 0;
$commentsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->query(
        "SELECT c.id, c.parent_id, c.content, c.report_count AS reports, c.status, c.created_at,
                u.id AS author_id, u.full_name AS author_name,
                p.id AS post_id, p.title AS post_title,
                (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id) AS replies
         FROM comments c
         JOIN users u ON u.id = c.user_id
         JOIN posts p ON p.id = c.post_id
         ORDER BY c.created_at DESC"
    );
    $rows = $stmt->fetchAll();

    if ($rows) {
        $commentIds = array_column($rows, 'id');
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));

        $reportsStmt = $pdo->prepare(
            "SELECT target_id, reason, description FROM reports
             WHERE target_type = 'comment' AND target_id IN ($placeholders)
             ORDER BY created_at DESC"
        );
        $reportsStmt->execute($commentIds);
        $reasonsByComment = [];
        foreach ($reportsStmt->fetchAll() as $row) {
            $label = $row['description'] !== null && $row['description'] !== ''
                ? $row['description']
                : ($reasonLabels[$row['reason']] ?? ucfirst($row['reason']));
            $reasonsByComment[$row['target_id']][] = $label;
        }

        foreach ($rows as $row) {
            $reports = (int) $row['reports'];
            $comment = [
                'id' => $displayId((int) $row['id']),
                'realId' => (int) $row['id'],
                'post' => $row['post_title'],
                'postId' => (int) $row['post_id'],
                'author' => (int) $row['author_id'],
                'authorName' => $row['author_name'],
                'text' => $row['content'],
                'replies' => (int) $row['replies'],
                'reports' => $reports,
                'reportReasons' => $reasonsByComment[$row['id']] ?? [],
                'status' => $row['status'],
                'type' => $row['parent_id'] !== null ? 'reply' : 'top-level',
                'parent' => $row['parent_id'] !== null ? $displayId((int) $row['parent_id']) : null,
                'posted' => date('M j, Y', strtotime($row['created_at'])),
            ];
            $comments[] = $comment;
            $commentsById[$comment['id']] = $comment;

            $totalCount++;
            if ($comment['status'] === 'visible') {
                $visibleCount++;
            } else {
                $hiddenCount++;
            }
            if ($reports > 0) {
                $reportedCount++;
            }
        }
    }
} catch (Throwable $e) {
    error_log('[UKN admin/comments] ' . $e->getMessage());
    $commentsDbError = true;
    $comments = [];
    $commentsById = [];
}
require __DIR__ . '/includes/header.php';
?>
<?php if ($commentsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load comments.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="ukn-admin-stat-grid mb-4">
  <button type="button" class="ukn-admin-stat-btn" data-comment-summary-filter="status:">
    <?php ukn_stat_card(['label' => 'Total Comments', 'value' => number_format($totalCount), 'icon' => 'chat_bubble']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-comment-summary-filter="status:visible">
    <?php ukn_stat_card(['label' => 'Visible', 'value' => number_format($visibleCount), 'icon' => 'visibility']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-comment-summary-filter="status:hidden">
    <?php ukn_stat_card(['label' => 'Hidden', 'value' => number_format($hiddenCount), 'icon' => 'visibility_off']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-comment-summary-filter="reported:reported">
    <?php ukn_stat_card(['label' => 'Reported', 'value' => number_format($reportedCount), 'icon' => 'flag']); ?>
  </button>
</div>
<div class="card mb-3">
  <div class="card-body">
    <div class="ukn-admin-filters">
      <div class="ukn-search ukn-admin-filters__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="commentSearchInput" class="ukn-visually-hidden">Search comments by content, author or post</label>
        <input type="search" id="commentSearchInput" class="form-control" placeholder="Search comments by content, author or post…" data-comment-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="commentStatusFilter">Filter by status</label>
      <select id="commentStatusFilter" class="form-select form-select-sm" data-comment-filter="status">
        <option value="">All Statuses</option>
        <option value="visible">Visible</option>
        <option value="hidden">Hidden</option>
      </select>
      <label class="ukn-visually-hidden" for="commentReportedFilter">Filter by reported state</label>
      <select id="commentReportedFilter" class="form-select form-select-sm" data-comment-filter="reported">
        <option value="">All Comments</option>
        <option value="reported">Reported</option>
        <option value="not-reported">Not Reported</option>
      </select>
      <label class="ukn-visually-hidden" for="commentTypeFilter">Filter by comment type</label>
      <select id="commentTypeFilter" class="form-select form-select-sm" data-comment-filter="type">
        <option value="">All</option>
        <option value="top-level">Top-level Comments</option>
        <option value="reply">Replies</option>
      </select>
      <label class="ukn-visually-hidden" for="commentSort">Sort comments</label>
      <select id="commentSort" class="form-select form-select-sm" data-comment-sort>
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="most-reported">Most Reported</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-comment-clear-filters>Clear Filters</button>
    </div>
  </div>
</div>
<p class="ukn-body-sm ukn-text-muted" data-comment-result-count role="status"><?= count($comments) ?> comments found</p>
<div class="card">
  <div class="table-responsive">
    <table class="table ukn-admin-table" data-comment-table>
      <thead>
        <tr>
          <th scope="col">Comment</th>
          <th scope="col">Author</th>
          <th scope="col">Post</th>
          <th scope="col">Replies</th>
          <th scope="col">Reports</th>
          <th scope="col">Status</th>
          <th scope="col">Posted</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($comments as $c):
            $author = ['id' => $c['author'], 'name' => $c['authorName']];
            $isVisible = $c['status'] === 'visible';
            $reportReasons = $c['reportReasons'] ?? [];
            $parent = $c['parent'] ?? null;
            $parentComment = $parent ? ($commentsById[$parent] ?? null) : null;
        ?>
          <tr
            data-comment-row
            data-comment-id="<?= htmlspecialchars($c['id']) ?>"
            data-comment-search="<?= htmlspecialchars(strtolower($c['id'] . ' ' . $c['text'] . ' ' . $author['name'] . ' ' . $c['post'])) ?>"
            data-comment-status="<?= htmlspecialchars($c['status']) ?>"
            data-comment-reported="<?= $c['reports'] > 0 ? 'reported' : 'not-reported' ?>"
            data-comment-type="<?= htmlspecialchars($c['type']) ?>"
            data-comment-reports="<?= (int) $c['reports'] ?>"
            data-comment-date-sort="<?= (int) strtotime($c['posted']) ?>"
            data-comment-text="<?= htmlspecialchars($c['text']) ?>"
            data-comment-author-id="<?= $author['id'] ?>"
            data-comment-author-name="<?= htmlspecialchars($author['name']) ?>"
            data-comment-post-title="<?= htmlspecialchars($c['post']) ?>"
            data-comment-post-id="<?= (int) $c['postId'] ?>"
            data-comment-replies="<?= (int) $c['replies'] ?>"
            data-comment-posted="<?= htmlspecialchars($c['posted']) ?>"
            data-comment-report-reasons="<?= htmlspecialchars(implode('|', $reportReasons)) ?>"
            data-comment-parent-id="<?= htmlspecialchars($parent ?? '') ?>"
            data-comment-parent-author="<?= htmlspecialchars($parentComment ? $parentComment['authorName'] : '') ?>"
            data-comment-parent-text="<?= htmlspecialchars($parentComment['text'] ?? '') ?>"
          >
            <td data-label="Comment" class="ukn-body-sm ukn-clamp-2" data-comment-cell="text"><?= htmlspecialchars($c['text']) ?></td>
            <td data-label="Author"><a href="user-details.php?id=<?= $author['id'] ?>" aria-label="View <?= htmlspecialchars($author['name']) ?> in Admin"><?= htmlspecialchars($author['name']) ?></a></td>
            <td data-label="Post" class="ukn-body-sm ukn-truncate"><?= htmlspecialchars($c['post']) ?></td>
            <td data-label="Replies"><?= (int) $c['replies'] ?></td>
            <td data-label="Reports"><?= $c['reports'] > 0 ? (int) $c['reports'] . ' reports' : '0' ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$c['status']] ?>" data-comment-status-badge><?= htmlspecialchars($statusLabels[$c['status']]) ?></span></td>
            <td data-label="Posted"><?= htmlspecialchars($c['posted']) ?></td>
            <td data-label="Actions">
              <div class="d-flex gap-1 justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#commentDetailModal" data-comment-view>View</button>
                <div class="dropdown">
                  <button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Comment actions by <?= htmlspecialchars($author['name']) ?>">
                    <span class="ms" aria-hidden="true">more_vert</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-comment-hide
                        <?= $isVisible ? '' : 'hidden' ?>
                        data-delete-title="Hide Comment?"
                        data-delete-message="Hide this comment from <?= htmlspecialchars($author['name']) ?> in demo mode? This is a demo action — no participants will be notified and no backend data will be changed."
                        data-delete-confirm-label="Hide Comment"
                        data-success-message="Comment hidden in demo mode."
                      >Hide</button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-comment-restore
                        <?= $isVisible ? 'hidden' : '' ?>
                        data-delete-title="Restore Comment?"
                        data-delete-message="Restore this comment from <?= htmlspecialchars($author['name']) ?> so it's visible again? This is a demo action only."
                        data-delete-confirm-label="Restore Comment"
                        data-success-message="Comment restored in demo mode."
                      >Restore</button>
                    </li>
                    <?php if ($c['reports'] > 0): ?>
                      <li><a class="dropdown-item" href="reports.php">Review Reports</a></li>
                    <?php endif; ?>
                  </ul>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-body"<?= $comments ? ' hidden' : '' ?> data-comment-empty>
    <?php ukn_empty_state([
        'icon' => 'chat_bubble',
        'title' => 'No comments found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-comment-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
  <div class="card-body ukn-admin-pagination" data-comment-pagination>
    <span class="ukn-body-sm ukn-text-muted" data-comment-pagination-summary></span>
    <div class="d-flex gap-1" data-comment-pagination-pages></div>
  </div>
</div>
<?php endif; ?>
<div class="modal fade" id="commentDetailModal" tabindex="-1" aria-labelledby="commentDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="commentDetailModalLabel">Comment Details</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
          <span class="ukn-body-sm ukn-text-muted" data-comment-detail="id">—</span>
          <span class="ukn-status ukn-status-accent" data-comment-detail="status">—</span>
        </div>
        <p class="ukn-body mb-2" data-comment-detail="text">—</p>
        <p class="ukn-body-sm mb-3">
          By <a href="#" data-comment-detail-link="author">—</a>
          <span class="ukn-text-muted"> &middot; <span data-comment-detail="posted"></span></span>
        </p>
        <div class="ukn-admin-row" hidden data-comment-detail-parent-row>
          <span class="ukn-body-sm ukn-text-muted">Reply to</span>
          <span class="ms-auto text-end"><span data-comment-detail="parentAuthor"></span><span class="d-block ukn-body-sm ukn-text-muted ukn-clamp-2" data-comment-detail="parentText"></span></span>
        </div>
        <div class="ukn-admin-row"><span class="ukn-body-sm ukn-text-muted">Parent Post</span><span class="fw-bold ms-auto text-end" data-comment-detail="post">—</span></div>
        <div class="ukn-admin-inline-stats my-3">
          <div><span class="ukn-eyebrow d-block">Replies</span><span class="fw-bold" data-comment-detail="replies">—</span></div>
          <div><span class="ukn-eyebrow d-block">Reports</span><span class="fw-bold" data-comment-detail="reports">—</span></div>
        </div>
        <div hidden data-comment-detail-reports-row>
          <div class="ukn-eyebrow mb-1">Report Reasons</div>
          <p class="ukn-body-sm" data-comment-detail="reportReasons"></p>
          <a href="reports.php" class="ukn-body-sm">Review Reports</a>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn btn-outline-secondary btn-sm" data-comment-detail-link="app">View Discussion</a>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>