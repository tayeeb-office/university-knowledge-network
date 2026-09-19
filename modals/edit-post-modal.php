<?php
/**
 * Edit Post modal — same form as modals/create-post-modal.php (identical
 * fields, classes and skill-picker), pre-filled with mock existing-post
 * values. A page opens this from a post's "Edit" action
 * (components/post-card.php's more-actions menu) instead of a separate
 * edit page.
 *
 * $editPost below is mock data standing in for "the post currently being
 * edited" — a real integration would set it from the clicked post before
 * this file renders instead of hardcoding one example. Frontend-only:
 * assets/js/core/modal.js drives the mock "Save Changes" flow, no real
 * post update happens.
 */
$editPost = $editPost ?? [
    'title' => 'Need Help Understanding Database Normalization',
    'content' => "I get 1NF and 2NF but 3NF stops making sense the moment a table has two candidate keys. Anyone mentoring on this before Thursday?",
    'skills' => ['MySQL', 'Database', '3NF'],
];
?>
<div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form data-mock-form="post" data-success-message="Changes saved successfully." novalidate>
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
            <textarea class="form-control ukn-textarea-lg" id="editPostContent" name="content" data-validate="required"><?= htmlspecialchars($editPost['content']) ?></textarea>
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
