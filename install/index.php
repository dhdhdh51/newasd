<?php
/**
 * School ERP — Web Installer
 * Multi-step setup wizard (no framework, pure PHP 8.1+)
 */
session_start();

define('INSTALLER_VERSION', '1.0.0');
define('ROOT', dirname(__DIR__) . '/');
define('CONFIG_FILE', ROOT . 'config/config.php');
define('DB_SQL_FILE', ROOT . 'database.sql');
define('INSTALL_LOCK', ROOT . 'install/.installed');

/* ── If already installed, block access ─────────────────── */
if (file_exists(INSTALL_LOCK)) {
    http_response_code(403);
    die('<!DOCTYPE html><html><head><title>403</title>
    <style>body{font-family:Poppins,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f5f3ff;margin:0}
    .box{text-align:center;padding:40px;background:#fff;border-radius:20px;box-shadow:0 8px 32px rgba(99,102,241,.15);max-width:420px}
    h2{color:#6366f1;font-size:1.5rem}p{color:#666}a{color:#6366f1;font-weight:600}</style></head>
    <body><div class="box"><div style="font-size:3rem">🔒</div><h2>Already Installed</h2>
    <p>The application is already installed.<br>Delete <code>install/.installed</code> to re-run.</p>
    <a href="/">← Go to Application</a></div></body></html>');
}

$step   = (int)($_GET['step'] ?? $_SESSION['install_step'] ?? 1);
$errors = [];

/* ── Step handlers ────────────────────────────────────────── */

/* Step 2 → validate & save DB config */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_config'])) {
    $_SESSION['db_host']    = trim($_POST['db_host']    ?? 'localhost');
    $_SESSION['db_user']    = trim($_POST['db_user']    ?? '');
    $_SESSION['db_pass']    = $_POST['db_pass']         ?? '';
    $_SESSION['db_name']    = trim($_POST['db_name']    ?? '');
    $_SESSION['site_url']   = rtrim(trim($_POST['site_url'] ?? ''), '/');

    // Test connection
    try {
        $testPdo = new PDO(
            "mysql:host={$_SESSION['db_host']};charset=utf8mb4",
            $_SESSION['db_user'],
            $_SESSION['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        $_SESSION['install_step'] = 3;
        header('Location: ?step=3'); exit;
    } catch (PDOException $e) {
        $errors[] = 'Database connection failed: ' . htmlspecialchars($e->getMessage());
        $step = 2;
    }
}

/* Step 3 → create DB & import schema */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_import'])) {
    $host = $_SESSION['db_host'] ?? 'localhost';
    $user = $_SESSION['db_user'] ?? '';
    $pass = $_SESSION['db_pass'] ?? '';
    $name = $_SESSION['db_name'] ?? '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        // Create DB if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$name}`");

        // Import SQL
        if (file_exists(DB_SQL_FILE)) {
            $sql = file_get_contents(DB_SQL_FILE);
            // Split on semicolons but preserve stored procedures etc.
            $statements = preg_split('/;\s*\n/', $sql);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt && stripos($stmt, '--') !== 0 && strlen($stmt) > 5) {
                    try { $pdo->exec($stmt); } catch (PDOException $e) { /* skip duplicate errors */ }
                }
            }
        }

        $_SESSION['install_step'] = 4;
        header('Location: ?step=4'); exit;
    } catch (PDOException $e) {
        $errors[] = 'Import failed: ' . htmlspecialchars($e->getMessage());
        $step = 3;
    }
}

