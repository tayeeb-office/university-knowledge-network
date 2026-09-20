<?php
require_once __DIR__ . '/../../components/post-card.php';
require_once __DIR__ . '/../../components/empty-state.php';
$myPosts = [
    [
        'id' => 1, 'author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'department' => 'Computer Science',
        'authorHref' => ukn_route_href('learner-profile'), 'time' => '12 min ago',
        'title' => 'Need Help Understanding Database Normalization',
        'excerpt' => "I get 1NF and 2NF but 3NF stops making sense once foreign keys are involved. Does anyone have a simple example that isn't the classic student/course table?",
        'tags' => ['DBMS', 'MySQL'], 'score' => 24, 'comments' => 8, 'isOwner' => true,
    ],
    [
        'id' => 402, 'author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'department' => 'Computer Science',
        'authorHref' => ukn_route_href('learner-profile'), 'time' => '2 days ago',
        'title' => 'What Is the Best Way to Practice Python Data Analysis?',
        'excerpt' => "I've finished the basics of pandas but I'm not sure what to build next. Should I pick a Kaggle dataset, or is there a more structured way to practice before jumping into a real project?",
        'tags' => ['Python', 'Data Analysis'], 'score' => 31, 'comments' => 11, 'isOwner' => true,
    ],
    [
        'id' => 403, 'author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'department' => 'Computer Science',
        'authorHref' => ukn_route_href('learner-profile'), 'time' => '4 days ago',
        'title' => 'Looking for a Study Partner for Database Systems',
        'excerpt' => "Preparing for the DBMS midterm and would rather work through past papers with someone than alone. CS or related department, evenings work best for me.",
        'tags' => ['Database Design', 'DBMS'], 'score' => 14, 'comments' => 5, 'isOwner' => true,
    ],
    [
        'id' => 404, 'author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'department' => 'Computer Science',
        'authorHref' => ukn_route_href('learner-profile'), 'time' => '1 week ago',
        'title' => 'Things I Learned After My First Group Project in DBMS Lab',
        'excerpt' => "Splitting the schema design before agreeing on naming conventions was our first mistake. Writing down a short list of what actually went wrong in case it saves someone else a merge conflict.",
        'tags' => ['Database Design', 'MySQL'], 'score' => 19, 'comments' => 6, 'isOwner' => true,
    ],
    [
        'id' => 405, 'author' => 'Nabila Rahman', 'initials' => 'NR', 'role' => 'Learner', 'department' => 'Computer Science',
        'authorHref' => ukn_route_href('learner-profile'), 'time' => '2 weeks ago',
        'title' => 'Which MySQL Resources Actually Helped You Learn Joins?',
        'excerpt' => "I understand INNER JOIN fine but LEFT/RIGHT JOIN with multiple tables still takes me a few tries to get right. Looking for resources that go beyond the two-table textbook examples.",
        'tags' => ['MySQL', 'DBMS'], 'score' => 9, 'comments' => 3, 'isOwner' => true,
    ],
];
$skillOptions = [];
foreach ($myPosts as $post) {
    foreach ($post['tags'] as $tag) {
        $skillOptions[$tag] = true;
    }
}
$skillOptions = array_keys($skillOptions);
sort($skillOptions);
?>
<div class="ukn-page-header">
  <div>
    <h1>My Posts</h1>
    <p class="ukn-page-header__sub">Manage the discussions and questions you've shared.</p>
  </div>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPostModal">
    <span class="ms" aria-hidden="true">add</span>Create Post
  </button>
</div>
<div class="d-flex flex-wrap gap-2 mb-3" data-post-filters="my">
  <div class="ukn-search">
    <span class="ms" aria-hidden="true">search</span>
    <label for="myPostsSearch" class="ukn-visually-hidden">Search your posts</label>
    <input type="search" id="myPostsSearch" class="form-control" placeholder="Search your posts..." data-post-search-input>
  </div>
  <select class="form-select w-auto" aria-label="Filter by skill" data-post-skill-filter>
    <option value="">All Skills</option>
    <?php foreach ($skillOptions as $skill): ?>
      <option value="<?= htmlspecialchars(strtolower($skill)) ?>"><?= htmlspecialchars($skill) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div data-post-list="my">
  <?php foreach ($myPosts as $post): ukn_post_card($post); endforeach; ?>
</div>
<div<?= $myPosts ? ' hidden' : '' ?> data-post-list-empty>
  <?php ukn_empty_state([
      'icon' => 'forum',
      'title' => "You haven't created any posts yet.",
      'message' => 'Start a discussion or ask the community a question.',
      'action' => ['label' => 'Create Post', 'href' => '#', 'attrs' => 'data-bs-toggle="modal" data-bs-target="#createPostModal"'],
  ]); ?>
</div>