<?php
/**
 * Admin shell closer — always paired with admin/includes/header.php.
 * Closes <main>/.ukn-shell, renders the mobile offcanvas nav (reusing
 * the exact #uknMobileNav id and .ukn-nav-link markup convention the
 * user-facing app's includes/mobile-nav.php + assets/js/core/mobile-nav.js
 * already use, so that same close-on-link-click behavior works here with
 * no new JS), the one shared Delete/Confirm modal (reused as-is for every
 * mock destructive-ish Admin confirmation — Suspend/Restore User now,
 * future Admin pages later — instead of a second confirmation framework),
 * and shared + page-specific scripts.
 */
?>
    </main><!-- /.ukn-main -->
  </div><!-- /.ukn-shell -->

  <div class="offcanvas offcanvas-start ukn-mobile-nav" tabindex="-1" id="uknMobileNav" aria-labelledby="uknMobileNavLabel">
    <div class="offcanvas-header">
      <span class="ukn-header__title" id="uknMobileNavLabel">
        <strong>University</strong>
        <span>Knowledge Network Admin</span>
      </span>
      <button type="button" class="btn-icon" data-bs-dismiss="offcanvas" aria-label="Close admin navigation">
        <span class="ms" aria-hidden="true">close</span>
      </button>
    </div>
    <div class="offcanvas-body">
      <?php foreach ($adminNavItems as $item): $isActive = $item['slug'] === $adminActiveNav; ?>
        <a
          href="<?= htmlspecialchars($item['href']) ?>"
          class="ukn-nav-link<?= $isActive ? ' is-active' : '' ?>"
          <?= $isActive ? 'aria-current="page"' : '' ?>
        >
          <span class="ms" aria-hidden="true"><?= htmlspecialchars($item['icon']) ?></span>
          <span class="ukn-nav-text"><?= htmlspecialchars($item['label']) ?></span>
        </a>
      <?php endforeach; ?>
      <a href="../index.php?page=home" class="ukn-nav-link ukn-nav-link--footer">
        <span class="ms" aria-hidden="true">open_in_new</span>
        <span class="ukn-nav-text">View Application</span>
      </a>
    </div>
  </div>

  <?php include __DIR__ . '/../../modals/delete-confirmation-modal.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app.js"></script>
  <script src="../assets/js/core/theme.js"></script>
  <script src="../assets/js/core/mobile-nav.js"></script>
  <script src="../assets/js/core/toast.js"></script>
  <script src="../assets/js/core/modal.js"></script>
  <?php foreach ($adminPageScripts as $src): ?>
    <script src="<?= htmlspecialchars($src) ?>"></script>
  <?php endforeach; ?>
</body>
</html>