/* Step 4 → create admin account */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    $admin_name  = trim($_POST['admin_name']  ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_pass  = $_POST['admin_pass']        ?? '';
    $admin_pass2 = $_POST['admin_pass2']       ?? '';

    if (!$admin_name)                    $errors[] = 'Admin name is required.';
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (strlen($admin_pass) < 8)         $errors[] = 'Password must be at least 8 characters.';
    if ($admin_pass !== $admin_pass2)    $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $host = $_SESSION['db_host'];
        $user = $_SESSION['db_user'];
        $pass = $_SESSION['db_pass'];
        $name = $_SESSION['db_name'];

        try {
            $pdo = new PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            // Delete old default admin if exists
            $pdo->exec("DELETE FROM users WHERE email='admin@school.com'");
            // Insert new admin
            $hash = password_hash($admin_pass, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (name,email,password,role,status) VALUES (?,?,?,'admin','active')")
                ->execute([$admin_name, $admin_email, $hash]);

            $_SESSION['admin_email'] = $admin_email;
            $_SESSION['install_step'] = 5;
            header('Location: ?step=5'); exit;
        } catch (PDOException $e) {
            $errors[] = 'Could not create admin: ' . htmlspecialchars($e->getMessage());
        }
        $step = 4;
    }
}

/* Step 5 → write config & lock */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish'])) {
    $site_url = $_SESSION['site_url'] ?? ('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

    $config_content = <<<PHP
<?php
/**
 * School ERP - Main Configuration
 * Auto-generated by installer on <?= date('Y-m-d H:i:s') ?>

 * PHP 8.1+
 */

error_reporting(0);
ini_set('display_errors', 0);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST',    '{$_SESSION['db_host']}');
define('DB_USER',    '{$_SESSION['db_user']}');
define('DB_PASS',    '{$_SESSION['db_pass']}');
define('DB_NAME',    '{$_SESSION['db_name']}');
define('DB_CHARSET', 'utf8mb4');

define('SITE_BASE_PATH', '');
define('SITE_URL',   '{$site_url}');

define('ROOT_PATH',     __DIR__ . '/../');
define('CONFIG_PATH',   __DIR__ . '/');
define('INCLUDES_PATH', __DIR__ . '/../includes/');
define('UPLOADS_PATH',  __DIR__ . '/../uploads/');
define('ASSETS_URL',    SITE_URL . '/assets');
define('UPLOADS_URL',   SITE_URL . '/uploads');

define('CSRF_TOKEN_LENGTH', 32);
define('OTP_EXPIRY_MINUTES', 10);
define('SESSION_TIMEOUT', 1800);

define('APP_NAME',      'School ERP System');
define('APP_VERSION',   '1.0.0');
define('ACADEMIC_YEAR', '2025-2026');
define('PASS_PERCENTAGE', 33);

define('MAX_FILE_SIZE',  5 * 1024 * 1024);
define('ALLOWED_IMAGES', ['jpg','jpeg','png','gif','webp']);
define('ALLOWED_DOCS',   ['pdf','doc','docx','jpg','jpeg','png']);

date_default_timezone_set('Asia/Kolkata');

require_once CONFIG_PATH . 'database.php';
require_once INCLUDES_PATH . 'functions.php';
require_once INCLUDES_PATH . 'notifications.php';

function get_setting(string \$key, string \$default = ''): string {
    global \$pdo;
    static \$cache = [];
    if (!isset(\$cache[\$key])) {
        try {
            \$stmt = \$pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
            \$stmt->execute([\$key]);
            \$row = \$stmt->fetch();
            \$cache[\$key] = \$row ? (string)\$row['setting_value'] : \$default;
        } catch (Exception \$e) { return \$default; }
    }
    return \$cache[\$key];
}
PHP;

    file_put_contents(CONFIG_FILE, $config_content);
    file_put_contents(INSTALL_LOCK, date('Y-m-d H:i:s') . ' — Installation complete.');

    // Update site_url in settings table
    try {
        $pdo2 = new PDO(
            "mysql:host={$_SESSION['db_host']};dbname={$_SESSION['db_name']};charset=utf8mb4",
            $_SESSION['db_user'], $_SESSION['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo2->prepare("UPDATE settings SET setting_value=? WHERE setting_key='site_url'")->execute([$site_url]);
    } catch (Exception $e) {}

    session_destroy();
    header('Location: ' . $site_url . '/auth/login.php?installed=1'); exit;
}

/* ── Server requirements check ───────────────────────────── */
function check_requirements(): array {
    $checks = [];
    $checks[] = ['PHP Version >= 8.1', PHP_VERSION_ID >= 80100, PHP_VERSION];
    $checks[] = ['PDO Extension',       extension_loaded('pdo'),         extension_loaded('pdo') ? 'Loaded' : 'Missing'];
    $checks[] = ['PDO MySQL',           extension_loaded('pdo_mysql'),   extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing'];
    $checks[] = ['MySQLi Extension',    extension_loaded('mysqli'),      extension_loaded('mysqli') ? 'Loaded' : 'Missing'];
    $checks[] = ['JSON Extension',      extension_loaded('json'),        extension_loaded('json') ? 'Loaded' : 'Missing'];
    $checks[] = ['OpenSSL Extension',   extension_loaded('openssl'),     extension_loaded('openssl') ? 'Loaded' : 'Missing'];
    $checks[] = ['Mbstring Extension',  extension_loaded('mbstring'),    extension_loaded('mbstring') ? 'Loaded' : 'Missing'];
    $checks[] = ['config/ writable',    is_writable(ROOT . 'config/'),   is_writable(ROOT . 'config/') ? 'Writable' : 'Not Writable'];
    $checks[] = ['uploads/ writable',   is_writable(ROOT . 'uploads/'),  is_writable(ROOT . 'uploads/') ? 'Writable' : 'Not Writable'];
    return $checks;
}

$req_checks = check_requirements();
$all_pass   = !in_array(false, array_column($req_checks, 1));

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>School ERP — Installer</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#f0f2ff 0%,#faf5ff 50%,#f0f9ff 100%);min-height:100vh;color:#1e1b4b}
    .install-wrap{max-width:720px;margin:0 auto;padding:40px 16px 60px}

    /* Header */
    .install-header{text-align:center;margin-bottom:40px}
    .logo-circle{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;margin:0 auto 16px;box-shadow:0 8px 24px rgba(99,102,241,.4)}
    .install-header h1{font-size:1.8rem;font-weight:800;color:#1e1b4b}
    .install-header p{color:#6b7280;font-size:.9rem}

    /* Step bar */
    .step-bar{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:36px;position:relative}
    .step-item{display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;z-index:1}
    .step-num{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;background:#e5e7eb;color:#9ca3af;transition:all .3s}
    .step-num.done{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;box-shadow:0 4px 12px rgba(99,102,241,.4)}
    .step-num.active{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;box-shadow:0 4px 16px rgba(99,102,241,.5);transform:scale(1.1)}
    .step-label{font-size:.68rem;font-weight:600;color:#9ca3af;text-align:center;max-width:70px}
    .step-label.active{color:#6366f1}
    .step-line{flex:1;height:2px;background:#e5e7eb;margin:0 8px;margin-bottom:22px}
    .step-line.done{background:linear-gradient(90deg,#6366f1,#8b5cf6)}

    /* Card */
    .install-card{background:rgba(255,255,255,.8);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.6);border-radius:20px;box-shadow:0 8px 32px rgba(99,102,241,.12);padding:36px;margin-bottom:24px}
    .card-title{font-size:1.2rem;font-weight:700;color:#1e1b4b;margin-bottom:6px}
    .card-subtitle{color:#6b7280;font-size:.85rem;margin-bottom:24px}

    /* Form controls */
    .prem-label{font-size:.8rem;font-weight:600;color:#374151;margin-bottom:6px;display:block}
    .prem-input{border:1.5px solid rgba(99,102,241,.2);border-radius:10px;padding:11px 14px;font-family:'Poppins',sans-serif;font-size:.875rem;background:rgba(255,255,255,.8);width:100%;outline:none;transition:border-color .25s,box-shadow .25s;color:#1e1b4b}
    .prem-input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15);background:#fff}
    .form-group{margin-bottom:18px}
    .form-hint{font-size:.74rem;color:#9ca3af;margin-top:4px}

    /* Buttons */
    .btn-prem{background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;border-radius:12px;padding:13px 28px;font-family:'Poppins',sans-serif;font-weight:700;font-size:.9rem;color:#fff;cursor:pointer;box-shadow:0 6px 20px rgba(99,102,241,.4);transition:all .25s;display:inline-flex;align-items:center;gap:8px}
    .btn-prem:hover{background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 8px 28px rgba(99,102,241,.55);transform:translateY(-2px)}
    .btn-outline{background:transparent;border:2px solid #6366f1;color:#6366f1;border-radius:12px;padding:11px 24px;font-family:'Poppins',sans-serif;font-weight:600;font-size:.9rem;cursor:pointer;transition:all .25s}
    .btn-outline:hover{background:#6366f1;color:#fff}

    /* Requirements table */
    .req-row{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-radius:10px;margin-bottom:8px;font-size:.875rem}
    .req-row.pass{background:rgba(16,185,129,.08);}
    .req-row.fail{background:rgba(239,68,68,.08);}
    .req-badge{font-size:.75rem;font-weight:600;padding:3px 10px;border-radius:20px}
    .req-badge.ok{background:rgba(16,185,129,.15);color:#059669}
    .req-badge.no{background:rgba(239,68,68,.15);color:#dc2626}

    /* Alerts */
    .alert-err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:14px 18px;color:#7f1d1d;font-size:.875rem;margin-bottom:20px}
    .alert-ok{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);border-radius:12px;padding:14px 18px;color:#065f46;font-size:.875rem;margin-bottom:20px}

    /* Success step */
    .success-circle{width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;font-size:2.8rem;color:#fff;margin:0 auto 24px;box-shadow:0 12px 32px rgba(16,185,129,.4);animation:popIn .5s ease}
    @keyframes popIn{from{transform:scale(0)}to{transform:scale(1)}}
  </style>
</head>
<body>
<div class="install-wrap">

  <!-- Header -->
  <div class="install-header">
    <div class="logo-circle"><i class="fa-solid fa-graduation-cap"></i></div>
    <h1>School ERP Installer</h1>
    <p>Version <?= INSTALLER_VERSION ?> &bull; PHP <?= PHP_VERSION ?></p>
  </div>

  <!-- Step Bar -->
  <?php
  $steps = ['Requirements', 'Database', 'Import', 'Admin', 'Complete'];
  ?>
  <div class="step-bar">
    <?php foreach ($steps as $i => $label):
      $n = $i + 1;
      $numClass   = $n < $step ? 'done' : ($n === $step ? 'active' : '');
      $labelClass = $n === $step ? 'active' : '';
    ?>
    <div class="step-item">
      <div class="step-num <?= $numClass ?>">
        <?php if ($n < $step): ?><i class="bi bi-check-lg"></i><?php else: ?><?= $n ?><?php endif; ?>
      </div>
      <div class="step-label <?= $labelClass ?>"><?= $label ?></div>
    </div>
    <?php if ($i < count($steps)-1): ?>
    <div class="step-line <?= $n < $step ? 'done' : '' ?>"></div>
    <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- Errors -->
  <?php if (!empty($errors)): ?>
  <div class="alert-err">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?= implode('<br>', $errors) ?>
  </div>
  <?php endif; ?>

  <!-- ══ STEP 1: Requirements ══════════════════════════════ -->
  <?php if ($step === 1): ?>
  <div class="install-card">
    <div class="card-title">Server Requirements</div>
    <div class="card-subtitle">Checking your server meets all requirements before installation.</div>

    <?php foreach ($req_checks as [$label, $ok, $val]): ?>
    <div class="req-row <?= $ok ? 'pass' : 'fail' ?>">
      <div><i class="bi bi-<?= $ok ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?> me-2"></i><?= $label ?></div>
      <span class="req-badge <?= $ok ? 'ok' : 'no' ?>"><?= htmlspecialchars((string)$val) ?></span>
    </div>
    <?php endforeach; ?>

    <?php if (!$all_pass): ?>
    <div class="alert-err mt-4">
      <i class="bi bi-exclamation-triangle me-2"></i>
      Some requirements are not met. Please fix them before continuing.
    </div>
    <?php endif; ?>

    <div class="text-end mt-4">
      <?php if ($all_pass): ?>
      <a href="?step=2" class="btn-prem"><i class="bi bi-arrow-right"></i>Continue</a>
      <?php else: ?>
      <a href="?step=1" class="btn-outline">Re-check</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ STEP 2: Database Config ═══════════════════════════ -->
  <?php elseif ($step === 2): ?>
  <div class="install-card">
    <div class="card-title">Database Configuration</div>
    <div class="card-subtitle">Enter your MySQL database connection details.</div>

    <form method="POST">
      <input type="hidden" name="db_config" value="1">
      <div class="row g-3">
        <div class="col-md-6 form-group">
          <label class="prem-label">Database Host <span style="color:#ef4444">*</span></label>
          <input type="text" name="db_host" class="prem-input" required
                 value="<?= htmlspecialchars($_SESSION['db_host'] ?? 'localhost') ?>" placeholder="localhost">
        </div>
        <div class="col-md-6 form-group">
          <label class="prem-label">Database Name <span style="color:#ef4444">*</span></label>
          <input type="text" name="db_name" class="prem-input" required
                 value="<?= htmlspecialchars($_SESSION['db_name'] ?? 'school_erp') ?>" placeholder="school_erp">
          <div class="form-hint">Will be created if it doesn't exist.</div>
        </div>
        <div class="col-md-6 form-group">
          <label class="prem-label">Database Username <span style="color:#ef4444">*</span></label>
          <input type="text" name="db_user" class="prem-input" required
                 value="<?= htmlspecialchars($_SESSION['db_user'] ?? '') ?>" placeholder="root or cpanel_user">
        </div>
        <div class="col-md-6 form-group">
          <label class="prem-label">Database Password</label>
          <input type="password" name="db_pass" class="prem-input"
                 value="<?= htmlspecialchars($_SESSION['db_pass'] ?? '') ?>" placeholder="Leave empty if none">
        </div>
        <div class="col-12 form-group">
          <label class="prem-label">Site URL <span style="color:#ef4444">*</span></label>
          <input type="url" name="site_url" class="prem-input" required
                 value="<?= htmlspecialchars($_SESSION['site_url'] ?? ('http://'.$_SERVER['HTTP_HOST'])) ?>"
                 placeholder="https://yourschool.com">
          <div class="form-hint">No trailing slash. Example: https://myschool.edu.in</div>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="?step=1" class="btn-outline">← Back</a>
        <button type="submit" class="btn-prem"><i class="bi bi-database"></i>Test & Continue</button>
      </div>
    </form>
  </div>

  <!-- ══ STEP 3: Import Database ═══════════════════════════ -->
  <?php elseif ($step === 3): ?>
  <div class="install-card">
    <div class="card-title">Database Import</div>
    <div class="card-subtitle">
      Ready to create all tables and import default data into
      <strong><?= htmlspecialchars($_SESSION['db_name'] ?? '') ?></strong>.
    </div>

    <div class="req-row pass mb-2">
      <div><i class="bi bi-check-circle-fill text-success me-2"></i>Connection verified</div>
      <span class="req-badge ok"><?= htmlspecialchars($_SESSION['db_host'] ?? '') ?></span>
    </div>
    <div class="req-row pass mb-2">
      <div><i class="bi bi-check-circle-fill text-success me-2"></i>Schema file found</div>
      <span class="req-badge ok"><?= file_exists(DB_SQL_FILE) ? 'database.sql' : 'MISSING' ?></span>
    </div>

    <?php if (!file_exists(DB_SQL_FILE)): ?>
    <div class="alert-err mt-3">
      <i class="bi bi-exclamation-triangle me-2"></i>
      <strong>database.sql</strong> not found at root. Please upload it and try again.
    </div>
    <?php else: ?>
    <div class="alert-ok mt-3">
      <i class="bi bi-info-circle me-2"></i>
      This will create all required tables and insert default data. Existing tables will not be dropped.
    </div>

    <form method="POST">
      <input type="hidden" name="run_import" value="1">
      <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="?step=2" class="btn-outline">← Back</a>
        <button type="submit" class="btn-prem">
          <i class="bi bi-cloud-upload"></i>Import Database
        </button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- ══ STEP 4: Admin Account ══════════════════════════════ -->
  <?php elseif ($step === 4): ?>
  <div class="install-card">
    <div class="card-title">Create Administrator Account</div>
    <div class="card-subtitle">Set up the super admin login credentials for your ERP.</div>

    <form method="POST">
      <input type="hidden" name="create_admin" value="1">
      <div class="form-group">
        <label class="prem-label">Full Name <span style="color:#ef4444">*</span></label>
        <input type="text" name="admin_name" class="prem-input" required
               value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Super Admin') ?>" placeholder="Your full name">
      </div>
      <div class="form-group">
        <label class="prem-label">Email Address <span style="color:#ef4444">*</span></label>
        <input type="email" name="admin_email" class="prem-input" required
               value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" placeholder="admin@yourschool.com">
      </div>
      <div class="row g-3">
        <div class="col-md-6 form-group">
          <label class="prem-label">Password <span style="color:#ef4444">*</span></label>
          <input type="password" name="admin_pass" class="prem-input" required minlength="8" placeholder="Min. 8 characters">
        </div>
        <div class="col-md-6 form-group">
          <label class="prem-label">Confirm Password <span style="color:#ef4444">*</span></label>
          <input type="password" name="admin_pass2" class="prem-input" required minlength="8" placeholder="Repeat password">
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="?step=3" class="btn-outline">← Back</a>
        <button type="submit" class="btn-prem"><i class="bi bi-person-check"></i>Create Admin</button>
      </div>
    </form>
  </div>

  <!-- ══ STEP 5: Complete ═══════════════════════════════════ -->
  <?php elseif ($step === 5): ?>
  <div class="install-card text-center">
    <div class="success-circle">🎉</div>
    <h2 style="font-size:1.5rem;font-weight:800;color:#1e1b4b;margin-bottom:10px">Installation Complete!</h2>
    <p style="color:#6b7280;margin-bottom:28px">
      Your School ERP is ready. The <code>install/</code> folder has been locked.<br>
      Admin email: <strong><?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?></strong>
    </p>

    <div style="background:rgba(99,102,241,.08);border-radius:14px;padding:18px;margin-bottom:28px;text-align:left">
      <p style="font-weight:700;color:#6366f1;margin-bottom:12px"><i class="bi bi-shield-check me-2"></i>Security Checklist</p>
      <ul style="list-style:none;padding:0;font-size:.875rem;color:#374151">
        <li class="mb-2">✅ <code>install/.installed</code> lock file created</li>
        <li class="mb-2">✅ <code>config/config.php</code> written with your settings</li>
        <li class="mb-2">✅ Admin account created with bcrypt password</li>
        <li class="mb-2">⚠️ You may optionally delete the <code>install/</code> folder via cPanel for extra security</li>
      </ul>
    </div>

    <form method="POST">
      <input type="hidden" name="finish" value="1">
      <button type="submit" class="btn-prem" style="font-size:1rem;padding:15px 36px">
        <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
      </button>
    </form>
  </div>

  <?php endif; ?>

  <p class="text-center text-muted" style="font-size:.75rem;margin-top:20px">
    School ERP Installer v<?= INSTALLER_VERSION ?> &bull; Secure Setup Wizard
  </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
