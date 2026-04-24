<?php
require_once dirname(__DIR__) . '/config/config.php';

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
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['last_activity'] = time();
            $pdo->prepare("UPDATE users SET last_login=NOW() WHERE id=?")->execute([$user['id']]);
            redirect(SITE_URL . '/' . $user['role'] . '/');
        } else {
            $error = 'Invalid credentials or account inactive.';
        }
    }
}

$site_name = get_setting('site_name', 'School ERP');
$site_logo = get_setting('site_logo', '');
$selected_role = $_POST['role'] ?? 'student';

$roles = [
    'admin'   => ['icon' => 'fa-shield-halved',  'bi' => 'shield-lock',   'label' => 'Admin',   'color' => '#6366f1'],
    'student' => ['icon' => 'fa-user-graduate',  'bi' => 'person-circle', 'label' => 'Student', 'color' => '#10b981'],
    'teacher' => ['icon' => 'fa-chalkboard-user','bi' => 'person-badge',  'label' => 'Teacher', 'color' => '#f59e0b'],
    'parent'  => ['icon' => 'fa-people-roof',    'bi' => 'people',        'label' => 'Parent',  'color' => '#06b6d4'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= sanitize($site_name) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
  <style>
    body { font-family:'Poppins',sans-serif; }

    /* ── Left Panel ─────────────────────────────── */
    .login-left {
      background: linear-gradient(145deg,#1e1b4b 0%,#312e81 45%,#4c1d95 100%);
      min-height:100vh; position:relative; overflow:hidden;
      display:flex; align-items:center; justify-content:center;
    }
    .login-left::after {
      content:'';position:absolute;inset:0;
      background: radial-gradient(ellipse 80% 60% at 40% 30%,rgba(99,102,241,.4),transparent),
                  radial-gradient(ellipse 60% 70% at 80% 80%,rgba(139,92,246,.3),transparent);
      pointer-events:none;
    }
    .orb {
      position:absolute; border-radius:50%; filter:blur(70px);
      pointer-events:none; animation:floatOrb 9s ease-in-out infinite alternate;
    }
    .orb-a { width:300px;height:300px;background:rgba(99,102,241,.35);top:-80px;left:-80px; }
    .orb-b { width:220px;height:220px;background:rgba(167,139,250,.3);bottom:-60px;right:-40px;animation-delay:3s; }
    .orb-c { width:150px;height:150px;background:rgba(6,182,212,.25);top:45%;left:55%;animation-delay:6s; }
    @keyframes floatOrb {
      from{transform:translateY(0) scale(1);}
      to{transform:translateY(-36px) scale(1.1);}
    }

    .brand-circle {
      width:90px;height:90px;border-radius:50%;
      background:rgba(255,255,255,.15);
      backdrop-filter:blur(16px);
      border:2px solid rgba(255,255,255,.25);
      display:flex;align-items:center;justify-content:center;
      font-size:2.4rem; margin:0 auto 20px;
      box-shadow:0 8px 32px rgba(0,0,0,.2);
    }

    .feature-pill {
      display:inline-flex;align-items:center;gap:8px;
      background:rgba(255,255,255,.1);
      backdrop-filter:blur(12px);
      border:1px solid rgba(255,255,255,.18);
      border-radius:40px;padding:10px 18px;
      color:#fff;font-size:.82rem;font-weight:500;
      transition:all .28s ease;
    }
    .feature-pill:hover{background:rgba(255,255,255,.18);transform:translateY(-2px);}

    /* ── Right Panel ─────────────────────────────── */
    .login-right {
      min-height:100vh;
      background:linear-gradient(160deg,#f8faff 0%,#ffffff 55%,#f5f3ff 100%);
      display:flex;align-items:center;justify-content:center;
      position:relative;
    }
    .login-right::before {
      content:'';position:absolute;inset:0;
      background:radial-gradient(ellipse 70% 50% at 85% 15%,rgba(99,102,241,.06),transparent);
      pointer-events:none;
    }
    .form-box {
      width:100%;max-width:440px;padding:0 32px;position:relative;z-index:1;
    }

    /* role cards */
    .role-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
    .role-card-label {
      display:flex;flex-direction:column;align-items:center;gap:6px;
      padding:13px 8px;
      border:2px solid rgba(99,102,241,.15);
      border-radius:12px;cursor:pointer;
      font-size:.75rem;font-weight:600;color:#6b7280;
      background:rgba(255,255,255,.8);backdrop-filter:blur(6px);
      transition:all .25s ease; text-align:center;
    }
    .role-card-label i { font-size:1.6rem; }
    .role-card-label:hover {
      border-color:var(--primary-color);
      color:var(--primary-color);
      background:rgba(99,102,241,.07);
      transform:translateY(-3px);
      box-shadow:0 6px 18px rgba(99,102,241,.15);
    }
    .role-card-label.active {
      border-color:var(--primary-color);
      background:rgba(99,102,241,.12);
      color:var(--primary-color);
      box-shadow:0 6px 18px rgba(99,102,241,.22);
    }

    /* premium input */
    .prem-input {
      border:1.5px solid rgba(99,102,241,.2);border-radius:10px;
      padding:11px 14px;font-family:'Poppins',sans-serif;font-size:.875rem;
      background:rgba(255,255,255,.8);backdrop-filter:blur(6px);
      transition:all .25s ease;
    }
    .prem-input:focus {
      border-color:#6366f1;
      box-shadow:0 0 0 3px rgba(99,102,241,.15);
      background:#fff;outline:none;
    }
    .prem-input-group { position:relative; }
    .prem-input-group .prem-icon {
      position:absolute;left:14px;top:50%;transform:translateY(-50%);
      color:#6366f1;font-size:.95rem;pointer-events:none;
    }
    .prem-input-group .prem-input { padding-left:40px; }
    .prem-input-group .prem-toggle {
      position:absolute;right:12px;top:50%;transform:translateY(-50%);
      background:none;border:none;color:#9ca3af;cursor:pointer;font-size:.95rem;
      padding:4px;border-radius:6px;transition:color .2s;
    }
    .prem-input-group .prem-toggle:hover { color:#6366f1; }

    /* sign-in btn */
    .btn-signin {
      background:linear-gradient(135deg,#6366f1,#8b5cf6);
      border:none;border-radius:12px;
      padding:13px;font-weight:700;font-size:.95rem;
      color:#fff;width:100%;letter-spacing:.3px;
      box-shadow:0 6px 20px rgba(99,102,241,.45);
      transition:all .28s ease;
    }
    .btn-signin:hover {
      background:linear-gradient(135deg,#4f46e5,#7c3aed);
      box-shadow:0 8px 28px rgba(99,102,241,.6);
      transform:translateY(-2px);color:#fff;
    }
    .btn-signin:active { transform:translateY(0); }

    :root { --primary-color:#6366f1; }
  </style>
</head>
<body>
<div class="container-fluid p-0">
  <div class="row g-0 min-vh-100">

    <!-- ══ LEFT PANEL ══════════════════════════════════════════ -->
    <div class="col-12 col-lg-6 login-left d-none d-lg-flex">
      <!-- orbs -->
      <div class="orb orb-a"></div>
      <div class="orb orb-b"></div>
      <div class="orb orb-c"></div>

      <div class="text-center text-white px-5 position-relative" style="z-index:1">
        <!-- Logo -->
        <div class="brand-circle mb-3">
          <?php if ($site_logo): ?>
            <img src="<?= get_upload_url($site_logo) ?>" alt="Logo" style="width:58px;height:58px;object-fit:cover;border-radius:50%">
          <?php else: ?>
            <i class="fa-solid fa-graduation-cap"></i>
          <?php endif; ?>
        </div>

        <h2 class="fw-800 mb-1" style="font-size:1.8rem;font-weight:800"><?= sanitize($site_name) ?></h2>
        <p class="mb-5" style="opacity:.7;font-size:.9rem">Complete School Management System</p>

        <!-- Feature pills -->
        <div class="d-flex flex-column gap-3 align-items-start mx-auto" style="max-width:300px">
          <?php
          $features = [
            ['fa-users',            'Manage Students, Teachers & Parents'],
            ['fa-chart-pie',        'Real-time Analytics & Reports'],
            ['fa-file-invoice',     'Fee Management & PayU Payments'],
            ['fa-bell',             'Smart Notifications System'],
            ['fa-shield-halved',    'Secure Role-Based Access'],
          ];
          foreach ($features as [$ic,$lbl]):
          ?>
          <div class="feature-pill">
            <i class="fa-solid <?= $ic ?>" style="color:#a5b4fc;font-size:1rem"></i>
            <span><?= $lbl ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <p class="mt-5 small" style="opacity:.4"><?= sanitize(get_setting('footer_text','© '.date('Y').' '.$site_name)) ?></p>
      </div>
    </div>

    <!-- ══ RIGHT PANEL ═════════════════════════════════════════ -->
    <div class="col-12 col-lg-6 login-right">
      <div class="form-box py-5">

        <!-- Mobile logo -->
        <div class="text-center mb-4 d-lg-none">
          <?php if ($site_logo): ?>
            <img src="<?= get_upload_url($site_logo) ?>" alt="Logo" height="56" class="rounded-circle mb-2">
          <?php else: ?>
            <i class="fa-solid fa-graduation-cap text-primary" style="font-size:3rem"></i>
          <?php endif; ?>
          <h4 class="fw-bold mt-2 mb-0"><?= sanitize($site_name) ?></h4>
        </div>

        <!-- Heading -->
        <div class="mb-4">
          <h3 class="fw-800 mb-1" style="font-weight:800;color:#1e1b4b">Welcome back 👋</h3>
          <p class="text-muted small mb-0">Sign in to access your portal</p>
        </div>

        <!-- Alerts -->
        <?php if ($timeout): ?>
        <div class="alert alert-warning d-flex gap-2 align-items-center mb-4 py-2">
          <i class="bi bi-clock-history"></i>
          <span>Session expired. Please sign in again.</span>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger d-flex gap-2 align-items-center mb-4 py-2">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span><?= sanitize($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <?= csrf_field() ?>

          <!-- Role selector -->
          <div class="mb-4">
            <label class="form-label">Sign in as</label>
            <div class="role-grid">
              <?php foreach ($roles as $rkey => $rdata): ?>
              <div>
                <input class="visually-hidden" type="radio" name="role"
                       id="role_<?= $rkey ?>" value="<?= $rkey ?>"
                       <?= $selected_role === $rkey ? 'checked' : '' ?>>
                <label class="role-card-label <?= $selected_role === $rkey ? 'active' : '' ?>"
                       for="role_<?= $rkey ?>">
                  <i class="fa-solid <?= $rdata['icon'] ?>" style="color:<?= $rdata['color'] ?>"></i>
                  <span><?= $rdata['label'] ?></span>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <div class="prem-input-group">
              <i class="bi bi-envelope prem-icon"></i>
              <input type="email" id="email" name="email" class="form-control prem-input"
                     value="<?= sanitize($_POST['email'] ?? '') ?>"
                     placeholder="your@email.com" required autofocus>
            </div>
          </div>

          <!-- Password -->
          <div class="mb-4">
            <div class="d-flex justify-content-between">
              <label class="form-label" for="password">Password</label>
              <a href="<?= SITE_URL ?>/auth/forgot-password.php"
                 class="small text-decoration-none" style="color:#6366f1;font-weight:600">Forgot password?</a>
            </div>
            <div class="prem-input-group">
              <i class="bi bi-lock prem-icon"></i>
              <input type="password" id="password" name="password" class="form-control prem-input"
                     placeholder="••••••••" required>
              <button class="prem-toggle" type="button" id="togglePwd" tabindex="-1">
                <i class="bi bi-eye" id="toggleIcon"></i>
              </button>
            </div>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-signin">
            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Sign In
          </button>
        </form>

        <!-- Quick links -->
        <div class="mt-4 pt-3 border-top d-flex justify-content-center gap-4 flex-wrap">
          <a href="<?= SITE_URL ?>/public/admission.php"
             class="small text-decoration-none d-flex align-items-center gap-1" style="color:#6366f1;font-weight:600">
            <i class="fa-solid fa-file-pen"></i> Apply for Admission
          </a>
          <a href="<?= SITE_URL ?>/public/admission-status.php"
             class="small text-decoration-none d-flex align-items-center gap-1 text-muted">
            <i class="fa-solid fa-magnifying-glass"></i> Track Application
          </a>
          <a href="<?= SITE_URL ?>/"
             class="small text-decoration-none d-flex align-items-center gap-1 text-muted">
            <i class="fa-solid fa-house"></i> Home
          </a>
        </div>

        <p class="text-center text-muted mt-4 mb-0" style="font-size:.75rem">
          <?= sanitize(get_setting('footer_text','© '.date('Y').' '.$site_name)) ?>
        </p>
      </div>
    </div>

  </div><!-- /row -->
</div><!-- /container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password toggle
document.getElementById('togglePwd')?.addEventListener('click', function() {
  const pwd  = document.getElementById('password');
  const icon = document.getElementById('toggleIcon');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    pwd.type = 'password';
    icon.className = 'bi bi-eye';
  }
});

// Role card active state sync
document.querySelectorAll('input[name="role"]').forEach(input => {
  input.addEventListener('change', function() {
    document.querySelectorAll('.role-card-label').forEach(l => l.classList.remove('active'));
    this.closest('div').querySelector('.role-card-label').classList.add('active');
  });
});
</script>
</body>
</html>
