<?php
/**
 * Home / Community Feed — main center content.
 * Routed via index.php?page=home (see index.php's $routes map).
 *
 * This file holds ONLY page-specific main content — the header, left
 * sidebar, the Home contextual right sidebar (includes/right-sidebar.php,
 * $rightSidebarContext = 'home' set by index.php) and footer come from
 * the shell in index.php / includes/header.php / includes/footer.php,
 * not from here.
 *
 * Frontend-only mock feed: no database, no API, no real post/vote/save
 * persistence. Posts render through the shared components/post-card.php
 * (not duplicated here); Create/Edit/Delete reuse the shared modal
 * system already wired up in includes/footer.php.
 */
require_once __DIR__ . '/../components/post-card.php';
require_once __DIR__ . '/../components/empty-state.php';

$viewerName = $currentUser['name'] ?? 'Member';

/**
 * Realistic mock community posts (brand guide section 29 — no Lorem
 * Ipsum). 'following' flags a handful of posts so the Following tab has
 * something to show; 'id' 1 belongs to the current viewer (Nabila Rahman)
 * so it's the only card that renders owner-only Edit/Delete controls.
 */
$communityPosts = [
    [
        'id' => 1, 'author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'department' => 'Computer Science',
        'time' => '12 min ago',
        'title' => 'Need Help Understanding Database Normalization',
        'excerpt' => "I get 1NF and 2NF but 3NF stops making sense once foreign keys are involved. Does anyone have a simple example that isn't the classic student/course table?",
        'tags' => ['Database', 'MySQL', 'DBMS'], 'score' => 24, 'comments' => 8, 'following' => true,
    ],
    [
        'id' => 2, 'author' => 'Rahim Ahmed', 'initials' => 'RA', 'role' => 'Mentor', 'department' => 'Computer Science',
        'time' => '35 min ago',
        'title' => 'A Simple Way to Start Learning Python for Data Analysis',
        'excerpt' => 'Skip the theory-heavy courses at first. Start with pandas on a dataset you actually care about — it clicks a lot faster than notebooks full of print statements.',
        'tags' => ['Python', 'Data Analysis'], 'score' => 48, 'comments' => 12, 'following' => true,
    ],
    [
        'id' => 3, 'author' => 'Sara Khan', 'initials' => 'SK', 'role' => 'Learner', 'department' => 'Business Administration',
        'time' => '1 hour ago',
        'title' => 'Looking for a Public Speaking Practice Partner',
        'excerpt' => 'Preparing for a case competition presentation and would love a few practice run-throughs with someone this week. Open to swapping skills too.',
        'tags' => ['Public Speaking', 'Communication'], 'score' => 16, 'comments' => 6, 'following' => false,
    ],
    [
        'id' => 4, 'author' => 'Hasan Mahmud', 'initials' => 'HM', 'role' => 'Mentor', 'department' => 'Electrical Engineering',
        'time' => '2 hours ago',
        'title' => 'Can Someone Explain Arduino Interrupts With a Practical Example?',
        'excerpt' => 'I understand the attachInterrupt() syntax but keep getting inconsistent readings on a push-button debounce circuit. A real wiring example would help more than the docs.',
        'tags' => ['Arduino', 'Embedded Systems'], 'score' => 31, 'comments' => 9, 'following' => false,
    ],
    [
        'id' => 5, 'author' => 'Imran Chowdhury', 'initials' => 'IC', 'role' => 'Learner', 'department' => 'English',
        'time' => '3 hours ago',
        'title' => 'How Do You Improve Academic Presentation Skills?',
        'excerpt' => 'My seminar presentations feel flat even when the research is solid. Looking for practical habits, not just "practice more" advice.',
        'tags' => ['Presentation', 'Communication'], 'score' => 19, 'comments' => 14, 'following' => false,
    ],
    [
        'id' => 6, 'author' => 'Tanvir Hossain', 'initials' => 'TH', 'role' => 'Learner', 'department' => 'Computer Science',
        'time' => '5 hours ago',
        'title' => 'Things I Learned While Building My First React Project',
        'excerpt' => 'Mainly that prop drilling gets painful fast and useEffect dependency arrays are not optional reading. Sharing a few mistakes so others can skip them.',
        'tags' => ['React', 'JavaScript'], 'score' => 27, 'comments' => 10, 'following' => false,
    ],
    [
        'id' => 7, 'author' => 'Sara Khan', 'initials' => 'SK', 'role' => 'Learner', 'department' => 'Business Administration',
        'time' => '7 hours ago',
        'title' => 'Best Resources for Learning UI/UX Design as a Beginner',
        'excerpt' => 'Business student trying to pick up enough UI/UX to prototype my own capstone project. Free resources preferred over paid courses for now.',
        'tags' => ['UI/UX Design'], 'score' => 22, 'comments' => 5, 'following' => false,
    ],
    [
        'id' => 8, 'author' => 'Rahim Ahmed', 'initials' => 'RA', 'role' => 'Mentor', 'department' => 'Computer Science',
        'time' => '1 day ago',
        'title' => "What's the Fastest Way to Get Comfortable With SQL Joins?",
        'excerpt' => 'Draw the two tables on paper before writing any query. Sounds basic, but it fixes most of the confusion I see in mentoring sessions.',
        'tags' => ['MySQL', 'SQL'], 'score' => 37, 'comments' => 11, 'following' => true,
    ],
];

