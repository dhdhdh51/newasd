<?php
require_once dirname(__DIR__) . '/config/config.php';
$txnid = sanitize($_GET['txnid'] ?? ($_POST['txnid'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payment Failed</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
      <div class="card border-0 shadow-sm text-center">
        <div class="card-body py-5">
          <div class="text-danger display-1 mb-3">
            <i class="bi bi-x-circle-fill"></i>
          </div>
          <h3 class="fw-bold text-danger mb-1">Payment Failed</h3>
          <p class="text-muted mb-3">Your payment could not be processed. No amount was deducted.</p>
          <?php if ($txnid): ?>
          <p class="text-muted small">Reference: <code><?= sanitize($txnid) ?></code></p>
          <?php endif; ?>
          <div class="alert alert-warning text-start small">
            <i class="bi bi-info-circle me-1"></i>
            Common reasons: Insufficient balance, wrong OTP, bank timeout, or cancelled by user.
          </div>
          <div class="d-flex gap-2 justify-content-center mt-3">
            <a href="<?= SITE_URL ?>/student/fees.php" class="btn btn-primary px-4">
              <i class="bi bi-arrow-counterclockwise me-2"></i>Try Again
            </a>
            <a href="<?= SITE_URL ?>/student/" class="btn btn-outline-secondary">Dashboard</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
