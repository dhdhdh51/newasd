<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$page_title = 'Settings';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Settings','active'=>true]];

$tab = sanitize($_GET['tab'] ?? 'general');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $updates = [];
    $allowed = [
        'site_name','site_tagline','site_url','footer_text','contact_email',
        'contact_phone','contact_address','academic_year','currency','currency_symbol',
        'smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from','smtp_from_name','smtp_encryption',
        'payu_merchant_key','payu_merchant_salt','payu_mode','payu_surl','payu_furl',
        'meta_title','meta_description','meta_keywords','pass_percentage',
        'contact_map_embed','social_facebook','social_twitter','social_instagram','social_youtube',
        'about_mission','about_vision',
    ];

    $upsert = $pdo->prepare(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
         ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
    );

    foreach ($allowed as $key) {
        if (isset($_POST[$key])) {
            $upsert->execute([$key, sanitize($_POST[$key])]);
        }
    }

    // Logo upload
    if (!empty($_FILES['site_logo']['name'])) {
        $logo = upload_file($_FILES['site_logo'], 'logos', ALLOWED_IMAGES);
        if ($logo) {
            $upsert->execute(['site_logo', $logo]);
        }
    }

    // Favicon upload
    if (!empty($_FILES['site_favicon']['name'])) {
        $fav = upload_file($_FILES['site_favicon'], 'logos', ['ico','png','gif','jpg']);
        if ($fav) {
            $upsert->execute(['site_favicon', $fav]);
        }
    }

    set_flash('success', 'Settings saved successfully!');
    redirect(SITE_URL . '/admin/settings/?tab=' . $tab);
}

