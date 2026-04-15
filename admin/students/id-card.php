<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$id = sanitize_int($_GET['id'] ?? 0);
if (!$id) { redirect(SITE_URL.'/admin/students/'); }

$stmt = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name,
            p.name as parent_name, p.phone as parent_phone, p.relation
     FROM students s
     LEFT JOIN classes c    ON s.class_id=c.id
     LEFT JOIN sections sec ON s.section_id=sec.id
     LEFT JOIN parents p    ON s.parent_id=p.id
     WHERE s.id=? LIMIT 1"
);
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) { redirect(SITE_URL.'/admin/students/'); }

$site_name    = get_setting('site_name','School ERP');
$site_logo    = get_setting('site_logo','');
$contact_phone= get_setting('contact_phone','');
$contact_email= get_setting('contact_email','');
$contact_addr = get_setting('contact_address','');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ID Card - <?= sanitize($s['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #e9ecef; }
    .id-card {
      width: 340px;
      border-radius: 16px;
      overflow: hidden;
      background: #fff;
      box-shadow: 0 4px 20px rgba(0,0,0,.15);
      font-family: Arial, sans-serif;
    }
    .id-card-header {
      background: linear-gradient(135deg, #0d6efd, #0099ff);
      padding: 16px;
      color: white;
      text-align: center;
    }
    .id-card-header h5 { margin: 4px 0 0; font-size: 15px; font-weight: 700; }
    .id-card-header small { font-size: 11px; opacity: .8; }
    .id-card-body { padding: 16px; }
    .student-photo {
      width: 80px; height: 80px;
      border-radius: 50%;
      border: 3px solid #0d6efd;
      object-fit: cover;
    }
    .student-name { font-size: 16px; font-weight: 700; color: #212529; }
    .student-id   { font-size: 13px; color: #0d6efd; font-weight: 600; }
    .info-row { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; font-size: 12px; }
    .info-label { color: #6c757d; min-width: 70px; }
    .info-value { font-weight: 600; color: #212529; }
    .id-card-footer {
      background: #f8f9fa;
      border-top: 1px solid #dee2e6;
      padding: 10px 16px;
      font-size: 11px;
      color: #6c757d;
      text-align: center;
    }
    .barcode-area {
      text-align: center;
      padding: 8px 0;
      font-family: monospace;
      font-size: 12px;
      letter-spacing: 2px;
      color: #333;
    }
    @media print {
      body { background: white !important; }
      .no-print { display: none !important; }
      .id-card { box-shadow: none; margin: 0; }
    }
  </style>
</head>
<body>

<div class="no-print text-center py-3">
  <button onclick="window.print()" class="btn btn-primary me-2">
    <i class="bi bi-printer me-1"></i>Print ID Card
  </button>
  <a href="<?= SITE_URL ?>/admin/students/view.php?id=<?= $id ?>" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back
  </a>
</div>

<div class="d-flex justify-content-center align-items-start py-3">
  <!-- Front of ID Card -->
  <div class="id-card m-3">
    <div class="id-card-header">
      <?php if ($site_logo): ?>
        <img src="<?= get_upload_url($site_logo) ?>" height="40" class="mb-1 rounded">
      <?php endif; ?>
      <h5><?= sanitize($site_name) ?></h5>
      <small>Student Identity Card</small>
    </div>

    <div class="id-card-body">
      <div class="d-flex gap-3 align-items-center mb-3">
        <?php if ($s['photo']): ?>
          <img src="<?= get_upload_url($s['photo']) ?>" class="student-photo" alt="Photo">
        <?php else: ?>
          <div class="student-photo bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4">
            <?= strtoupper(substr($s['name'],0,1)) ?>
          </div>
        <?php endif; ?>
        <div>
          <div class="student-name"><?= sanitize($s['name']) ?></div>
          <div class="student-id"><?= sanitize($s['student_id']) ?></div>
          <div class="badge bg-primary mt-1" style="font-size:10px">
            <?= sanitize(($s['class_name']??'') . ($s['section_name']?' - '.$s['section_name']:'')) ?>
          </div>
        </div>
      </div>

      <?php $rows = [
        ['DOB',        format_date($s['dob'])],
        ['Gender',     $s['gender']??'-'],
        ['Blood Grp',  $s['blood_group']??'-'],
        ['Phone',      $s['phone']??'-'],
        ['Parent',     $s['parent_name']??'-'],
        ['Admitted',   format_date($s['admission_date'])],
      ]; ?>
      <?php foreach ($rows as [$l,$v]): ?>
      <div class="info-row">
        <span class="info-label"><?= $l ?>:</span>
        <span class="info-value"><?= sanitize((string)$v) ?></span>
      </div>
      <?php endforeach; ?>

      <div class="barcode-area mt-2">
        ||||| <?= sanitize($s['student_id']) ?> |||||
      </div>
    </div>

    <div class="id-card-footer">
      <?= sanitize($contact_phone) ?> | <?= sanitize($contact_email) ?><br>
      <?= sanitize($contact_addr) ?>
    </div>
  </div>

  <!-- Back of ID Card -->
  <div class="id-card m-3">
    <div class="id-card-header">
      <h5><?= sanitize($site_name) ?></h5>
      <small>Important Information</small>
    </div>
    <div class="id-card-body" style="font-size:12px">
      <p class="fw-semibold mb-2">Terms & Conditions:</p>
      <ol class="ps-3" style="font-size:11px;line-height:1.6">
        <li>This card is the property of <?= sanitize($site_name) ?>.</li>
        <li>If found, please return to the school office.</li>
        <li>This card must be carried at all times on school premises.</li>
        <li>Any misuse of this card will lead to disciplinary action.</li>
        <li>Card is non-transferable and valid for current academic year.</li>
      </ol>

      <div class="mt-3 p-2 bg-light rounded text-center" style="font-size:11px">
        <strong>Academic Year:</strong> <?= get_setting('academic_year','2025-2026') ?><br>
        <strong>Issue Date:</strong> <?= date('d M Y') ?>
      </div>

      <div class="mt-3 text-center">
        <div style="border-top:1px solid #333;width:120px;margin:0 auto;margin-top:30px"></div>
        <div style="font-size:10px;color:#666">Principal's Signature</div>
      </div>
    </div>

    <div class="id-card-footer">
      <strong><?= sanitize($site_name) ?></strong><br>
      <?= sanitize($contact_addr) ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></script>
</body>
</html>
