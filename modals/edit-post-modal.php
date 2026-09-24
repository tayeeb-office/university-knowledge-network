<?php
// Filled per trigger by modal.js (data-edit-post-* attributes); saved by backend/posts/update.php,
// which only accepts the logged-in author's own post.
$editPost = ($editPost ?? []) + ['title' => '', 'content' => '', 'skills' => []];
?>
<div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form action="backend/posts/update.php" method="post" data-validated-form novalidate>
        <?= function_exists('csrfField') ? csrfField() : '' ?>
        <?= function_exists('uknReturnToField') ? uknReturnToField() : '' ?>
        <input type="hidden" name="post_id" value="" data-edit-post-id-input>
        <div class="modal-header">
          <h2 class="modal-title ukn-h3" id="editPostModalLabel">Edit Post</h2>
          <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
            <span class="ms" aria-hidden="true">close</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="ukn-form-group">
            <label for="editPostTitle" class="form-label">Post Title <span class="ukn-text-danger" aria-hidden="true">*</span></label>
            <input type="text" class="form-control" id="editPostTitle" name="title" maxlength="120" value="<?= htmlspecialchars($editPost['title']) ?>" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="title" hidden>
              <span class="ms" aria-hidden="true">error</span>Please add a title for your post.
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="editPostContent" class="form-label">Post Content <span class="ukn-text-danger" aria-hidden="true">*</span></label>
            <textarea class="form-control ukn-textarea-lg" id="editPostContent" name="content" maxlength="10000" data-validate="required"><?= htmlspecialchars($editPost['content']) ?></textarea>
            <div class="ukn-field-message is-invalid" data-error-for="content" hidden>
              <span class="ms" aria-hidden="true">error</span>Add some content before saving.
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="editPostSkillInput" class="form-label">Related Skills <span class="ukn-text-danger" aria-hidden="true">*</span></label>
            <div class="ukn-tag-input" data-skill-picker>
              <?php foreach ($editPost['skills'] as $skill): ?>
                <button type="button" class="ukn-tag-skill" data-skill-tag="<?= htmlspecialchars($skill) ?>" aria-label="Remove <?= htmlspecialchars($skill) ?>">
                  <?= htmlspecialchars($skill) ?> <span class="ms" aria-hidden="true">close</span>
                </button>
              <?php endforeach; ?>
              <input type="text" id="editPostSkillInput" placeholder="Type a skill, press Enter" list="editPostSkillOptions" data-skill-input>
            </div>
            <datalist id="editPostSkillOptions">
              <option value="Python"></option>
              <option value="MySQL"></option>
              <option value="React"></option>
              <option value="UI/UX Design"></option>
              <option value="Data Analysis"></option>
              <option value="Public Speaking"></option>
            </datalist>
            <input type="hidden" name="skills" data-skill-value data-validate="required" value="<?= htmlspecialchars(implode('|', $editPost['skills'])) ?>">
            <div class="ukn-field-message is-invalid" data-error-for="skills" hidden>
              <span class="ms" aria-hidden="true">error</span>Add at least one related skill.
            </div>
          </div>
          <div class="ukn-form-group mb-0">
            <label class="form-label">Attachment (optional)</label>
            <div class="ukn-dropzone">
              <span class="ms" aria-hidden="true">image</span>Drop an image or screenshot here (optional)
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>