<?php
require_once __DIR__ . '/../backend/helpers/auth.php';
requireAdmin();
require_once __DIR__ . '/../backend/helpers/csrf.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/error-state.php';
require_once __DIR__ . '/../components/stat-card.php';
require_once __DIR__ . '/../backend/config/database.php';
$adminActiveNav = 'posts';
$adminPageTitle = 'Posts';
$adminPageSub = 'Review community posts, reported content and visibility status.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/moderation.js'];
$statusLabels = ['visible' => 'Visible', 'hidden' => 'Hidden'];
$statusClass = ['visible' => 'ukn-status-accent', 'hidden' => 'ukn-status-neutral'];
$reasonLabels = [
    'academic-integrity' => 'Academic integrity concern', 'off-topic' => 'Off-topic',
    'spam' => 'Spam', 'inappropriate' => 'Inappropriate', 'harassment' => 'Harassment', 'other' => 'Other',
];

$posts = [];
$skillOptions = [];
$totalCount = 0;
$visibleCount = 0;
$hiddenCount = 0;
$reportedCount = 0;
$postsDbError = false;

try {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->query(
        "SELECT p.id, p.title, p.content, p.vote_score AS votes, p.comment_count AS comments,
                p.report_count AS reports, p.status, p.created_at,
                u.id AS author_id, u.full_name AS author_name, d.name AS author_department
         FROM posts p
         JOIN users u ON u.id = p.user_id
         LEFT JOIN departments d ON d.id = u.department_id
         ORDER BY p.created_at DESC"
    );
    $rows = $stmt->fetchAll();

    if ($rows) {
        $postIds = array_column($rows, 'id');
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));

        $tagsStmt = $pdo->prepare(
            "SELECT ps.post_id, s.name FROM post_skills ps JOIN skills s ON s.id = ps.skill_id
             WHERE ps.post_id IN ($placeholders)"
        );
        $tagsStmt->execute($postIds);
        $tagsByPost = [];
        foreach ($tagsStmt->fetchAll() as $row) {
            $tagsByPost[$row['post_id']][] = $row['name'];
        }

        $reportsStmt = $pdo->prepare(
            "SELECT target_id, reason, description FROM reports
             WHERE target_type = 'post' AND target_id IN ($placeholders)
             ORDER BY created_at DESC"
        );
        $reportsStmt->execute($postIds);
        $reasonsByPost = [];
        foreach ($reportsStmt->fetchAll() as $row) {
            $label = $row['description'] !== null && $row['description'] !== ''
                ? $row['description']
                : ($reasonLabels[$row['reason']] ?? ucfirst($row['reason']));
            $reasonsByPost[$row['target_id']][] = $label;
        }

        foreach ($rows as $row) {
            $tags = $tagsByPost[$row['id']] ?? [];
            foreach ($tags as $tag) {
                $skillOptions[$tag] = true;
            }
            $reports = (int) $row['reports'];
            $post = [
                'id' => 'UKN-P-' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT),
                'publicId' => (int) $row['id'],
                'title' => $row['title'],
                'author' => (int) $row['author_id'],
                'authorName' => $row['author_name'],
                'authorDepartment' => (string) ($row['author_department'] ?? ''),
                'tags' => $tags,
                'excerpt' => $row['content'],
                'votes' => (int) $row['votes'],
                'comments' => (int) $row['comments'],
                'reports' => $reports,
                'reportReasons' => $reasonsByPost[$row['id']] ?? [],
                'status' => $row['status'],
                'posted' => date('M j, Y', strtotime($row['created_at'])),
            ];
            $posts[] = $post;

            $totalCount++;
            if ($post['status'] === 'visible') {
                $visibleCount++;
            } else {
                $hiddenCount++;
            }
            if ($reports > 0) {
                $reportedCount++;
            }
        }
    }
    $skillOptions = array_keys($skillOptions);
    sort($skillOptions);
} catch (Throwable $e) {
    error_log('[UKN admin/posts] ' . $e->getMessage());
    $postsDbError = true;
    $posts = [];
}
require __DIR__ . '/includes/header.php';
?>
<?php if ($postsDbError): ?>
  <?php ukn_error_state([
      'title' => 'Unable to load posts.',
      'message' => 'Something went wrong while loading this page. Please try again shortly.',
  ]); ?>
