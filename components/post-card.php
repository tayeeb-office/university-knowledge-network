<?php
require_once __DIR__ . '/../backend/helpers/avatars.php';
if (!function_exists('ukn_post_card')) {
    function ukn_post_card(array $post, array $options = []): void
    {
        $post += [
            'href' => '#', 'author' => 'Member', 'authorHref' => null, 'initials' => '?', 'role' => 'Learner',
            'department' => '', 'time' => '', 'title' => 'Untitled post', 'excerpt' => '',
            'tags' => [], 'score' => 0, 'voteState' => 0, 'comments' => 0, 'saved' => false,
            'following' => false, 'isOwner' => true,
        ];
        $isDetail = ($options['variant'] ?? 'feed') === 'detail';
        $upClass = $post['voteState'] === 1 ? ' is-active' : '';
        $downClass = $post['voteState'] === -1 ? ' is-active' : '';
        $skillIds = [
            'python' => 1, 'mysql' => 2, 'react' => 3, 'ui/ux design' => 4, 'data analysis' => 5,
            'public speaking' => 6, 'database design' => 7, 'arduino' => 8, 'academic writing' => 9, 'digital marketing' => 10,
        ];
        // Vote / save / delete are real POST forms (backend/posts/*.php). The buttons stay where
        // they are and point at hidden forms via the HTML form="" attribute.
        $postId = (int) ($post['id'] ?? 0);
        $formAttr = static fn (string $kind): string => $postId > 0
            ? 'type="submit" form="' . $kind . 'Post-' . $postId . '"'
            : 'type="button"';
        $searchText = strtolower($post['title'] . ' ' . $post['author'] . ' ' . implode(' ', $post['tags']));
        $skillsAttr = strtolower(implode('|', $post['tags']));
        ?>
        <article
          class="card ukn-post-card mb-3<?= $isDetail ? '' : ' ukn-card-interactive' ?>"
          data-post-id="<?= htmlspecialchars((string) ($post['id'] ?? '')) ?>"
          data-votes="<?= (int) $post['score'] ?>"
          data-following="<?= $post['following'] ? 'true' : 'false' ?>"
          data-post-search="<?= htmlspecialchars($searchText) ?>"
          data-post-skills="<?= htmlspecialchars($skillsAttr) ?>"
        >
          <div class="ukn-vote-rail">
            <button <?= $formAttr('vote') ?> name="value" value="1" class="ukn-vote-btn is-up<?= $upClass ?>" data-vote-up aria-label="Upvote this post" aria-pressed="<?= $post['voteState'] === 1 ? 'true' : 'false' ?>">
              <span class="ms" aria-hidden="true">arrow_upward</span>
            </button>
            <span class="ukn-vote-score" data-vote-score data-vote-base="<?= (int) $post['score'] ?>"><?= (int) $post['score'] ?></span>
            <button <?= $formAttr('vote') ?> name="value" value="-1" class="ukn-vote-btn is-down<?= $downClass ?>" data-vote-down aria-label="Downvote this post" aria-pressed="<?= $post['voteState'] === -1 ? 'true' : 'false' ?>">
              <span class="ms" aria-hidden="true">arrow_downward</span>
            </button>
          </div>
          <div class="ukn-post-card__body">
            <div class="ukn-post-card__meta">
              <?= uknAvatarHtml($post['avatar_path'] ?? null, (string) $post['initials'], 'ukn-avatar ukn-avatar-sm') ?>
              <?php if ($post['authorHref']): ?>
                <a href="<?= htmlspecialchars($post['authorHref']) ?>" class="text-body fw-bold"><?= htmlspecialchars($post['author']) ?></a>
              <?php else: ?>
                <strong class="text-body"><?= htmlspecialchars($post['author']) ?></strong>
              <?php endif; ?>
              <span class="ukn-role-chip"><?= htmlspecialchars($post['role']) ?></span>
              <?php if ($post['department']): ?><span><?= htmlspecialchars($post['department']) ?></span><?php endif; ?>
              <?php if ($post['time']): ?><span class="ukn-dot"><?= htmlspecialchars($post['time']) ?></span><?php endif; ?>
            </div>
            <?php if ($isDetail): ?>
              <h1 class="ukn-post-card__title ukn-post-card__title--detail"><?= htmlspecialchars($post['title']) ?></h1>
            <?php else: ?>
              <h2 class="ukn-post-card__title">
                <a href="<?= htmlspecialchars($post['href']) ?>"><?= htmlspecialchars($post['title']) ?></a>
              </h2>
            <?php endif; ?>
            <?php if ($post['excerpt']):
              if ($isDetail):
                foreach (preg_split('/\n\s*\n/', trim($post['excerpt'])) as $paragraph): ?>
                  <p class="ukn-body ukn-prose mb-2"><?= htmlspecialchars($paragraph) ?></p>
                <?php endforeach;
              else: ?>
                <p class="ukn-body ukn-prose ukn-clamp-3 mb-2"><?= htmlspecialchars($post['excerpt']) ?></p>
              <?php endif;
            endif; ?>
            <?php if ($post['tags']): ?>
              <div class="d-flex flex-wrap gap-2 mb-2">
                <?php foreach ($post['tags'] as $tag):
                  $skillId = $skillIds[strtolower($tag)] ?? null;
                  $tagHref = 'index.php?page=skill-details' . ($skillId ? '&id=' . $skillId : '');
                ?>
                  <a href="<?= htmlspecialchars($tagHref) ?>" class="ukn-tag-skill"><?= htmlspecialchars($tag) ?></a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="d-flex align-items-center gap-1 flex-wrap">
              <?php if ($isDetail): ?>
                <a href="#comments" class="btn-ghost">
                  <span class="ms" aria-hidden="true">chat_bubble</span><span data-post-comment-count><?= (int) $post['comments'] ?></span> comments
                </a>
              <?php else: ?>
                <a href="<?= htmlspecialchars($post['href']) ?>" class="btn-ghost">
                  <span class="ms" aria-hidden="true">chat_bubble</span><?= (int) $post['comments'] ?> comments
                </a>
              <?php endif; ?>
              <button
                <?= $formAttr('save') ?>
                name="action"
                value="<?= $post['saved'] ? 'unsave' : 'save' ?>"
                class="btn-ghost<?= $post['saved'] ? ' is-active' : '' ?>"
                data-save-post
                data-saved="<?= $post['saved'] ? 'true' : 'false' ?>"
                aria-pressed="<?= $post['saved'] ? 'true' : 'false' ?>"
              >
                <span class="ms" aria-hidden="true" data-save-icon><?= $post['saved'] ? 'bookmark' : 'bookmark_border' ?></span>
                <span data-save-label><?= $post['saved'] ? 'Saved' : 'Save' ?></span>
              </button>
              <?php if ($postId > 0 && function_exists('uknAppUrl')):
                // Only the public post URL and title are shared (built from the post id, never href).
                $shareUrl = uknAppUrl() . '/index.php?page=post-details&id=' . $postId;
                $shareTitle = (string) $post['title'];
                $shareLinks = [
                    'WhatsApp' => 'https://wa.me/?text=' . rawurlencode($shareTitle . ' ' . $shareUrl),
                    'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareUrl),
                    'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($shareUrl),
                    'X'        => 'https://twitter.com/intent/tweet?text=' . rawurlencode($shareTitle) . '&url=' . rawurlencode($shareUrl),
                ];
              ?>
                <div class="dropdown">
                  <button type="button" class="btn-ghost" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Share this post">
                    <span class="ms" aria-hidden="true">share</span>Share
                  </button>
                  <ul class="dropdown-menu" data-share-menu data-share-url="<?= htmlspecialchars($shareUrl) ?>" data-share-title="<?= htmlspecialchars($shareTitle) ?>">
                    <li>
                      <button type="button" class="dropdown-item" data-share-copy>
                        <span class="ms" aria-hidden="true">link</span>Copy link
                      </button>
                    </li>
                    <?php foreach ($shareLinks as $network => $shareHref): ?>
                      <li>
                        <a class="dropdown-item" href="<?= htmlspecialchars($shareHref) ?>" target="_blank" rel="noopener noreferrer" data-share-network="<?= htmlspecialchars(strtolower($network)) ?>">
                          <span class="ms" aria-hidden="true">open_in_new</span><?= htmlspecialchars($network) ?>
                        </a>
                      </li>
                    <?php endforeach; ?>
                    <li data-share-native-item hidden>
                      <button type="button" class="dropdown-item" data-share-native>
                        <span class="ms" aria-hidden="true">ios_share</span>More options&hellip;
                      </button>
                    </li>
                  </ul>
                </div>
              <?php endif; ?>
              <?php if ($isDetail && $postId > 0 && empty($post['isOwner']) && defined('UKN_CURRENT_USER_ID') && UKN_CURRENT_USER_ID > 0): ?>
                <button type="button" class="btn-ghost" data-bs-toggle="modal" data-bs-target="#reportModal" data-report-target-type="post" data-report-target-id="<?= $postId ?>">
                  <span class="ms" aria-hidden="true">flag</span>Report
                </button>
              <?php endif; ?>
              <?php if (!empty($post['isOwner'])): ?>
                <div class="dropdown ms-auto">
                  <button type="button" class="btn-icon btn-icon-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Post options">
                    <span class="ms" aria-hidden="true">more_horiz</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <button
                        type="button"
                        class="dropdown-item"
                        data-bs-toggle="modal"
                        data-bs-target="#editPostModal"
                        data-edit-post-id="<?= htmlspecialchars((string) ($post['id'] ?? '')) ?>"
                        data-edit-post-title="<?= htmlspecialchars($post['title']) ?>"
                        data-edit-post-content="<?= htmlspecialchars($post['content'] ?? $post['excerpt']) ?>"
                        data-edit-post-skills="<?= htmlspecialchars(implode('|', $post['tags'])) ?>"
                      >
                        <span class="ms" aria-hidden="true">edit</span>Edit post
                      </button>
                    </li>
                    <li>
                      <button
                        type="button"
                        class="dropdown-item ukn-text-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteConfirmationModal"
                        data-delete-title="<?= htmlspecialchars('Delete “' . $post['title'] . '”?') ?>"
                        data-delete-message="The post and its comments, votes and references will be removed. This action cannot be undone."
                        data-delete-confirm-label="Delete"
                        data-delete-form="deletePost-<?= $postId ?>"
                      >
                        <span class="ms" aria-hidden="true">delete</span>Delete post
                      </button>
                    </li>
                  </ul>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($postId > 0): ?>
            <?php foreach (['vote' => 'vote', 'save' => 'save'] + (!empty($post['isOwner']) ? ['delete' => 'delete'] : []) as $kind => $endpoint): ?>
              <form id="<?= $kind ?>Post-<?= $postId ?>" action="backend/posts/<?= $endpoint ?>.php" method="post" hidden>
                <?= function_exists('csrfField') ? csrfField() : '' ?>
                <?= function_exists('uknReturnToField') ? uknReturnToField() : '' ?>
                <input type="hidden" name="post_id" value="<?= $postId ?>">
              </form>
            <?php endforeach; ?>
          <?php endif; ?>
        </article>
        <?php
    }
}