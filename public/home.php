<?php
/**
 * School ERP — Public Landing Page
 * All content managed via Admin → Landing Page
 */
require_once dirname(__DIR__) . '/config/config.php';

// Pull all lp_ settings in one query
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'lp_%' OR setting_key IN ('site_name','site_tagline','site_logo','contact_email','contact_phone','contact_address','footer_text')")->fetchAll();
$s = [];
foreach ($rows as $r) { $s[$r['setting_key']] = $r['setting_value']; }
function lp(array $s, string $k, string $d = ''): string {
    return htmlspecialchars($s[$k] ?? $d, ENT_QUOTES, 'UTF-8');
}

// Gallery images
$gallery = [];
if (($s['lp_show_gallery'] ?? '1') === '1') {
    $gallery = $pdo->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order ASC, id ASC LIMIT 12")->fetchAll();
}

// Latest notices
$notices = [];
if (($s['lp_show_notices'] ?? '1') === '1') {
    $notices = $pdo->query("SELECT * FROM notices WHERE is_active=1 ORDER BY created_at DESC LIMIT 6")->fetchAll();
}

$site_name = lp($s, 'site_name', 'School ERP');
$hero_img  = !empty($s['lp_hero_image']) ? UPLOADS_URL . '/' . htmlspecialchars($s['lp_hero_image'], ENT_QUOTES) : '';
$about_img = !empty($s['lp_about_image']) ? UPLOADS_URL . '/' . htmlspecialchars($s['lp_about_image'], ENT_QUOTES) : '';