// Load all settings
$settings = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
function s(array $settings, string $key, string $default = ''): string {
    return htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

include INCLUDES_PATH . 'header.php';
?>

<ul class="nav nav-tabs mb-3 flex-wrap">
  <?php foreach ([
    'general' => ['General','gear'],
    'smtp'    => ['SMTP Email','envelope'],
    'payment' => ['Payment','credit-card'],
    'seo'     => ['SEO','search'],
  ] as $t => [$label, $icon]): ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab===$t?'active':'' ?>" href="?tab=<?= $t ?>">
      <i class="bi bi-<?= $icon ?> me-1"></i><?= $label ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<form method="POST" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <?php if ($tab === 'general'): ?>
  <div class="row g-3">
    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 fw-semibold">Website Identity</div>
        <div class="card-body">
          <div class="mb-3"><label class="form-label fw-semibold">Site Name</label><input type="text" name="site_name" class="form-control" value="<?= s($settings,'site_name') ?>"></div>
          <div class="mb-3"><label class="form-label fw-semibold">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?= s($settings,'site_tagline') ?>"></div>
          <div class="mb-3"><label class="form-label fw-semibold">Site URL</label><input type="url" name="site_url" class="form-control" value="<?= s($settings,'site_url') ?>"></div>
          <div class="mb-3"><label class="form-label fw-semibold">Footer Text</label><input type="text" name="footer_text" class="form-control" value="<?= s($settings,'footer_text') ?>"></div>
          <div class="mb-3"><label class="form-label fw-semibold">Academic Year</label><input type="text" name="academic_year" class="form-control" value="<?= s($settings,'academic_year') ?>"></div>
          <div class="row g-2">
            <div class="col-6"><label class="form-label fw-semibold">Currency</label><input type="text" name="currency" class="form-control" value="<?= s($settings,'currency','INR') ?>"></div>
            <div class="col-6"><label class="form-label fw-semibold">Symbol</label><input type="text" name="currency_symbol" class="form-control" value="<?= s($settings,'currency_symbol','₹') ?>"></div>
          </div>
          <div class="mt-3"><label class="form-label fw-semibold">Pass Percentage</label><input type="number" name="pass_percentage" class="form-control" value="<?= s($settings,'pass_percentage','33') ?>" min="1" max="100"></div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 fw-semibold">Contact Details</div>
        <div class="card-body">
          <div class="mb-3"><label class="form-label fw-semibold">Email</label><input type="email" name="contact_email" class="form-control" value="<?= s($settings,'contact_email') ?>"></div>
          <div class="mb-3"><label class="form-label fw-semibold">Phone</label><input type="text" name="contact_phone" class="form-control" value="<?= s($settings,'contact_phone') ?>"></div>
          <div class="mb-3"><label class="form-label fw-semibold">Address</label><textarea name="contact_address" class="form-control" rows="3"><?= s($settings,'contact_address') ?></textarea></div>
          <div class="mb-3"><label class="form-label fw-semibold">Google Maps Embed</label><textarea name="contact_map_embed" class="form-control" rows="3" placeholder="Paste full &lt;iframe&gt; code from Google Maps → Share → Embed a map"><?= s($settings,'contact_map_embed') ?></textarea><div class="form-text">Go to Google Maps → Share → Embed a map → copy the full &lt;iframe&gt; code.</div></div>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white border-0 fw-semibold">Social Media Links</div>
        <div class="card-body">
          <div class="mb-2"><label class="form-label fw-semibold small">Facebook URL</label><input type="url" name="social_facebook" class="form-control form-control-sm" value="<?= s($settings,'social_facebook') ?>" placeholder="https://facebook.com/..."></div>
          <div class="mb-2"><label class="form-label fw-semibold small">Twitter / X URL</label><input type="url" name="social_twitter" class="form-control form-control-sm" value="<?= s($settings,'social_twitter') ?>" placeholder="https://twitter.com/..."></div>
          <div class="mb-2"><label class="form-label fw-semibold small">Instagram URL</label><input type="url" name="social_instagram" class="form-control form-control-sm" value="<?= s($settings,'social_instagram') ?>" placeholder="https://instagram.com/..."></div>
          <div class="mb-0"><label class="form-label fw-semibold small">YouTube URL</label><input type="url" name="social_youtube" class="form-control form-control-sm" value="<?= s($settings,'social_youtube') ?>" placeholder="https://youtube.com/..."></div>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white border-0 fw-semibold">Logo & Favicon</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Site Logo</label>
            <?php if (!empty($settings['site_logo'])): ?>
            <div class="mb-2"><img src="<?= get_upload_url($settings['site_logo']) ?>" height="50" class="rounded border"></div>
            <?php endif; ?>
            <input type="file" name="site_logo" class="form-control" accept="image/*">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Favicon</label>
            <?php if (!empty($settings['site_favicon'])): ?>
            <div class="mb-2"><img src="<?= get_upload_url($settings['site_favicon']) ?>" height="32" class="rounded border"></div>
            <?php endif; ?>
            <input type="file" name="site_favicon" class="form-control" accept="image/x-icon,image/png,image/gif">
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php elseif ($tab === 'smtp'): ?>
  <div class="card border-0 shadow-sm" style="max-width:600px">
    <div class="card-header bg-white border-0 fw-semibold"><i class="bi bi-envelope me-2"></i>SMTP Email Configuration</div>
    <div class="card-body">
      <div class="alert alert-info small">
        <i class="bi bi-info-circle me-1"></i>For Gmail, use <code>smtp.gmail.com</code> port <code>587</code> (TLS) or <code>465</code> (SSL). Enable 2FA and use an App Password.
      </div>
      <div class="row g-3">
        <div class="col-12 col-md-8"><label class="form-label fw-semibold">SMTP Host</label><input type="text" name="smtp_host" class="form-control" value="<?= s($settings,'smtp_host','smtp.gmail.com') ?>" placeholder="smtp.gmail.com"></div>
        <div class="col-12 col-md-4"><label class="form-label fw-semibold">Port</label><input type="number" name="smtp_port" class="form-control" value="<?= s($settings,'smtp_port','587') ?>"></div>
        <div class="col-12"><label class="form-label fw-semibold">SMTP Username</label><input type="email" name="smtp_user" class="form-control" value="<?= s($settings,'smtp_user') ?>" placeholder="your@gmail.com"></div>
        <div class="col-12"><label class="form-label fw-semibold">SMTP Password / App Password</label><input type="password" name="smtp_pass" class="form-control" value="<?= s($settings,'smtp_pass') ?>" placeholder="App password"></div>
        <div class="col-12"><label class="form-label fw-semibold">From Email</label><input type="email" name="smtp_from" class="form-control" value="<?= s($settings,'smtp_from') ?>"></div>
        <div class="col-12"><label class="form-label fw-semibold">From Name</label><input type="text" name="smtp_from_name" class="form-control" value="<?= s($settings,'smtp_from_name') ?>"></div>
        <div class="col-12">
          <label class="form-label fw-semibold">Encryption</label>
          <select name="smtp_encryption" class="form-select">
            <?php foreach (['tls','ssl',''] as $enc): ?>
            <option value="<?= $enc ?>" <?= s($settings,'smtp_encryption','tls')===$enc?'selected':'' ?>><?= $enc === '' ? 'None' : strtoupper($enc) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <?php elseif ($tab === 'payment'): ?>
  <div class="card border-0 shadow-sm" style="max-width:600px">
    <div class="card-header bg-white border-0 fw-semibold"><i class="bi bi-credit-card me-2"></i>PayU Payment Gateway</div>
    <div class="card-body">
      <div class="alert alert-warning small">
        <i class="bi bi-exclamation-triangle me-1"></i>Get these keys from your PayU merchant dashboard.
      </div>
      <div class="row g-3">
        <div class="col-12"><label class="form-label fw-semibold">Merchant Key</label><input type="text" name="payu_merchant_key" class="form-control" value="<?= s($settings,'payu_merchant_key') ?>"></div>
        <div class="col-12"><label class="form-label fw-semibold">Merchant Salt</label><input type="text" name="payu_merchant_salt" class="form-control" value="<?= s($settings,'payu_merchant_salt') ?>"></div>
        <div class="col-12">
          <label class="form-label fw-semibold">Mode</label>
          <select name="payu_mode" class="form-select">
            <option value="test" <?= s($settings,'payu_mode','test')==='test'?'selected':'' ?>>Test</option>
            <option value="live" <?= s($settings,'payu_mode','test')==='live'?'selected':'' ?>>Live</option>
          </select>
        </div>
        <div class="col-12"><label class="form-label fw-semibold">Success URL</label><input type="url" name="payu_surl" class="form-control" value="<?= s($settings,'payu_surl') ?>" placeholder="https://yourdomain.com/payment/success.php"></div>
        <div class="col-12"><label class="form-label fw-semibold">Failure URL</label><input type="url" name="payu_furl" class="form-control" value="<?= s($settings,'payu_furl') ?>" placeholder="https://yourdomain.com/payment/failure.php"></div>
      </div>
    </div>
  </div>

  <?php elseif ($tab === 'seo'): ?>
  <div class="card border-0 shadow-sm" style="max-width:600px">
    <div class="card-header bg-white border-0 fw-semibold"><i class="bi bi-search me-2"></i>SEO Settings</div>
    <div class="card-body">
      <div class="mb-3"><label class="form-label fw-semibold">Meta Title</label><input type="text" name="meta_title" class="form-control" value="<?= s($settings,'meta_title') ?>" maxlength="70"><div class="form-text">Recommended: 50-60 characters</div></div>
      <div class="mb-3"><label class="form-label fw-semibold">Meta Description</label><textarea name="meta_description" class="form-control" rows="3" maxlength="160"><?= s($settings,'meta_description') ?></textarea><div class="form-text">Recommended: 150-160 characters</div></div>
      <div class="mb-3"><label class="form-label fw-semibold">Meta Keywords</label><input type="text" name="meta_keywords" class="form-control" value="<?= s($settings,'meta_keywords') ?>" placeholder="school erp, school management..."></div>
    </div>
  </div>
  <?php endif; ?>

  <div class="mt-3">
    <button type="submit" class="btn btn-primary px-4">
      <i class="bi bi-save me-2"></i>Save Settings
    </button>
  </div>
</form>

<?php include INCLUDES_PATH . 'footer.php'; ?>
