<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$page_title = 'Dashboard';
$breadcrumb = [['label'=>'Dashboard','active'=>true]];

$stats = get_dashboard_stats();

// Recent admissions
$recent_admissions = $pdo->query(
    "SELECT a.*, c.name as class_name FROM admissions a
     LEFT JOIN classes c ON a.class_applying=c.id
     ORDER BY a.created_at DESC LIMIT 5"
)->fetchAll();

// Recent students
$recent_students = $pdo->query(
    "SELECT s.*, c.name as class_name, sec.name as section_name
     FROM students s
     LEFT JOIN classes c ON s.class_id=c.id
     LEFT JOIN sections sec ON s.section_id=sec.id
     ORDER BY s.created_at DESC LIMIT 5"
)->fetchAll();

// Fee summary per month
$fee_chart = $pdo->query(
    "SELECT DATE_FORMAT(created_at,'%b') as month, SUM(amount) as total
     FROM fees WHERE status='paid' AND YEAR(created_at)=YEAR(NOW())
     GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)"
)->fetchAll();

// Attendance today
$today_present = (int)$pdo->query(
    "SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Present'"
)->fetchColumn();
$today_absent  = (int)$pdo->query(
    "SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Absent'"
)->fetchColumn();

include INCLUDES_PATH . 'header.php';
?>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Students',   $stats['total_students'],   'people-fill',     'primary',  SITE_URL.'/admin/students/'],
    ['Teachers',   $stats['total_teachers'],   'person-badge',    'success',  SITE_URL.'/admin/teachers/'],
    ['Parents',    $stats['total_parents'],    'people',          'info',     SITE_URL.'/admin/parents/'],
    ['Pending Admissions', $stats['total_admissions'], 'file-earmark-person', 'warning', SITE_URL.'/admin/admissions/'],
  ];
  foreach ($cards as [$label,$val,$icon,$color,$link]):
  ?>
  <div class="col-6 col-lg-3">
    <a href="<?= $link ?>" class="text-decoration-none">
      <div class="card stat-card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
            <i class="bi bi-<?= $icon ?>"></i>
          </div>
          <div>
            <div class="fs-4 fw-bold"><?= number_format($val) ?></div>
            <div class="small text-muted"><?= $label ?></div>
          </div>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>

  <!-- Fees collected -->
  <div class="col-6 col-lg-3">
    <div class="card stat-card border-0 shadow-sm bg-success text-white h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-white bg-opacity-25 text-white">
          <i class="bi bi-cash-stack"></i>
        </div>
        <div>
          <div class="fs-5 fw-bold"><?= currency_format($stats['total_fees_paid']) ?></div>
          <div class="small opacity-75">Fees Collected</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Fees due -->
  <div class="col-6 col-lg-3">
    <div class="card stat-card border-0 shadow-sm bg-danger text-white h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-white bg-opacity-25 text-white">
          <i class="bi bi-exclamation-circle"></i>
        </div>
        <div>
          <div class="fs-5 fw-bold"><?= currency_format($stats['total_fees_due']) ?></div>
          <div class="small opacity-75">Fees Pending</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Today Attendance -->
  <div class="col-6 col-lg-3">
    <div class="card stat-card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-purple bg-opacity-10 text-purple">
          <i class="bi bi-calendar-check"></i>
        </div>
        <div>
          <div class="fs-4 fw-bold"><?= $today_present ?><small class="fs-6 text-muted fw-normal"> / <?= $today_present + $today_absent ?></small></div>
          <div class="small text-muted">Present Today</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CHARTS + TABLES -->
<div class="row g-3 mb-4">
  <!-- Fee chart -->
  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-bar-chart me-2 text-primary"></i>Monthly Fee Collection (<?= date('Y') ?>)
      </div>
      <div class="card-body">
        <canvas id="feeChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <!-- Attendance donut -->
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-pie-chart me-2 text-success"></i>Today's Attendance
      </div>
      <div class="card-body d-flex flex-column align-items-center justify-content-center">
        <canvas id="attendanceChart" height="180"></canvas>
        <div class="d-flex gap-4 mt-3">
          <div class="text-center"><div class="fw-bold text-success fs-4"><?= $today_present ?></div><div class="small text-muted">Present</div></div>
          <div class="text-center"><div class="fw-bold text-danger fs-4"><?= $today_absent ?></div><div class="small text-muted">Absent</div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- RECENT ADMISSIONS + STUDENTS -->
