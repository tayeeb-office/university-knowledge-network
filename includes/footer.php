<?php
$showRightSidebar = $showRightSidebar ?? true;
?>
  </main>
  <?php if ($showRightSidebar): ?>
    <?php include __DIR__ . '/right-sidebar.php'; ?>
  <?php endif; ?>
</div>
<?php if (!$showRightSidebar): ?>
  <footer class="ukn-footer">
    UKN Community Guidelines &middot; Help &middot; Privacy<br>
    Student Union Building, Room 214
  </footer>
<?php endif; ?>
<?php include __DIR__ . '/mobile-nav.php'; ?>
<?php

include __DIR__ . '/../modals/create-post-modal.php';
include __DIR__ . '/../modals/edit-post-modal.php';
include __DIR__ . '/../modals/session-request-modal.php';
include __DIR__ . '/../modals/rating-modal.php';
include __DIR__ . '/../modals/delete-confirmation-modal.php';
include __DIR__ . '/../modals/role-switch-modal.php';
include __DIR__ . '/../modals/change-password-modal.php';
?>