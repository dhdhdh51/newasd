<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$exam_id    = sanitize_int($_GET['exam_id']    ?? 0);
$student_id = sanitize_int($_GET['student_id'] ?? 0);

// Verify this student owns the requested report
$me = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$me || $me['id'] !== $student_id) {
    die('<p class="text-danger text-center mt-5">Access denied.</p>');
}

if (!$exam_id || !$student_id) die('Invalid parameters.');

// Load exam
$stmt = $pdo->prepare("SELECT e.*, c.name as class_name FROM exams e LEFT JOIN classes c ON e.class_id=c.id WHERE e.id=?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

// Load student with parent
$stmt = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name,
            p.name as parent_name, p.relation as parent_relation, p.phone as parent_phone
     FROM students s
     LEFT JOIN classes  c   ON s.class_id=c.id
     LEFT JOIN sections sec ON s.section_id=sec.id
     LEFT JOIN parents  p   ON s.parent_id=p.id
     WHERE s.id=?"
);
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$exam || !$student) die('Not found.');

// Check result is published
$stmt = $pdo->prepare("SELECT * FROM results WHERE exam_id=? AND student_id=? AND published=1");
$stmt->execute([$exam_id, $student_id]);
$result = $stmt->fetch();
if (!$result) die('<p class="text-center text-muted mt-5">Result not published yet.</p>');

// Load marks
$stmt = $pdo->prepare(
    "SELECT m.*, sub.name as subject_name, sub.code as subject_code
     FROM marks m JOIN subjects sub ON m.subject_id=sub.id
     WHERE m.exam_id=? AND m.student_id=?
     ORDER BY sub.name"
);
$stmt->execute([$exam_id, $student_id]);
$marks = $stmt->fetchAll();