$notice_colors = ['general'=>'primary','exam'=>'warning','event'=>'success','holiday'=>'danger','admission'=>'info'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= lp($s,'site_name','School ERP') ?> — <?= lp($s,'site_tagline','Empowering Education') ?></title>
  <meta name="description" content="<?= lp($s,'lp_hero_subtitle','Quality education for every student.') ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
  <style>
    :root {
      --lp-primary: <?= lp($s,'lp_primary_color','#0d6efd') ?>;
    }

    /* ── Navbar ── */
    #mainNav {
      transition: background .35s, box-shadow .35s, padding .35s;
      padding-top: 18px; padding-bottom: 18px;
    }
    #mainNav.scrolled {
      background: #fff !important;
      box-shadow: 0 2px 20px rgba(0,0,0,.08);
      padding-top: 10px; padding-bottom: 10px;
    }
    #mainNav.scrolled .nav-link,
    #mainNav.scrolled .navbar-brand { color: #212529 !important; }
    #mainNav.scrolled .navbar-toggler { border-color: #aaa; }

    /* ── Hero ── */
    .hero-section {
      min-height: 100vh;
      display: flex; align-items: center;
      position: relative; overflow: hidden;
      background: linear-gradient(135deg, #0d3b7a 0%, #1565c0 60%, #0d6efd 100%);
    }
    .hero-bg {
      position: absolute; inset: 0;
      background-size: cover; background-position: center;
      background-repeat: no-repeat;
    }
    .hero-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(10,30,70,.82) 0%, rgba(13,110,253,.45) 100%);
    }
    .hero-content { position: relative; z-index: 2; }
    .hero-badge {
      display: inline-block;
      background: rgba(255,255,255,.15);
      border: 1px solid rgba(255,255,255,.3);
      color: #fff;
      border-radius: 50px;
      padding: 6px 18px;
      font-size: .8rem;
      letter-spacing: .08em;
      text-transform: uppercase;
      backdrop-filter: blur(4px);
      margin-bottom: 20px;
    }
    .hero-title {
      font-size: clamp(2.2rem, 5vw, 4rem);
      font-weight: 800;
      line-height: 1.15;
      color: #fff;
    }
    .hero-title span { color: #7ec8ff; }
    .hero-subtitle {
      font-size: clamp(1rem, 2vw, 1.25rem);
      color: rgba(255,255,255,.85);
      max-width: 560px;
      line-height: 1.7;
    }
    .hero-scroll-hint {
      position: absolute; bottom: 30px; left: 50%;
      transform: translateX(-50%);
      color: rgba(255,255,255,.5);
      font-size: .8rem;
      text-align: center;
      z-index: 2;
      animation: bounce 2s infinite;
    }
    @keyframes bounce {
      0%,100% { transform: translateX(-50%) translateY(0); }
      50%      { transform: translateX(-50%) translateY(8px); }
    }

    /* ── Stats strip ── */
    .stats-strip { background: var(--lp-primary); }
    .stat-item { border-right: 1px solid rgba(255,255,255,.2); }
    .stat-item:last-child { border-right: none; }
    .stat-number {
      font-size: 2.2rem;
      font-weight: 800;
      color: #fff;
      line-height: 1;
    }
    .stat-label { color: rgba(255,255,255,.75); font-size: .85rem; }

    /* ── Section helpers ── */
    .section-badge {
      display: inline-block;
      background: rgba(13,110,253,.1);
      color: var(--lp-primary);
      border-radius: 50px;
      padding: 5px 16px;
      font-size: .78rem;
      font-weight: 600;
      letter-spacing: .06em;
      text-transform: uppercase;
      margin-bottom: 12px;
    }
    .section-title {
      font-size: clamp(1.6rem, 3vw, 2.4rem);
      font-weight: 800;
      color: #1a2340;
      line-height: 1.2;
    }
    .section-divider {
      width: 50px; height: 4px;
      background: var(--lp-primary);
      border-radius: 2px;
      margin: 14px 0 0;
    }

    /* ── About section ── */
    .about-img-wrap {
      position: relative; border-radius: 20px; overflow: hidden;
    }
    .about-img-wrap img { border-radius: 20px; object-fit: cover; }
    .about-badge-float {
      position: absolute; bottom: 24px; left: -20px;
      background: var(--lp-primary); color: #fff;
      border-radius: 14px; padding: 16px 22px;
      box-shadow: 0 8px 30px rgba(13,110,253,.35);
      text-align: center; min-width: 110px;
    }
    .about-badge-float .big { font-size: 1.8rem; font-weight: 800; }

    /* ── Feature cards ── */
    .feature-card {
      border-radius: 16px;
      padding: 28px 24px;
      background: #fff;
      border: 1px solid #e9ecef;
      transition: transform .25s, box-shadow .25s;
      height: 100%;
    }
    .feature-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 40px rgba(0,0,0,.09);
    }
    .feature-icon {
      width: 56px; height: 56px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 16px;
    }

    /* ── Gallery grid ── */
    .gallery-item {
      position: relative; overflow: hidden;
      border-radius: 12px; cursor: pointer;
      aspect-ratio: 1;
    }
    .gallery-item img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform .4s;
    }
    .gallery-item:hover img { transform: scale(1.08); }
    .gallery-item-overlay {
      position: absolute; inset: 0;
      background: rgba(13,110,253,.65);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; transition: opacity .3s;
      color: #fff; font-size: 1.8rem;
    }
    .gallery-item:hover .gallery-item-overlay { opacity: 1; }

    /* ── Notices ── */
    .notice-card {
      border-left: 4px solid var(--lp-primary);
      border-radius: 0 10px 10px 0;
      background: #fff;
      padding: 14px 18px;
      margin-bottom: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
      transition: transform .2s;
    }
    .notice-card:hover { transform: translateX(4px); }

    /* ── CTA section ── */
    .cta-section {
      background: linear-gradient(135deg, #0d3b7a 0%, #0d6efd 100%);
      position: relative; overflow: hidden;
    }
    .cta-section::before {
      content: '';
      position: absolute; top: -80px; right: -80px;
      width: 300px; height: 300px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
    }
    .cta-section::after {
      content: '';
      position: absolute; bottom: -60px; left: -60px;
      width: 220px; height: 220px;
      border-radius: 50%;
      background: rgba(255,255,255,.04);
    }

    /* ── Footer ── */
    .site-footer {
      background: #0f1b2d;
      color: rgba(255,255,255,.7);
    }
    .site-footer h6 { color: #fff; }
    .site-footer a { color: rgba(255,255,255,.6); text-decoration: none; }
    .site-footer a:hover { color: #7ec8ff; }
    .footer-bottom {
      border-top: 1px solid rgba(255,255,255,.08);
    }

    /* ── Lightbox ── */
    #lightbox {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,.92);
      z-index: 9999;
      align-items: center; justify-content: center;
    }
    #lightbox.open { display: flex; }
    #lightbox img { max-width: 90vw; max-height: 85vh; border-radius: 8px; }
    #lightbox-close {
      position: absolute; top: 20px; right: 28px;
      color: #fff; font-size: 2rem; cursor: pointer;
    }

    /* Mobile nav */
    @media (max-width: 991.98px) {
      #mainNav { background: rgba(10,30,70,.95) !important; padding-top: 12px; padding-bottom: 12px; }
      .about-badge-float { left: 10px; }
    }
  </style>
</head>
<body>

<!-- ═══════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════ -->
<nav id="mainNav" class="navbar navbar-expand-lg fixed-top" style="background:transparent;">
  <div class="container">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= SITE_URL ?>/">
      <?php if (!empty($s['site_logo'])): ?>
        <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($s['site_logo'], ENT_QUOTES) ?>"
             height="38" alt="Logo" class="rounded">
      <?php else: ?>
        <div style="width:38px;height:38px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
          <i class="bi bi-mortarboard-fill text-white fs-5"></i>
        </div>
      <?php endif; ?>
      <span class="fw-bold text-white"><?= $site_name ?></span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <i class="bi bi-list text-white fs-3"></i>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 py-2 py-lg-0">
        <li class="nav-item"><a class="nav-link text-white fw-medium" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link text-white fw-medium" href="#features">Why Us</a></li>
        <?php if (!empty($gallery)): ?>
        <li class="nav-item"><a class="nav-link text-white fw-medium" href="#gallery">Gallery</a></li>
        <?php endif; ?>
        <?php if (!empty($notices)): ?>
        <li class="nav-item"><a class="nav-link text-white fw-medium" href="#notices">Notices</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link text-white fw-medium" href="#contact">Contact</a></li>
        <li class="nav-item ms-lg-2">
          <a class="btn btn-outline-light btn-sm px-3" href="<?= SITE_URL ?>/auth/login.php">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login
          </a>
        </li>
        <li class="nav-item ms-lg-1">
          <a class="btn btn-warning btn-sm px-3 fw-semibold" href="<?= SITE_URL ?>/public/admission.php">
            <i class="bi bi-pencil-square me-1"></i>Apply Now
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ═══════════════════════════════════════════
     HERO
════════════════════════════════════════════ -->
<section class="hero-section">
  <?php if ($hero_img): ?>
  <div class="hero-bg" style="background-image:url('<?= $hero_img ?>')"></div>
  <?php endif; ?>
  <div class="hero-overlay"></div>

  <div class="container hero-content py-5">
    <div class="row align-items-center min-vh-100 py-5">
      <div class="col-lg-7">
        <div class="hero-badge">
          <i class="bi bi-stars me-1"></i>
          <?= lp($s,'site_tagline','Empowering Education') ?>
        </div>
        <h1 class="hero-title mb-4">
          <?php
          $title = lp($s,'lp_hero_title','Welcome to Our School');
          // Make last word highlighted
          $words = explode(' ', $title);
          $last  = array_pop($words);
          echo implode(' ', $words) . ' <span>' . htmlspecialchars($last, ENT_QUOTES) . '</span>';
          ?>
        </h1>
        <p class="hero-subtitle mb-4">
          <?= lp($s,'lp_hero_subtitle','Empowering students with quality education, values, and excellence since 2000.') ?>
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= SITE_URL . lp($s,'lp_hero_btn1_url','/public/admission.php') ?>"
             class="btn btn-warning btn-lg fw-semibold px-4 shadow">
            <i class="bi bi-pencil-square me-2"></i>
            <?= lp($s,'lp_hero_btn1_text','Apply for Admission') ?>
          </a>
          <a href="<?= SITE_URL . lp($s,'lp_hero_btn2_url','/public/admission-status.php') ?>"
             class="btn btn-outline-light btn-lg px-4">
            <i class="bi bi-search me-2"></i>
            <?= lp($s,'lp_hero_btn2_text','Check Status') ?>
          </a>
        </div>

        <!-- Quick links row -->
        <div class="mt-5 d-flex flex-wrap gap-3">
          <?php foreach ([
            ['bi-person-circle','Student Login','/auth/login.php'],
            ['bi-person-badge','Teacher Login','/auth/login.php'],
            ['bi-people','Parent Login','/auth/login.php'],
          ] as [$icon,$label,$url]): ?>
          <a href="<?= SITE_URL . $url ?>"
             style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:8px 16px;color:#fff;text-decoration:none;font-size:.82rem;backdrop-filter:blur(4px);"
             class="d-flex align-items-center gap-2">
            <i class="bi <?= $icon ?>"></i><?= $label ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if (($s['lp_show_stats'] ?? '1') === '1'): ?>
      <div class="col-lg-5 mt-5 mt-lg-0">
        <div class="row g-3">
          <?php $stats = [
            ['lp_stat_students','1200+','lp_stat_label1','Students Enrolled','bi-people-fill','rgba(255,200,0,.15)','#ffc800'],
            ['lp_stat_teachers','80+','lp_stat_label2','Expert Teachers','bi-person-badge-fill','rgba(100,220,100,.15)','#64dc64'],
            ['lp_stat_years','25+','lp_stat_label3','Years of Excellence','bi-award-fill','rgba(100,180,255,.15)','#64b4ff'],
            ['lp_stat_success','98%','lp_stat_label4','Pass Rate','bi-graph-up-arrow','rgba(255,130,130,.15)','#ff8282'],
          ];
          foreach ($stats as [$numKey,$numDefault,$lblKey,$lblDefault,$icon,$bg,$color]): ?>
          <div class="col-6">
            <div style="background:rgba(255,255,255,.1);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);border-radius:16px;padding:20px;">
              <div style="width:44px;height:44px;background:<?= $bg ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:1.3rem;"></i>
              </div>
              <div style="font-size:1.8rem;font-weight:800;color:#fff;line-height:1;">
                <?= lp($s,$numKey,$numDefault) ?>
              </div>
              <div style="color:rgba(255,255,255,.7);font-size:.8rem;margin-top:4px;">
                <?= lp($s,$lblKey,$lblDefault) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="hero-scroll-hint">
    <i class="bi bi-chevron-double-down d-block mb-1 fs-5"></i>
    Scroll down
  </div>
</section>

<?php if (($s['lp_show_about'] ?? '1') === '1'): ?>
<!-- ═══════════════════════════════════════════
     ABOUT
════════════════════════════════════════════ -->
<section id="about" class="py-6" style="padding:80px 0; background:#f8f9ff;">
  <div class="container">
    <div class="row align-items-center g-5">
      <!-- Image side -->
      <div class="col-lg-5">
        <div class="about-img-wrap">
          <?php if ($about_img): ?>
            <img src="<?= $about_img ?>" alt="About" class="img-fluid w-100" style="height:420px;">
          <?php else: ?>
            <div style="height:420px;background:linear-gradient(135deg,#0d6efd,#0d3b7a);border-radius:20px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-building" style="font-size:6rem;color:rgba(255,255,255,.3);"></i>
            </div>
          <?php endif; ?>
          <div class="about-badge-float">
            <div class="big"><?= lp($s,'lp_stat_years','25+') ?></div>
            <div style="font-size:.75rem;opacity:.9;">Years of<br>Excellence</div>
          </div>
        </div>
      </div>

      <!-- Text side -->
      <div class="col-lg-7">
        <div class="section-badge">About Us</div>
        <h2 class="section-title"><?= lp($s,'lp_about_title','About Our School') ?></h2>
        <div class="section-divider mb-4"></div>
        <p class="text-muted lh-lg" style="font-size:1.05rem;">
          <?= nl2br(lp($s,'lp_about_text','We are committed to providing a nurturing, inclusive learning environment.')) ?>
        </p>
        <div class="row g-3 mt-2">
          <?php foreach ([
            ['bi-check-circle-fill','Experienced Faculty','text-success'],
            ['bi-check-circle-fill','Modern Facilities','text-success'],
            ['bi-check-circle-fill','Holistic Curriculum','text-success'],
            ['bi-check-circle-fill','Safe Environment','text-success'],
          ] as [$icon,$text,$cls]): ?>
          <div class="col-6">
            <div class="d-flex align-items-center gap-2">
              <i class="bi <?= $icon ?> <?= $cls ?>"></i>
              <span class="fw-medium"><?= $text ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-4">
          <a href="<?= SITE_URL ?>/public/admission.php"
             class="btn btn-primary btn-lg px-4 me-2">
            <i class="bi bi-pencil-square me-2"></i>Apply Now
          </a>
          <a href="#contact" class="btn btn-outline-secondary btn-lg px-4">
            <i class="bi bi-telephone me-2"></i>Contact Us
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     WHY CHOOSE US
════════════════════════════════════════════ -->
<section id="features" style="padding:80px 0; background:#fff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge">Why Choose Us</div>
      <h2 class="section-title">What Makes Us Different</h2>
      <div class="section-divider mx-auto"></div>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['bi-award-fill','bg-primary bg-opacity-10 text-primary',    'Academic Excellence',   'Consistently high results with personalised attention to every student\'s learning journey.'],
        ['bi-shield-check-fill','bg-success bg-opacity-10 text-success','Safe & Secure Campus', 'CCTV-monitored, gated campus with trained staff ensuring student safety at all times.'],
        ['bi-laptop-fill','bg-info bg-opacity-10 text-info',          'Smart Classrooms',      'Technology-enabled learning with digital boards, online portals, and modern labs.'],
        ['bi-trophy-fill','bg-warning bg-opacity-10 text-warning',    'Sports & Activities',   'Comprehensive sports programme, cultural events, and clubs nurturing all-round development.'],
        ['bi-people-fill','bg-danger bg-opacity-10 text-danger',      'Expert Teachers',       'Qualified, experienced educators dedicated to inspiring and guiding every student.'],
        ['bi-heart-fill','bg-purple bg-opacity-10 text-purple',       'Values & Character',    'Strong emphasis on ethics, discipline, and community service alongside academics.'],
      ] as [$icon,$iconCls,$title,$desc]): ?>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon <?= $iconCls ?>">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h5 class="fw-bold mb-2"><?= $title ?></h5>
          <p class="text-muted small mb-0"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!empty($gallery)): ?>
