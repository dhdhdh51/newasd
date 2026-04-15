<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$page_title = 'Teachers';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Teachers','active'=>true]];
$page_action = '<a href="' . SITE_URL . '/admin/teachers/add.php" class="btn btn-primary btn-sm">
    <i class="bi bi-person-plus me-1"></i>Add Teacher</a>';

$search  = sanitize($_GET['q'] ?? '');
$per_page= 15;
$page_num= sanitize_int($_GET['page'] ?? 1);

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = "(t.name LIKE ? OR t.teacher_id LIKE ? OR t.email LIKE ? OR t.phone LIKE ?)";
    $like     = "%{$search}%";
    $params   = [$like,$like,$like,$like];
}
$wh = implode(' AND ', $where);

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM teachers t WHERE $wh")
    ->execute($params) ? (int)$pdo->prepare("SELECT COUNT(*) FROM teachers t WHERE $wh")->execute($params) : 0;

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM teachers t WHERE $wh");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pag   = paginate($total, $per_page, $page_num);

$stmt = $pdo->prepare(
    "SELECT t.*, c.name as class_name, s.name as subject_name
     FROM teachers t
     LEFT JOIN classes c  ON t.class_id=c.id
     LEFT JOIN subjects s ON t.subject_id=s.id
     WHERE $wh ORDER BY t.created_at DESC LIMIT ? OFFSET ?"
);
$params[] = $per_page;
$params[] = $pag['offset'];
$stmt->execute($params);
$teachers = $stmt->fetchAll();

include INCLUDES_PATH . 'header.php';
?>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-sm-6 col-md-4">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control" placeholder="Search teachers..."
                 value="<?= sanitize($search) ?>">
        </div>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm me-1">Filter</button>
        <a href="<?= SITE_URL ?>/admin/teachers/" class="btn btn-outline-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th><th>Photo</th><th>Teacher ID</th><th>Name</th>
            <th>Subject</th><th>Class</th><th>Phone</th><th>Status</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($teachers)): ?>
          <tr><td colspan="9" class="text-center py-5 text-muted">
            <i class="bi bi-person-badge display-6 d-block mb-2"></i>No teachers found.
          </td></tr>
          <?php else: foreach ($teachers as $i => $t): ?>
          <tr>
            <td class="text-muted small"><?= $pag['offset']+$i+1 ?></td>
            <td>
              <?php if ($t['photo']): ?>
                <img src="<?= get_upload_url($t['photo']) ?>" class="rounded-circle" width="36" height="36" style="object-fit:cover">
              <?php else: ?>
                <div class="avatar-placeholder rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:14px">
                  <?= strtoupper(substr($t['name'],0,1)) ?>
                </div>
              <?php endif; ?>
            </td>
            <td><code class="text-success"><?= sanitize($t['teacher_id']??'N/A') ?></code></td>
            <td class="fw-semibold"><?= sanitize($t['name']) ?></td>
            <td><?= sanitize($t['subject_name']??'-') ?></td>
            <td><?= sanitize($t['class_name']??'-') ?></td>
            <td><?= sanitize($t['phone']??'-') ?></td>
            <td><span class="badge bg-<?= $t['status']==='active'?'success':'secondary' ?>"><?= ucfirst($t['status']) ?></span></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a href="<?= SITE_URL ?>/admin/teachers/edit.php?id=<?= $t['id'] ?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                <a href="<?= SITE_URL ?>/admin/teachers/delete.php?id=<?= $t['id'] ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?= pagination_links($pag, SITE_URL.'/admin/teachers/?q='.urlencode($search)) ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
