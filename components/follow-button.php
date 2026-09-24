<?php
if (!function_exists('ukn_follow_form')) {
    /**
     * Follow / Following button posting to backend/follows/follow|unfollow.php (Step 39).
     * $buttonClass carries the caller's existing button styling.
     */
    function ukn_follow_form(int $memberId, bool $following, string $buttonClass, string $formClass = 'd-inline-flex'): void
    {
        ?>
        <form action="backend/follows/<?= $following ? 'unfollow' : 'follow' ?>.php" method="post" class="<?= htmlspecialchars($formClass) ?> m-0">
          <?= function_exists('csrfField') ? csrfField() : '' ?>
          <?= function_exists('uknReturnToField') ? uknReturnToField() : '' ?>
          <input type="hidden" name="following_id" value="<?= $memberId ?>">
          <button type="submit" class="<?= htmlspecialchars($buttonClass) ?>" data-following="<?= $following ? 'true' : 'false' ?>" aria-pressed="<?= $following ? 'true' : 'false' ?>">
            <span data-follow-label><?= $following ? 'Following' : 'Follow' ?></span>
          </button>
        </form>
        <?php
    }
}
