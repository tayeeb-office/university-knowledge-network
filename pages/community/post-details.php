<?php
/**
 * Post Details — main center content only. Routed via
 * index.php?page=post-details[&id=..] (see index.php's $routes map). The
 * header, left sidebar, contextual right sidebar ($rightSidebarContext =
 * 'post', set by index.php's $sidebarContextByPage map — About Author /
 * Related Skills / Related Discussions, see includes/right-sidebar.php,
 * already built around this exact mock post) and footer come from the
 * shell — not from here.
 *
 * The full post reuses components/post-card.php's 'detail' variant
 * (full, unclamped content; a plain heading instead of a self-link; a
 * same-page #comments anchor instead of a link to this page) — no second
 * "full post" markup. Voting/Save/Edit/Delete are all the exact same
 * generic mechanisms every other post-card.php usage already relies on
 * (assets/js/components/voting.js, save-post.js, assets/js/core/modal.js's
 * contextual Edit/Delete population) — nothing here duplicates them.
 *
 * Comments/replies are plain PHP-rendered markup (there's no dedicated
 * comment component in this project, and comments only ever appear on
 * this one page, so one wouldn't earn its keep) using the exact same
 * structural convention assets/js/components/comments.js's JS-built
 * comments/replies use, so new and server-rendered comments behave
 * identically. Comment voting reuses the same .ukn-vote markup
 * post-card.php's rail uses, so assets/js/components/voting.js drives it
 * for free.
 *
 * $_GET['id'] only ever indexes into the small $posts lookup below — it
 * is never concatenated into a query or file path, so there is no
 * injection/traversal surface. An unrecognized id falls back to id 1 —
 * the one post includes/right-sidebar.php's 'post' context is itself
 * already built around, so that fallback keeps center content and
 * sidebar in agreement.
 *
 * Frontend-only mock data throughout — no real comment/reply/vote/save
 * persistence, no database, no backend of any kind.
 */
require_once __DIR__ . '/../../components/post-card.php';

$posts = [
    1 => [
        'id' => 1, 'author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'department' => 'Computer Science',
        'authorHref' => ukn_route_href('learner-profile'), 'time' => '12 min ago',
        'title' => 'Need Help Understanding Database Normalization',
        'excerpt' => "I get 1NF and 2NF but 3NF stops making sense once foreign keys are involved. I understand a table needs a primary key and that every column should depend on the whole key, not just part of it, but I can't tell where 2NF ends and 3NF begins in practice.\n\nCould someone walk through a simple example — maybe a student, course and instructor table — showing exactly which dependency each normal form removes? I don't need the formal definitions again, just a concrete before-and-after.",
        'tags' => ['Database', 'MySQL', 'DBMS'], 'score' => 24, 'comments' => 8, 'saved' => false, 'isOwner' => true,
    ],
];
$requestedId = isset($_GET['id']) && is_string($_GET['id']) && isset($posts[(int) $_GET['id']]) ? (int) $_GET['id'] : 1;
$post = $posts[$requestedId];

