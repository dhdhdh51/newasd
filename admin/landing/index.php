<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('admin');

$page_title = 'Landing Page';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Landing Page','active'=>true]];
$tab = sanitize($_GET['tab'] ?? 'hero');

// ── Handle POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    $action = sanitize($_POST['action'] ?? '');

    $upsert = $pdo->prepare(
        "INSERT INTO settings (setting_key,setting_value) VALUES (?,?)
         ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
    );

    // ── Delete gallery image ──────────────────────────────
    if ($action === 'delete_gallery') {
        $gid = sanitize_int($_POST['gallery_id'] ?? 0);
        if ($gid) {
            $row = $pdo->prepare("SELECT image FROM gallery WHERE id=?")->execute([$gid])
                   ? $pdo->query("SELECT image FROM gallery WHERE id=$gid")->fetch()
                   : null;
            $stmt = $pdo->prepare("SELECT image FROM gallery WHERE id=?");
            $stmt->execute([$gid]);
            $row = $stmt->fetch();
            if ($row) { delete_upload($row['image']); }
            $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$gid]);
        }
        set_flash('success', 'Gallery image deleted.');
        redirect(SITE_URL . '/admin/landing/?tab=gallery');
    }

    // ── Toggle gallery active ─────────────────────────────
    if ($action === 'toggle_gallery') {
        $gid = sanitize_int($_POST['gallery_id'] ?? 0);
        if ($gid) {
            $pdo->prepare("UPDATE gallery SET is_active = 1-is_active WHERE id=?")->execute([$gid]);
        }
        redirect(SITE_URL . '/admin/landing/?tab=gallery');
    }

    // ── Delete notice ─────────────────────────────────────
    if ($action === 'delete_notice') {
        $nid = sanitize_int($_POST['notice_id'] ?? 0);
        if ($nid) { $pdo->prepare("DELETE FROM notices WHERE id=?")->execute([$nid]); }
        set_flash('success', 'Notice deleted.');
        redirect(SITE_URL . '/admin/landing/?tab=notices');
    }

    // ── Toggle notice ─────────────────────────────────────
    if ($action === 'toggle_notice') {
        $nid = sanitize_int($_POST['notice_id'] ?? 0);
        if ($nid) { $pdo->prepare("UPDATE notices SET is_active=1-is_active WHERE id=?")->execute([$nid]); }
        redirect(SITE_URL . '/admin/landing/?tab=notices');
    }

    // ── Add notice ────────────────────────────────────────
    if ($action === 'add_notice') {
        $title    = sanitize($_POST['notice_title'] ?? '');
        $body     = sanitize($_POST['notice_body']  ?? '');
        $category = sanitize($_POST['notice_cat']   ?? 'general');
        if ($title) {
            $pdo->prepare("INSERT INTO notices (title,body,category,is_active) VALUES (?,?,?,1)")
                ->execute([$title, $body, $category]);
            set_flash('success', 'Notice added.');
        }
        redirect(SITE_URL . '/admin/landing/?tab=notices');
    }

    // ── Upload gallery image ──────────────────────────────
    if ($action === 'add_gallery') {
        if (!empty($_FILES['gallery_images']['name'][0])) {
            $uploaded = 0;
            $count    = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name'     => $_FILES['gallery_images']['name'][$i],
                    'type'     => $_FILES['gallery_images']['type'][$i],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error'    => $_FILES['gallery_images']['error'][$i],
                    'size'     => $_FILES['gallery_images']['size'][$i],
                ];
                if ($file['error'] !== UPLOAD_ERR_OK) continue;
                $path = upload_file($file, 'gallery', ALLOWED_IMAGES);
                if ($path) {
                    $title = sanitize($_POST['gallery_title'] ?? '');
                    $pdo->prepare("INSERT INTO gallery (title,image,sort_order,is_active) VALUES (?,?,0,1)")
                        ->execute([$title, $path]);
                    $uploaded++;
                }
            }
            set_flash('success', "$uploaded image(s) uploaded.");
        } else {
            set_flash('danger', 'Please select at least one image.');
        }
        redirect(SITE_URL . '/admin/landing/?tab=gallery');
    }

    // ── Save text/toggle settings ─────────────────────────
    $textKeys = [
        'lp_hero_title','lp_hero_subtitle','lp_hero_btn1_text','lp_hero_btn1_url',
        'lp_hero_btn2_text','lp_hero_btn2_url',
        'lp_about_title','lp_about_text',
        'lp_stat_students','lp_stat_teachers','lp_stat_years','lp_stat_success',
        'lp_stat_label1','lp_stat_label2','lp_stat_label3','lp_stat_label4',
        'lp_show_gallery','lp_show_notices','lp_show_about','lp_show_stats',
        'lp_primary_color','lp_cta_title','lp_cta_text',
    ];
    foreach ($textKeys as $k) {
        if (isset($_POST[$k])) { $upsert->execute([$k, sanitize($_POST[$k])]); }
    }
    // Checkboxes (unchecked = not posted, treat as '0')
    foreach (['lp_show_gallery','lp_show_notices','lp_show_about','lp_show_stats'] as $k) {
        if (!isset($_POST[$k])) { $upsert->execute([$k, '0']); }
    }

    // Hero image upload
    if (!empty($_FILES['lp_hero_image']['name'])) {
        $img = upload_file($_FILES['lp_hero_image'], 'landing', ALLOWED_IMAGES);
        if ($img) { $upsert->execute(['lp_hero_image', $img]); }
    }
    // About image upload
    if (!empty($_FILES['lp_about_image']['name'])) {
        $img = upload_file($_FILES['lp_about_image'], 'landing', ALLOWED_IMAGES);
        if ($img) { $upsert->execute(['lp_about_image', $img]); }
    }

    set_flash('success', 'Landing page saved!');
    redirect(SITE_URL . '/admin/landing/?tab=' . $tab);
}

