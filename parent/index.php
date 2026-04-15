<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('parent');

$parent = get_parent_by_user_id((int)$_SESSION['user_id']);
if (!$parent) {
    set_flash('error', 'Parent record not found. Contact admin.');
    redirect(SITE_URL . '/auth/login.php');
}

// Get children
$children_stmt = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name
     FROM students s
     LEFT JOIN classes c    ON s.class_id   = c.id
     LEFT JOIN sections sec ON s.section_id = sec.id
     WHERE s.parent_id = ? AND s.status = 'active'"
);
$children_stmt->execute([$parent['id']]);
$children = $children_stmt->fetchAll();

$notifications = get_notifications(5);

$page_title = 'Parent Dashboard';
$breadcrumb = [['label'=>'Dashboard','active'=>true]];
include INCLUDES_PATH . 'header.php';
?>

<!-- Welcome -->
<div class="card border-0 shadow-sm mb-3 bg-info text-white">
  <div class="card-body d-flex align-items-center gap-3 py-3">
    <div class="rounded-circle bg-white text-info d-flex align-items-center justify-content-center fw-bold fs-3"
         style="width:56px;height:56px">
      <?= strtoupper(substr($parent['name'],0,1)) ?>
    </div>
    <div>
      <h5 class="mb-0 fw-bold">Welcome, <?= sanitize($parent['name']) ?></h5>
      <small class="opacity-75"><?= sanitize($parent['relation']??'Parent') ?> | <?= sanitize($parent['phone']??'') ?></small>
    </div>
  </div>
</div>

<!-- Children cards -->
<h6 class="fw-semibold mb-3">
  <i class="bi bi-people me-2 text-primary"></i>My Children (<?= count($children) ?>)
</h6>

<?php if (empty($children)): ?>
<div class="alert alert-info">No children linked to your account. Contact admin.</div>
<?php else: ?>
<div class="row g-3 mb-4">
  <?php foreach ($children as $child):
    $att     = attendance_summary((int)$child['id']);
    $fee_s   = $pdo->prepare("SELECT SUM(amount) as t, SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) as p FROM fees WHERE student_id=?");
    $fee_s->execute([$child['id']]);
    $fees    = $fee_s->fetch();
    $latest  = $pdo->prepare("SELECT r.grade,r.percentage,r.result,e.name FROM results r JOIN exams e ON r.exam_id=e.id WHERE r.student_id=? AND r.published=1 ORDER BY r.created_at DESC LIMIT 1");
    $latest->execute([$child['id']]);
    $latest  = $latest->fetch();
  ?>
  <div class="col-12 col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <?php if ($child['photo']): ?>
            <img src="<?= get_upload_url($child['photo']) ?>" class="rounded-circle"
                 width="60" height="60" style="object-fit:cover;border:3px solid #0d6efd">
          <?php else: ?>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-3"
                 style="width:60px;height:60px">
              <?= strtoupper(substr($child['name'],0,1)) ?>
            </div>
          <?php endif; ?>
          <div>
            <h6 class="fw-bold mb-0"><?= sanitize($child['name']) ?></h6>
            <code class="text-primary small"><?= sanitize($child['student_id']) ?></code>
            <div class="badge bg-primary mt-1">
              <?= sanitize(($child['class_name']??'') . ($child['section_name']?' - '.$child['section_name']:'')) ?>
            </div>
          </div>
          <div class="ms-auto">
            <a href="<?= SITE_URL ?>/parent/child.php?id=<?= $child['id'] ?>"
               class="btn btn-sm btn-outline-primary">
              <i class="bi bi-eye me-1"></i>Details
            </a>
          </div>
        </div>

        <div class="row g-2 text-center">
          <div class="col-4">
            <div class="p-2 bg-success bg-opacity-10 rounded">
              <div class="fw-bold text-success"><?= $att['percentage'] ?>%</div>
              <div style="font-size:.7rem" class="text-muted">Attendance</div>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-warning bg-opacity-10 rounded">
              <div class="fw-bold text-warning"><?= $latest ? $latest['grade'] : '-' ?></div>
              <div style="font-size:.7rem" class="text-muted">Latest Grade</div>
            </div>
          </div>
          <div class="col-4">
            <div class="p-2 bg-danger bg-opacity-10 rounded">
              <div class="fw-bold text-danger"><?= currency_format((float)($fees['p']??0)) ?></div>
              <div style="font-size:.7rem" class="text-muted">Fee Due</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Notifications -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 fw-semibold">
    <i class="bi bi-bell me-2 text-primary"></i>Notifications
  </div>
  <div class="list-group list-group-flush" style="max-height:300px;overflow-y:auto">
    <?php if (empty($notifications)): ?>
    <div class="text-center py-4 text-muted list-group-item">No notifications</div>
    <?php else: foreach ($notifications as $n): ?>
    <div class="list-group-item px-3 py-2 <?= !$n['is_read']?'bg-light':'' ?>">
      <div class="fw-semibold small"><?= sanitize($n['title']) ?></div>
      <div class="text-muted" style="font-size:.8rem"><?= sanitize($n['message']) ?></div>
      <div class="text-muted mt-1" style="font-size:.7rem"><?= date('d M, h:i A',strtotime($n['created_at'])) ?></div>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
