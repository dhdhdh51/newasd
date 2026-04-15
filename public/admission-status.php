<?php
require_once dirname(__DIR__) . '/config/config.php';

$site_name = get_setting('site_name','School ERP');
$app_id    = sanitize($_GET['id'] ?? ($_POST['app_id'] ?? ''));
$admission = null;
$searched  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $app_id) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_protect();
        $app_id = sanitize($_POST['app_id'] ?? '');
    }
    if (!empty($app_id)) {
        $searched = true;
        $stmt = $pdo->prepare(
            "SELECT a.*, c.name as class_name FROM admissions a
             LEFT JOIN classes c ON a.class_applying=c.id
             WHERE a.application_id=? LIMIT 1"
        );
        $stmt->execute([$app_id]);
        $admission = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admission Status - <?= sanitize($site_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
      <i class="bi bi-mortarboard-fill me-2"></i><?= sanitize($site_name) ?>
    </a>
    <div class="ms-auto d-flex gap-2">
      <a href="<?= SITE_URL ?>/public/admission.php" class="btn btn-outline-light btn-sm">Apply Now</a>
      <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-light btn-sm">Login</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="text-center mb-4">
        <h2 class="fw-bold">Track Admission Status</h2>
        <p class="text-muted">Enter your Application ID to check the status of your admission.</p>
      </div>

      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
          <form method="POST">
            <?= csrf_field() ?>
            <label class="form-label fw-semibold">Application ID</label>
            <div class="input-group">
              <input type="text" name="app_id" class="form-control form-control-lg font-monospace"
                     value="<?= sanitize($app_id) ?>"
                     placeholder="e.g., APP2026-0001" required autofocus>
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-search me-1"></i>Check
              </button>
            </div>
          </form>
        </div>
      </div>

      <?php if ($searched && !$admission): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
          <i class="bi bi-search text-muted display-4 d-block mb-3"></i>
          <h5 class="text-muted">Application Not Found</h5>
          <p class="text-muted small">
            No application found for ID: <code><?= sanitize($app_id) ?></code><br>
            Please verify your Application ID and try again.
          </p>
          <a href="<?= SITE_URL ?>/public/admission.php" class="btn btn-primary mt-2">
            Apply for Admission
          </a>
        </div>
      </div>

      <?php elseif ($admission): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <div class="d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0">Application Details</h6>
            <?php
            $status_cfg = match($admission['status']) {
                'approved' => ['success','check-circle-fill','Approved'],
                'rejected' => ['danger','x-circle-fill','Rejected'],
                default    => ['warning','clock-fill','Pending Review'],
            };
            ?>
            <span class="badge bg-<?= $status_cfg[0] ?> px-3 py-2 fs-6">
              <i class="bi bi-<?= $status_cfg[1] ?> me-1"></i><?= $status_cfg[2] ?>
            </span>
          </div>
        </div>
        <div class="card-body">

          <!-- Status timeline -->
          <div class="d-flex align-items-center mb-4 gap-2">
            <?php foreach (['submitted'=>'Submitted','pending'=>'Under Review','approved'=>'Decision'] as $step => $label):
              $done = match($step) {
                'submitted' => true,
                'pending'   => true,
                'approved'  => in_array($admission['status'],['approved','rejected']),
              };
              $active = match($step) {
                'submitted' => $admission['status']==='pending',
                'pending'   => $admission['status']==='pending',
                'approved'  => in_array($admission['status'],['approved','rejected']),
              };
            ?>
            <div class="text-center flex-fill">
              <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold"
                   style="width:36px;height:36px;background:<?= $done?'#0d6efd':'#dee2e6' ?>;color:<?= $done?'white':'#aaa' ?>">
                <?= $done ? '<i class="bi bi-check-lg"></i>' : '' ?>
              </div>
              <div class="small mt-1 text-<?= $done?'primary':'muted' ?>"><?= $label ?></div>
            </div>
            <?php if ($step !== 'approved'): ?>
            <div class="flex-grow-0" style="height:2px;background:#dee2e6;flex:1"></div>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <!-- Details table -->
          <table class="table table-sm">
            <tr><th width="40%">Application ID</th><td><code><?= sanitize($admission['application_id']) ?></code></td></tr>
            <tr><th>Applicant Name</th><td><?= sanitize($admission['name']) ?></td></tr>
            <tr><th>Class Applied</th><td><?= sanitize($admission['class_name'] ?? 'N/A') ?></td></tr>
            <tr><th>Applied On</th><td><?= format_date($admission['created_at'],'d M Y, h:i A') ?></td></tr>
            <tr><th>Parent/Guardian</th><td><?= sanitize($admission['parent_name'] ?? '-') ?></td></tr>
            <tr><th>Contact</th><td><?= sanitize($admission['phone'] ?? '-') ?></td></tr>
            <?php if ($admission['reviewed_at']): ?>
            <tr><th>Decision Date</th><td><?= format_date($admission['reviewed_at'],'d M Y, h:i A') ?></td></tr>
            <?php endif; ?>
            <?php if ($admission['remarks']): ?>
            <tr>
              <th>Remarks</th>
              <td class="<?= $admission['status']==='rejected'?'text-danger':'' ?>">
                <?= sanitize($admission['remarks']) ?>
              </td>
            </tr>
            <?php endif; ?>
          </table>

          <?php if ($admission['status'] === 'approved'): ?>
          <div class="alert alert-success mt-3 mb-0">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Congratulations!</strong> Your admission has been approved.
            Please contact the school office to complete the enrollment process.
          </div>
          <?php elseif ($admission['status'] === 'rejected'): ?>
          <div class="alert alert-danger mt-3 mb-0">
            <i class="bi bi-x-circle-fill me-2"></i>
            We regret to inform you that your application has not been approved at this time.
            <?= $admission['remarks'] ? 'Reason: '.sanitize($admission['remarks']) : '' ?>
          </div>
          <?php else: ?>
          <div class="alert alert-warning mt-3 mb-0">
            <i class="bi bi-clock-fill me-2"></i>
            Your application is under review. You will be notified via email once a decision is made.
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<footer class="text-center py-3 text-muted small bg-white border-top mt-4">
  <?= sanitize(get_setting('footer_text','')) ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