$site_name = get_setting('site_name', 'School ERP');
$site_logo = get_setting('site_logo', '');
$contact   = get_setting('contact_address', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Report Card - <?= sanitize($student['name']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Poppins', Arial, sans-serif; background: #f4f6fb; color: #1a1a2e; }
    .no-print { background: #1a1a2e; padding: 14px 20px; display: flex; gap: 10px; justify-content: center; }
    .no-print button, .no-print a {
      padding: 8px 22px; border-radius: 8px; font-weight: 600; font-size: 14px;
      cursor: pointer; text-decoration: none; border: none;
    }
    .btn-print { background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff; }
    .btn-back  { background: rgba(255,255,255,.1); color: #fff; border: 1px solid rgba(255,255,255,.2) !important; }
    .report-card {
      max-width: 820px; margin: 24px auto; background: #fff;
      border-radius: 16px; overflow: hidden;
      box-shadow: 0 20px 60px rgba(99,102,241,.18);
    }
    .rc-header {
      background: linear-gradient(135deg,#1a1a2e 0%,#6366f1 60%,#8b5cf6 100%);
      color: #fff; padding: 28px 30px; display: flex; align-items: center; gap: 18px; position: relative; overflow: hidden;
    }
    .rc-header::before {
      content:''; position:absolute; top:-40px; right:-40px; width:160px; height:160px;
      background: rgba(255,255,255,.06); border-radius:50%;
    }
    .rc-logo { width:70px; height:70px; border-radius:12px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:32px; flex-shrink:0; }
    .rc-logo img { width:70px; height:70px; object-fit:cover; border-radius:12px; }
    .rc-title { font-size:22px; font-weight:700; letter-spacing:.3px; }
    .rc-sub { font-size:12px; opacity:.8; margin-top:2px; }
    .rc-badge { background:rgba(255,255,255,.2); border-radius:6px; padding:4px 12px; font-size:11px; font-weight:600; letter-spacing:.5px; }
    .student-photo { width:75px; height:75px; border-radius:12px; object-fit:cover; border:3px solid rgba(255,255,255,.4); margin-left:auto; }

    .rc-body { padding: 28px 30px; }
    .section-title { font-size:12px; font-weight:700; letter-spacing:.08em; color:#6366f1; text-transform:uppercase; margin-bottom:12px; padding-bottom:6px; border-bottom:2px solid #ede9fe; }
    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 24px; margin-bottom:24px; }
    .info-item label { font-size:11px; color:#888; display:block; margin-bottom:2px; }
    .info-item span  { font-size:13px; font-weight:600; color:#1a1a2e; }

    .marks-table { width:100%; border-collapse:collapse; margin-bottom:24px; font-size:13px; }
    .marks-table th { background:#f5f3ff; color:#6366f1; font-weight:600; padding:10px 12px; text-align:left; border-bottom:2px solid #ede9fe; }
    .marks-table td { padding:10px 12px; border-bottom:1px solid #f0f0f0; }
    .marks-table tr:hover td { background:#fafafe; }
    .grade-pill { display:inline-block; padding:2px 10px; border-radius:20px; font-weight:700; font-size:12px; }
    .pass { background:#dcfce7; color:#166534; }
    .fail { background:#fee2e2; color:#991b1b; }

    .summary-box { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
    .summary-item { background:#f8f7ff; border-radius:12px; padding:14px; text-align:center; border:1px solid #ede9fe; }
    .summary-item .val { font-size:22px; font-weight:700; color:#6366f1; }
    .summary-item .lbl { font-size:11px; color:#888; margin-top:2px; }

    .sig-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:24px; margin-top:36px; padding-top:24px; border-top:1px dashed #e0e0e0; }
    .sig-item { text-align:center; }
    .sig-line { border-top:1px solid #bbb; padding-top:8px; font-size:12px; color:#888; margin-top:36px; }

    .parent-box { background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; padding:14px 18px; margin-bottom:24px; display:flex; align-items:center; gap:14px; }
    .parent-box .icon { font-size:24px; color:#0ea5e9; flex-shrink:0; }
    .parent-box .lbl { font-size:11px; color:#666; }
    .parent-box .val { font-size:14px; font-weight:600; color:#0c4a6e; }
    .parent-missing { background:#fef9c3; border:1px solid #fde047; border-radius:12px; padding:12px 16px; margin-bottom:24px; font-size:12px; color:#854d0e; }

    .watermark { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%) rotate(-30deg); font-size:90px; color:rgba(99,102,241,.04); pointer-events:none; z-index:0; font-weight:900; white-space:nowrap; }
    .footer-bar { text-align:center; font-size:11px; color:#aaa; margin-top:16px; padding-top:12px; border-top:1px solid #f0f0f0; }

    @media print {
      body { background:#fff !important; }
      .no-print { display:none !important; }
      .report-card { margin:0 !important; box-shadow:none !important; border-radius:0 !important; }
      @page { margin:10mm; size:A4; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">🖨️ Print Report Card</button>
  <a href="<?= SITE_URL ?>/student/results.php" class="btn-back">← Back to Results</a>
</div>

<div class="watermark"><?= sanitize($site_name) ?></div>

<div class="report-card">
  <!-- Header -->
  <div class="rc-header">
    <div class="rc-logo">
      <?php if ($site_logo): ?>
        <img src="<?= get_upload_url($site_logo) ?>" alt="Logo">
      <?php else: ?>
        🏫
      <?php endif; ?>
    </div>
    <div>
      <div class="rc-title"><?= sanitize($site_name) ?></div>
      <div class="rc-sub"><?= sanitize($contact) ?></div>
      <div style="margin-top:10px"><span class="rc-badge">📋 STUDENT REPORT CARD &bull; <?= sanitize(get_setting('academic_year','2025-2026')) ?></span></div>
    </div>
    <?php if ($student['photo']): ?>
      <img class="student-photo" src="<?= get_upload_url($student['photo']) ?>" alt="Photo">
    <?php endif; ?>
  </div>

  <!-- Body -->
  <div class="rc-body">

    <!-- Student Info -->
    <div class="section-title">Student Information</div>
    <div class="info-grid">
      <div class="info-item"><label>Student Name</label><span><?= sanitize($student['name']) ?></span></div>
      <div class="info-item"><label>Student ID</label><span><?= sanitize($student['student_id']) ?></span></div>
      <div class="info-item"><label>Class &amp; Section</label><span><?= sanitize(($student['class_name']??'-').($student['section_name']?' - '.$student['section_name']:'')) ?></span></div>
      <div class="info-item"><label>Date of Birth</label><span><?= format_date($student['dob']) ?></span></div>
      <div class="info-item"><label>Gender</label><span><?= sanitize($student['gender'] ?? '-') ?></span></div>
      <div class="info-item"><label>Exam</label><span><?= sanitize($exam['name']) ?> (<?= sanitize($exam['type']) ?>)</span></div>
    </div>

    <!-- Parent Info -->
    <div class="section-title">Parent / Guardian Information</div>
    <?php if ($student['parent_name']): ?>
    <div class="parent-box">
      <span class="icon">👨‍👩‍👦</span>
      <div style="flex:1">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
          <div><div class="lbl">Name</div><div class="val"><?= sanitize($student['parent_name']) ?></div></div>
          <div><div class="lbl">Relation</div><div class="val"><?= sanitize($student['parent_relation'] ?? 'Guardian') ?></div></div>
          <div><div class="lbl">Phone</div><div class="val"><?= sanitize($student['parent_phone'] ?? '-') ?></div></div>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="parent-missing">⚠️ Parent / Guardian information not added. Please update your profile to include parent details on the report card.</div>
    <?php endif; ?>

    <!-- Marks Table -->
    <div class="section-title">Subject-wise Performance</div>
    <table class="marks-table">
      <thead>
        <tr>
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
          <tr><td colspan="8" style="text-align:center;color:#888;padding:20px">No marks entered</td></tr>
        <?php else: foreach ($marks as $i => $m):
          $pct    = $m['max_marks'] > 0 ? round(($m['marks_obtained'] / $m['max_marks']) * 100, 1) : 0;
          $grade  = calculate_grade($pct);
          $remark = $pct >= 75 ? 'Excellent' : ($pct >= 60 ? 'Good' : ($pct >= 33 ? 'Average' : 'Needs Improvement'));
          $fail   = $pct < get_pass_percentage();
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td style="font-weight:600"><?= sanitize($m['subject_name']) ?></td>
          <td style="font-family:monospace;color:#6366f1"><?= sanitize($m['subject_code'] ?? '') ?></td>
          <td style="text-align:center"><?= $m['max_marks'] ?></td>
          <td style="text-align:center;font-weight:700;color:<?= $fail ? '#dc2626' : '#16a34a' ?>"><?= $m['marks_obtained'] ?></td>
          <td style="text-align:center"><?= $pct ?>%</td>
          <td style="text-align:center"><span class="grade-pill <?= $fail ? 'fail' : 'pass' ?>"><?= $grade ?></span></td>
          <td style="color:#888;font-size:12px"><?= $remark ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <!-- Summary -->
    <div class="section-title">Result Summary</div>
    <div class="summary-box">
      <div class="summary-item">
        <div class="val"><?= $result['total_marks'] ?></div>
        <div class="lbl">Total Marks</div>
      </div>
      <div class="summary-item">
        <div class="val"><?= $result['max_marks'] ?></div>
        <div class="lbl">Maximum</div>
      </div>
      <div class="summary-item">
        <div class="val" style="color:<?= $result['percentage'] >= get_pass_percentage() ? '#6366f1' : '#dc2626' ?>"><?= $result['percentage'] ?>%</div>
        <div class="lbl">Percentage</div>
      </div>
      <div class="summary-item">
        <div class="val" style="color:<?= $result['result']==='Pass' ? '#16a34a' : '#dc2626' ?>"><?= $result['result'] ?></div>
        <div class="lbl">Result</div>
      </div>
    </div>

    <!-- Grade Legend -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px">
      <?php foreach ([['A+','90+'],['A','80-89'],['B+','70-79'],['B','60-69'],['C','50-59'],['D','33-49'],['F','<33']] as [$g,$r]): ?>
      <div style="background:#f5f3ff;border-radius:6px;padding:4px 10px;font-size:11px;color:#6366f1;font-weight:600">
        <?= $g ?>: <?= $r ?>%
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($result['remarks']): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px">
      <strong>Teacher Remarks:</strong> <?= sanitize($result['remarks']) ?>
    </div>
    <?php endif; ?>

    <!-- Signatures -->
    <div class="sig-row">
      <div class="sig-item"><div class="sig-line">Class Teacher</div></div>
      <div class="sig-item"><div class="sig-line">Examiner</div></div>
      <div class="sig-item"><div class="sig-line">Principal</div></div>
    </div>

    <div class="footer-bar">
      <?= sanitize(get_setting('footer_text','')) ?> &bull; Generated on <?= date('d M Y H:i') ?>
    </div>
  </div>
</div>

<script>
const p = new URLSearchParams(window.location.search);
if (p.get('print') === '1') window.print();
</script>
</body>
</html>
