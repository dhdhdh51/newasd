<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');

$page_title = 'Admissions';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Admissions','active'=>true]];

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    csrf_protect();
    $aid     = sanitize_int($_POST['admission_id'] ?? 0);
    $status  = sanitize($_POST['status'] ?? '');
    $remarks = sanitize($_POST['remarks'] ?? '');

    if ($aid && in_array($status, ['approved','rejected','pending'])) {
        $pdo->prepare(
            "UPDATE admissions SET status=?,remarks=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?"
        )->execute([$status, $remarks, $_SESSION['user_id'], $aid]);

        // Get admission email
        $stmt = $pdo->prepare("SELECT * FROM admissions WHERE id=?");
        $stmt->execute([$aid]);
        $adm = $stmt->fetch();

        if ($adm && $adm['email']) {
            SchoolMailer::sendAdmissionStatus($adm['email'], $adm['name'], $status, $remarks);
        }

        set_flash('success', "Admission status updated to '{$status}'.");
    }
    redirect(SITE_URL . '/admin/admissions/');
}

$filter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$per_page = 15;
$page_num = sanitize_int($_GET['page'] ?? 1);

$where  = ['1=1'];
$params = [];
if ($filter) { $where[] = "a.status=?"; $params[] = $filter; }
if ($search) { $where[] = "(a.name LIKE ? OR a.application_id LIKE ? OR a.email LIKE ?)"; $like="%{$search}%"; $params=array_merge($params,[$like,$like,$like]); }
$wh = implode(' AND ', $where);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM admissions a WHERE $wh");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pag   = paginate($total, $per_page, $page_num);

$stmt = $pdo->prepare(
    "SELECT a.*, c.name as class_name FROM admissions a
     LEFT JOIN classes c ON a.class_applying=c.id
     WHERE $wh ORDER BY a.created_at DESC LIMIT ? OFFSET ?"
);
$params[] = $per_page; $params[] = $pag['offset'];
$stmt->execute($params);
$admissions = $stmt->fetchAll();

// Count by status
$counts = $pdo->query("SELECT status, COUNT(*) as cnt FROM admissions GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

include INCLUDES_PATH . 'header.php';
?>

<!-- Status Filter Pills -->
<div class="d-flex gap-2 flex-wrap mb-3">
  <a href="<?= SITE_URL ?>/admin/admissions/" class="btn btn-sm btn-<?= $filter===''?'primary':'outline-primary' ?>">All (<?= array_sum($counts) ?>)</a>
  <a href="?status=pending"  class="btn btn-sm btn-<?= $filter==='pending'?'warning':'outline-warning' ?>">Pending (<?= $counts['pending']??0 ?>)</a>
  <a href="?status=approved" class="btn btn-sm btn-<?= $filter==='approved'?'success':'outline-success' ?>">Approved (<?= $counts['approved']??0 ?>)</a>
  <a href="?status=rejected" class="btn btn-sm btn-<?= $filter==='rejected'?'danger':'outline-danger' ?>">Rejected (<?= $counts['rejected']??0 ?>)</a>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
      <input type="hidden" name="status" value="<?= sanitize($filter) ?>">
      <div class="input-group input-group-sm flex-grow-1" style="max-width:300px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Search..." value="<?= sanitize($search) ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="<?= SITE_URL ?>/admin/admissions/" class="btn btn-outline-secondary btn-sm">Clear</a>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>App ID</th><th>Name</th><th>Class</th>
            <th>Parent</th><th>Applied</th><th>Status</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($admissions)): ?>
          <tr><td colspan="8" class="text-center py-5 text-muted">
            <i class="bi bi-file-earmark-person display-6 d-block mb-2"></i>No admissions found.
          </td></tr>
          <?php else: foreach ($admissions as $i => $a): ?>
          <tr>
            <td class="text-muted small"><?= $pag['offset']+$i+1 ?></td>
            <td><code class="text-warning"><?= sanitize($a['application_id']) ?></code></td>
            <td>
              <div class="fw-semibold"><?= sanitize($a['name']) ?></div>
              <div class="small text-muted"><?= sanitize($a['email'] ?? '') ?></div>
            </td>
            <td><?= sanitize($a['class_name'] ?? '-') ?></td>
            <td>
              <div><?= sanitize($a['parent_name'] ?? '-') ?></div>
              <div class="small text-muted"><?= sanitize($a['parent_phone'] ?? '') ?></div>
            </td>
            <td class="small text-muted"><?= format_date($a['created_at']) ?></td>
            <td>
              <span class="badge bg-<?= match($a['status']){
                'approved'=>'success','rejected'=>'danger',default=>'warning'
              } ?>">
                <?= ucfirst($a['status']) ?>
              </span>
            </td>
            <td class="text-end">
              <button class="btn btn-outline-primary btn-sm"
                      onclick="openReview(<?= $a['id'] ?>, '<?= addslashes(sanitize($a['name'])) ?>', '<?= $a['status'] ?>')">
                <i class="bi bi-eye"></i> Review
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?= pagination_links($pag, SITE_URL.'/admin/admissions/?status='.$filter.'&q='.urlencode($search)) ?>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Review Admission - <span id="modalName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="reviewForm">
          <?= csrf_field() ?>
          <input type="hidden" name="update_status" value="1">
          <input type="hidden" name="admission_id" id="modal_admission_id">

          <div class="mb-3">
            <label class="form-label fw-semibold">Decision</label>
            <div class="d-flex gap-2">
              <?php foreach (['approved','rejected','pending'] as $st): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status" id="st_<?= $st ?>" value="<?= $st ?>">
                <label class="form-check-label" for="st_<?= $st ?>"><?= ucfirst($st) ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Remarks</label>
            <textarea name="remarks" class="form-control" rows="3" placeholder="Reason for decision..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle me-2"></i>Submit Decision</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openReview(id, name, status) {
  document.getElementById('modal_admission_id').value = id;
  document.getElementById('modalName').textContent    = name;
  const radio = document.getElementById('st_' + status);
  if (radio) radio.checked = true;
  new bootstrap.Modal(document.getElementById('reviewModal')).show();
}
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