<div class="row g-3">
  <!-- Recent admissions -->
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-file-earmark-person me-2 text-warning"></i>Recent Admissions</span>
        <a href="<?= SITE_URL ?>/admin/admissions/" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 small">
            <thead class="table-light">
              <tr>
                <th>App ID</th><th>Name</th><th>Class</th><th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent_admissions)): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">No admissions yet</td></tr>
              <?php else: foreach ($recent_admissions as $a): ?>
              <tr>
                <td class="fw-mono"><?= sanitize($a['application_id']) ?></td>
                <td><?= sanitize($a['name']) ?></td>
                <td><?= sanitize($a['class_name'] ?? '-') ?></td>
                <td>
                  <span class="badge bg-<?= match($a['status']){
                    'approved'=>'success','rejected'=>'danger',default=>'warning'
                  } ?>">
                    <?= ucfirst($a['status']) ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent students -->
  <div class="col-12 col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-people-fill me-2 text-primary"></i>Recent Students</span>
        <a href="<?= SITE_URL ?>/admin/students/" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 small">
            <thead class="table-light">
              <tr><th>Student ID</th><th>Name</th><th>Class</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php if (empty($recent_students)): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">No students yet</td></tr>
              <?php else: foreach ($recent_students as $s): ?>
              <tr>
                <td class="fw-mono small"><?= sanitize($s['student_id']) ?></td>
                <td>
                  <?php if ($s['photo']): ?>
                    <img src="<?= get_upload_url($s['photo']) ?>" class="rounded-circle me-1" width="24" height="24" style="object-fit:cover">
                  <?php endif; ?>
                  <?= sanitize($s['name']) ?>
                </td>
                <td><?= sanitize(($s['class_name'] ?? '-') . ' ' . ($s['section_name'] ?? '')) ?></td>
                <td><span class="badge bg-<?= $s['status']==='active'?'success':'secondary' ?>"><?= ucfirst($s['status']) ?></span></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row g-2 mt-3">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <h6 class="fw-semibold mb-3">Quick Actions</h6>
        <div class="d-flex flex-wrap gap-2">
          <?php
          $quick = [
            ['Add Student',  '/admin/students/add.php',    'person-plus',      'primary'],
            ['Add Teacher',  '/admin/teachers/add.php',    'person-badge',     'success'],
            ['Add Fee',      '/admin/fees/add.php',        'receipt',          'info'],
            ['Mark Attend.', '/admin/attendance/',         'calendar-check',   'warning'],
            ['New Exam',     '/admin/exams/add.php',       'clipboard2-data',  'purple'],
            ['Send Notif.',  '/admin/notifications/',      'bell',             'secondary'],
            ['Settings',     '/admin/settings/',           'gear',             'dark'],
          ];
          foreach ($quick as [$label,$url,$icon,$color]):
          ?>
          <a href="<?= SITE_URL . $url ?>" class="btn btn-sm btn-<?= $color ?> btn-outline-<?= $color ?> border px-3">
            <i class="bi bi-<?= $icon ?> me-1"></i><?= $label ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Fee Chart
const feeData = <?= json_encode(array_column($fee_chart, 'total')) ?>;
const feeLabels = <?= json_encode(array_column($fee_chart, 'month')) ?>;

new Chart(document.getElementById('feeChart'), {
  type: 'bar',
  data: {
    labels: feeLabels.length ? feeLabels : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    datasets: [{
      label: 'Fee Collected',
      data: feeData.length ? feeData : [],
      backgroundColor: 'rgba(13, 110, 253, 0.6)',
      borderColor: 'rgba(13, 110, 253, 1)',
      borderWidth: 1,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});

// Attendance donut
new Chart(document.getElementById('attendanceChart'), {
  type: 'doughnut',
  data: {
    labels: ['Present', 'Absent'],
    datasets: [{
      data: [<?= $today_present ?>, <?= $today_absent ?>],
      backgroundColor: ['#198754', '#dc3545'],
      borderWidth: 0,
    }]
  },
  options: {
    cutout: '70%',
    plugins: { legend: { position: 'bottom' } },
    responsive: true,
  }
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