// ── Load settings ──────────────────────────────────────────
$rows = $pdo->query("SELECT setting_key,setting_value FROM settings")->fetchAll();
$cfg  = [];
foreach ($rows as $r) { $cfg[$r['setting_key']] = $r['setting_value']; }
function cfg(array $c, string $k, string $d = ''): string {
    return htmlspecialchars($c[$k] ?? $d, ENT_QUOTES, 'UTF-8');
}

// Gallery items
$gallery = $pdo->query("SELECT * FROM gallery ORDER BY sort_order ASC, id ASC")->fetchAll();

// Notices
$notices = $pdo->query("SELECT * FROM notices ORDER BY created_at DESC")->fetchAll();

include INCLUDES_PATH . 'header.php';
?>

<!-- Tab nav -->
<ul class="nav nav-tabs mb-4 flex-wrap">
  <?php foreach ([
    'hero'    => ['Hero Section',      'image'],
    'about'   => ['About & Stats',     'info-circle'],
    'gallery' => ['Photo Gallery',     'images'],
    'notices' => ['Notices & Events',  'bell'],
    'display' => ['Display & Colours', 'palette'],
  ] as $t => [$label, $icon]): ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab===$t?'active':'' ?>" href="?tab=<?= $t ?>">
      <i class="bi bi-<?= $icon ?> me-1"></i><?= $label ?>
    </a>
  </li>
  <?php endforeach; ?>
  <li class="nav-item ms-auto">
    <a href="<?= SITE_URL ?>/" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
      <i class="bi bi-eye me-1"></i>Preview Site
    </a>
  </li>
</ul>

<?php if (in_array($tab, ['hero','about','display'])): ?>
<form method="POST" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save_settings">

<?php endif; ?>