$comments = [
    [
        'author' => 'Rahim Ahmed', 'initials' => 'RA', 'role' => 'Mentor', 'authorHref' => ukn_route_href('mentor-profile') . '&id=2',
        'time' => '8 min ago', 'votes' => 12,
        'text' => '2NF removes partial dependency — a non-key column depending on only part of a composite key. 3NF removes transitive dependency — a non-key column depending on another non-key column instead of the key itself. Want me to walk through a students/courses example?',
        'replies' => [
            ['author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'time' => '6 min ago', 'text' => 'Yes please — a students/courses example would really help.'],
        ],
    ],
    [
        'author' => 'Ayesha Rahman', 'initials' => 'AR', 'role' => 'Learner', 'authorHref' => ukn_route_href('learner-profile'),
        'time' => '5 min ago', 'votes' => 6,
        'text' => 'I had the same confusion. A student-course-instructor example helped me understand the difference.',
        'replies' => [],
    ],
    [
        'author' => 'Hasan Mahmud', 'initials' => 'HM', 'role' => 'Mentor', 'authorHref' => ukn_route_href('mentor-profile') . '&id=3',
        'time' => '2 min ago', 'votes' => 4,
        'text' => 'Once you see 2NF and 3NF applied to the same table side by side, the difference stops feeling abstract — happy to share a quick before/after if it helps.',
        'replies' => [],
    ],
];
?>
<div data-post-detail>
  <a href="<?= htmlspecialchars(ukn_route_href('home')) ?>" class="ukn-body-sm d-inline-flex align-items-center gap-1 mb-3">
    <span class="ms" aria-hidden="true">arrow_back</span>Back to Community
  </a>

  <?php ukn_post_card($post, ['variant' => 'detail']); ?>

  <div class="card mb-3" id="comments">
    <div class="card-body">
      <h2 class="ukn-h4 mb-3"><span data-comments-heading-count><?= (int) $post['comments'] ?></span> Comments</h2>

      <form data-comment-form novalidate>
        <div class="ukn-cluster align-items-start" data-comment-composer data-current-user-name="<?= htmlspecialchars($currentUser['name'] ?? 'Nabila Rahman') ?>" data-current-user-initials="<?= htmlspecialchars($currentUser['initials'] ?? 'NR') ?>">
          <span class="ukn-avatar flex-shrink-0" aria-hidden="true"><?= htmlspecialchars($currentUser['initials'] ?? 'NR') ?></span>
          <div class="flex-fill ukn-min-w-0">
            <label for="newCommentText" class="ukn-visually-hidden">Add to the discussion</label>
            <textarea class="form-control" id="newCommentText" placeholder="Add to the discussion..."></textarea>
            <div class="ukn-field-message is-invalid mt-1" data-comment-error hidden>
              <span class="ms" aria-hidden="true">error</span>Write a comment before posting.
            </div>
            <div class="d-flex justify-content-end mt-2">
              <button type="submit" class="btn btn-primary btn-sm">Comment</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div data-comments-list>
    <?php foreach ($comments as $comment): ?>
      <div data-comment class="mb-2">
        <div class="card">
          <div class="card-body">
            <div class="ukn-cluster mb-2">
              <span class="ukn-avatar ukn-avatar-sm" aria-hidden="true"><?= htmlspecialchars($comment['initials']) ?></span>
              <div>
                <a href="<?= htmlspecialchars($comment['authorHref']) ?>" class="fw-bold text-body"><?= htmlspecialchars($comment['author']) ?></a>
                <span class="ukn-role-chip"><?= htmlspecialchars($comment['role']) ?></span>
                <div class="ukn-body-sm"><?= htmlspecialchars($comment['time']) ?></div>
              </div>
            </div>
            <p class="ukn-body-sm mb-2"><?= htmlspecialchars($comment['text']) ?></p>
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <span class="ukn-vote" data-vote-state="0">
                <button type="button" class="ukn-vote-btn is-up" data-vote-up aria-label="Upvote this comment" aria-pressed="false"><span class="ms" aria-hidden="true">arrow_upward</span></button>
                <span class="ukn-vote-score" data-vote-score data-vote-base="<?= (int) $comment['votes'] ?>"><?= (int) $comment['votes'] ?></span>
                <button type="button" class="ukn-vote-btn is-down" data-vote-down aria-label="Downvote this comment" aria-pressed="false"><span class="ms" aria-hidden="true">arrow_downward</span></button>
              </span>
              <button type="button" class="btn-ghost" data-comment-reply-toggle>Reply</button>
              <button type="button" class="btn-ghost" data-comment-report>Report</button>
            </div>

            <form data-reply-form hidden class="mt-3" novalidate>
              <label class="ukn-visually-hidden">Reply to <?= htmlspecialchars($comment['author']) ?></label>
              <textarea class="form-control form-control-sm" placeholder="Write a reply..."></textarea>
              <div class="ukn-field-message is-invalid mt-1" data-reply-error hidden>
                <span class="ms" aria-hidden="true">error</span>Write a reply before posting.
              </div>
              <div class="d-flex gap-2 mt-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-reply-cancel>Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm">Reply</button>
              </div>
            </form>
          </div>
        </div>

        <div data-replies-list class="mt-2">
          <?php foreach ($comment['replies'] as $reply): ?>
            <div class="card mt-2 ms-2 ms-md-4" data-reply>
              <div class="card-body">
                <div class="ukn-cluster mb-2">
                  <span class="ukn-avatar ukn-avatar-sm" aria-hidden="true"><?= htmlspecialchars($reply['initials']) ?></span>
                  <div>
                    <strong class="text-body"><?= htmlspecialchars($reply['author']) ?></strong>
                    <span class="ukn-role-chip"><?= htmlspecialchars($reply['role']) ?></span>
                    <div class="ukn-body-sm"><?= htmlspecialchars($reply['time']) ?></div>
                  </div>
                </div>
                <p class="ukn-body-sm mb-0"><?= htmlspecialchars($reply['text']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
