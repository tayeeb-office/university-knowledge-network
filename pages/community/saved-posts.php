<?php
/**
 * Saved Posts — main center content only. Routed via
 * index.php?page=saved-posts (see index.php's $routes map). The header,
 * left sidebar, contextual right sidebar and footer come from the shell —
 * not from here.
 *
 * Posts reuse components/post-card.php exactly as Home does (default
 * 'feed' variant), each with 'saved' => true and 'isOwner' => false —
 * these are other people's posts the mock user bookmarked, so no
 * Edit/Delete dropdown renders for any of them (post-card.php's own
 * isOwner gate, unchanged). Unsaving reuses the exact same generic
 * assets/js/components/save-post.js toggle every post-card.php save
 * button already has; assets/js/pages/community.js additionally removes
 * the card from THIS page's list once a post-card.php button here
 * actually becomes unsaved (see that file) — Save/Unsave itself is not
 * reimplemented.
 *
 * These 4 posts are the exact same ones already seeded on pages/home.php
 * (same ids/titles/authors/tags/scores) rather than inventing different
 * mock content for "the same posts" bookmarked from two different pages.
 *
 * Frontend-only mock data throughout — no real save/bookmark persistence,
 * no database, no backend of any kind.
 */
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/empty-state.php';

$savedPosts = [
    [
        'id' => 2, 'author' => 'Rahim Ahmed', 'initials' => 'RA', 'role' => 'Mentor', 'department' => 'Computer Science',
        'authorHref' => ukn_route_href('mentor-profile') . '&id=2', 'time' => '35 min ago',
        'title' => 'A Simple Way to Start Learning Python for Data Analysis',
        'excerpt' => 'Skip the theory-heavy courses at first. Start with pandas on a dataset you actually care about — it clicks a lot faster than notebooks full of print statements.',
        'tags' => ['Python', 'Data Analysis'], 'score' => 48, 'comments' => 12, 'saved' => true, 'isOwner' => false,
    ],
    [
        'id' => 4, 'author' => 'Hasan Mahmud', 'initials' => 'HM', 'role' => 'Mentor', 'department' => 'Electrical Engineering',
        'authorHref' => ukn_route_href('mentor-profile') . '&id=3', 'time' => '2 hours ago',
        'title' => 'Can Someone Explain Arduino Interrupts With a Practical Example?',
        'excerpt' => 'I understand the attachInterrupt() syntax but keep getting inconsistent readings on a push-button debounce circuit. A real wiring example would help more than the docs.',
        'tags' => ['Arduino', 'Embedded Systems'], 'score' => 31, 'comments' => 9, 'saved' => true, 'isOwner' => false,
    ],
    [
        'id' => 5, 'author' => 'Imran Chowdhury', 'initials' => 'IC', 'role' => 'Learner', 'department' => 'English',
        'authorHref' => ukn_route_href('learner-profile') . '&id=1', 'time' => '3 hours ago',
        'title' => 'How Do You Improve Academic Presentation Skills?',
        'excerpt' => 'My seminar presentations feel flat even when the research is solid. Looking for practical habits, not just "practice more" advice.',
        'tags' => ['Presentation', 'Communication'], 'score' => 19, 'comments' => 14, 'saved' => true, 'isOwner' => false,
    ],
    [
        'id' => 7, 'author' => 'Sara Khan', 'initials' => 'SK', 'role' => 'Learner', 'department' => 'Business Administration',
        'authorHref' => ukn_route_href('learner-profile') . '&id=2', 'time' => '7 hours ago',
        'title' => 'Best Resources for Learning UI/UX Design as a Beginner',
        'excerpt' => 'Business student trying to pick up enough UI/UX to prototype my own capstone project. Free resources preferred over paid courses for now.',
        'tags' => ['UI/UX Design'], 'score' => 22, 'comments' => 5, 'saved' => true, 'isOwner' => false,
    ],
];
?>
<div class="ukn-page-header">
  <div>
    <h1>Saved Posts</h1>
    <p class="ukn-page-header__sub">Posts you've saved to read or revisit later.</p>
  </div>
</div>

<div data-post-list="saved">
  <?php foreach ($savedPosts as $post): ukn_post_card($post); endforeach; ?>
</div>

<div<?= $savedPosts ? ' hidden' : '' ?> data-post-list-empty>
  <?php ukn_empty_state([
      'icon' => 'bookmark_border',
      'title' => 'No saved posts yet.',
      'message' => "Save useful discussions and they'll appear here.",
      'action' => ['label' => 'Browse Community', 'href' => ukn_route_href('home')],
      'dashed' => true,
  ]); ?>
</div>
