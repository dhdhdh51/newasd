<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$id = sanitize_int($_GET['id'] ?? 0);
if (!$id) { set_flash('error','Invalid student.'); redirect(SITE_URL.'/admin/students/'); }

$stmt = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name,
            p.name as parent_name, p.phone as parent_phone, p.email as parent_email, p.relation
     FROM students s
     LEFT JOIN classes c    ON s.class_id=c.id
     LEFT JOIN sections sec ON s.section_id=sec.id
     LEFT JOIN parents p    ON s.parent_id=p.id
     WHERE s.id=? LIMIT 1"
);
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) { set_flash('error','Student not found.'); redirect(SITE_URL.'/admin/students/'); }

// Attendance summary
$att = attendance_summary($id);

// Recent results
$results = $pdo->prepare(
    "SELECT r.*, e.name as exam_name, e.type as exam_type
     FROM results r JOIN exams e ON r.exam_id=e.id
     WHERE r.student_id=? ORDER BY r.created_at DESC LIMIT 5"
);
$results->execute([$id]);
$results = $results->fetchAll();

// Fee summary
$fees = $pdo->prepare(
    "SELECT SUM(amount) as total,
            SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid,
            SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) as pending
     FROM fees WHERE student_id=?"
);
$fees->execute([$id]);
$fee_summary = $fees->fetch();

