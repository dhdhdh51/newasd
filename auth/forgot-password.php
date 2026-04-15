<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'mailer.php';

if (is_logged_in()) redirect(SITE_URL . '/' . get_user_role() . '/');

$step    = $_SESSION['otp_step'] ?? 1;
$message = '';
$error   = '';
$site_name = get_setting('site_name', 'School ERP');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    if ($step === 1) {
        // Step 1: Verify email
        $email = sanitize_email($_POST['email'] ?? '');
        if (!validate_email($email)) {
            $error = 'Enter a valid email address.';
        } else {
            $stmt = $pdo->prepare("SELECT id,name FROM users WHERE email=? AND status='active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $otp = generate_otp();
                save_otp($email, $otp);
                $sent = SchoolMailer::sendOTP($email, $user['name'], $otp);

                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_step']  = 2;
                $step = 2;
                $message = $sent
                    ? 'OTP sent to your email. Valid for ' . OTP_EXPIRY_MINUTES . ' minutes.'
                    : 'OTP generated (email service may be unconfigured): ' . $otp;
            } else {
                $error = 'Email not found in our records.';
            }
        }

    } elseif ($step === 2) {
        // Step 2: Verify OTP
        $otp   = trim($_POST['otp'] ?? '');
        $email = $_SESSION['otp_email'] ?? '';

        if (empty($otp)) {
            $error = 'Enter the OTP.';
        } elseif (verify_otp($email, $otp)) {
            $_SESSION['otp_step']     = 3;
            $_SESSION['otp_verified'] = true;
            $step = 3;
            $message = 'OTP verified! Set your new password.';
        } else {
            $error = 'Invalid or expired OTP.';
        }

    } elseif ($step === 3) {
        // Step 3: Reset password
        if (!isset($_SESSION['otp_verified'])) {
            $step = 1; $error = 'Session expired. Start again.';
        } else {
            $email    = $_SESSION['otp_email'] ?? '';
            $password = $_POST['password']         ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password=? WHERE email=?")
                    ->execute([$hash, $email]);

                // Cleanup session
                unset($_SESSION['otp_step'], $_SESSION['otp_email'], $_SESSION['otp_verified']);
                set_flash('success', 'Password reset successful! Please login.');
                redirect(SITE_URL . '/auth/login.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password - <?= sanitize($site_name) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
</head>
<body class="auth-body bg-light">

<div class="container">
  <div class="row justify-content-center min-vh-100 align-items-center">
    <div class="col-12 col-sm-10 col-md-6 col-lg-5 col-xl-4">
      <div class="card shadow border-0">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <i class="bi bi-shield-lock text-primary display-5"></i>
            <h4 class="fw-bold mt-2">Forgot Password</h4>
            <p class="text-muted small"><?= sanitize($site_name) ?></p>
          </div>

          <!-- Progress steps -->
          <div class="d-flex justify-content-center mb-4 gap-3">
            <?php foreach ([1=>'Email',2=>'OTP',3=>'Reset'] as $s => $label): ?>
            <div class="text-center">
              <div class="step-circle <?= $step >= $s ? 'active' : '' ?>"><?= $s ?></div>
              <div class="small mt-1 text-muted"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
          </div>

          <?php if ($error):   ?><div class="alert alert-danger small"><i class="bi bi-exclamation-triangle me-1"></i><?= sanitize($error) ?></div><?php endif; ?>
          <?php if ($message): ?><div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i><?= sanitize($message) ?></div><?php endif; ?>

          <form method="POST" action="">
            <?= csrf_field() ?>

            <?php if ($step === 1): ?>
              <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                  <input type="email" name="email" class="form-control"
                         placeholder="Enter registered email" required autofocus
                         value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-2"></i>Send OTP
              </button>

            <?php elseif ($step === 2): ?>
              <p class="text-muted small mb-3">OTP sent to: <strong><?= sanitize($_SESSION['otp_email'] ?? '') ?></strong></p>
              <div class="mb-3">
                <label class="form-label fw-semibold">Enter OTP</label>
                <input type="text" name="otp" class="form-control text-center fw-bold fs-4 letter-spacing-4"
                       maxlength="6" placeholder="000000" required autofocus
                       inputmode="numeric" pattern="\d{6}">
                <div class="form-text">OTP expires in <?= OTP_EXPIRY_MINUTES ?> minutes.</div>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-circle me-2"></i>Verify OTP
              </button>
              <div class="text-center mt-2">
                <a href="<?= SITE_URL ?>/auth/forgot-password.php?restart=1" class="text-muted small">Resend OTP</a>
              </div>

            <?php elseif ($step === 3): ?>
              <div class="mb-3">
                <label class="form-label fw-semibold">New Password</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-lock"></i></span>
                  <input type="password" name="password" class="form-control" id="pwd"
                         placeholder="Min 8 characters" required minlength="8">
                  <button class="btn btn-outline-secondary" type="button" id="togglePwd1">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Confirm Password</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                  <input type="password" name="confirm_password" class="form-control" id="cpwd"
                         placeholder="Repeat password" required>
                </div>
              </div>
              <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-check-lg me-2"></i>Reset Password
              </button>
            <?php endif; ?>
          </form>

          <div class="text-center mt-3">
            <a href="<?= SITE_URL ?>/auth/login.php" class="text-muted small">
              <i class="bi bi-arrow-left me-1"></i>Back to Login
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// OTP auto-focus next input
document.querySelector('[name="otp"]')?.addEventListener('input', function() {
  if (this.value.length === 6) this.form.submit();
});
// Password toggle
document.getElementById('togglePwd1')?.addEventListener('click', function() {
  const pwd = document.getElementById('pwd');
  const icon = this.querySelector('i');
  pwd.type   = pwd.type === 'password' ? 'text' : 'password';
  icon.className = pwd.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});
<?php if (isset($_GET['restart'])): ?>
// Restart
<?php session_unset(); session_destroy(); redirect(SITE_URL . '/auth/forgot-password.php'); ?>
<?php endif; ?>
</script>
</body>
</html>
