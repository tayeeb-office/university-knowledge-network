<?php
require_once __DIR__ . '/includes/users-data.php';
require_once __DIR__ . '/../components/empty-state.php';
require_once __DIR__ . '/../components/stat-card.php';
$adminActiveNav = 'posts';
$adminPageTitle = 'Posts';
$adminPageSub = 'Review community posts, reported content and visibility status.';
$adminPageStyles = ['../assets/css/admin/tables.css', '../assets/css/admin/forms.css'];
$adminPageScripts = ['../assets/js/admin/moderation.js'];
$users = ukn_admin_mock_users();
function ukn_admin_author(array $users, int $id): array
{
    $u = $users[$id];
    return ['id' => $id, 'name' => $u['name'], 'department' => $u['department']];
}
$posts = [
    ['id' => 'UKN-P-0001', 'publicId' => 1, 'title' => 'Need Help Understanding Database Normalization', 'author' => 1, 'tags' => ['DBMS', 'MySQL', 'Database Design'], 'excerpt' => 'I understand 1NF, but I am still confused about the practical difference between 2NF and 3NF.', 'votes' => 24, 'comments' => 8, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 13, 2026'],
    ['id' => 'UKN-P-0002', 'publicId' => 2, 'title' => 'A Simple Way to Start Learning Python for Data Analysis', 'author' => 2, 'tags' => ['Python', 'Data Analysis'], 'excerpt' => 'Skip the theory-heavy courses at first. Start with pandas on a dataset you actually care about.', 'votes' => 48, 'comments' => 12, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 13, 2026'],
    ['id' => 'UKN-P-0003', 'publicId' => 3, 'title' => 'Looking for a Public Speaking Practice Partner', 'author' => 6, 'tags' => ['Public Speaking', 'Communication'], 'excerpt' => 'Preparing for a case competition presentation and would love a few practice run-throughs.', 'votes' => 16, 'comments' => 6, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 13, 2026'],
    ['id' => 'UKN-P-0004', 'publicId' => 4, 'title' => 'Can Someone Explain Arduino Interrupts With a Practical Example?', 'author' => 4, 'tags' => ['Arduino', 'Embedded Systems'], 'excerpt' => 'I understand the attachInterrupt() syntax but keep getting inconsistent readings.', 'votes' => 31, 'comments' => 9, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 13, 2026'],
    ['id' => 'UKN-P-0005', 'publicId' => 5, 'title' => 'How Do You Improve Academic Presentation Skills?', 'author' => 3, 'tags' => ['Presentation', 'Communication'], 'excerpt' => 'My seminar presentations feel flat even when the research is solid.', 'votes' => 19, 'comments' => 14, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 13, 2026'],
    ['id' => 'UKN-P-0006', 'publicId' => 6, 'title' => 'Things I Learned While Building My First React Project', 'author' => 10, 'tags' => ['React', 'JavaScript'], 'excerpt' => 'Mainly that prop drilling gets painful fast and useEffect dependency arrays are not optional reading.', 'votes' => 27, 'comments' => 10, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 13, 2026'],
    ['id' => 'UKN-P-0007', 'publicId' => 7, 'title' => 'Best Resources for Learning UI/UX Design as a Beginner', 'author' => 6, 'tags' => ['UI/UX Design'], 'excerpt' => 'Business student trying to pick up enough UI/UX to prototype my own capstone project.', 'votes' => 22, 'comments' => 5, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 13, 2026'],
    ['id' => 'UKN-P-0008', 'publicId' => 8, 'title' => "What's the Fastest Way to Get Comfortable With SQL Joins?", 'author' => 2, 'tags' => ['MySQL', 'SQL'], 'excerpt' => 'Draw the two tables on paper before writing any query.', 'votes' => 37, 'comments' => 11, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 12, 2026'],
    ['id' => 'UKN-P-0009', 'publicId' => 402, 'title' => 'What Is the Best Way to Practice Python Data Analysis?', 'author' => 1, 'tags' => ['Python', 'Data Analysis'], 'excerpt' => 'Looking for a structured way to practice beyond following along with tutorials.', 'votes' => 31, 'comments' => 11, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 10, 2026'],
    ['id' => 'UKN-P-0010', 'publicId' => 403, 'title' => 'Looking for a Study Partner for Database Systems', 'author' => 1, 'tags' => ['Database Design', 'DBMS'], 'excerpt' => 'Anyone else in DBMS lab this semester want to study together before exams?', 'votes' => 14, 'comments' => 5, 'reports' => 0, 'status' => 'visible', 'posted' => 'Sep 6, 2026'],
    ['id' => 'UKN-P-0011', 'publicId' => 404, 'title' => 'Things I Learned After My First Group Project in DBMS Lab', 'author' => 1, 'tags' => ['Database Design', 'MySQL'], 'excerpt' => 'A few things I wish I knew before splitting up the schema design work.', 'votes' => 19, 'comments' => 6, 'reports' => 0, 'status' => 'visible', 'posted' => 'Aug 30, 2026'],
    ['id' => 'UKN-P-0012', 'publicId' => 405, 'title' => 'Which MySQL Resources Actually Helped You Learn Joins?', 'author' => 1, 'tags' => ['MySQL', 'DBMS'], 'excerpt' => 'Looking for practice problems that go beyond simple INNER JOIN examples.', 'votes' => 9, 'comments' => 3, 'reports' => 0, 'status' => 'visible', 'posted' => 'Aug 25, 2026'],
    ['id' => 'UKN-P-0013', 'publicId' => 3, 'title' => 'Best Resources for Learning MySQL Joins?', 'author' => 5, 'tags' => ['MySQL', 'Database'], 'excerpt' => 'Looking for practice problems that go beyond simple INNER JOIN examples.', 'votes' => 31, 'comments' => 6, 'reports' => 1, 'reportReasons' => ['Off-topic (very similar to an existing post)'], 'status' => 'visible', 'posted' => 'Sep 9, 2026'],
    ['id' => 'UKN-P-0014', 'publicId' => 1, 'title' => 'Anyone Want to Exchange Completed Assignment Files?', 'author' => 14, 'tags' => ['Academic'], 'excerpt' => 'Looking to trade completed assignments from last semester to save time.', 'votes' => 3, 'comments' => 2, 'reports' => 3, 'reportReasons' => ['Off-topic', 'Academic integrity concern'], 'status' => 'visible', 'posted' => 'Sep 13, 2026'],
    ['id' => 'UKN-P-0015', 'publicId' => 1, 'title' => 'Check Out My New Side Project Website!', 'author' => 15, 'tags' => ['Digital Marketing'], 'excerpt' => 'Promoting a personal side project unrelated to coursework or mentoring.', 'votes' => 2, 'comments' => 0, 'reports' => 2, 'reportReasons' => ['Spam', 'Repeated promotional content'], 'status' => 'hidden', 'posted' => 'Sep 12, 2026'],
];
$statusLabels = ['visible' => 'Visible', 'hidden' => 'Hidden'];
$statusClass = ['visible' => 'ukn-status-accent', 'hidden' => 'ukn-status-neutral'];
$skillOptions = [];
foreach ($posts as $p) {
    foreach ($p['tags'] as $tag) {
        $skillOptions[$tag] = true;
    }
}
$skillOptions = array_keys($skillOptions);
sort($skillOptions);
require __DIR__ . '/includes/header.php';
?>
<div class="ukn-admin-stat-grid mb-4">
  <button type="button" class="ukn-admin-stat-btn" data-post-summary-filter="status:">
    <?php ukn_stat_card(['label' => 'Total Posts', 'value' => '1,126', 'icon' => 'article']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-post-summary-filter="status:visible">
    <?php ukn_stat_card(['label' => 'Visible', 'value' => '1,102', 'icon' => 'visibility']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-post-summary-filter="status:hidden">
    <?php ukn_stat_card(['label' => 'Hidden', 'value' => '24', 'icon' => 'visibility_off']); ?>
  </button>
  <button type="button" class="ukn-admin-stat-btn" data-post-summary-filter="reported:reported">
    <?php ukn_stat_card(['label' => 'Reported', 'value' => '8', 'icon' => 'flag']); ?>
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
            $author = ukn_admin_author($users, $p['author']);
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
                        data-post-hide
                        <?= $isVisible ? '' : 'hidden' ?>
                        data-delete-title="Hide Post?"
                        data-delete-message="Hide &ldquo;<?= htmlspecialchars($p['title']) ?>&rdquo; from the community in demo mode? This is a demo action — no participants will be notified and no backend data will be changed."
                        data-delete-confirm-label="Hide Post"
                        data-success-message="Post hidden in demo mode."
                      >Hide</button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-post-restore
                        <?= $isVisible ? 'hidden' : '' ?>
                        data-delete-title="Restore Post?"
                        data-delete-message="Restore &ldquo;<?= htmlspecialchars($p['title']) ?>&rdquo; so it's visible to the community again? This is a demo action only."
                        data-delete-confirm-label="Restore Post"
                        data-success-message="Post restored in demo mode."
                      >Restore</button>
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
  <div class="card-body" hidden data-post-empty>
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