<!-- ════ HERO TAB ═══════════════════════════════════════ -->
<?php if ($tab === 'hero'): ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold border-0">
        <i class="bi bi-type me-2 text-primary"></i>Hero Text
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Main Heading</label>
          <input type="text" name="lp_hero_title" class="form-control" value="<?= cfg($cfg,'lp_hero_title','Welcome to Our School') ?>">
          <div class="form-text">The last word gets highlighted automatically.</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Sub-heading / Tagline</label>
          <textarea name="lp_hero_subtitle" class="form-control" rows="3"><?= cfg($cfg,'lp_hero_subtitle') ?></textarea>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Button 1 — Text</label>
            <input type="text" name="lp_hero_btn1_text" class="form-control" value="<?= cfg($cfg,'lp_hero_btn1_text','Apply for Admission') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Button 1 — URL</label>
            <input type="text" name="lp_hero_btn1_url" class="form-control" value="<?= cfg($cfg,'lp_hero_btn1_url','/public/admission.php') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Button 2 — Text</label>
            <input type="text" name="lp_hero_btn2_text" class="form-control" value="<?= cfg($cfg,'lp_hero_btn2_text','Check Status') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Button 2 — URL</label>
            <input type="text" name="lp_hero_btn2_url" class="form-control" value="<?= cfg($cfg,'lp_hero_btn2_url','/public/admission-status.php') ?>">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold border-0">
        <i class="bi bi-image me-2 text-primary"></i>Hero Background Image
      </div>
      <div class="card-body">
        <?php if (!empty($cfg['lp_hero_image'])): ?>
        <div class="mb-3 position-relative">
          <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($cfg['lp_hero_image'], ENT_QUOTES) ?>"
               class="img-fluid rounded" style="max-height:180px;width:100%;object-fit:cover;">
          <span class="badge bg-success position-absolute top-0 end-0 m-2">Current</span>
        </div>
        <?php else: ?>
        <div class="mb-3 bg-light rounded d-flex align-items-center justify-content-center" style="height:140px;">
          <div class="text-center text-muted">
            <i class="bi bi-image display-4 d-block mb-1"></i>
            <small>No image set — using gradient</small>
          </div>
        </div>
        <?php endif; ?>
        <label class="form-label fw-semibold">Upload New Image</label>
        <input type="file" name="lp_hero_image" class="form-control" accept="image/*"
               data-photo-preview="heroPreview">
        <div class="form-text">Recommended: 1920×1080px, JPG/PNG/WebP, max 5 MB.</div>
        <img id="heroPreview" src="" class="img-fluid rounded mt-2 d-none" style="max-height:120px;">
      </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold border-0">
        <i class="bi bi-megaphone me-2 text-primary"></i>Admission CTA Banner
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">CTA Title</label>
          <input type="text" name="lp_cta_title" class="form-control" value="<?= cfg($cfg,'lp_cta_title','Start Your Journey With Us') ?>">
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold">CTA Sub-text</label>
          <textarea name="lp_cta_text" class="form-control" rows="2"><?= cfg($cfg,'lp_cta_text') ?></textarea>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ════ ABOUT & STATS TAB ══════════════════════════════ -->
