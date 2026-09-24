<?php
if (!function_exists('ukn_skill_toggle_form')) {
    /**
     * "Add to Learning / Learning" (or Teaching) button, posting to backend/skills/add|remove.php.
     * $formClass is applied to the wrapping form (e.g. layout classes like ms-auto).
     */
    function ukn_skill_toggle_form(int $skillId, string $kind, bool $added, string $buttonClass = '', string $formClass = ''): void
    {
        $kindLabel = $kind === 'teaching' ? 'Teaching' : 'Learning';
        ?>
        <form action="backend/skills/<?= $added ? 'remove' : 'add' ?>.php" method="post" class="d-inline-flex m-0<?= $formClass ? ' ' . htmlspecialchars($formClass) : '' ?>">
          <?= function_exists('csrfField') ? csrfField() : '' ?>
          <?= function_exists('uknReturnToField') ? uknReturnToField() : '' ?>
          <input type="hidden" name="type" value="<?= $kind === 'teaching' ? 'teaching' : 'learning' ?>">
          <input type="hidden" name="skill_id" value="<?= $skillId ?>">
          <button type="submit" class="btn btn-sm <?= $added ? 'btn-outline-secondary' : 'btn-outline-primary' ?><?= $buttonClass ? ' ' . htmlspecialchars($buttonClass) : '' ?>" data-skill-toggle="<?= $kind === 'teaching' ? 'teaching' : 'learning' ?>" data-state="<?= $added ? 'added' : 'add' ?>"<?= $added ? ' aria-label="Remove from ' . $kindLabel . ' skills"' : '' ?>>
            <span class="ms" aria-hidden="true"><?= $added ? 'check' : 'add' ?></span>
            <span data-skill-toggle-label><?= $added ? $kindLabel : 'Add to ' . $kindLabel ?></span>
          </button>
        </form>
        <?php
    }
}
