<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$student = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$student) redirect(SITE_URL . '/auth/login.php');

// View report card
if (isset($_GET['report'])) {
    $exam_id = sanitize_int($_GET['report']);
    redirect(SITE_URL . '/admin/results/report-card.php?exam_id=' . $exam_id . '&student_id=' . $student['id']);
}

$results = $pdo->prepare(
    "SELECT r.*, e.name as exam_name, e.type, e.start_date
     FROM results r JOIN exams e ON r.exam_id=e.id
     WHERE r.student_id=? AND r.published=1
     ORDER BY r.created_at DESC"
);
$results->execute([$student['id']]);
$results = $results->fetchAll();

$page_title = 'My Results';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/student/'],['label'=>'Results','active'=>true]];
include INCLUDES_PATH . 'header.php';
?>

<?php if (empty($results)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-award display-4 d-block mb-3"></i>
    No results published yet. Check back later.
  </div>
</div>
<?php else: ?>

<div class="row g-3 mb-3">
  <?php
  $best = collect_best($results);
  $cards = [
    ['Exams Taken', count($results), 'clipboard2-check', 'primary'],
    ['Best %', max(array_column($results,'percentage')).'%', 'trophy', 'warning'],
    ['Average %', round(array_sum(array_column($results,'percentage'))/count($results),1).'%', 'bar-chart', 'info'],
    ['Pass Count', count(array_filter($results, fn($r)=>$r['result']==='Pass')), 'check-circle', 'success'],
  ];
  foreach ($cards as [$label,$val,$icon,$color]):
  ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center py-3 h-100">
      <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-2 mb-2 d-block"></i>
      <div class="fs-4 fw-bold text-<?= $color ?>"><?= $val ?></div>
      <div class="small text-muted"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php foreach ($results as $r): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h6 class="fw-bold mb-0"><?= sanitize($r['exam_name']) ?></h6>
      <small class="text-muted"><?= $r['type'] ?> | <?= format_date($r['start_date']) ?></small>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <span class="badge bg-<?= get_grade_color($r['grade']??'F') ?> px-3 py-2 fs-6"><?= $r['grade'] ?></span>
      <span class="badge bg-<?= $r['result']==='Pass'?'success':'danger' ?> px-3 py-2"><?= $r['result'] ?></span>
      <a href="<?= SITE_URL ?>/student/results.php?report=<?= $r['exam_id'] ?>"
         class="btn btn-outline-primary btn-sm" target="_blank">
        <i class="bi bi-printer me-1"></i>Report Card
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="row g-3 align-items-center">
      <div class="col-12 col-md-8">
        <!-- Progress bar -->
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between mb-1">
              <small class="fw-semibold">Overall: <?= $r['total_marks'] ?>/<?= $r['max_marks'] ?></small>
              <small><?= $r['percentage'] ?>%</small>
            </div>
            <div class="progress" style="height:10px">
              <div class="progress-bar bg-<?= get_grade_color($r['grade']??'F') ?>"
                   style="width:<?= $r['percentage'] ?>%"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4 text-md-end">
        <?php
        // Load subject marks
        $marks = $pdo->prepare(
            "SELECT m.marks_obtained, m.max_marks, s.name as subject_name
             FROM marks m JOIN subjects s ON m.subject_id=s.id
             WHERE m.exam_id=? AND m.student_id=?"
        );
        $marks->execute([$r['exam_id'], $student['id']]);
        $subject_marks = $marks->fetchAll();
        ?>
        <div class="d-flex flex-wrap gap-1 justify-content-md-end">
          <?php foreach ($subject_marks as $sm):
            $pct = $sm['max_marks'] > 0 ? round(($sm['marks_obtained']/$sm['max_marks'])*100) : 0;
          ?>
          <div class="badge bg-<?= get_grade_color(calculate_grade($pct)) ?> bg-opacity-15 text-<?= get_grade_color(calculate_grade($pct)) ?> border px-2 py-1" style="font-size:.7rem">
            <?= sanitize($sm['subject_name']) ?>: <?= $sm['marks_obtained'] ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php
function collect_best(array $results): float {
    if (empty($results)) return 0.0;
    return max(array_column($results,'percentage'));
}
?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
