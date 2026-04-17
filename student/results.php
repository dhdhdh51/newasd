<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

// Load student with parent info
$stmt = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name,
            p.name as parent_name, p.relation as parent_relation,
            p.phone as parent_phone, p.email as parent_email
     FROM students s
     LEFT JOIN classes c    ON s.class_id=c.id
     LEFT JOIN sections sec ON s.section_id=sec.id
     LEFT JOIN parents p    ON s.parent_id=p.id
     WHERE s.user_id=?"
);
$stmt->execute([(int)$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) redirect(SITE_URL . '/auth/login.php');

// View report card — redirect to printable report card
if (isset($_GET['report'])) {
    $exam_id = sanitize_int($_GET['report']);
    // Allow student to view their own report card
    redirect(SITE_URL . '/student/report-card.php?exam_id=' . $exam_id . '&student_id=' . $student['id']);
}

$results_stmt = $pdo->prepare(
    "SELECT r.*, e.name as exam_name, e.type, e.start_date
     FROM results r JOIN exams e ON r.exam_id=e.id
     WHERE r.student_id=? AND r.published=1
     ORDER BY r.created_at DESC"
);
$results_stmt->execute([$student['id']]);
$results = $results_stmt->fetchAll();

$page_title = 'My Results';
$breadcrumb = [
    ['label'=>'Dashboard','url'=>SITE_URL.'/student/'],
    ['label'=>'Results & Report Cards','active'=>true],
];
include INCLUDES_PATH . 'header.php';
?>

<?php flash_message(); ?>

<!-- Parent Info Alert -->
<?php if (!$student['parent_id']): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
  <i class="bi bi-people-fill fs-4 flex-shrink-0"></i>
  <div class="flex-grow-1">
    <strong>Parent information missing!</strong>
    Your report card will not display parent details until you add a parent / guardian.
  </div>
  <a href="<?= SITE_URL ?>/student/profile.php?tab=parent" class="btn btn-warning btn-sm flex-shrink-0">
    <i class="bi bi-person-plus me-1"></i>Add Parent
  </a>
</div>
<?php else: ?>
<!-- Compact Parent Info Bar -->
<div class="card border-0 shadow-sm mb-4 premium-card border-start border-4 border-success">
  <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
    <i class="bi bi-people-fill text-success fs-5"></i>
    <div class="small">
      <span class="text-muted">Parent / Guardian:</span>
      <strong class="ms-1"><?= sanitize($student['parent_name']) ?></strong>
      <span class="text-muted ms-2">(<?= sanitize($student['parent_relation'] ?? 'Guardian') ?>)</span>
      <?php if ($student['parent_phone']): ?>
        <span class="ms-2"><i class="bi bi-telephone-fill me-1 text-muted"></i><?= sanitize($student['parent_phone']) ?></span>
      <?php endif; ?>
    </div>
    <a href="<?= SITE_URL ?>/student/profile.php?tab=parent" class="btn btn-outline-success btn-sm ms-auto">
      <i class="bi bi-pencil me-1"></i>Edit
    </a>
  </div>
</div>
<?php endif; ?>

<?php if (empty($results)): ?>
<div class="card border-0 shadow-sm premium-card">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-award display-4 d-block mb-3 text-muted opacity-50"></i>
    <h5>No Published Results Yet</h5>
    <p class="mb-0">Results will appear here once your teacher publishes them.</p>
  </div>
</div>
<?php else: ?>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
  <?php
  $best_pct  = max(array_column($results, 'percentage'));
  $avg_pct   = round(array_sum(array_column($results, 'percentage')) / count($results), 1);
  $pass_cnt  = count(array_filter($results, fn($r) => $r['result'] === 'Pass'));
  $stat_cards = [
    ['Exams Taken',   count($results),          'clipboard2-check', 'primary'],
    ['Best %',        $best_pct . '%',           'trophy-fill',      'warning'],
    ['Average %',     $avg_pct  . '%',           'bar-chart-fill',   'info'],
    ['Pass Count',    $pass_cnt,                 'check-circle-fill','success'],
  ];
  foreach ($stat_cards as [$label, $val, $icon, $color]):
  ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm premium-card text-center py-3 h-100">
      <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-2 mb-2 d-block"></i>
      <div class="fs-4 fw-bold text-<?= $color ?>"><?= $val ?></div>
      <div class="small text-muted"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Result Cards -->
<?php foreach ($results as $r): ?>
<div class="card border-0 shadow-sm premium-card mb-3">
  <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
    <div>
      <h6 class="fw-bold mb-0"><?= sanitize($r['exam_name']) ?></h6>
      <small class="text-muted"><?= sanitize($r['type']) ?> &bull; <?= format_date($r['start_date']) ?></small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <span class="badge bg-<?= get_grade_color($r['grade'] ?? 'F') ?> px-3 py-2 fs-6"><?= $r['grade'] ?></span>
      <span class="badge bg-<?= $r['result']==='Pass'?'success':'danger' ?> px-3 py-2"><?= $r['result'] ?></span>
      <a href="<?= SITE_URL ?>/student/results.php?report=<?= $r['exam_id'] ?>"
         class="btn btn-outline-primary btn-sm" target="_blank">
        <i class="bi bi-printer me-1"></i>Report Card
      </a>
    </div>
  </div>
  <div class="card-body pt-0">
    <!-- Progress -->
    <div class="mb-3">
      <div class="d-flex justify-content-between mb-1">
        <small class="fw-semibold">Overall: <?= $r['total_marks'] ?>/<?= $r['max_marks'] ?></small>
        <small class="fw-bold"><?= $r['percentage'] ?>%</small>
      </div>
      <div class="progress rounded-pill" style="height:10px">
        <div class="progress-bar bg-<?= get_grade_color($r['grade'] ?? 'F') ?> rounded-pill"
             style="width:<?= $r['percentage'] ?>%" role="progressbar"></div>
      </div>
    </div>
    <!-- Subject Pills -->
    <?php
    $marks_stmt = $pdo->prepare(
        "SELECT m.marks_obtained, m.max_marks, s.name as subject_name
         FROM marks m JOIN subjects s ON m.subject_id=s.id
         WHERE m.exam_id=? AND m.student_id=?"
    );
    $marks_stmt->execute([$r['exam_id'], $student['id']]);
    $subject_marks = $marks_stmt->fetchAll();
    ?>
    <div class="d-flex flex-wrap gap-1">
      <?php foreach ($subject_marks as $sm):
        $pct   = $sm['max_marks'] > 0 ? round(($sm['marks_obtained'] / $sm['max_marks']) * 100) : 0;
        $grade = calculate_grade($pct);
        $color = get_grade_color($grade);
      ?>
      <div class="badge bg-<?= $color ?> bg-opacity-15 text-<?= $color ?> border border-<?= $color ?> px-2 py-1"
           style="font-size:.7rem" title="<?= sanitize($sm['subject_name']) ?>: <?= $sm['marks_obtained'] ?>/<?= $sm['max_marks'] ?>">
        <?= sanitize($sm['subject_name']) ?>: <?= $sm['marks_obtained'] ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
