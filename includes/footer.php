  </div><!-- /.container-fluid -->
</main><!-- /.main-content -->

<footer class="app-footer text-center py-2 small text-muted">
  <?= sanitize(get_setting('footer_text', '© ' . date('Y') . ' School ERP. All rights reserved.')) ?>
  &nbsp;|&nbsp; <a href="<?= SITE_URL ?>" class="text-muted text-decoration-none">v<?= APP_VERSION ?></a>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= ASSETS_URL ?>/js/main.js"></script>

<?php if (!empty($extra_scripts)) echo $extra_scripts; ?>

<script>
// Sidebar toggle
const sidebar      = document.getElementById('sidebar');
const overlay      = document.getElementById('sidebarOverlay');
const sidebarToggle= document.getElementById('sidebarToggle');

if (sidebarToggle) {
  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
  });
}
if (overlay) {
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
  });
}

// Mark notification read via AJAX
document.querySelectorAll('.notif-item').forEach(el => {
  el.addEventListener('click', function(e) {
    e.preventDefault();
    const id  = this.dataset.id;
    const url = this.href;
    fetch('<?= SITE_URL ?>/notifications/read.php?id=' + id, {method:'GET'})
      .then(() => { window.location.href = url; })
      .catch(()  => { window.location.href = url; });
  });
});

// Auto-dismiss flash messages
document.querySelectorAll('.alert.alert-dismissible').forEach(el => {
  setTimeout(() => { el.classList.remove('show'); }, 5000);
});
</script>
</body>
</html>