$page_title = sanitize($s['name']);
$breadcrumb = [
    ['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],
    ['label'=>'Students','url'=>SITE_URL.'/admin/students/'],
    ['label'=>$s['name'],'active'=>true]
];
$page_action = '<div class="d-flex gap-2">
    <a href="' . SITE_URL . '/admin/students/id-card.php?id=' . $id . '" class="btn btn-info btn-sm text-white">
      <i class="bi bi-card-text me-1"></i>ID Card</a>
    <a href="' . SITE_URL . '/admin/students/edit.php?id=' . $id . '" class="btn btn-warning btn-sm">
      <i class="bi bi-pencil me-1"></i>Edit</a>
    <a href="' . SITE_URL . '/admin/students/delete.php?id=' . $id . '" class="btn btn-danger btn-sm"
       onclick="return confirm(\'Delete this student?\')">
      <i class="bi bi-trash me-1"></i>Delete</a>
</div>';

include INCLUDES_PATH . 'header.php';
?>

<div class="row g-3">
  <!-- Profile card -->
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center py-4">
        <?php if ($s['photo']): ?>
          <img src="<?= get_upload_url($s['photo']) ?>" class="rounded-circle border shadow"
               width="100" height="100" style="object-fit:cover">
        <?php else: ?>
          <div class="avatar-xl mx-auto bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-2">
            <?= strtoupper(substr($s['name'],0,1)) ?>
          </div>
        <?php endif; ?>
        <h5 class="fw-bold mt-3 mb-0"><?= sanitize($s['name']) ?></h5>
        <code class="text-primary"><?= sanitize($s['student_id']) ?></code>
        <div class="mt-2">
          <span class="badge bg-<?= $s['status']==='active'?'success':'secondary' ?> px-3">
            <?= ucfirst($s['status']) ?>
          </span>
        </div>

        <div class="mt-3 text-start">
          <?php $details = [
            ['bi-mortarboard','Class', ($s['class_name']??'-') . ($s['section_name']?' - '.$s['section_name']:'')],
            ['bi-calendar','DOB', format_date($s['dob'])],
            ['bi-gender-ambiguous','Gender', $s['gender'] ?? '-'],
            ['bi-droplet','Blood', $s['blood_group'] ?? '-'],
            ['bi-telephone','Phone', $s['phone'] ?? '-'],
            ['bi-envelope','Email', $s['email'] ?? '-'],
            ['bi-calendar-check','Admitted', format_date($s['admission_date'])],
          ]; ?>
          <?php foreach ($details as [$icon,$label,$val]): ?>
          <div class="d-flex gap-2 mb-2 align-items-center">
            <i class="bi bi-<?= $icon ?> text-primary flex-shrink-0"></i>
            <div>
              <div class="text-muted" style="font-size:0.7rem;line-height:1"><?= $label ?></div>
              <div class="small fw-semibold"><?= sanitize((string)$val) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Parent Info -->
    <?php if ($s['parent_name']): ?>
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white border-0 fw-semibold small">
        <i class="bi bi-people me-2 text-info"></i>Parent Information
      </div>
      <div class="card-body">
        <?php $pdetails = [
          ['bi-person','Name', $s['parent_name']],
          ['bi-heart','Relation', $s['relation']??''],
          ['bi-telephone','Phone', $s['parent_phone']??'-'],
          ['bi-envelope','Email', $s['parent_email']??'-'],
        ]; ?>
        <?php foreach ($pdetails as [$icon,$label,$val]): ?>
        <div class="d-flex gap-2 mb-1 align-items-center">
          <i class="bi bi-<?= $icon ?> text-info flex-shrink-0 small"></i>
          <div class="small"><span class="text-muted"><?= $label ?>: </span><?= sanitize((string)$val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Details -->
  <div class="col-12 col-lg-8">
    <!-- Stats row -->
    <div class="row g-3 mb-3">
      <div class="col-4">
        <div class="card border-0 bg-success bg-opacity-10 h-100">
          <div class="card-body text-center py-3">
            <div class="fs-3 fw-bold text-success"><?= $att['present'] ?? 0 ?></div>
            <div class="small text-muted">Present Days</div>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="card border-0 bg-danger bg-opacity-10 h-100">
          <div class="card-body text-center py-3">
            <div class="fs-3 fw-bold text-danger"><?= $att['absent'] ?? 0 ?></div>
            <div class="small text-muted">Absent Days</div>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="card border-0 bg-primary bg-opacity-10 h-100">
          <div class="card-body text-center py-3">
            <div class="fs-3 fw-bold text-primary"><?= $att['percentage'] ?? 0 ?>%</div>
            <div class="small text-muted">Attendance</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fee Summary -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white border-0 fw-semibold small d-flex justify-content-between">
        <span><i class="bi bi-receipt me-2 text-success"></i>Fee Summary</span>
        <a href="<?= SITE_URL ?>/admin/fees/?student_id=<?= $id ?>" class="text-primary small">Manage Fees</a>
      </div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-4 text-center">
            <div class="fw-bold"><?= currency_format((float)($fee_summary['total']??0)) ?></div>
            <div class="text-muted small">Total</div>
          </div>
          <div class="col-4 text-center">
            <div class="fw-bold text-success"><?= currency_format((float)($fee_summary['paid']??0)) ?></div>
            <div class="text-muted small">Paid</div>
          </div>
          <div class="col-4 text-center">
            <div class="fw-bold text-danger"><?= currency_format((float)($fee_summary['pending']??0)) ?></div>
            <div class="text-muted small">Pending</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Results -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold small d-flex justify-content-between">
        <span><i class="bi bi-award me-2 text-warning"></i>Recent Results</span>
        <a href="<?= SITE_URL ?>/admin/results/?student_id=<?= $id ?>" class="text-primary small">All Results</a>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0 small">
            <thead class="table-light">
              <tr><th>Exam</th><th>Total</th><th>%</th><th>Grade</th><th>Result</th><th>Report</th></tr>
            </thead>
            <tbody>
              <?php if (empty($results)): ?>
              <tr><td colspan="6" class="text-center py-3 text-muted">No results yet</td></tr>
              <?php else: foreach ($results as $r): ?>
              <tr>
                <td><?= sanitize($r['exam_name']) ?><br><small class="text-muted"><?= $r['exam_type'] ?></small></td>
                <td><?= $r['total_marks'] ?>/<?= $r['max_marks'] ?></td>
                <td><?= $r['percentage'] ?>%</td>
                <td><span class="badge bg-<?= get_grade_color($r['grade']??'F') ?>"><?= $r['grade'] ?></span></td>
                <td><span class="badge bg-<?= $r['result']==='Pass'?'success':'danger' ?>"><?= $r['result'] ?></span></td>
                <td>
                  <a href="<?= SITE_URL ?>/admin/results/report-card.php?exam_id=<?= $r['exam_id'] ?>&student_id=<?= $id ?>"
                     class="btn btn-xs btn-outline-primary btn-sm py-0 px-2" target="_blank">
                     <i class="bi bi-printer"></i></a>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
