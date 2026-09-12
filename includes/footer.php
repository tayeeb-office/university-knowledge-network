<?php
/**
 * Global application shell — closer.
 *
 * The approved design has no page-wide marketing footer; the app's only
 * "footer" copy (community guidelines + campus address) lives inside the
 * right sidebar (see includes/right-sidebar.php). This file stays purely
 * structural: it closes <main> and ".ukn-shell" opened by includes/header.php,
 * renders the contextual right sidebar (unless the page opted out), falls
 * back to a minimal utility strip when there is no right sidebar to host
 * that copy, and includes the mobile offcanvas nav.
 *
 * Usage — always pair with includes/header.php:
 *   <?php include __DIR__ . '/../../includes/header.php'; ?>
 *   ...page-specific main content...
 *   <?php include __DIR__ . '/../../includes/footer.php'; ?>
 */
$showRightSidebar = $showRightSidebar ?? true;
?>
  </main><!-- /.ukn-main -->

  <?php if ($showRightSidebar): ?>
    <?php include __DIR__ . '/right-sidebar.php'; ?>
  <?php endif; ?>
</div><!-- /.ukn-shell -->

<?php if (!$showRightSidebar): ?>
  <footer class="ukn-footer">
    UKN Community Guidelines &middot; Help &middot; Privacy<br>
    Student Union Building, Room 214
  </footer>
<?php endif; ?>

<?php include __DIR__ . '/mobile-nav.php'; ?>
