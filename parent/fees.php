<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('parent');

$parent = get_parent_by_user_id((int)$_SESSION['user_id']);
if (!$parent) redirect(SITE_URL . '/auth/login.php');

// All children of this parent
$children_stmt = $pdo->prepare("SELECT id,name,student_id FROM students WHERE parent_id=? AND status='active'");
$children_stmt->execute([$parent['id']]);
$children = $children_stmt->fetchAll();

$child_ids = array_column($children,'id');

$fees = [];
if (!empty($child_ids)) {
    $in   = implode(',',array_fill(0,count($child_ids),'?'));
    $stmt = $pdo->prepare(
        "SELECT f.*, s.name as student_name, s.student_id as stu_id
         FROM fees f JOIN students s ON f.student_id=s.id
         WHERE f.student_id IN ($in) ORDER BY f.created_at DESC"
    );
    $stmt->execute($child_ids);
    $fees = $stmt->fetchAll();
}

$total   = array_sum(array_column($fees,'amount'));
$paid    = array_sum(array_column(array_filter($fees,fn($f)=>$f['status']==='paid'),'amount'));
$pending = $total - $paid;

$page_title = 'Fee Status';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/parent/'],['label'=>'Fee Status','active'=>true]];
include INCLUDES_PATH . 'header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-4"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-4 fw-bold"><?= currency_format($total) ?></div><div class="small text-muted">Total</div></div></div>
  <div class="col-4"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-4 fw-bold text-success"><?= currency_format($paid) ?></div><div class="small text-muted">Paid</div></div></div>
  <div class="col-4"><div class="card border-0 shadow-sm text-center py-3"><div class="fs-4 fw-bold text-danger"><?= currency_format($pending) ?></div><div class="small text-muted">Pending</div></div></div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 fw-semibold">
    <i class="bi bi-receipt me-2 text-success"></i>Fee Invoices
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Invoice</th><th>Student</th><th>Type</th><th>Amount</th><th>Due Date</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($fees)): ?>
          <tr><td colspan="6" class="text-center py-5 text-muted">No fee records found.</td></tr>
          <?php else: foreach ($fees as $f): ?>
          <tr>
            <td><code class="text-success"><?= sanitize($f['invoice_no']) ?></code></td>
            <td>
              <div class="fw-semibold"><?= sanitize($f['student_name']) ?></div>
              <code class="text-muted small"><?= sanitize($f['stu_id']) ?></code>
            </td>
            <td><?= sanitize($f['fee_type']) ?></td>
            <td class="fw-semibold"><?= currency_format((float)$f['amount']) ?></td>
            <td class="small <?= $f['due_date']&&$f['due_date']<date('Y-m-d')&&$f['status']==='pending'?'text-danger fw-bold':'text-muted' ?>">
              <?= format_date($f['due_date']) ?>
            </td>
            <td>
              <span class="badge bg-<?= match($f['status']){'paid'=>'success','overdue'=>'danger','partial'=>'info',default=>'warning'} ?>">
                <?= ucfirst($f['status']) ?>
              </span>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="alert alert-info mt-3 small">
  <i class="bi bi-info-circle me-1"></i>
  To make a payment, please log in to your child's student portal or contact the school office.
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
