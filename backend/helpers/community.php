<?php
// Phase F (Steps 34–41): posts, comments, votes, saves, follows and notifications.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/skills.php';

if (!defined('UKN_POST_TITLE_MAX')) {
    define('UKN_POST_TITLE_MAX', 120);     // matches the post forms' maxlength
    define('UKN_POST_CONTENT_MAX', 10000);
    define('UKN_POST_SKILLS_MAX', 10);
    define('UKN_COMMENT_MAX', 2000);
}

if (!function_exists('uknRecountPostComments')) {
    /**
     * Sets posts.comment_count to the comments the community can see on that post: visible
     * comments whose whole ancestor chain is visible (anything under a hidden comment, at any
     * depth, is not shown). Call inside the writer's transaction. Step 50 moderation and the
     * Step 36 delete both use it, so hidden comments never make the stored count drift.
     */
    function uknRecountPostComments(PDO $pdo, int $postId): void
    {
        $count = $pdo->prepare(
            "WITH RECURSIVE shown AS (
                 SELECT id FROM comments WHERE post_id = ? AND parent_id IS NULL AND status = 'visible'
                 UNION ALL
                 SELECT c.id FROM comments c JOIN shown s ON c.parent_id = s.id WHERE c.status = 'visible'
             )
             SELECT COUNT(*) FROM shown"
        );
        $count->execute([$postId]);
        $pdo->prepare('UPDATE posts SET comment_count = ? WHERE id = ?')
            ->execute([(int) $count->fetchColumn(), $postId]);
    }
}
if (!function_exists('uknShownCommentAuthor')) {
    /**
     * Author id of $commentId when the community can see it on $postId: the comment and every
     * ancestor up to its top-level comment are visible and belong to that post. Null otherwise
     * (missing, hidden, under a hidden comment, or on another post). One recursive query.
     */
    function uknShownCommentAuthor(PDO $pdo, int $commentId, int $postId): ?int
    {
        $chain = $pdo->prepare(
            "WITH RECURSIVE chain AS (
                 SELECT id, parent_id, post_id, user_id, status FROM comments WHERE id = ?
                 UNION ALL
                 SELECT c.id, c.parent_id, c.post_id, c.user_id, c.status
                 FROM comments c JOIN chain ON c.id = chain.parent_id
             )
             SELECT id, parent_id, post_id, user_id, status FROM chain"
        );
        $chain->execute([$commentId]);
        $authorId = null;
        $reachesTop = false;
        foreach ($chain->fetchAll() as $row) {
            if ($row['status'] !== 'visible' || (int) $row['post_id'] !== $postId) {
                return null;
            }
            if ((int) $row['id'] === $commentId) {
                $authorId = (int) $row['user_id'];
            }
            $reachesTop = $reachesTop || $row['parent_id'] === null;
        }
        return $reachesTop ? $authorId : null;
    }
}
if (!function_exists('uknDecoratePosts')) {
    /**
     * Adds the viewer's own state to post rows (keyed by 'id'): voteState (-1/0/1), saved, and
     * isOwner and following (viewer follows the author) when the row carries 'author_id'.
     * Two batched queries for the whole list, plus the request's cached following set.
     */
    function uknDecoratePosts(array $posts): array
    {
        $user = getCurrentUser();
        if ($posts === [] || $user === null) {
            foreach ($posts as &$post) {
                $post += ['voteState' => 0, 'saved' => false];
                $post['isOwner'] = false;
                $post['following'] = false;
            }
            return $posts;
        }
        $userId = (int) $user['id'];
        $ids = array_values(array_unique(array_map('intval', array_column($posts, 'id'))));
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pdo = getDatabaseConnection();
        $votes = $pdo->prepare("SELECT post_id, value FROM post_votes WHERE user_id = ? AND post_id IN ($in)");
        $votes->execute(array_merge([$userId], $ids));
        $voteByPost = array_map('intval', array_column($votes->fetchAll(), 'value', 'post_id'));
        $saves = $pdo->prepare("SELECT post_id FROM saved_posts WHERE user_id = ? AND post_id IN ($in)");
        $saves->execute(array_merge([$userId], $ids));
        $savedIds = array_flip(array_map('intval', $saves->fetchAll(PDO::FETCH_COLUMN)));
        foreach ($posts as &$post) {
            $id = (int) $post['id'];
            $post['voteState'] = $voteByPost[$id] ?? 0;
            $post['saved'] = isset($savedIds[$id]);
            if (array_key_exists('author_id', $post)) {
                $post['isOwner'] = (int) $post['author_id'] === $userId;
                $post['following'] = (bool) uknFollowStateFor((int) $post['author_id']);
            }
        }
        return $posts;
    }
}
if (!function_exists('uknCurrentUserFollowingIds')) {
    /** Ids the logged-in user follows, as a set (id => true). One query per request. */
    function uknCurrentUserFollowingIds(): array
    {
        static $following = null;
        if ($following !== null) {
            return $following;
        }
        $following = [];
        $user = getCurrentUser();
        if ($user !== null) {
            $stmt = getDatabaseConnection()->prepare('SELECT following_id FROM follows WHERE follower_id = ?');
            $stmt->execute([(int) $user['id']]);
            $following = array_flip(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
        }
        return $following;
    }
}
if (!function_exists('uknFollowStateFor')) {
    /**
     * Whether the logged-in user follows $targetId: null when no Follow button applies (guest, or
     * the viewer's own account), otherwise true/false. Follows are user-to-user, so the active
     * Learner/Mentor mode never matters. Reads the cached set above (no query per target).
     */
    function uknFollowStateFor(int $targetId): ?bool
    {
        $user = getCurrentUser();
        if ($user === null || $targetId <= 0 || $targetId === (int) $user['id']) {
            return null;
        }
        return isset(uknCurrentUserFollowingIds()[$targetId]);
    }
}
if (!function_exists('uknParsePostSkills')) {
    /**
     * Pipe-separated skill names from the post form's skill picker → active skill ids.
     * Related skills are optional: a missing or empty value is valid (no tags). Every name that
     * is given must still be an active directory skill. Returns [ids, error]; error is null when valid.
     */
    function uknParsePostSkills(PDO $pdo, ?string $raw): array
    {
        if ($raw === null) {
            return [[], null];
        }
        $names = array_values(array_unique(array_filter(array_map('trim', explode('|', $raw)), 'strlen')));
        if ($names === []) {
            return [[], null];
        }
        if (count($names) > UKN_POST_SKILLS_MAX) {
            return [[], 'Add at most ' . UKN_POST_SKILLS_MAX . ' skills.'];
        }
        $ids = [];
        foreach ($names as $name) {
            $id = uknFindActiveSkillIdByName($pdo, $name);
            if ($id === null) {
                return [[], "\"{$name}\" is not a skill in the directory."];
            }
            $ids[] = $id;
        }
        return [array_values(array_unique($ids)), null];
    }
}
if (!function_exists('uknValidatePostInput')) {
    /** Title + content + skills from a post form. Returns [data, error]. */
    function uknValidatePostInput(PDO $pdo): array
    {
        foreach (['title', 'content', 'skills'] as $field) {
            if (array_key_exists($field, $_POST) && uknPostString($field) === null) {
                return [[], 'Invalid post data.'];
            }
        }
        $title = sanitizeInput(uknPostString('title') ?? '');
        $content = sanitizeInput(uknPostString('content') ?? '');
        if (!validateLength($title, 1, UKN_POST_TITLE_MAX)) {
            return [[], 'Add a title (up to ' . UKN_POST_TITLE_MAX . ' characters).'];
        }
        if (!validateLength($content, 1, UKN_POST_CONTENT_MAX)) {
            return [[], 'Add some content (up to ' . UKN_POST_CONTENT_MAX . ' characters).'];
        }
        [$skillIds, $error] = uknParsePostSkills($pdo, uknPostString('skills'));
        if ($error !== null) {
            return [[], $error];
        }
        return [['title' => $title, 'content' => $content, 'skill_ids' => $skillIds], null];
    }
}
if (!defined('UKN_NOTIFICATION_SETTINGS')) {
    // user_settings columns a notification can be gated by (Settings → Notifications).
    define('UKN_NOTIFICATION_SETTINGS', [
        'notify_session_updates', 'notify_session_reminders', 'notify_community_replies',
        'notify_follow_activity', 'notify_learner_requests', 'notify_rating_received',
    ]);
}
if (!function_exists('uknNotify')) {
    /**
     * Creates a notification (type: session, community, rating or system) inside the caller's
     * transaction, so it exists only if the action commits. Never notifies the actor about their
     * own action, respects the recipient's user_settings preference ($setting; null = no
     * preference exists for this event) and, with $once, skips an identical notification that
     * already exists (e.g. re-follows). Returns true if a notification was created.
     */
    function uknNotify(PDO $pdo, int $recipientId, int $actorId, string $icon, string $message, ?string $link, ?string $setting, bool $once = false, string $type = 'community'): bool
    {
        if ($recipientId === $actorId || !in_array($type, ['session', 'community', 'rating', 'system'], true)
            || ($setting !== null && !in_array($setting, UKN_NOTIFICATION_SETTINGS, true))) {
            return false;
        }
        if ($setting !== null) {
            $pref = $pdo->prepare("SELECT {$setting} FROM user_settings WHERE user_id = ?");
            $pref->execute([$recipientId]);
            $enabled = $pref->fetchColumn();
            if ($enabled !== false && (int) $enabled !== 1) {
                return false;
            }
        }
        $message = mb_substr($message, 0, 255, 'UTF-8');
        if ($once) {
            $dup = $pdo->prepare('SELECT 1 FROM notifications WHERE user_id = ? AND message = ? AND link_url <=> ? LIMIT 1');
            $dup->execute([$recipientId, $message, $link]);
            if ($dup->fetchColumn() !== false) {
                return false;
            }
        }
        $pdo->prepare(
            'INSERT INTO notifications (user_id, type, icon, message, link_url) VALUES (?, ?, ?, ?, ?)'
        )->execute([$recipientId, $type, $icon, $message, $link]);
        return true;
    }
}
if (!function_exists('uknQuoteTitle')) {
    /** A post title shortened for notification text. */
    function uknQuoteTitle(string $title): string
    {
        return '"' . (mb_strlen($title, 'UTF-8') > 80 ? mb_substr($title, 0, 79, 'UTF-8') . '…' : $title) . '"';
    }
}
if (!function_exists('uknSafeAppLink')) {
    /** Only in-app page links (index.php?page=…) may be used as notification targets. */
    function uknSafeAppLink(?string $link): ?string
    {
        return is_string($link) && preg_match('/^index\.php\?page=[a-z0-9-]+(&[a-z_]+=[A-Za-z0-9_-]*)*$/', $link) ? $link : null;
    }
}
