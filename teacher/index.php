<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('teacher');

$teacher = get_teacher_by_user_id((int)$_SESSION['user_id']);
if (!$teacher) {
    set_flash('error', 'Teacher record not found.');
    redirect(SITE_URL . '/auth/login.php');
}

// Stats
$class_id = (int)($teacher['class_id'] ?? 0);
$student_count = $class_id
    ? (int)$pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id=? AND status='active'")->execute([$class_id]) ? (int)$pdo->query("SELECT COUNT(*) FROM students WHERE class_id={$class_id} AND status='active'")->fetchColumn() : 0
    : 0;

// Get count properly
$sc_stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE class_id=? AND status='active'");
$sc_stmt->execute([$class_id]);
$student_count = (int)$sc_stmt->fetchColumn();

$today_present = $class_id ? (int)$pdo->query(
    "SELECT COUNT(*) FROM attendance WHERE class_id={$class_id} AND date=CURDATE() AND status='Present'"
)->fetchColumn() : 0;

$today_absent = $class_id ? (int)$pdo->query(
    "SELECT COUNT(*) FROM attendance WHERE class_id={$class_id} AND date=CURDATE() AND status='Absent'"
)->fetchColumn() : 0;

$notifications = get_notifications(5);

$page_title = 'Teacher Dashboard';
$breadcrumb = [['label'=>'Dashboard','active'=>true]];
include INCLUDES_PATH . 'header.php';
?>

<!-- Welcome card -->
<div class="card border-0 shadow-sm mb-3 bg-success text-white">
  <div class="card-body d-flex align-items-center gap-3 py-3">
    <?php if ($teacher['photo']): ?>
      <img src="<?= get_upload_url($teacher['photo']) ?>" class="rounded-circle"
           width="56" height="56" style="object-fit:cover;border:3px solid rgba(255,255,255,.5)">
    <?php else: ?>
      <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center fw-bold fs-3"
           style="width:56px;height:56px">
        <?= strtoupper(substr($teacher['name'],0,1)) ?>
      </div>
    <?php endif; ?>
    <div>
      <h5 class="mb-0 fw-bold">Welcome, <?= sanitize($teacher['name']) ?></h5>
      <small class="opacity-75">
        <?= sanitize($teacher['teacher_id'] ?? '') ?> &nbsp;|&nbsp;
        <?= sanitize($teacher['subject_name'] ?? 'N/A') ?> &nbsp;|&nbsp;
        <?= sanitize($teacher['class_name'] ?? 'N/A') ?>
      </small>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
      <a href="<?= SITE_URL ?>/teacher/attendance.php" class="btn btn-sm btn-light text-success fw-semibold">
        <i class="bi bi-calendar-check me-1"></i>Mark Attendance
      </a>
      <a href="<?= SITE_URL ?>/teacher/marks.php" class="btn btn-sm btn-outline-light">
        <i class="bi bi-pencil-square me-1"></i>Enter Marks
      </a>
    </div>
  </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-3">
  <?php $stats = [
    ['My Students',   $student_count,   'people-fill',    'primary'],
    ['Present Today', $today_present,   'calendar-check', 'success'],
    ['Absent Today',  $today_absent,    'calendar-x',     'danger'],
  ]; ?>
  <?php foreach ($stats as [$label,$val,$icon,$color]): ?>
  <div class="col-4">
    <div class="card border-0 shadow-sm text-center py-3 h-100">
      <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-2 d-block mb-1"></i>
      <div class="fs-3 fw-bold text-<?= $color ?>"><?= $val ?></div>
      <div class="small text-muted"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Quick actions + notifications -->
<div class="row g-3">
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-lightning me-2 text-warning"></i>Quick Actions
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="<?= SITE_URL ?>/teacher/attendance.php" class="btn btn-outline-success text-start">
            <i class="bi bi-calendar-check me-2"></i>Mark Today's Attendance
          </a>
          <a href="<?= SITE_URL ?>/teacher/marks.php" class="btn btn-outline-primary text-start">
            <i class="bi bi-pencil-square me-2"></i>Enter Exam Marks
          </a>
          <a href="<?= SITE_URL ?>/teacher/report-cards.php" class="btn btn-outline-warning text-start">
            <i class="bi bi-award me-2"></i>View / Print Report Cards
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-bell me-2 text-primary"></i>Notifications
      </div>
      <div class="list-group list-group-flush" style="max-height:260px;overflow-y:auto">
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
  </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
