<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'mailer.php';
require_once ROOT_PATH . 'payment/payu.php';

$salt = get_setting('payu_merchant_salt','');
$data = $_POST;

if (empty($data)) {
    redirect(SITE_URL . '/student/fees.php');
}

$status  = sanitize($data['status']  ?? '');
$txnid   = sanitize($data['txnid']   ?? '');
$amount  = sanitize_float($data['amount'] ?? 0);
$fee_id  = sanitize_int($data['udf1'] ?? 0);

// Verify hash
if (!verify_payu_hash($data, $salt)) {
    set_flash('error', 'Payment verification failed. Contact admin.');
    redirect(SITE_URL . '/student/fees.php');
}

if (strtolower($status) === 'success') {
    // Update fee status
    $pdo->prepare("UPDATE fees SET status='paid' WHERE id=?")->execute([$fee_id]);

    // Record transaction
    $pdo->prepare(
        "INSERT INTO transactions (fee_id,student_id,amount,payment_method,txn_id,payu_txn_id,payu_response,status)
         VALUES (?,?,?,'PayU',?,?,?,'success')
         ON DUPLICATE KEY UPDATE status='success',payu_txn_id=VALUES(payu_txn_id)"
    )->execute([
        $fee_id,
        sanitize_int($data['udf1'] ?? 0),
        $amount,
        $txnid,
        sanitize($data['payuMoneyId'] ?? $txnid),
        json_encode($data)
    ]);

    // Get student info for email
    $fee_stmt = $pdo->prepare("SELECT f.*,s.name,s.email FROM fees f JOIN students s ON f.student_id=s.id WHERE f.id=?");
    $fee_stmt->execute([$fee_id]);
    $fee = $fee_stmt->fetch();

    if ($fee && $fee['email']) {
        SchoolMailer::sendPaymentConfirmation($fee['email'], $fee['name'], $txnid, $amount);
    }

    // Notification
    if ($fee) {
        $stu_stmt = $pdo->prepare("SELECT user_id FROM students WHERE id=?");
        $stu_stmt->execute([$fee['student_id']]);
        $uid = $stu_stmt->fetchColumn();
        create_notification($uid,'student','Payment Successful',"Payment of " . currency_format($amount) . " received. Txn: {$txnid}", 'success');
    }

    $site_name = get_setting('site_name','School ERP');
} else {
    // Payment failed — record it
    $pdo->prepare(
        "INSERT INTO transactions (fee_id,amount,payment_method,txn_id,payu_response,status)
         VALUES (?,?,'PayU',?,?,'failed')"
    )->execute([$fee_id, $amount, $txnid, json_encode($data)]);

    redirect(SITE_URL . '/payment/failure.php?txnid=' . urlencode($txnid));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payment Successful</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
      <div class="card border-0 shadow-sm text-center">
        <div class="card-body py-5">
          <div class="text-success display-1 mb-3">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <h3 class="fw-bold text-success mb-1">Payment Successful!</h3>
          <p class="text-muted mb-3">Your fee payment has been processed successfully.</p>

          <div class="bg-light rounded p-3 mb-4 text-start">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted small">Transaction ID:</span>
              <code><?= sanitize($txnid) ?></code>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted small">Amount Paid:</span>
              <strong><?= currency_format($amount) ?></strong>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted small">Date:</span>
              <span><?= date('d M Y, h:i A') ?></span>
            </div>
          </div>

          <p class="text-muted small mb-4">A confirmation email has been sent to your registered email address.</p>

          <div class="d-flex gap-2 justify-content-center">
            <a href="<?= SITE_URL ?>/student/fees.php" class="btn btn-primary px-4">
              <i class="bi bi-receipt me-2"></i>View Receipts
            </a>
            <a href="<?= SITE_URL ?>/student/" class="btn btn-outline-secondary">
              Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