<?php else: ?>
<div class="ukn-admin-stat-grid mb-4">
  <button type="button" class="ukn-admin-stat-btn" data-post-summary-filter="status:">
    <?php ukn_stat_card(['label' => 'Total Posts', 'value' => number_format($totalCount), 'icon' => 'article']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-post-summary-filter="status:visible">
    <?php ukn_stat_card(['label' => 'Visible', 'value' => number_format($visibleCount), 'icon' => 'visibility']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-post-summary-filter="status:hidden">
    <?php ukn_stat_card(['label' => 'Hidden', 'value' => number_format($hiddenCount), 'icon' => 'visibility_off']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-post-summary-filter="reported:reported">
    <?php ukn_stat_card(['label' => 'Reported', 'value' => number_format($reportedCount), 'icon' => 'flag']); ?>
  </button>
</div>
<div class="card mb-3">
  <div class="card-body">
    <div class="ukn-admin-filters">
      <div class="ukn-search ukn-admin-filters__search">
        <span class="ms" aria-hidden="true">search</span>
        <label for="postSearchInput" class="ukn-visually-hidden">Search posts by title, author or content</label>
        <input type="search" id="postSearchInput" class="form-control" placeholder="Search posts by title, author or content…" data-post-search autocomplete="off">
      </div>
      <label class="ukn-visually-hidden" for="postStatusFilter">Filter by status</label>
      <select id="postStatusFilter" class="form-select form-select-sm" data-post-filter="status">
        <option value="">All Statuses</option>
        <option value="visible">Visible</option>
        <option value="hidden">Hidden</option>
      </select>
      <label class="ukn-visually-hidden" for="postReportedFilter">Filter by reported state</label>
      <select id="postReportedFilter" class="form-select form-select-sm" data-post-filter="reported">
        <option value="">All Posts</option>
        <option value="reported">Reported</option>
        <option value="not-reported">Not Reported</option>
      </select>
      <label class="ukn-visually-hidden" for="postSkillFilter">Filter by skill</label>
      <select id="postSkillFilter" class="form-select form-select-sm" data-post-filter="skill">
        <option value="">All Skills</option>
        <?php foreach ($skillOptions as $skill): ?>
          <option value="<?= htmlspecialchars(strtolower($skill)) ?>"><?= htmlspecialchars($skill) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="ukn-visually-hidden" for="postSort">Sort posts</label>
      <select id="postSort" class="form-select form-select-sm" data-post-sort>
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="most-reported">Most Reported</option>
        <option value="most-discussed">Most Discussed</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-post-clear-filters>Clear Filters</button>
    </div>
  </div>
</div>
<p class="ukn-body-sm ukn-text-muted" data-post-result-count role="status"><?= count($posts) ?> posts found</p>
<div class="card">
  <div class="table-responsive">
    <table class="table ukn-admin-table" data-post-table>
      <thead>
        <tr>
          <th scope="col">Post</th>
          <th scope="col">Author</th>
          <th scope="col">Skills</th>
          <th scope="col">Votes</th>
          <th scope="col">Comments</th>
          <th scope="col">Reports</th>
          <th scope="col">Status</th>
          <th scope="col">Posted</th>
          <th scope="col" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $p):
            $author = ['id' => $p['author'], 'name' => $p['authorName'], 'department' => $p['authorDepartment']];
            $isVisible = $p['status'] === 'visible';
            $reportReasons = $p['reportReasons'] ?? [];
        ?>
          <tr
            data-post-row
            data-post-id="<?= htmlspecialchars($p['id']) ?>"
            data-post-search="<?= htmlspecialchars(strtolower($p['id'] . ' ' . $p['title'] . ' ' . $author['name'] . ' ' . $p['excerpt'] . ' ' . implode(' ', $p['tags']))) ?>"
            data-post-status="<?= htmlspecialchars($p['status']) ?>"
            data-post-reported="<?= $p['reports'] > 0 ? 'reported' : 'not-reported' ?>"
            data-post-skills="<?= htmlspecialchars(strtolower(implode('|', $p['tags']))) ?>"
            data-post-reports="<?= (int) $p['reports'] ?>"
            data-post-comments="<?= (int) $p['comments'] ?>"
            data-post-date-sort="<?= (int) strtotime($p['posted']) ?>"
            data-post-title="<?= htmlspecialchars($p['title']) ?>"
            data-post-excerpt="<?= htmlspecialchars($p['excerpt']) ?>"
            data-post-author-id="<?= $author['id'] ?>"
            data-post-author-name="<?= htmlspecialchars($author['name']) ?>"
            data-post-author-department="<?= htmlspecialchars($author['department']) ?>"
            data-post-tags="<?= htmlspecialchars(implode('|', $p['tags'])) ?>"
            data-post-votes="<?= (int) $p['votes'] ?>"
            data-post-posted="<?= htmlspecialchars($p['posted']) ?>"
            data-post-public-id="<?= (int) $p['publicId'] ?>"
            data-post-report-reasons="<?= htmlspecialchars(implode('|', $reportReasons)) ?>"
          >
            <td data-label="Post" class="fw-bold" data-post-cell="title"><?= htmlspecialchars($p['title']) ?></td>
            <td data-label="Author">
              <a href="user-details.php?id=<?= $author['id'] ?>" aria-label="View <?= htmlspecialchars($author['name']) ?> in Admin"><?= htmlspecialchars($author['name']) ?></a>
            </td>
            <td data-label="Skills">
              <?php foreach ($p['tags'] as $tag): ?><span class="ukn-tag-neutral"><?= htmlspecialchars($tag) ?></span> <?php endforeach; ?>
            </td>
            <td data-label="Votes"><?= (int) $p['votes'] ?></td>
            <td data-label="Comments"><?= (int) $p['comments'] ?></td>
            <td data-label="Reports"><?= $p['reports'] > 0 ? (int) $p['reports'] . ' reports' : '0' ?></td>
            <td data-label="Status"><span class="ukn-status <?= $statusClass[$p['status']] ?>" data-post-status-badge><?= htmlspecialchars($statusLabels[$p['status']]) ?></span></td>
            <td data-label="Posted"><?= htmlspecialchars($p['posted']) ?></td>
            <td data-label="Actions">
              <form action="../backend/admin/posts/status.php" method="post" id="postStatus-<?= (int) $p['publicId'] ?>" hidden>
                <?= csrfField() ?>
                <?= uknReturnToField() ?>
                <input type="hidden" name="post_id" value="<?= (int) $p['publicId'] ?>">
                <input type="hidden" name="status" value="<?= $isVisible ? 'hidden' : 'visible' ?>">
              </form>
              <div class="d-flex gap-1 justify-content-md-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#postDetailModal" data-post-view>View</button>
                <div class="dropdown">
                  <button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post actions for <?= htmlspecialchars($p['title']) ?>">
                    <span class="ms" aria-hidden="true">more_vert</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-delete-form="postStatus-<?= (int) $p['publicId'] ?>"
                        <?php if ($isVisible): ?>
                        data-delete-title="Hide Post?"
                        data-delete-message="Hide &ldquo;<?= htmlspecialchars($p['title']) ?>&rdquo; from the community? It disappears from feeds, search and its own page; its comments, votes, saves and reports are kept, and it can be restored."
                        data-delete-confirm-label="Hide Post"
                        <?php else: ?>
                        data-delete-title="Restore Post?"
                        data-delete-message="Restore &ldquo;<?= htmlspecialchars($p['title']) ?>&rdquo; so it's visible to the community again?"
                        data-delete-confirm-label="Restore Post"
                        <?php endif; ?>
                      ><?= $isVisible ? 'Hide' : 'Restore' ?></button>
                    </li>
                    <?php if ($p['reports'] > 0): ?>
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
  <div class="card-body"<?= $posts ? ' hidden' : '' ?> data-post-empty>
    <?php ukn_empty_state([
        'icon' => 'article',
        'title' => 'No posts found.',
        'message' => 'Try changing your search or filters.',
        'action' => ['label' => 'Clear Filters', 'href' => '#', 'attrs' => 'data-post-clear-filters'],
        'dashed' => true,
    ]); ?>
  </div>
  <div class="card-body ukn-admin-pagination" data-post-pagination>
    <span class="ukn-body-sm ukn-text-muted" data-post-pagination-summary></span>
    <div class="d-flex gap-1" data-post-pagination-pages></div>
  </div>
</div>
<?php endif; ?>
<div class="modal fade" id="postDetailModal" tabindex="-1" aria-labelledby="postDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title ukn-h3" id="postDetailModalLabel">Post Details</h2>
        <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
          <span class="ms" aria-hidden="true">close</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
          <span class="ukn-body-sm ukn-text-muted" data-post-detail="id">—</span>
          <span class="ukn-status ukn-status-accent" data-post-detail="status">—</span>
        </div>
        <h3 class="ukn-h4" data-post-detail="title">—</h3>
        <p class="ukn-body-sm mb-2">
          By <a href="#" data-post-detail-link="author">—</a>
          <span class="ukn-text-muted"> &middot; <span data-post-detail="posted"></span></span>
        </p>
        <div class="d-flex flex-wrap gap-2 mb-3" data-post-detail-tags></div>
        <p class="ukn-body" data-post-detail="excerpt">—</p>
        <div class="ukn-admin-inline-stats mb-3">
          <div><span class="ukn-eyebrow d-block">Votes</span><span class="fw-bold" data-post-detail="votes">—</span></div>
          <div><span class="ukn-eyebrow d-block">Comments</span><span class="fw-bold" data-post-detail="comments">—</span></div>
          <div><span class="ukn-eyebrow d-block">Reports</span><span class="fw-bold" data-post-detail="reports">—</span></div>
        </div>
        <div hidden data-post-detail-reports-row>
          <div class="ukn-eyebrow mb-1">Report Reasons</div>
          <p class="ukn-body-sm" data-post-detail="reportReasons"></p>
          <a href="reports.php" class="ukn-body-sm">Review Reports</a>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn btn-outline-secondary btn-sm" data-post-detail-link="app">View in Application</a>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>