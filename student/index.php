<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$student = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$student) {
    set_flash('error', 'Student record not found. Contact admin.');
    redirect(SITE_URL . '/auth/login.php');
}

$page_title = 'Student Dashboard';
$att        = attendance_summary((int)$student['id']);

// Results
$results = $pdo->prepare(
    "SELECT r.*, e.name as exam_name, e.type FROM results r
     JOIN exams e ON r.exam_id=e.id WHERE r.student_id=? AND r.published=1
     ORDER BY r.created_at DESC LIMIT 3"
);
$results->execute([$student['id']]);
$results = $results->fetchAll();

// Fee summary
$fee_summary = $pdo->prepare(
    "SELECT SUM(amount) as total,
            SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid,
            SUM(CASE WHEN status='pending' OR status='overdue' THEN amount ELSE 0 END) as pending
     FROM fees WHERE student_id=?"
)->execute([$student['id']]) ? null : null;
$fee_stmt = $pdo->prepare("SELECT SUM(amount) as total, SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid, SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) as pending FROM fees WHERE student_id=?");
$fee_stmt->execute([$student['id']]);
$fees = $fee_stmt->fetch();

// Notifications
$notifications = get_notifications(5);

include INCLUDES_PATH . 'header.php';
?>

<div class="row g-3 mb-3">
  <!-- Profile summary -->
  <div class="col-12 col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-4">
        <?php if ($student['photo']): ?>
          <img src="<?= get_upload_url($student['photo']) ?>" class="rounded-circle border mb-3"
               width="80" height="80" style="object-fit:cover">
        <?php else: ?>
          <div class="avatar-xl mx-auto bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2 mb-3">
            <?= strtoupper(substr($student['name'],0,1)) ?>
          </div>
        <?php endif; ?>
        <h5 class="fw-bold mb-1"><?= sanitize($student['name']) ?></h5>
        <code class="text-primary d-block mb-1"><?= sanitize($student['student_id']) ?></code>
        <span class="badge bg-primary">
          <?= sanitize(($student['class_name']??'') . ($student['section_name']?' - '.$student['section_name']:'')) ?>
        </span>

        <div class="d-flex gap-2 justify-content-center mt-3">
          <a href="<?= SITE_URL ?>/student/id-card.php" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-card-text me-1"></i>ID Card
          </a>
          <a href="<?= SITE_URL ?>/student/profile.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Profile
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick stats -->
  <div class="col-12 col-md-8">
    <div class="row g-3 h-100">
      <div class="col-6">
        <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10">
          <div class="card-body text-center">
            <i class="bi bi-calendar-check text-success fs-2 mb-2 d-block"></i>
            <div class="fs-2 fw-bold text-success"><?= $att['percentage'] ?>%</div>
            <div class="small text-muted">Attendance</div>
            <div class="small text-muted"><?= $att['present'] ?? 0 ?> / <?= $att['total'] ?? 0 ?> days</div>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10">
          <div class="card-body text-center">
            <i class="bi bi-receipt text-warning fs-2 mb-2 d-block"></i>
            <div class="fs-5 fw-bold text-danger"><?= currency_format((float)($fees['pending']??0)) ?></div>
            <div class="small text-muted">Fee Pending</div>
            <a href="<?= SITE_URL ?>/student/fees.php" class="btn btn-sm btn-warning mt-2">Pay Now</a>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
          <div class="card-body text-center">
            <i class="bi bi-award text-primary fs-2 mb-2 d-block"></i>
            <?php if (!empty($results)): ?>
            <div class="fs-4 fw-bold text-primary"><?= $results[0]['grade'] ?></div>
            <div class="small text-muted">Latest Grade</div>
            <div class="small text-muted"><?= sanitize($results[0]['exam_name']) ?></div>
            <?php else: ?>
            <div class="text-muted small">No results yet</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card border-0 shadow-sm h-100 bg-info bg-opacity-10">
          <div class="card-body text-center">
            <i class="bi bi-cash-stack text-info fs-2 mb-2 d-block"></i>
            <div class="fs-5 fw-bold text-success"><?= currency_format((float)($fees['paid']??0)) ?></div>
            <div class="small text-muted">Fee Paid</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Results & Notifications -->
<div class="row g-3">
  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 d-flex justify-content-between fw-semibold">
        <span><i class="bi bi-award me-2 text-warning"></i>Recent Results</span>
        <a href="<?= SITE_URL ?>/student/results.php" class="text-primary small">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 small">
            <thead class="table-light">
              <tr><th>Exam</th><th>Total</th><th>%</th><th>Grade</th><th>Result</th><th>Report</th></tr>
            </thead>
            <tbody>
              <?php if (empty($results)): ?>
              <tr><td colspan="6" class="text-center py-4 text-muted">No published results yet.</td></tr>
              <?php else: foreach ($results as $r): ?>
              <tr>
                <td><?= sanitize($r['exam_name']) ?><br><small class="text-muted"><?= $r['type'] ?></small></td>
                <td><?= $r['total_marks'] ?>/<?= $r['max_marks'] ?></td>
                <td><?= $r['percentage'] ?>%</td>
                <td><span class="badge bg-<?= get_grade_color($r['grade']??'F') ?>"><?= $r['grade'] ?></span></td>
                <td><span class="badge bg-<?= $r['result']==='Pass'?'success':'danger' ?>"><?= $r['result'] ?></span></td>
                <td>
                  <a href="<?= SITE_URL ?>/student/results.php?report=<?= $r['exam_id'] ?>"
                     class="btn btn-xs btn-outline-primary btn-sm py-0 px-2" target="_blank">
                    <i class="bi bi-printer"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-bell me-2 text-primary"></i>Notifications
      </div>
      <div class="list-group list-group-flush" style="max-height:300px;overflow-y:auto">
        <?php if (empty($notifications)): ?>
        <div class="text-center py-4 text-muted list-group-item">No notifications</div>
        <?php else: foreach ($notifications as $n): ?>
        <div class="list-group-item px-3 py-2 <?= !$n['is_read'] ? 'bg-light' : '' ?>">
          <div class="fw-semibold small"><?= sanitize($n['title']) ?></div>
          <div class="text-muted" style="font-size:.8rem"><?= sanitize($n['message']) ?></div>
          <div class="text-muted mt-1" style="font-size:.7rem"><?= date('d M, h:i A', strtotime($n['created_at'])) ?></div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
