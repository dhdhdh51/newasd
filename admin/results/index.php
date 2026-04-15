<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');

$page_title = 'Results';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Results','active'=>true]];

$exams    = $pdo->query("SELECT * FROM exams ORDER BY created_at DESC")->fetchAll();
$classes  = get_classes();

$exam_id    = sanitize_int($_GET['exam_id'] ?? 0);
$class_id   = sanitize_int($_GET['class_id'] ?? 0);
$student_id = sanitize_int($_GET['student_id'] ?? 0);

// Publish results
if (isset($_GET['publish']) && $exam_id) {
    $pdo->prepare("UPDATE results SET published=1 WHERE exam_id=?")->execute([$exam_id]);

    // Notify students and parents
    $stu_stmt = $pdo->prepare(
        "SELECT s.email, s.name FROM students s
         JOIN results r ON r.student_id=s.id WHERE r.exam_id=? AND r.published=1"
    );
    $stu_stmt->execute([$exam_id]);
    $exam_name_row = $pdo->prepare("SELECT name FROM exams WHERE id=?");
    $exam_name_row->execute([$exam_id]);
    $exam_name = $exam_name_row->fetchColumn();

    foreach ($stu_stmt->fetchAll() as $stu) {
        if ($stu['email']) {
            SchoolMailer::sendResultPublished($stu['email'], $stu['name'], $exam_name);
        }
    }
    notify_role('student', 'Results Published', "Results for '{$exam_name}' are now available.", 'success');
    set_flash('success', 'Results published and students notified!');
    redirect(SITE_URL . "/admin/results/?exam_id={$exam_id}&class_id={$class_id}");
}

$results = [];
if ($exam_id) {
    $where  = ['r.exam_id=?'];
    $params = [$exam_id];

    if ($class_id) {
        $where[]  = "s.class_id=?";
        $params[] = $class_id;
    }
    if ($student_id) {
        $where[]  = "r.student_id=?";
        $params[] = $student_id;
    }
    $wh = implode(' AND ', $where);

    $stmt = $pdo->prepare(
        "SELECT r.*, s.name as student_name, s.student_id, s.photo,
                c.name as class_name, sec.name as section_name
         FROM results r
         JOIN students s ON r.student_id=s.id
         LEFT JOIN classes c ON s.class_id=c.id
         LEFT JOIN sections sec ON s.section_id=sec.id
         WHERE $wh ORDER BY r.percentage DESC"
    );
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

include INCLUDES_PATH . 'header.php';
?>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-4">
        <label class="form-label fw-semibold small">Exam</label>
        <select name="exam_id" class="form-select form-select-sm">
          <option value="">Select Exam</option>
          <?php foreach ($exams as $ex): ?>
          <option value="<?= $ex['id'] ?>" <?= $exam_id==$ex['id']?'selected':'' ?>>
            <?= sanitize($ex['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label fw-semibold small">Class</label>
        <select name="class_id" class="form-select form-select-sm">
          <option value="">All Classes</option>
          <?php foreach ($classes as $cl): ?>
          <option value="<?= $cl['id'] ?>" <?= $class_id==$cl['id']?'selected':'' ?>><?= sanitize($cl['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-search me-1"></i>View
        </button>
        <?php if ($exam_id): ?>
        <a href="?exam_id=<?= $exam_id ?>&class_id=<?= $class_id ?>&publish=1"
           class="btn btn-success btn-sm ms-1"
           onclick="return confirm('Publish results? Students will be notified.')">
          <i class="bi bi-check-circle me-1"></i>Publish Results
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($results)): ?>
<!-- Summary stats -->
<div class="row g-3 mb-3">
  <?php
  $pass_count = count(array_filter($results, fn($r) => $r['result'] === 'Pass'));
  $fail_count = count($results) - $pass_count;
  $avg_pct    = count($results) > 0 ? round(array_sum(array_column($results,'percentage')) / count($results), 1) : 0;
  $top        = $results[0] ?? null;
  ?>
  <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-3 fw-bold text-primary"><?= count($results) ?></div><div class="small text-muted">Total</div></div></div>
  <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-3 fw-bold text-success"><?= $pass_count ?></div><div class="small text-muted">Pass</div></div></div>
  <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-3 fw-bold text-danger"><?= $fail_count ?></div><div class="small text-muted">Fail</div></div></div>
  <div class="col-6 col-md-3"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-3 fw-bold text-info"><?= $avg_pct ?>%</div><div class="small text-muted">Avg %</div></div></div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between">
    <span><i class="bi bi-award me-2 text-warning"></i>Results</span>
    <span class="badge bg-primary"><?= count($results) ?> students</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Rank</th><th>Student</th><th>Class</th>
            <th>Total</th><th>%</th><th>Grade</th><th>Result</th><th>Published</th><th>Report</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $rank => $r): ?>
          <tr>
            <td>
              <?php if ($rank === 0): ?><i class="bi bi-trophy-fill text-warning fs-5"></i>
              <?php elseif ($rank === 1): ?><i class="bi bi-trophy-fill text-secondary fs-5"></i>
              <?php elseif ($rank === 2): ?><i class="bi bi-trophy-fill text-danger-emphasis fs-5"></i>
              <?php else: ?><span class="text-muted"><?= $rank+1 ?></span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($r['photo']): ?>
                  <img src="<?= get_upload_url($r['photo']) ?>" class="rounded-circle" width="28" height="28" style="object-fit:cover">
                <?php else: ?>
                  <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:28px;height:28px;font-size:12px">
                    <?= strtoupper(substr($r['student_name'],0,1)) ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div class="fw-semibold small"><?= sanitize($r['student_name']) ?></div>
                  <code class="text-muted" style="font-size:0.7rem"><?= sanitize($r['student_id']) ?></code>
                </div>
              </div>
            </td>
            <td class="small"><?= sanitize(($r['class_name']??'-').($r['section_name']?' - '.$r['section_name']:'')) ?></td>
            <td><?= $r['total_marks'] ?>/<?= $r['max_marks'] ?></td>
            <td class="fw-semibold"><?= $r['percentage'] ?>%</td>
            <td><span class="badge bg-<?= get_grade_color($r['grade']??'F') ?>"><?= $r['grade'] ?></span></td>
            <td><span class="badge bg-<?= $r['result']==='Pass'?'success':'danger' ?>"><?= $r['result'] ?></span></td>
            <td><span class="badge bg-<?= $r['published']?'success':'warning' ?>"><?= $r['published']?'Yes':'No' ?></span></td>
            <td>
              <a href="<?= SITE_URL ?>/admin/results/report-card.php?exam_id=<?= $exam_id ?>&student_id=<?= $r['student_id'] ?>"
                 target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2">
                <i class="bi bi-printer"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($exam_id): ?>
<div class="alert alert-info">No results found. <a href="<?= SITE_URL ?>/admin/marks/?exam_id=<?= $exam_id ?>">Enter marks</a> first to generate results.</div>
<?php else: ?>
<div class="alert alert-info">Select an exam to view results.</div>
<?php endif; ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
