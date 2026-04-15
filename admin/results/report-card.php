<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$exam_id    = sanitize_int($_GET['exam_id'] ?? 0);
$student_id = sanitize_int($_GET['student_id'] ?? 0);

if (!$exam_id || !$student_id) {
    die('Invalid parameters.');
}

// Load exam
$stmt = $pdo->prepare("SELECT e.*, c.name as class_name FROM exams e LEFT JOIN classes c ON e.class_id=c.id WHERE e.id=?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

// Load student
$stmt = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name,
            p.name as parent_name, p.phone as parent_phone
     FROM students s
     LEFT JOIN classes c ON s.class_id=c.id
     LEFT JOIN sections sec ON s.section_id=sec.id
     LEFT JOIN parents p ON s.parent_id=p.id
     WHERE s.id=?"
);
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$exam || !$student) die('Not found.');

// Load marks per subject
$stmt = $pdo->prepare(
    "SELECT m.*, sub.name as subject_name, sub.code as subject_code
     FROM marks m JOIN subjects sub ON m.subject_id=sub.id
     WHERE m.exam_id=? AND m.student_id=?
     ORDER BY sub.name"
);
$stmt->execute([$exam_id, $student_id]);
$marks = $stmt->fetchAll();

// Load result
$stmt = $pdo->prepare("SELECT * FROM results WHERE exam_id=? AND student_id=?");
$stmt->execute([$exam_id, $student_id]);
$result = $stmt->fetch();