<?php elseif ($tab === 'about'): ?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold border-0">
        <i class="bi bi-info-circle me-2 text-primary"></i>About Section
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Section Heading</label>
          <input type="text" name="lp_about_title" class="form-control" value="<?= cfg($cfg,'lp_about_title','About Our School') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">About Text</label>
          <textarea name="lp_about_text" class="form-control" rows="6"><?= cfg($cfg,'lp_about_text') ?></textarea>
          <div class="form-text">Use line breaks for paragraphs.</div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold border-0">
        <i class="bi bi-bar-chart me-2 text-primary"></i>Stats Numbers
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php $statFields = [
            ['lp_stat_students','1200+','lp_stat_label1','Students Enrolled'],
            ['lp_stat_teachers','80+',  'lp_stat_label2','Expert Teachers'],
            ['lp_stat_years',   '25+',  'lp_stat_label3','Years of Excellence'],
            ['lp_stat_success', '98%',  'lp_stat_label4','Pass Rate'],
          ];
          foreach ($statFields as $i => [$nk,$nd,$lk,$ld]): ?>
          <div class="col-6">
            <label class="form-label fw-semibold small">Stat <?= $i+1 ?> — Number</label>
            <input type="text" name="<?= $nk ?>" class="form-control form-control-sm"
                   value="<?= cfg($cfg,$nk,$nd) ?>" placeholder="<?= $nd ?>">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold small">Stat <?= $i+1 ?> — Label</label>
            <input type="text" name="<?= $lk ?>" class="form-control form-control-sm"
                   value="<?= cfg($cfg,$lk,$ld) ?>" placeholder="<?= $ld ?>">
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold border-0">
        <i class="bi bi-image me-2 text-primary"></i>About Section Image
      </div>
      <div class="card-body">
        <?php if (!empty($cfg['lp_about_image'])): ?>
        <div class="mb-3">
          <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($cfg['lp_about_image'], ENT_QUOTES) ?>"
               class="img-fluid rounded" style="max-height:200px;width:100%;object-fit:cover;">
        </div>
        <?php else: ?>
        <div class="mb-3 bg-light rounded d-flex align-items-center justify-content-center" style="height:140px;">
          <div class="text-center text-muted">
            <i class="bi bi-image display-4 d-block mb-1"></i><small>No image set</small>
          </div>
        </div>
        <?php endif; ?>
        <label class="form-label fw-semibold">Upload Image</label>
        <input type="file" name="lp_about_image" class="form-control" accept="image/*"
               data-photo-preview="aboutPreview">
        <div class="form-text">Recommended: 600×500px. Square or portrait format works best.</div>
        <img id="aboutPreview" src="" class="img-fluid rounded mt-2 d-none" style="max-height:120px;">
      </div>
    </div>
  </div>
</div>

<!-- ════ GALLERY TAB ════════════════════════════════════ -->
<?php elseif ($tab === 'gallery'): ?>
<!-- Upload form -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-semibold border-0">
    <i class="bi bi-cloud-upload me-2 text-primary"></i>Upload Photos
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_gallery">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Caption (optional)</label>
          <input type="text" name="gallery_title" class="form-control" placeholder="e.g. Sports Day 2025">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Select Images <span class="text-muted small">(multiple allowed)</span></label>
          <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple required>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-upload me-1"></i>Upload
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Gallery grid -->
<?php if (empty($gallery)): ?>
<div class="text-center py-5 text-muted">
  <i class="bi bi-images display-3 d-block mb-3"></i>
  <p>No gallery images yet. Upload some above.</p>
</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($gallery as $img): ?>
  <div class="col-6 col-md-4 col-lg-3">
    <div class="card border-0 shadow-sm h-100">
      <div style="height:160px;overflow:hidden;border-radius:8px 8px 0 0;">
        <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($img['image'], ENT_QUOTES) ?>"
             class="w-100 h-100" style="object-fit:cover;">
      </div>
      <div class="card-body p-2">
        <p class="small fw-semibold mb-2 text-truncate"><?= htmlspecialchars($img['title'] ?: '(no caption)', ENT_QUOTES) ?></p>
        <div class="d-flex gap-1">
          <!-- Toggle -->
          <form method="POST" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle_gallery">
            <input type="hidden" name="gallery_id" value="<?= $img['id'] ?>">
            <button type="submit" class="btn btn-xs btn-<?= $img['is_active'] ? 'success' : 'secondary' ?> btn-sm" style="font-size:.72rem;padding:2px 8px;" title="<?= $img['is_active'] ? 'Active — click to hide' : 'Hidden — click to show' ?>">
              <i class="bi bi-<?= $img['is_active'] ? 'eye' : 'eye-slash' ?>"></i>
            </button>
          </form>
          <!-- Delete -->
          <form method="POST" class="d-inline" onsubmit="return confirm('Delete this image?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_gallery">
            <input type="hidden" name="gallery_id" value="<?= $img['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.72rem;padding:2px 8px;">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ════ NOTICES TAB ════════════════════════════════════ -->