foreach ($communityPosts as &$post) {
    $post['href'] = 'index.php?page=post-details&id=' . $post['id'];
    $post['authorHref'] = ukn_route_href($post['role'] === 'Mentor' ? 'mentor-profile' : 'learner-profile') . '&id=' . $post['id'];
    $post['isOwner'] = ($post['author'] === $viewerName);
}
unset($post);

$firstBatch = array_slice($communityPosts, 0, 5);
$remainingBatch = array_slice($communityPosts, 5);
?>
<div class="ukn-page-header">
  <div>
    <h1>Home</h1>
    <p class="ukn-page-header__sub">Ask questions, share what you know, and connect with learners and mentors across campus.</p>
  </div>
</div>

<?php if (empty($communityPosts)): ?>

  <?php
  ukn_empty_state([
      'icon' => 'forum',
      'title' => 'No posts to show yet.',
      'message' => 'Start a discussion or share something with the community.',
      'action' => ['label' => 'Create Post', 'href' => '#'],
      'dashed' => true,
  ]);
  ?>

<?php else: ?>

  <div class="card mb-3">
    <div class="card-body ukn-composer__row">
      <span class="ukn-avatar ukn-avatar-sm" aria-hidden="true"><?= htmlspecialchars($currentUser['initials'] ?? '?') ?></span>
      <button type="button" class="form-control ukn-composer__field" data-bs-toggle="modal" data-bs-target="#createPostModal">
        Share something with the community&hellip;
      </button>
      <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createPostModal">
        <span class="ms" aria-hidden="true">add</span>Post
      </button>
    </div>
  </div>

  <div class="ukn-feed-controls" data-feed>
    <ul class="nav nav-tabs mb-3" aria-label="Sort community feed">
      <li class="nav-item">
        <button type="button" class="nav-link active" data-feed-tab="latest" aria-pressed="true">Latest</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" data-feed-tab="popular" aria-pressed="false">Popular</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" data-feed-tab="following" aria-pressed="false">Following</button>
      </li>
    </ul>

    <div data-feed-list>
      <?php foreach ($firstBatch as $post): ukn_post_card($post); ?>
      <?php endforeach; ?>
      <?php if ($remainingBatch): ?>
        <div data-feed-more hidden>
          <?php foreach ($remainingBatch as $post): ukn_post_card($post); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="ukn-feed-end">
      <?php if ($remainingBatch): ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-load-more>Load More</button>
      <?php endif; ?>
      <p class="ukn-body-sm mb-0" data-feed-end<?= $remainingBatch ? ' hidden' : '' ?>>You&rsquo;re all caught up.</p>
    </div>
  </div>

<?php endif; ?>
