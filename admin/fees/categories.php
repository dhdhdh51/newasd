<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$page_title = 'Fee Categories';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => SITE_URL . '/admin/'],
    ['label' => 'Fees',      'url' => SITE_URL . '/admin/fees/'],
    ['label' => 'Fee Categories', 'active' => true],
];

$errors  = [];
$success = '';

/* ── DELETE ─────────────────────────────────────────────────── */
if (isset($_GET['delete'])) {
    csrf_protect();
    $id = sanitize_int($_GET['delete']);
    if ($id) {
        $pdo->prepare("DELETE FROM fee_categories WHERE id=?")->execute([$id]);
        set_flash('success', 'Fee category deleted.');
    }
    redirect(SITE_URL . '/admin/fees/categories.php');
}

/* ── TOGGLE apply_on_admission ───────────────────────────────── */
if (isset($_GET['toggle_aoa'])) {
    csrf_protect();
    $id = sanitize_int($_GET['toggle_aoa']);
    if ($id) {
        $pdo->prepare("UPDATE fee_categories SET apply_on_admission = 1 - apply_on_admission WHERE id=?")->execute([$id]);
        set_flash('success', 'Admission fee toggle updated.');
    }
    redirect(SITE_URL . '/admin/fees/categories.php');
}

/* ── TOGGLE is_active ────────────────────────────────────────── */
if (isset($_GET['toggle_active'])) {
    csrf_protect();
    $id = sanitize_int($_GET['toggle_active']);
    if ($id) {
        $pdo->prepare("UPDATE fee_categories SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        set_flash('success', 'Fee category status updated.');
    }
    redirect(SITE_URL . '/admin/fees/categories.php');
}

/* ── GLOBAL TOGGLE for fee_apply_on_admission setting ───────── */
if (isset($_GET['toggle_global'])) {
    csrf_protect();
    $cur = get_setting('fee_apply_on_admission', '1');
    $new = $cur === '1' ? '0' : '1';
    $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key='fee_apply_on_admission'")->execute([$new]);
    if (!$pdo->query("SELECT id FROM settings WHERE setting_key='fee_apply_on_admission'")->fetch()) {
        $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('fee_apply_on_admission',?)")->execute([$new]);
    }
    set_flash('success', 'Global fee-on-admission setting updated.');
    redirect(SITE_URL . '/admin/fees/categories.php');
}

/* ── ADD / EDIT ──────────────────────────────────────────────── */
$edit_cat = null;
if (isset($_GET['edit'])) {
    $edit_id = sanitize_int($_GET['edit']);
    $stmt    = $pdo->prepare("SELECT * FROM fee_categories WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_cat = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    csrf_protect();
    $cat_id   = sanitize_int($_POST['cat_id']   ?? 0);
    $name     = sanitize($_POST['name']          ?? '');
    $desc     = sanitize($_POST['description']   ?? '');
    $amount   = (float)($_POST['amount']         ?? 0);
    $class_id = sanitize_int($_POST['class_id']  ?? 0) ?: null;
    $aoa      = isset($_POST['apply_on_admission']) ? 1 : 0;
    $active   = isset($_POST['is_active'])           ? 1 : 0;

    if (!$name)          $errors[] = 'Category name is required.';
    if ($amount < 0)     $errors[] = 'Amount must be positive.';

    if (empty($errors)) {
        if ($cat_id) {
            $pdo->prepare(
                "UPDATE fee_categories SET name=?,description=?,amount=?,class_id=?,apply_on_admission=?,is_active=? WHERE id=?"
            )->execute([$name, $desc ?: null, $amount, $class_id, $aoa, $active, $cat_id]);
            set_flash('success', 'Fee category updated.');
        } else {
            $pdo->prepare(
                "INSERT INTO fee_categories (name,description,amount,class_id,apply_on_admission,is_active) VALUES (?,?,?,?,?,?)"
            )->execute([$name, $desc ?: null, $amount, $class_id, $aoa, $active]);
            set_flash('success', 'Fee category created.');
        }
        redirect(SITE_URL . '/admin/fees/categories.php');
    }
}

$categories = $pdo->query(
    "SELECT fc.*, c.name as class_name
     FROM fee_categories fc
     LEFT JOIN classes c ON fc.class_id=c.id
     ORDER BY fc.is_active DESC, fc.name ASC"
)->fetchAll();

$classes              = get_classes();
$global_fee_on_adm    = get_setting('fee_apply_on_admission', '1');
$csrf                 = generate_csrf();

include INCLUDES_PATH . 'header.php';
?>

<?php flash_message(); ?>
<?php foreach ($errors as $e): ?>
<div class="alert alert-danger"><?= sanitize($e) ?></div>
<?php endforeach; ?>

<!-- Global Toggle Banner -->
<div class="card border-0 shadow-sm mb-4 premium-card">
  <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <h6 class="fw-bold mb-1"><i class="bi bi-toggles me-2 text-primary"></i>Auto-Apply Fees on Admission Approval</h6>
      <p class="text-muted small mb-0">When enabled, fee categories marked "Apply on Admission" will auto-generate fee invoices when an admission is approved.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="badge bg-<?= $global_fee_on_adm === '1' ? 'success' : 'secondary' ?> px-3 py-2 fs-6">
        <?= $global_fee_on_adm === '1' ? 'ENABLED' : 'DISABLED' ?>
      </span>
      <a href="?toggle_global=1&_csrf=<?= $csrf ?>" class="btn btn-<?= $global_fee_on_adm === '1' ? 'outline-danger' : 'outline-success' ?> btn-sm"
         onclick="return confirm('Toggle global fee-on-admission setting?')">
        <i class="bi bi-toggle-<?= $global_fee_on_adm === '1' ? 'on' : 'off' ?> me-1"></i>
        <?= $global_fee_on_adm === '1' ? 'Disable' : 'Enable' ?>
      </a>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Form -->
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm premium-card h-100">
      <div class="card-header border-0 bg-transparent">
        <h6 class="fw-bold mb-0">
          <i class="bi bi-<?= $edit_cat ? 'pencil-square' : 'plus-circle' ?> me-2 text-primary"></i>
          <?= $edit_cat ? 'Edit Category' : 'Add Fee Category' ?>
        </h6>
      </div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="save_category" value="1">
          <input type="hidden" name="cat_id" value="<?= (int)($edit_cat['id'] ?? 0) ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control premium-input" required
                   value="<?= sanitize($edit_cat['name'] ?? '') ?>" placeholder="e.g. Tuition Fee">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Amount (<?= sanitize(get_setting('currency_symbol', '₹')) ?>)</label>
            <input type="number" name="amount" class="form-control premium-input" step="0.01" min="0"
                   value="<?= $edit_cat['amount'] ?? '0' ?>" placeholder="0.00">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Applicable Class</label>
            <select name="class_id" class="form-select premium-input">
              <option value="">All Classes</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($edit_cat['class_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
                  <?= sanitize($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control premium-input" rows="2"
                      placeholder="Optional description"><?= sanitize($edit_cat['description'] ?? '') ?></textarea>
          </div>

          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="apply_on_admission" id="aoa"
                     <?= ($edit_cat['apply_on_admission'] ?? 0) ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="aoa">
                Apply on Admission Approval
                <i class="bi bi-info-circle text-muted ms-1" title="Auto-generate fee invoice when admission is approved"></i>
              </label>
            </div>
            <div class="form-text">Automatically create a fee invoice when an admission application is approved.</div>
          </div>

          <div class="mb-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" id="active"
                     <?= ($edit_cat['is_active'] ?? 1) ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="active">Active</label>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">
              <i class="bi bi-save me-1"></i><?= $edit_cat ? 'Update' : 'Add Category' ?>
            </button>
            <?php if ($edit_cat): ?>
              <a href="<?= SITE_URL ?>/admin/fees/categories.php" class="btn btn-outline-secondary">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- List -->
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm premium-card">
      <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2 text-primary"></i>All Fee Categories (<?= count($categories) ?>)</h6>
        <a href="<?= SITE_URL ?>/admin/fees/" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Back to Fees
        </a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Category</th>
                <th>Class</th>
                <th>Amount</th>
                <th class="text-center">On Admission</th>
                <th class="text-center">Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($categories)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No fee categories yet.</td></tr>
              <?php else: foreach ($categories as $cat): ?>
              <tr class="<?= $cat['is_active'] ? '' : 'opacity-50' ?>">
                <td>
                  <div class="fw-semibold"><?= sanitize($cat['name']) ?></div>
                  <?php if ($cat['description']): ?>
                    <div class="small text-muted"><?= sanitize($cat['description']) ?></div>
                  <?php endif; ?>
                </td>
                <td><?= $cat['class_name'] ? sanitize($cat['class_name']) : '<span class="badge bg-secondary">All Classes</span>' ?></td>
                <td class="fw-bold"><?= currency_format((float)$cat['amount']) ?></td>
                <td class="text-center">
                  <a href="?toggle_aoa=<?= $cat['id'] ?>&_csrf=<?= $csrf ?>"
                     class="badge bg-<?= $cat['apply_on_admission'] ? 'success' : 'secondary' ?> text-decoration-none px-3 py-2"
                     title="Click to toggle">
                    <?= $cat['apply_on_admission'] ? '<i class="bi bi-check-circle me-1"></i>Yes' : '<i class="bi bi-x-circle me-1"></i>No' ?>
                  </a>
                </td>
                <td class="text-center">
                  <a href="?toggle_active=<?= $cat['id'] ?>&_csrf=<?= $csrf ?>"
                     class="badge bg-<?= $cat['is_active'] ? 'success' : 'secondary' ?> text-decoration-none px-3 py-2">
                    <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                  </a>
                </td>
                <td class="text-end">
                  <a href="?edit=<?= $cat['id'] ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="?delete=<?= $cat['id'] ?>&_csrf=<?= $csrf ?>"
                     class="btn btn-outline-danger btn-sm ms-1"
                     onclick="return confirm('Delete this fee category? Existing fee records will not be affected.')">
                    <i class="bi bi-trash"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info mt-3 d-flex gap-2 align-items-start">
      <i class="bi bi-lightbulb-fill text-info fs-5 mt-1"></i>
      <div>
        <strong>How it works:</strong> When "Apply on Admission" is enabled for a category <em>and</em> the global
        auto-apply setting is ON, approving an admission application will automatically generate a fee invoice for
        the student using the matching categories. Class-specific categories apply only to students of that class.
      </div>
    </div>
  </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
