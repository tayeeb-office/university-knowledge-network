<?php
?>
<div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form action="backend/posts/create.php" method="post" data-validated-form novalidate>
        <?= function_exists('csrfField') ? csrfField() : '' ?>
        <?= function_exists('uknReturnToField') ? uknReturnToField() : '' ?>
        <div class="modal-header">
          <h2 class="modal-title ukn-h3" id="createPostModalLabel">Create Post</h2>
          <button type="button" class="btn-icon" data-bs-dismiss="modal" aria-label="Close">
            <span class="ms" aria-hidden="true">close</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="ukn-form-group">
            <label for="createPostTitle" class="form-label">Post Title <span class="ukn-text-danger" aria-hidden="true">*</span></label>
            <input type="text" class="form-control" id="createPostTitle" name="title" placeholder="A clear, specific title" maxlength="120" data-validate="required">
            <div class="ukn-field-message is-invalid" data-error-for="title" hidden>
              <span class="ms" aria-hidden="true">error</span>Please add a title for your post.
            </div>
          </div>
          <div class="ukn-form-group">
            <label for="createPostContent" class="form-label">Post Content <span class="ukn-text-danger" aria-hidden="true">*</span></label>
            <textarea class="form-control ukn-textarea-lg" id="createPostContent" name="content" maxlength="10000" placeholder="What did you learn, or what are you stuck on?" data-validate="required"></textarea>
            <div class="ukn-field-message is-invalid" data-error-for="content" hidden>
              <span class="ms" aria-hidden="true">error</span>Add some content before publishing.
            </div>
          </div>
          <div class="ukn-form-group mb-0">
            <label for="createPostSkillInput" class="form-label">Related Skills (optional)</label>
            <div class="ukn-tag-input" data-skill-picker>
              <input type="text" id="createPostSkillInput" placeholder="Type a skill, press Enter (e.g. Python, MySQL)" list="createPostSkillOptions" data-skill-input>
            </div>
            <datalist id="createPostSkillOptions">
              <option value="Python"></option>
              <option value="MySQL"></option>
              <option value="React"></option>
              <option value="UI/UX Design"></option>
              <option value="Data Analysis"></option>
              <option value="Public Speaking"></option>
            </datalist>
            <input type="hidden" name="skills" data-skill-value>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Publish Post</button>
        </div>
      </form>
    </div>
  </div>
</div>