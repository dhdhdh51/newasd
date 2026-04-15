<?php
require_once dirname(__DIR__) . '/config/config.php';

// If already logged in, redirect to panel
if (is_logged_in()) {
    $r = get_user_role();
    redirect(SITE_URL . '/' . $r . '/');
}

$error   = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    $email    = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = sanitize($_POST['role'] ?? '');

    if (empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND role=? AND status='active' LIMIT 1");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);

            $_SESSION['user_id']        = $user['id'];
            $_SESSION['user_name']      = $user['name'];
            $_SESSION['user_email']     = $user['email'];
            $_SESSION['user_role']      = $user['role'];
            $_SESSION['last_activity']  = time();

            // Update last login
            $pdo->prepare("UPDATE users SET last_login=NOW() WHERE id=?")
                ->execute([$user['id']]);

            redirect(SITE_URL . '/' . $user['role'] . '/');
        } else {
            $error = 'Invalid email, password, or role.';
        }
    }
}

$site_name = get_setting('site_name', 'School ERP');
$site_logo = get_setting('site_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?= sanitize($site_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
</head>
<body class="auth-body">

<div class="container-fluid vh-100 d-flex">
  <!-- Left panel - decorative (hidden on mobile) -->
  <div class="d-none d-lg-flex col-lg-6 auth-left align-items-center justify-content-center">
    <div class="text-center text-white px-4">
      <i class="bi bi-mortarboard-fill display-1 mb-3"></i>
      <h2 class="fw-bold"><?= sanitize($site_name) ?></h2>
      <p class="lead">Complete School Management System</p>
      <div class="row g-3 mt-4">
        <?php foreach ([
          ['icon'=>'people-fill','label'=>'Student Portal'],
          ['icon'=>'person-badge','label'=>'Teacher Portal'],
          ['icon'=>'people','label'=>'Parent Portal'],
          ['icon'=>'shield-lock','label'=>'Secure & Fast'],
        ] as $f): ?>
        <div class="col-6">
          <div class="auth-feature-card">
            <i class="bi bi-<?= $f['icon'] ?> fs-4 mb-1"></i>
            <div class="small"><?= $f['label'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Right panel - login form -->
  <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center bg-white">
    <div class="auth-form-wrap w-100 px-4 py-5">

      <!-- Logo + Name -->
      <div class="text-center mb-4">
        <?php if ($site_logo): ?>
          <img src="<?= get_upload_url($site_logo) ?>" alt="Logo" height="60" class="mb-2">
        <?php else: ?>
          <i class="bi bi-mortarboard-fill text-primary display-4"></i>
        <?php endif; ?>
        <h4 class="fw-bold mt-2"><?= sanitize($site_name) ?></h4>
        <p class="text-muted small">Sign in to your portal</p>
      </div>

      <?php if ($timeout): ?>
        <div class="alert alert-warning"><i class="bi bi-clock me-2"></i>Session expired. Please login again.</div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= sanitize($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <?= csrf_field() ?>

        <!-- Role selector -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Login As</label>
          <div class="d-flex gap-2 flex-wrap">
            <?php foreach (['admin','student','teacher','parent'] as $r):
              $selected_role = $_POST['role'] ?? 'student';
            ?>
            <div class="form-check role-card">
              <input class="form-check-input visually-hidden" type="radio" name="role"
                     id="role_<?= $r ?>" value="<?= $r ?>"
                     <?= ($selected_role === $r) ? 'checked' : '' ?>>
              <label class="form-check-label role-label" for="role_<?= $r ?>">
                <i class="bi bi-<?= match($r){
                  'admin'=>'shield-lock',
                  'student'=>'person-circle',
                  'teacher'=>'person-badge',
                  'parent'=>'people'
                } ?>"></i>
                <span><?= ucfirst($r) ?></span>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold" for="email">Email Address</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= sanitize($_POST['email'] ?? '') ?>"
                   placeholder="Enter your email" required autofocus>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold" for="password">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Enter your password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePwd">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember">
            <label class="form-check-label small" for="remember">Remember me</label>
          </div>
          <a href="<?= SITE_URL ?>/auth/forgot-password.php" class="small text-primary text-decoration-none">
            Forgot Password?
          </a>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
      </form>

      <div class="text-center mt-4">
        <a href="<?= SITE_URL ?>/public/admission.php" class="text-primary text-decoration-none small">
          <i class="bi bi-file-earmark-person me-1"></i>Apply for Admission
        </a>
        &nbsp;|&nbsp;
        <a href="<?= SITE_URL ?>/public/admission-status.php" class="text-muted text-decoration-none small">
          Track Application
        </a>
      </div>

      <p class="text-center text-muted small mt-4">
        <?= sanitize(get_setting('footer_text', '© ' . date('Y') . ' ' . $site_name)) ?>
      </p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
<script>
// Password toggle
document.getElementById('togglePwd')?.addEventListener('click', function() {
  const pwd = document.getElementById('password');
  const icon = this.querySelector('i');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    pwd.type = 'password';
    icon.className = 'bi bi-eye';
  }
});

// Role card active state
document.querySelectorAll('.role-label').forEach(label => {
  label.addEventListener('click', function() {
    document.querySelectorAll('.role-label').forEach(l => l.classList.remove('active'));
    this.classList.add('active');
  });
  const input = document.getElementById(label.htmlFor);
  if (input?.checked) label.classList.add('active');
});
</script>
</body>
</html>