$site_name = get_setting('site_name', 'School ERP');
$site_logo = get_setting('site_logo', '');
$contact   = get_setting('contact_address', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Report Card - <?= sanitize($student['name'] ?? '') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { font-family: Arial, sans-serif; background: #f0f0f0; }
    .report-card { max-width: 800px; margin: 20px auto; background: #fff; border: 2px solid #0d6efd; border-radius: 8px; overflow: hidden; }
    .rc-header { background: linear-gradient(135deg, #0d6efd, #0099ff); color: white; padding: 20px; }
    .rc-body { padding: 20px; }
    .rc-title { font-size: 22px; font-weight: bold; margin: 0; }
    .rc-sub { font-size: 13px; opacity: .85; }
    .info-table td { padding: 4px 8px; font-size: 13px; }
    .info-table td:first-child { font-weight: 600; color: #555; width: 120px; }
    .marks-table th { background: #f8f9fa; font-size: 13px; }
    .marks-table td { font-size: 13px; }
    .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-30deg); font-size: 80px; color: rgba(13,110,253,0.05); pointer-events: none; z-index: 0; font-weight: bold; white-space: nowrap; }
    .grade-pill { display: inline-block; padding: 2px 12px; border-radius: 20px; font-weight: bold; font-size: 14px; }
    @media print {
      body { background: white !important; }
      .no-print { display: none !important; }
      .report-card { border: 2px solid #333 !important; box-shadow: none !important; margin: 0 !important; }
      @page { margin: 10mm; }
    }
  </style>
</head>
<body>

<div class="no-print text-center py-3 bg-light">
  <button onclick="window.print()" class="btn btn-primary me-2">
    <i class="bi bi-printer me-1"></i>Print Report Card
  </button>
  <a href="javascript:history.back()" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back
  </a>
</div>

<div class="watermark"><?= sanitize($site_name) ?></div>

<div class="report-card shadow">
  <!-- Header -->
  <div class="rc-header d-flex align-items-center gap-3">
    <?php if ($site_logo): ?>
      <img src="<?= get_upload_url($site_logo) ?>" height="60" class="rounded">
    <?php else: ?>
      <div style="width:60px;height:60px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px">🏫</div>
    <?php endif; ?>
    <div>
      <div class="rc-title"><?= sanitize($site_name) ?></div>
      <div class="rc-sub"><?= sanitize($contact) ?></div>
      <div class="rc-sub">STUDENT REPORT CARD - <?= get_setting('academic_year','2025-2026') ?></div>
    </div>
    <?php if ($student['photo']): ?>
    <img src="<?= get_upload_url($student['photo']) ?>" class="ms-auto rounded"
         style="width:70px;height:70px;object-fit:cover;border:3px solid rgba(255,255,255,.5)">
    <?php endif; ?>
  </div>

  <!-- Body -->
  <div class="rc-body">
    <!-- Student Info -->
    <div class="row g-0 mb-4">
      <div class="col-12 col-md-6">
        <table class="info-table w-100">
          <tr><td>Student Name:</td><td class="fw-bold"><?= sanitize($student['name']) ?></td></tr>
          <tr><td>Student ID:</td><td><code><?= sanitize($student['student_id']) ?></code></td></tr>
          <tr><td>Class:</td><td><?= sanitize(($student['class_name']??'') . ($student['section_name']?' - '.$student['section_name']:'')) ?></td></tr>
          <tr><td>Exam:</td><td><?= sanitize($exam['name'] ?? '') ?> (<?= sanitize($exam['type'] ?? '') ?>)</td></tr>
        </table>
      </div>
      <div class="col-12 col-md-6">
        <table class="info-table w-100">
          <tr><td>Date of Birth:</td><td><?= format_date($student['dob']) ?></td></tr>
          <tr><td>Gender:</td><td><?= sanitize($student['gender'] ?? '-') ?></td></tr>
          <tr><td>Parent:</td><td><?= sanitize($student['parent_name'] ?? '-') ?></td></tr>
          <tr><td>Exam Date:</td><td><?= format_date($exam['start_date']) ?></td></tr>
        </table>
      </div>
    </div>

    <!-- Marks Table -->
    <h6 class="fw-bold border-bottom pb-2 mb-3">Subject-wise Performance</h6>
    <div class="table-responsive mb-4">
      <table class="table table-bordered marks-table">
        <thead>
          <tr class="table-primary">
            <th>#</th>
            <th>Subject</th>
            <th>Code</th>
            <th>Max Marks</th>
            <th>Obtained</th>
            <th>%</th>
            <th>Grade</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($marks)): ?>
          <tr><td colspan="8" class="text-center text-muted">No marks entered</td></tr>
          <?php else: foreach ($marks as $i => $m):
            $pct   = $m['max_marks'] > 0 ? round(($m['marks_obtained'] / $m['max_marks']) * 100, 1) : 0;
            $grade = calculate_grade($pct);
            $remark= $pct >= 75 ? 'Excellent' : ($pct >= 60 ? 'Good' : ($pct >= 33 ? 'Average' : 'Needs Improvement'));
          ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td class="fw-semibold"><?= sanitize($m['subject_name']) ?></td>
            <td><code><?= sanitize($m['subject_code'] ?? '') ?></code></td>
            <td class="text-center"><?= $m['max_marks'] ?></td>
            <td class="text-center fw-bold <?= $pct < get_pass_percentage() ? 'text-danger' : 'text-success' ?>">
              <?= $m['marks_obtained'] ?>
            </td>
            <td class="text-center"><?= $pct ?>%</td>
            <td class="text-center">
              <span class="grade-pill bg-<?= get_grade_color($grade) ?> bg-opacity-15 text-<?= get_grade_color($grade) ?> border border-<?= get_grade_color($grade) ?>">
                <?= $grade ?>
              </span>
            </td>
            <td class="text-muted small"><?= $remark ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Result Summary -->
    <?php if ($result): ?>
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="text-center p-3 bg-light rounded">
          <div class="fs-4 fw-bold"><?= $result['total_marks'] ?></div>
          <div class="small text-muted">Total Marks</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 bg-light rounded">
          <div class="fs-4 fw-bold"><?= $result['max_marks'] ?></div>
          <div class="small text-muted">Max Marks</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 bg-light rounded">
          <div class="fs-4 fw-bold text-primary"><?= $result['percentage'] ?>%</div>
          <div class="small text-muted">Percentage</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded bg-<?= $result['result']==='Pass'?'success':'danger' ?> bg-opacity-10">
          <div class="fs-4 fw-bold text-<?= $result['result']==='Pass'?'success':'danger' ?>">
            <?= $result['result'] ?>
          </div>
          <div class="small text-muted">Result</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Signature area -->
    <div class="row mt-5 pt-3">
      <div class="col-4 text-center">
        <div class="border-top pt-2"><small>Class Teacher</small></div>
      </div>
      <div class="col-4 text-center">
        <div class="border-top pt-2"><small>Examiner</small></div>
      </div>
      <div class="col-4 text-center">
        <div class="border-top pt-2"><small>Principal</small></div>
      </div>
    </div>

    <div class="text-center mt-4 text-muted small border-top pt-3">
      <?= sanitize(get_setting('footer_text','')) ?> | Generated: <?= date('d M Y H:i') ?>
    </div>
  </div>
</div>

<script>
// Auto print if ?print=1
const params = new URLSearchParams(window.location.search);
if (params.get('print') === '1') window.print();
</script>
</body>
</html>