<?php elseif ($tab === 'notices'): ?>
<!-- Add notice form -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-semibold border-0">
    <i class="bi bi-plus-circle me-2 text-primary"></i>Add New Notice
  </div>
  <div class="card-body">
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_notice">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Title</label>
          <input type="text" name="notice_title" class="form-control" placeholder="Notice title" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Category</label>
          <select name="notice_cat" class="form-select">
            <?php foreach (['general','exam','event','holiday','admission'] as $c): ?>
            <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Body (optional)</label>
          <input type="text" name="notice_body" class="form-control" placeholder="Brief description">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">Add</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Notices list -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Title</th><th>Category</th><th>Date</th><th>Status</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($notices)): ?>
          <tr><td colspan="5" class="text-center py-5 text-muted">No notices yet.</td></tr>
          <?php else: foreach ($notices as $n):
            $colors = ['general'=>'primary','exam'=>'warning','event'=>'success','holiday'=>'danger','admission'=>'info'];
            $c = $colors[$n['category']] ?? 'primary';
          ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($n['title'], ENT_QUOTES) ?></td>
            <td><span class="badge bg-<?= $c ?> bg-opacity-10 text-<?= $c ?>"><?= ucfirst($n['category']) ?></span></td>
            <td class="text-muted small"><?= date('d M Y', strtotime($n['created_at'])) ?></td>
            <td>
              <span class="badge bg-<?= $n['is_active'] ? 'success' : 'secondary' ?>">
                <?= $n['is_active'] ? 'Visible' : 'Hidden' ?>
              </span>
            </td>
            <td class="text-end">
              <form method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_notice">
                <input type="hidden" name="notice_id" value="<?= $n['id'] ?>">
                <button class="btn btn-sm btn-outline-<?= $n['is_active'] ? 'secondary' : 'success' ?>" title="Toggle">
                  <i class="bi bi-<?= $n['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                </button>
              </form>
              <form method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_notice">
                <input type="hidden" name="notice_id" value="<?= $n['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ════ DISPLAY & COLOURS TAB ══════════════════════════ -->
<?php elseif ($tab === 'display'): ?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold border-0">
        <i class="bi bi-toggles me-2 text-primary"></i>Show / Hide Sections
      </div>
      <div class="card-body">
        <?php foreach ([
          ['lp_show_about',   'About Section'],
          ['lp_show_stats',   'Statistics Block (in Hero)'],
          ['lp_show_gallery', 'Photo Gallery'],
          ['lp_show_notices', 'Notices & Events'],
        ] as [$key, $label]): ?>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="<?= $key ?>"
                 id="<?= $key ?>" value="1"
                 <?= ($cfg[$key] ?? '1') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label fw-medium" for="<?= $key ?>"><?= $label ?></label>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold border-0">
        <i class="bi bi-palette me-2 text-primary"></i>Brand Colour
      </div>
      <div class="card-body">
        <label class="form-label fw-semibold">Primary Colour</label>
        <div class="input-group">
          <input type="color" name="lp_primary_color" class="form-control form-control-color"
                 value="<?= cfg($cfg,'lp_primary_color','#0d6efd') ?>" style="max-width:60px;">
          <input type="text" class="form-control" id="colorHex"
                 value="<?= cfg($cfg,'lp_primary_color','#0d6efd') ?>"
                 oninput="document.querySelector('[name=lp_primary_color]').value=this.value">
        </div>
        <div class="form-text">Used for buttons, badges, and accent elements.</div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (in_array($tab, ['hero','about','display'])): ?>
<div class="mt-4">
  <button type="submit" class="btn btn-primary px-4">
    <i class="bi bi-save me-2"></i>Save Changes
  </button>
  <a href="<?= SITE_URL ?>/" target="_blank" class="btn btn-outline-secondary ms-2">
    <i class="bi bi-eye me-1"></i>Preview
  </a>
</div>
</form>
<?php endif; ?>

<script>
// Sync color picker ↔ hex input
document.querySelector('[name="lp_primary_color"]')?.addEventListener('input', function() {
  const hex = document.getElementById('colorHex');
  if (hex) hex.value = this.value;
});
// Photo preview
document.querySelectorAll('[data-photo-preview]').forEach(input => {
  input.addEventListener('change', function() {
    const img = document.getElementById(this.dataset.photoPreview);
    if (!img || !this.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; img.classList.remove('d-none'); };
    reader.readAsDataURL(this.files[0]);
  });
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
