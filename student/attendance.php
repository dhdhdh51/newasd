<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$student = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$student) redirect(SITE_URL . '/auth/login.php');

$month  = sanitize($_GET['month'] ?? date('Y-m'));
$att    = attendance_summary((int)$student['id'], $month);

// Monthly breakdown
$stmt = $pdo->prepare(
    "SELECT date, status FROM attendance WHERE student_id=?
     AND DATE_FORMAT(date,'%Y-%m')=? ORDER BY date DESC"
);
$stmt->execute([$student['id'], $month]);
$records = $stmt->fetchAll();

// Available months
$months_stmt = $pdo->prepare(
    "SELECT DISTINCT DATE_FORMAT(date,'%Y-%m') as ym FROM attendance
     WHERE student_id=? ORDER BY ym DESC"
);
$months_stmt->execute([$student['id']]);
$available_months = $months_stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'My Attendance';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/student/'],['label'=>'Attendance','active'=>true]];
include INCLUDES_PATH . 'header.php';
?>

<!-- Summary cards -->
<div class="row g-3 mb-3">
  <?php foreach ([
    ['Present',   $att['present']??0,   'success', 'calendar-check'],
    ['Absent',    $att['absent']??0,    'danger',  'calendar-x'],
    ['Late',      $att['late']??0,      'warning', 'calendar-minus'],
    ['Percentage',$att['percentage'].'%','primary', 'percent'],
  ] as [$label,$val,$color,$icon]): ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3 h-100">
      <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-2 d-block mb-1"></i>
      <div class="fs-3 fw-bold text-<?= $color ?>"><?= $val ?></div>
      <div class="small text-muted"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Month filter -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
      <label class="form-label mb-0 fw-semibold small">Month:</label>
      <select name="month" class="form-select form-select-sm" style="max-width:200px"
              onchange="this.form.submit()">
        <option value="<?= date('Y-m') ?>" <?= $month===date('Y-m')?'selected':'' ?>>
          Current Month (<?= date('M Y') ?>)
        </option>
        <?php foreach ($available_months as $ym): if ($ym === date('Y-m')) continue; ?>
        <option value="<?= $ym ?>" <?= $month===$ym?'selected':'' ?>>
          <?= date('M Y', strtotime($ym . '-01')) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<!-- Attendance progress bar -->
<?php if (($att['total']??0) > 0): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-1">
      <span class="fw-semibold small">Attendance Rate</span>
      <span class="fw-bold text-<?= $att['percentage'] >= 75 ? 'success' : ($att['percentage'] >= 60 ? 'warning' : 'danger') ?>">
        <?= $att['percentage'] ?>%
      </span>
    </div>
    <div class="progress" style="height:12px">
      <div class="progress-bar bg-<?= $att['percentage'] >= 75 ? 'success' : ($att['percentage'] >= 60 ? 'warning' : 'danger') ?>"
           style="width:<?= $att['percentage'] ?>%"></div>
    </div>
    <?php if ($att['percentage'] < 75): ?>
    <div class="alert alert-warning mt-2 py-1 small mb-0">
      <i class="bi bi-exclamation-triangle me-1"></i>
      Attendance below 75%. Please attend regularly.
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Records table -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between">
    <span><i class="bi bi-calendar3 me-2 text-primary"></i>
      <?= date('F Y', strtotime($month.'-01')) ?> — Attendance Records
    </span>
    <span class="badge bg-primary"><?= count($records) ?> days</span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($records)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-calendar-x display-6 d-block mb-2"></i>No attendance records for this month.
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>#</th><th>Date</th><th>Day</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($records as $i => $r): ?>
          <tr>
            <td class="text-muted small"><?= $i+1 ?></td>
            <td class="fw-semibold"><?= date('d M Y', strtotime($r['date'])) ?></td>
            <td class="text-muted"><?= date('l', strtotime($r['date'])) ?></td>
            <td>
              <span class="badge bg-<?= match($r['status']){
                'Present'=>'success','Absent'=>'danger','Late'=>'warning',default=>'info'
              } ?> px-3">
                <?= $r['status'] ?>
              </span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
