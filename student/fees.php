<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$student = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$student) redirect(SITE_URL . '/auth/login.php');

$fees = $pdo->prepare(
    "SELECT f.*, (SELECT COUNT(*) FROM transactions t WHERE t.fee_id=f.id AND t.status='success') as has_txn
     FROM fees f WHERE f.student_id=? ORDER BY f.created_at DESC"
);
$fees->execute([$student['id']]);
$fees = $fees->fetchAll();

// Fee summary
$fee_total   = array_sum(array_column($fees,'amount'));
$fee_paid    = array_sum(array_column(array_filter($fees, fn($f) => $f['status']==='paid'), 'amount'));
$fee_pending = $fee_total - $fee_paid;

// PayU settings
$payu_key     = get_setting('payu_merchant_key','');
$payu_salt    = get_setting('payu_merchant_salt','');
$payu_mode    = get_setting('payu_mode','test');
$payu_base    = $payu_mode === 'live'
    ? 'https://secure.payu.in/_payment'
    : 'https://test.payu.in/_payment';

$page_title = 'Fee Payment';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/student/'],['label'=>'Fees','active'=>true]];
include INCLUDES_PATH . 'header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-4"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-4 fw-bold"><?= currency_format($fee_total) ?></div><div class="small text-muted">Total</div></div></div>
  <div class="col-4"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-4 fw-bold text-success"><?= currency_format($fee_paid) ?></div><div class="small text-muted">Paid</div></div></div>
  <div class="col-4"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-4 fw-bold text-danger"><?= currency_format($fee_pending) ?></div><div class="small text-muted">Due</div></div></div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 fw-semibold"><i class="bi bi-receipt me-2 text-success"></i>My Invoices</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Invoice</th><th>Type</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php if (empty($fees)): ?>
          <tr><td colspan="6" class="text-center py-5 text-muted">No fee invoices found.</td></tr>
          <?php else: foreach ($fees as $f): ?>
          <tr>
            <td><code class="text-success"><?= sanitize($f['invoice_no']) ?></code></td>
            <td><?= sanitize($f['fee_type']) ?></td>
            <td class="fw-semibold"><?= currency_format((float)$f['amount']) ?></td>
            <td class="small <?= $f['due_date'] && $f['due_date'] < date('Y-m-d') && $f['status']==='pending' ? 'text-danger fw-bold' : 'text-muted' ?>">
              <?= format_date($f['due_date']) ?>
            </td>
            <td>
              <span class="badge bg-<?= match($f['status']){
                'paid'=>'success','overdue'=>'danger','partial'=>'info',default=>'warning'
              } ?>">
                <?= ucfirst($f['status']) ?>
              </span>
            </td>
            <td>
              <?php if ($f['status'] !== 'paid' && !empty($payu_key)): ?>
              <button class="btn btn-primary btn-sm"
                      onclick="payNow(<?= $f['id'] ?>, '<?= sanitize($f['invoice_no']) ?>', <?= $f['amount'] ?>)">
                <i class="bi bi-credit-card me-1"></i>Pay
              </button>
              <?php elseif ($f['status'] !== 'paid'): ?>
              <span class="text-muted small">Contact office</span>
              <?php else: ?>
              <span class="text-success small"><i class="bi bi-check-circle me-1"></i>Paid</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- PayU Payment Form (hidden) -->
<?php if (!empty($payu_key)): ?>
<form id="payuForm" action="<?= $payu_base ?>" method="POST" class="d-none">
  <input type="hidden" name="key"          id="payu_key"    value="<?= sanitize($payu_key) ?>">
  <input type="hidden" name="txnid"        id="payu_txnid">
  <input type="hidden" name="amount"       id="payu_amount">
  <input type="hidden" name="productinfo"  id="payu_product">
  <input type="hidden" name="firstname"    value="<?= sanitize($student['name']) ?>">
  <input type="hidden" name="email"        value="<?= sanitize($student['email'] ?? '') ?>">
  <input type="hidden" name="phone"        value="<?= sanitize($student['phone'] ?? '0000000000') ?>">
  <input type="hidden" name="surl"         value="<?= get_setting('payu_surl', SITE_URL.'/payment/success.php') ?>">
  <input type="hidden" name="furl"         value="<?= get_setting('payu_furl', SITE_URL.'/payment/failure.php') ?>">
  <input type="hidden" name="hash"         id="payu_hash">
  <input type="hidden" name="udf1"         id="payu_fee_id">
</form>

<script>
function payNow(feeId, invoiceNo, amount) {
  // Generate hash via AJAX
  fetch('<?= SITE_URL ?>/payment/payu.php?action=hash', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `fee_id=${feeId}&amount=${amount}&invoice=${invoiceNo}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.hash) {
      document.getElementById('payu_txnid').value   = data.txnid;
      document.getElementById('payu_amount').value  = amount;
      document.getElementById('payu_product').value = 'School Fee: ' + invoiceNo;
      document.getElementById('payu_hash').value    = data.hash;
      document.getElementById('payu_fee_id').value  = feeId;
      document.getElementById('payuForm').submit();
    } else {
      alert('Payment initialization failed. Please try again.');
    }
  })
  .catch(() => alert('Network error. Please try again.'));
}
</script>
<?php endif; ?>

<!-- Transaction History -->
<div class="card border-0 shadow-sm mt-3">
  <div class="card-header bg-white border-0 fw-semibold"><i class="bi bi-clock-history me-2 text-info"></i>Transaction History</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <?php
      $txns = $pdo->prepare(
          "SELECT t.*, f.invoice_no, f.fee_type FROM transactions t
           JOIN fees f ON t.fee_id=f.id WHERE t.student_id=?
           ORDER BY t.payment_date DESC LIMIT 20"
      );
      $txns->execute([$student['id']]);
      $txns = $txns->fetchAll();
      ?>
      <table class="table table-sm table-hover mb-0 small">
        <thead class="table-light">
          <tr><th>Date</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Txn ID</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($txns)): ?>
          <tr><td colspan="6" class="text-center py-3 text-muted">No transactions yet.</td></tr>
          <?php else: foreach ($txns as $t): ?>
          <tr>
            <td><?= date('d M Y', strtotime($t['payment_date'])) ?></td>
            <td><code><?= sanitize($t['invoice_no']) ?></code></td>
            <td><?= currency_format((float)$t['amount']) ?></td>
            <td><?= sanitize($t['payment_method'] ?? 'PayU') ?></td>
            <td class="text-muted"><?= sanitize($t['txn_id'] ?? '-') ?></td>
            <td><span class="badge bg-<?= $t['status']==='success'?'success':($t['status']==='failed'?'danger':'warning') ?>"><?= ucfirst($t['status']) ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