<!-- ═══════════════════════════════════════════
     GALLERY
════════════════════════════════════════════ -->
<section id="gallery" style="padding:80px 0; background:#f8f9ff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge">Our Campus</div>
      <h2 class="section-title">Photo Gallery</h2>
      <div class="section-divider mx-auto"></div>
    </div>
    <div class="row g-3">
      <?php foreach ($gallery as $img): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="gallery-item"
             onclick="openLightbox('<?= UPLOADS_URL . '/' . htmlspecialchars($img['image'], ENT_QUOTES) ?>', '<?= htmlspecialchars($img['title'] ?? '', ENT_QUOTES) ?>')">
          <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($img['image'], ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($img['title'] ?? 'Gallery', ENT_QUOTES) ?>"
               loading="lazy">
          <div class="gallery-item-overlay">
            <i class="bi bi-zoom-in"></i>
          </div>
        </div>
        <?php if (!empty($img['title'])): ?>
        <p class="text-center text-muted small mt-2 mb-0"><?= htmlspecialchars($img['title'], ENT_QUOTES) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($notices)): ?>
<!-- ═══════════════════════════════════════════
     NOTICES
════════════════════════════════════════════ -->
<section id="notices" style="padding:80px 0; background:#fff;">
  <div class="container">
    <div class="row g-5 align-items-start">
      <div class="col-lg-4">
        <div class="section-badge">Latest Updates</div>
        <h2 class="section-title">School Notices &amp; Events</h2>
        <div class="section-divider mb-4"></div>
        <p class="text-muted">Stay up to date with the latest announcements, exam schedules, events, and holidays from the school.</p>
        <a href="<?= SITE_URL ?>/public/admission.php" class="btn btn-primary">
          <i class="bi bi-pencil-square me-2"></i>Apply for Admission
        </a>
      </div>
      <div class="col-lg-8">
        <?php foreach ($notices as $n):
          $cat   = $n['category'] ?? 'general';
          $color = $notice_colors[$cat] ?? 'primary';
        ?>
        <div class="notice-card" style="border-left-color: var(--bs-<?= $color ?>);">
          <div class="d-flex align-items-start gap-3">
            <div>
              <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> text-capitalize mb-1">
                <?= htmlspecialchars($cat, ENT_QUOTES) ?>
              </span>
              <h6 class="fw-semibold mb-1"><?= htmlspecialchars($n['title'], ENT_QUOTES) ?></h6>
              <?php if (!empty($n['body'])): ?>
              <p class="text-muted small mb-0"><?= htmlspecialchars(mb_substr($n['body'], 0, 120), ENT_QUOTES) ?><?= mb_strlen($n['body']) > 120 ? '…' : '' ?></p>
              <?php endif; ?>
            </div>
            <div class="ms-auto text-nowrap text-muted small">
              <?= date('d M', strtotime($n['created_at'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════
     ADMISSION CTA
════════════════════════════════════════════ -->
<section class="cta-section py-5">
  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center">
      <div class="col-lg-8 text-white">
        <h2 class="fw-bold mb-2" style="font-size:clamp(1.5rem,3vw,2.2rem);">
          <?= lp($s,'lp_cta_title','Start Your Journey With Us') ?>
        </h2>
        <p class="mb-0 opacity-75"><?= lp($s,'lp_cta_text','Applications for the new academic session are now open.') ?></p>
      </div>
      <div class="col-lg-4 mt-4 mt-lg-0 text-lg-end d-flex flex-wrap gap-2 justify-content-lg-end">
        <a href="<?= SITE_URL ?>/public/admission.php" class="btn btn-warning btn-lg fw-semibold px-4">
          <i class="bi bi-pencil-square me-2"></i>Apply Now
        </a>
        <a href="<?= SITE_URL ?>/public/admission-status.php" class="btn btn-outline-light btn-lg px-4">
          <i class="bi bi-search me-2"></i>Track Status
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     CONTACT
════════════════════════════════════════════ -->
<section id="contact" style="padding:80px 0; background:#f8f9ff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge">Get In Touch</div>
      <h2 class="section-title">Contact Us</h2>
      <div class="section-divider mx-auto"></div>
    </div>
    <div class="row g-4 justify-content-center">
      <?php foreach ([
        ['bi-geo-alt-fill','text-primary','bg-primary','Address',    lp($s,'contact_address','123 School Street, City')],
        ['bi-telephone-fill','text-success','bg-success','Phone',     lp($s,'contact_phone','+91 9000000000')],
        ['bi-envelope-fill','text-warning','bg-warning','Email',      lp($s,'contact_email','info@school.com')],
      ] as [$icon,$tc,$bg,$label,$value]): ?>
      <div class="col-12 col-md-4">
        <div class="text-center p-4 bg-white rounded-4 shadow-sm h-100">
          <div class="mx-auto mb-3" style="width:56px;height:56px;border-radius:14px;background:var(--bs-<?= str_replace(['text-','bg-'],'',$bg) ?>);opacity:.1;"></div>
          <div class="mb-3" style="margin-top:-56px;padding-top:12px;">
            <i class="bi <?= $icon ?> <?= $tc ?>" style="font-size:1.6rem;"></i>
          </div>
          <h6 class="fw-bold"><?= $label ?></h6>
          <p class="text-muted small mb-0"><?= $value ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     FOOTER
════════════════════════════════════════════ -->
<footer class="site-footer pt-5 pb-3">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <?php if (!empty($s['site_logo'])): ?>
            <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($s['site_logo'], ENT_QUOTES) ?>" height="36" class="rounded">
          <?php else: ?>
            <div style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-mortarboard-fill text-white"></i>
            </div>
          <?php endif; ?>
          <span class="text-white fw-bold fs-5"><?= $site_name ?></span>
        </div>
        <p class="small"><?= lp($s,'site_tagline','Empowering Education') ?></p>
        <p class="small"><?= lp($s,'contact_address','123 School Street, City') ?></p>
      </div>
      <div class="col-lg-2 col-6">
        <h6 class="fw-semibold mb-3">Quick Links</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="#about">About Us</a></li>
          <li class="mb-2"><a href="#features">Why Choose Us</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/public/admission.php">Admission</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/public/admission-status.php">Track Status</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-6">
        <h6 class="fw-semibold mb-3">Portals</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-person-circle me-1"></i>Student Login</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-person-badge me-1"></i>Teacher Login</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-people me-1"></i>Parent Login</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-shield-lock me-1"></i>Admin Login</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h6 class="fw-semibold mb-3">Contact</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><i class="bi bi-telephone me-2 text-primary"></i><?= lp($s,'contact_phone','+91 9000000000') ?></li>
          <li class="mb-2"><i class="bi bi-envelope me-2 text-primary"></i><?= lp($s,'contact_email','info@school.com') ?></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom pt-3 text-center">
      <p class="small mb-0"><?= lp($s,'footer_text','© 2026 School ERP. All rights reserved.') ?></p>
    </div>
  </div>
</footer>

<!-- Lightbox -->
<div id="lightbox" onclick="if(event.target===this)closeLightbox()">
  <span id="lightbox-close" onclick="closeLightbox()">&times;</span>
  <img id="lightbox-img" src="" alt="">
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navbar scroll effect
window.addEventListener('scroll', () => {
  document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 60);
});

// Lightbox
function openLightbox(src, alt) {
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox-img').alt = alt || '';
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      // close mobile nav
      const nav = document.getElementById('navMenu');
      if (nav.classList.contains('show')) {
        bootstrap.Collapse.getInstance(nav)?.hide();
      }
    }
  });
});

// Counter animation
function animateCounters() {
  document.querySelectorAll('.stat-number[data-target]').forEach(el => {
    const target = parseFloat(el.dataset.target);
    const suffix = el.dataset.suffix || '';
    let current  = 0;
    const step   = target / 60;
    const timer  = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = Math.floor(current) + suffix;
      if (current >= target) clearInterval(timer);
    }, 25);
  });
}
// Trigger when stats section is visible
const statsObs = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) { animateCounters(); statsObs.disconnect(); } });
}, { threshold: 0.3 });
const statsSection = document.querySelector('.stats-strip');
if (statsSection) statsObs.observe(statsSection);
</script>
</body>
</html>
