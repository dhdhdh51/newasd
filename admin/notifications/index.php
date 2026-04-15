<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');

$page_title = 'Notifications';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Notifications','active'=>true]];

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $title    = sanitize($_POST['title']   ?? '');
    $message  = sanitize($_POST['message'] ?? '');
    $target   = sanitize($_POST['target']  ?? 'all');
    $mode     = sanitize($_POST['mode']    ?? 'dashboard');

    if (empty($title) || empty($message)) {
        $error = 'Title and message are required.';
    } else {
        // Create DB notification
        if ($mode === 'dashboard' || $mode === 'both') {
            notify_role($target, $title, $message, 'info');
        }

        // Send emails
        if ($mode === 'email' || $mode === 'both') {
            $roles = $target === 'all'
                ? ['student','teacher','parent']
                : [$target];

            $email_count = 0;
            foreach ($roles as $role) {
                $stmt = $pdo->prepare("SELECT u.name, u.email FROM users u WHERE u.role=? AND u.status='active' AND u.email IS NOT NULL");
                $stmt->execute([$role]);
                foreach ($stmt->fetchAll() as $user) {
                    if (SchoolMailer::sendCustomNotification($user['email'], $user['name'], $title, $message)) {
                        $email_count++;
                    }
                }
            }
            $success = "Notification sent! {$email_count} email(s) dispatched.";
        } else {
            $success = 'Notification sent to dashboard.';
        }
    }
}

// Recent notifications
$notifications = $pdo->query(
    "SELECT n.*, u.name as user_name FROM notifications n
     LEFT JOIN users u ON n.user_id=u.id
     ORDER BY n.created_at DESC LIMIT 50"
)->fetchAll();

include INCLUDES_PATH . 'header.php';
?>

<div class="row g-3">
  <!-- Send notification form -->
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-bell me-2 text-primary"></i>Send Notification
      </div>
      <div class="card-body">
        <?php if ($success): ?><div class="alert alert-success small"><?= sanitize($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-danger small"><?= sanitize($error) ?></div><?php endif; ?>

        <form method="POST">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Target Audience</label>
            <div class="d-flex gap-3 flex-wrap">
              <?php foreach (['all'=>'All Users','student'=>'Students','teacher'=>'Teachers','parent'=>'Parents'] as $val => $lbl): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="target" id="t_<?= $val ?>" value="<?= $val ?>"
                       <?= $val === 'all' ? 'checked' : '' ?>>
                <label class="form-check-label" for="t_<?= $val ?>"><?= $lbl ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Delivery Mode</label>
            <div class="d-flex gap-3 flex-wrap">
              <?php foreach (['dashboard'=>'Dashboard Only','email'=>'Email Only','both'=>'Both'] as $val => $lbl): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="mode" id="m_<?= $val ?>" value="<?= $val ?>"
                       <?= $val === 'dashboard' ? 'checked' : '' ?>>
                <label class="form-check-label" for="m_<?= $val ?>"><?= $lbl ?></label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Title *</label>
            <input type="text" name="title" class="form-control"
                   placeholder="Notification title" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Message *</label>
            <textarea name="message" class="form-control" rows="4"
                      placeholder="Your message here..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-send me-2"></i>Send Notification
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Notifications list -->
  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold d-flex justify-content-between">
        <span><i class="bi bi-list-ul me-2 text-primary"></i>Recent Notifications</span>
        <a href="<?= SITE_URL ?>/admin/notifications/clear.php" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Clear all?')">Clear All</a>
      </div>
      <div class="card-body p-0" style="max-height:600px;overflow-y:auto">
        <?php if (empty($notifications)): ?>
        <div class="text-center py-5 text-muted">
          <i class="bi bi-bell-slash display-6 d-block mb-2"></i>No notifications yet.
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($notifications as $n): ?>
          <div class="list-group-item px-3 py-2 <?= !$n['is_read'] ? 'bg-light' : '' ?>">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold small"><?= sanitize($n['title']) ?></div>
                <div class="text-muted" style="font-size:.8rem"><?= sanitize($n['message']) ?></div>
                <div class="text-muted mt-1" style="font-size:.7rem">
                  <span class="badge bg-<?= match($n['role']){
                    'student'=>'primary','teacher'=>'success','parent'=>'info',default=>'secondary'
                  } ?> me-1"><?= ucfirst($n['role']??'all') ?></span>
                  <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
                </div>
              </div>
              <?php if (!$n['is_read']): ?>
              <span class="badge bg-primary rounded-pill">New</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include INCLUDES_PATH . 'footer.php'; ?>
