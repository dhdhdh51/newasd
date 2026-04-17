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
    $notices = $pdo->query("SELECT * FROM notices WHERE is_active=1 ORDER BY created_at DESC LIMIT 4")->fetchAll();
}

// Testimonials
$testimonials = [];
if (($s['lp_show_testimonials'] ?? '1') === '1') {
    $testimonials = $pdo->query("SELECT * FROM testimonials WHERE is_active=1 ORDER BY sort_order ASC, id ASC LIMIT 6")->fetchAll();
}

$site_name = lp($s, 'site_name', 'School ERP');
$hero_img  = !empty($s['lp_hero_image']) ? UPLOADS_URL . '/' . htmlspecialchars($s['lp_hero_image'], ENT_QUOTES) : '';
$about_img = !empty($s['lp_about_image']) ? UPLOADS_URL . '/' . htmlspecialchars($s['lp_about_image'], ENT_QUOTES) : '';

$notice_meta = [
    'general'   => ['#6366f1','rgba(99,102,241,.12)','bi-info-circle'],
    'exam'      => ['#f59e0b','rgba(245,158,11,.12)', 'bi-clipboard2-data'],
    'event'     => ['#10b981','rgba(16,185,129,.12)', 'bi-calendar-event'],
    'holiday'   => ['#ef4444','rgba(239,68,68,.12)',  'bi-calendar-heart'],
    'admission' => ['#06b6d4','rgba(6,182,212,.12)',  'bi-person-plus'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= lp($s,'site_name','School ERP') ?> — <?= lp($s,'site_tagline','Empowering Education') ?></title>
  <meta name="description" content="<?= lp($s,'lp_hero_subtitle','Quality education for every student.') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
  <style>
    html,body{font-family:'Poppins',sans-serif!important;scroll-behavior:smooth;}
    *,*::before,*::after{box-sizing:border-box;}

    /* ── Navbar ── */
    #mainNav{background:linear-gradient(135deg,#1e1b4b,#312e81)!important;border-bottom:1px solid rgba(255,255,255,.07);transition:background .35s,box-shadow .35s,padding .35s;padding:14px 0;font-family:'Poppins',sans-serif;}
    #mainNav.scrolled{background:linear-gradient(135deg,rgba(30,27,75,.97),rgba(49,46,129,.97))!important;box-shadow:0 4px 32px rgba(99,102,241,.25);padding:8px 0;}
    .nav-brand-circle{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(99,102,241,.45);flex-shrink:0;}
    .lp-nav-link{font-family:'Poppins',sans-serif!important;font-weight:500;font-size:.875rem;color:rgba(255,255,255,.85)!important;padding:7px 14px!important;border-radius:8px;transition:background .2s,color .2s;}
    .lp-nav-link:hover,.lp-nav-link.active{color:#fff!important;background:rgba(255,255,255,.12);}
    @media(max-width:991.98px){
      #mainNav{padding:10px 0;}
      #navMenu.show,#navMenu.collapsing{background:rgba(15,12,41,.98);border-radius:0 0 16px 16px;padding:12px 16px;margin-top:8px;border-top:1px solid rgba(255,255,255,.07);}
      .lp-btn-login,.lp-btn-apply{width:100%;text-align:center;margin-top:6px;}
    }

    /* ── Hero ── */
    .hero-section{min-height:100vh;display:flex;align-items:center;position:relative;overflow:hidden;background:linear-gradient(135deg,#0f0c29 0%,#1e1b4b 40%,#312e81 70%,#4f46e5 100%);}
    .hero-bg{position:absolute;inset:0;background-size:cover;background-position:center;}
    .hero-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(10,8,30,.85) 0%,rgba(99,102,241,.3) 100%);}
    .hero-content{position:relative;z-index:2;}
    .hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(8px);color:#a5b4fc;border-radius:50px;padding:7px 20px;font-size:.78rem;letter-spacing:.1em;text-transform:uppercase;font-weight:500;margin-bottom:20px;}
    .hero-title{font-size:clamp(2.4rem,5.5vw,4.2rem);font-weight:900;line-height:1.1;color:#fff;letter-spacing:-.03em;margin-bottom:20px;}
    .hero-title span{color:#a5b4fc;}
    .hero-sub{font-size:clamp(1rem,2vw,1.2rem);color:rgba(255,255,255,.78);max-width:540px;line-height:1.75;margin-bottom:36px;}
    .hero-stat-card{background:rgba(255,255,255,.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.18);border-radius:18px;padding:22px;}
    .hero-stat-num{font-size:2rem;font-weight:900;color:#fff;line-height:1;}
    .hero-stat-lbl{color:rgba(255,255,255,.65);font-size:.78rem;margin-top:4px;}
    .hero-scroll-hint{position:absolute;bottom:32px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,.4);font-size:.75rem;text-align:center;z-index:2;animation:bounce 2s infinite;}
    @keyframes bounce{0%,100%{transform:translateX(-50%) translateY(0);}50%{transform:translateX(-50%) translateY(8px);}}

    /* ── Buttons ── */
    .btn-hero-primary{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:.9rem;padding:14px 32px;border-radius:12px;text-decoration:none;box-shadow:0 8px 28px rgba(99,102,241,.5);transition:box-shadow .2s,transform .15s;}
    .btn-hero-primary:hover{box-shadow:0 12px 36px rgba(99,102,241,.7);transform:translateY(-2px);color:#fff;}
    .btn-hero-outline{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.1);backdrop-filter:blur(6px);border:1.5px solid rgba(255,255,255,.35);color:#fff;font-family:'Poppins',sans-serif;font-weight:600;font-size:.9rem;padding:13px 28px;border-radius:12px;text-decoration:none;transition:background .2s,border-color .2s;}
    .btn-hero-outline:hover{background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.6);color:#fff;}
    .hero-quick-link{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:8px 16px;color:rgba(255,255,255,.8);text-decoration:none;font-size:.8rem;font-family:'Poppins',sans-serif;transition:background .2s,color .2s;}
    .hero-quick-link:hover{background:rgba(255,255,255,.16);color:#fff;}
    .btn-grad{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;color:#fff;font-family:'Poppins',sans-serif;font-weight:600;padding:12px 28px;border-radius:10px;text-decoration:none;box-shadow:0 6px 20px rgba(99,102,241,.4);transition:box-shadow .2s,transform .15s;}
    .btn-grad:hover{box-shadow:0 10px 28px rgba(99,102,241,.6);transform:translateY(-2px);color:#fff;}
    .btn-outline-ind{display:inline-flex;align-items:center;gap:8px;background:transparent;border:2px solid rgba(99,102,241,.4);color:#6366f1;font-family:'Poppins',sans-serif;font-weight:600;padding:11px 24px;border-radius:10px;text-decoration:none;transition:border-color .2s,background .2s;}
    .btn-outline-ind:hover{border-color:#6366f1;background:rgba(99,102,241,.06);color:#6366f1;}

    /* ── Section helpers ── */
    .sec-badge{display:inline-block;background:rgba(99,102,241,.1);color:#6366f1;border-radius:50px;padding:5px 18px;font-size:.72rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px;font-family:'Poppins',sans-serif;}
    .sec-title{font-family:'Poppins',sans-serif;font-size:clamp(1.6rem,3vw,2.4rem);font-weight:800;color:#1a2340;line-height:1.2;letter-spacing:-.02em;}
    .sec-title-white{color:#fff;}
    .sec-divider{width:50px;height:4px;background:linear-gradient(90deg,#6366f1,#8b5cf6);border-radius:2px;margin:14px 0 0;}

    /* ── About ── */
    .about-img-wrap{position:relative;border-radius:24px;}
    .about-img-wrap img,.about-img-ph{border-radius:24px;box-shadow:0 20px 60px rgba(99,102,241,.2);}
    .about-float-badge{position:absolute;bottom:-16px;right:-16px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:18px;padding:18px 22px;text-align:center;min-width:110px;box-shadow:0 10px 30px rgba(99,102,241,.45);font-family:'Poppins',sans-serif;}
    .about-float-badge .big{font-size:1.9rem;font-weight:900;line-height:1;}
    .about-float-badge .sm{font-size:.7rem;opacity:.88;}
    .about-check{display:flex;align-items:center;gap:10px;}
    .about-check i{color:#10b981;font-size:1.1rem;flex-shrink:0;}
    .about-check span{font-weight:500;color:#1e293b;font-size:.9rem;}

    /* ── Features ── */
    .feature-card{background:rgba(255,255,255,.88);backdrop-filter:blur(12px);border:1px solid rgba(99,102,241,.12);border-radius:20px;padding:28px 24px;transition:transform .25s,box-shadow .25s;height:100%;}
    .feature-card:hover{transform:translateY(-6px);box-shadow:0 16px 44px rgba(99,102,241,.18);}
    .feature-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:18px;}

    /* ── Gallery ── */
    .gallery-item{position:relative;overflow:hidden;border-radius:14px;cursor:pointer;aspect-ratio:1;box-shadow:0 4px 16px rgba(99,102,241,.12);transition:transform .3s,box-shadow .3s;}
    .gallery-item:hover{transform:scale(1.03);box-shadow:0 10px 30px rgba(99,102,241,.25);}
    .gallery-item img{width:100%;height:100%;object-fit:cover;transition:transform .4s;display:block;}
    .gallery-item:hover img{transform:scale(1.08);}
    .gallery-item-overlay{position:absolute;inset:0;background:linear-gradient(135deg,rgba(99,102,241,.75),rgba(139,92,246,.65));display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s;color:#fff;font-size:1.8rem;}
    .gallery-item:hover .gallery-item-overlay{opacity:1;}

    /* ── Notices ── */
    .notice-card{background:rgba(255,255,255,.88);backdrop-filter:blur(10px);border:1px solid rgba(99,102,241,.1);border-radius:14px;padding:16px 20px;margin-bottom:12px;box-shadow:0 3px 12px rgba(99,102,241,.08);transition:transform .2s;border-left:4px solid #6366f1;}
    .notice-card:hover{transform:translateX(5px);}

    /* ── Testimonials ── */
    .testi-card{background:rgba(255,255,255,.88);backdrop-filter:blur(12px);border:1px solid rgba(99,102,241,.12);border-radius:20px;padding:28px;border-top:4px solid #6366f1;height:100%;transition:transform .25s,box-shadow .25s;}
    .testi-card:hover{transform:translateY(-4px);box-shadow:0 14px 36px rgba(99,102,241,.18);}

    /* ── CTA ── */
    .cta-section{background:linear-gradient(135deg,#1e1b4b 0%,#4f46e5 50%,#7c3aed 100%);position:relative;overflow:hidden;padding:80px 0;}
    .cta-section::before{content:'';position:absolute;top:-80px;right:-80px;width:320px;height:320px;border-radius:50%;background:rgba(255,255,255,.06);}
    .cta-section::after{content:'';position:absolute;bottom:-60px;left:-60px;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.04);}

    /* ── Contact ── */
    .contact-item{background:rgba(255,255,255,.88);backdrop-filter:blur(12px);border:1px solid rgba(99,102,241,.12);border-radius:20px;padding:28px 20px;text-align:center;box-shadow:0 4px 18px rgba(99,102,241,.1);transition:transform .25s,box-shadow .25s;}
    .contact-item:hover{transform:translateY(-5px);box-shadow:0 12px 32px rgba(99,102,241,.2);}

    /* ── Footer ── */
    .lp-footer{background:linear-gradient(135deg,#0f0c29 0%,#1e1b4b 55%,#312e81 100%);color:rgba(255,255,255,.65);font-family:'Poppins',sans-serif;}
    .lp-footer a{color:rgba(255,255,255,.55);text-decoration:none;font-size:.85rem;display:inline-flex;align-items:center;gap:6px;transition:color .2s,padding-left .2s;}
    .lp-footer a:hover{color:#a5b4fc;padding-left:4px;}
    .lp-footer-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(99,102,241,.4),rgba(139,92,246,.4),transparent);}

    /* ── Lightbox ── */
    #lightbox{display:none;position:fixed;inset:0;background:rgba(10,8,30,.95);z-index:9999;align-items:center;justify-content:center;flex-direction:column;backdrop-filter:blur(8px);}
    #lightbox.open{display:flex;}
    #lb-close{position:absolute;top:20px;right:28px;color:rgba(255,255,255,.7);font-size:2rem;cursor:pointer;width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.1);border-radius:12px;transition:background .2s;}
    #lb-close:hover{background:rgba(255,255,255,.2);color:#fff;}
    #lb-img{max-width:90vw;max-height:80vh;border-radius:12px;object-fit:contain;box-shadow:0 20px 60px rgba(0,0,0,.5);}
  </style>
</head>
<body style="font-family:'Poppins',sans-serif;">

<!-- ═══════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════ -->
<nav id="mainNav" class="navbar navbar-expand-lg fixed-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="<?= SITE_URL ?>/">
      <div class="nav-brand-circle">
        <?php if (!empty($s['site_logo'])): ?>
          <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($s['site_logo'], ENT_QUOTES) ?>" height="26" alt="Logo" class="rounded">
        <?php else: ?>
          <i class="bi bi-mortarboard-fill text-white" style="font-size:1.1rem;"></i>
        <?php endif; ?>
      </div>
      <span style="font-family:'Poppins',sans-serif;font-weight:700;font-size:1rem;color:#fff;"><?= $site_name ?></span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <i class="bi bi-list text-white fs-3"></i>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 py-2 py-lg-0">
        <li class="nav-item"><a class="lp-nav-link nav-link" href="#about">About</a></li>
        <li class="nav-item"><a class="lp-nav-link nav-link" href="#features">Why Us</a></li>
        <?php if (!empty($gallery)): ?>
        <li class="nav-item"><a class="lp-nav-link nav-link" href="#gallery">Gallery</a></li>
        <?php endif; ?>
        <?php if (!empty($notices)): ?>
        <li class="nav-item"><a class="lp-nav-link nav-link" href="#notices">Notices</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="lp-nav-link nav-link" href="#contact">Contact</a></li>
        <li class="nav-item ms-lg-2">
          <a class="btn-hero-outline lp-btn-login" href="<?= SITE_URL ?>/auth/login.php" style="padding:8px 20px;font-size:.82rem;">
            <i class="bi bi-box-arrow-in-right"></i> Login
          </a>
        </li>
        <li class="nav-item ms-lg-1">
          <a class="btn-hero-primary lp-btn-apply" href="<?= SITE_URL ?>/public/admission.php" style="padding:8px 20px;font-size:.82rem;">
            <i class="bi bi-pencil-square"></i> Apply Now
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
             class="btn-hero-primary">
            <i class="bi bi-pencil-square"></i>
            <?= lp($s,'lp_hero_btn1_text','Apply for Admission') ?>
          </a>
          <a href="<?= SITE_URL . lp($s,'lp_hero_btn2_url','/public/admission-status.php') ?>"
             class="btn-hero-outline">
            <i class="bi bi-search"></i>
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
<section id="about" style="padding:80px 0;background:linear-gradient(135deg,rgba(99,102,241,.04),rgba(139,92,246,.03));">
  <div class="container">
    <div class="row align-items-center g-5">
      <!-- Image side -->
      <div class="col-lg-5">
        <div class="about-img-wrap">
          <?php if ($about_img): ?>
            <img src="<?= $about_img ?>" alt="About" class="img-fluid w-100 about-img-ph" style="height:420px;object-fit:cover;">
          <?php else: ?>
            <div class="about-img-ph" style="height:420px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-building" style="font-size:6rem;color:rgba(255,255,255,.3);"></i>
            </div>
          <?php endif; ?>
          <div class="about-float-badge">
            <div class="big"><?= lp($s,'lp_stat_years','25+') ?></div>
            <div class="sm">Years of<br>Excellence</div>
          </div>
        </div>
      </div>

      <!-- Text side -->
      <div class="col-lg-7">
        <div class="sec-badge">About Us</div>
        <h2 class="sec-title"><?= lp($s,'lp_about_title','About Our School') ?></h2>
        <div class="sec-divider mb-4"></div>
        <p style="color:#64748b;font-size:1rem;line-height:1.8;font-family:'Poppins',sans-serif;">
          <?= nl2br(lp($s,'lp_about_text','We are committed to providing a nurturing, inclusive learning environment.')) ?>
        </p>
        <div class="row g-3 mt-2">
          <?php foreach (['Experienced Faculty','Modern Facilities','Holistic Curriculum','Safe Environment'] as $text): ?>
          <div class="col-6">
            <div class="about-check">
              <i class="bi bi-check-circle-fill"></i>
              <span><?= $text ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-4 d-flex flex-wrap gap-3">
          <a href="<?= SITE_URL ?>/public/admission.php" class="btn-grad">
            <i class="bi bi-pencil-square"></i>Apply Now
          </a>
          <a href="#contact" class="btn-outline-ind">
            <i class="bi bi-telephone"></i>Contact Us
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
<section id="features" style="padding:80px 0;background:#f0f2ff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-badge">Why Choose Us</div>
      <h2 class="sec-title">What Makes Us Different</h2>
      <div class="sec-divider mx-auto"></div>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['bi-award-fill',        '#6366f1','rgba(99,102,241,.12)', 'Academic Excellence',  'Consistently high results with personalised attention to every student\'s learning journey.'],
        ['bi-shield-check-fill', '#10b981','rgba(16,185,129,.12)', 'Safe & Secure Campus', 'CCTV-monitored, gated campus with trained staff ensuring student safety at all times.'],
        ['bi-laptop-fill',       '#06b6d4','rgba(6,182,212,.12)',  'Smart Classrooms',     'Technology-enabled learning with digital boards, online portals, and modern labs.'],
        ['bi-trophy-fill',       '#f59e0b','rgba(245,158,11,.12)', 'Sports & Activities',  'Comprehensive sports programme, cultural events, and clubs nurturing all-round development.'],
        ['bi-people-fill',       '#ef4444','rgba(239,68,68,.12)',  'Expert Teachers',      'Qualified, experienced educators dedicated to inspiring and guiding every student.'],
        ['bi-heart-fill',        '#8b5cf6','rgba(139,92,246,.12)', 'Values & Character',   'Strong emphasis on ethics, discipline, and community service alongside academics.'],
      ] as [$icon,$color,$bg,$title,$desc]): ?>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon" style="background:<?= $bg ?>;color:<?= $color ?>;">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h5 style="font-family:'Poppins',sans-serif;font-weight:700;color:#1e293b;margin-bottom:8px;"><?= $title ?></h5>
          <p style="color:#64748b;font-size:.875rem;margin-bottom:0;line-height:1.7;"><?= $desc ?></p>
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
<section id="gallery" style="padding:80px 0;background:linear-gradient(135deg,rgba(99,102,241,.05),rgba(139,92,246,.04));">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-badge">Our Campus</div>
      <h2 class="sec-title">Photo Gallery</h2>
      <div class="sec-divider mx-auto"></div>
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
<section id="notices" style="padding:80px 0;background:#fff;">
  <div class="container">
    <div class="row g-5 align-items-start">
      <div class="col-lg-4">
        <div class="sec-badge">Latest Updates</div>
        <h2 class="sec-title">School Notices &amp; Events</h2>
        <div class="sec-divider mb-4"></div>
        <p style="color:#64748b;font-size:.875rem;line-height:1.75;">Stay up to date with the latest announcements, exam schedules, events, and holidays from the school.</p>
        <a href="<?= SITE_URL ?>/public/notices-page.php" class="btn-grad mt-2" style="margin-top:16px;display:inline-flex;">
          <i class="bi bi-bell"></i>View All Notices
        </a>
      </div>
      <div class="col-lg-8">
        <?php foreach ($notices as $n):
          $cat = $n['category'] ?? 'general';
          [$nc,$nb,$ni] = $notice_meta[$cat] ?? $notice_meta['general'];
        ?>
        <div class="notice-card" style="border-left-color:<?= $nc ?>;">
          <div class="d-flex align-items-start gap-3">
            <div style="width:42px;height:42px;background:<?= $nb ?>;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi <?= $ni ?>" style="color:<?= $nc ?>;font-size:1.1rem;"></i>
            </div>
            <div class="flex-grow-1">
              <span style="background:<?= $nb ?>;color:<?= $nc ?>;padding:2px 10px;border-radius:50px;font-size:.7rem;font-weight:600;font-family:'Poppins',sans-serif;text-transform:capitalize;">
                <?= htmlspecialchars($cat, ENT_QUOTES) ?>
              </span>
              <h6 style="font-family:'Poppins',sans-serif;font-weight:700;color:#1e293b;margin:6px 0 4px;"><?= htmlspecialchars($n['title'], ENT_QUOTES) ?></h6>
              <?php if (!empty($n['body'])): ?>
              <p style="color:#64748b;font-size:.875rem;margin-bottom:0;"><?= htmlspecialchars(mb_substr($n['body'], 0, 120), ENT_QUOTES) ?><?= mb_strlen($n['body']) > 120 ? '…' : '' ?></p>
              <?php endif; ?>
            </div>
            <div style="color:#94a3b8;font-size:.78rem;white-space:nowrap;flex-shrink:0;">
              <i class="bi bi-calendar2 me-1"></i><?= date('d M', strtotime($n['created_at'])) ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (($s['lp_show_courses'] ?? '1') === '1'): ?>
<!-- ═══════════════════════════════════════════
     COURSES / CLASSES
════════════════════════════════════════════ -->
<section style="padding:80px 0;background:linear-gradient(135deg,rgba(99,102,241,.04),rgba(139,92,246,.03));">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-badge">Academic Programmes</div>
      <h2 class="sec-title">Classes We Offer</h2>
      <div class="sec-divider mx-auto mb-3"></div>
      <p style="color:#64748b;font-size:.9rem;">Comprehensive education from Class 1 through Class 12, covering all major boards.</p>
    </div>
    <div class="row g-3">
      <?php
      $classGroups = [
        ['Primary (Class 1–5)',    range(1,5),   '#6366f1','rgba(99,102,241,.12)', 'bi-book-half'],
        ['Middle (Class 6–8)',     range(6,8),   '#10b981','rgba(16,185,129,.12)', 'bi-journal-text'],
        ['Secondary (Class 9–10)', range(9,10),  '#f59e0b','rgba(245,158,11,.12)', 'bi-mortarboard'],
        ['Senior (Class 11–12)',   range(11,12), '#ef4444','rgba(239,68,68,.12)',  'bi-award'],
      ];
      foreach ($classGroups as [$groupName,$classes,$color,$bg,$icon]): ?>
      <div class="col-12 col-md-6 col-lg-3">
        <div class="h-100 rounded-4 overflow-hidden" style="background:rgba(255,255,255,.88);backdrop-filter:blur(12px);border:1px solid <?= $bg ?>;box-shadow:0 4px 18px <?= $bg ?>;">
          <div class="p-3 text-white d-flex align-items-center gap-2" style="background:<?= $color ?>;">
            <i class="bi <?= $icon ?> fs-4"></i>
            <span class="fw-semibold" style="font-family:'Poppins',sans-serif;"><?= $groupName ?></span>
          </div>
          <div class="p-3">
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($classes as $c): ?>
              <span style="background:<?= $bg ?>;color:<?= $color ?>;border-radius:50px;padding:4px 14px;font-size:.78rem;font-weight:600;font-family:'Poppins',sans-serif;">
                Class <?= $c ?>
              </span>
              <?php endforeach; ?>
            </div>
            <p style="color:#64748b;font-size:.82rem;margin-top:12px;margin-bottom:0;line-height:1.6;">
              <?php $descs=[
                'Primary (Class 1–5)'    => 'Foundation skills — Maths, English, EVS, Hindi, Arts & Craft.',
                'Middle (Class 6–8)'     => 'Core subjects — Science, Social Studies, Languages, Computer.',
                'Secondary (Class 9–10)' => 'Board preparation — all streams with practical labs.',
                'Senior (Class 11–12)'   => 'Science, Commerce & Arts streams with expert faculty.',
              ]; echo $descs[$groupName] ?? ''; ?>
            </p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="<?= SITE_URL ?>/public/admission.php" class="btn-grad">
        <i class="bi bi-pencil-square"></i>Apply for Admission
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($testimonials)): ?>
<!-- ═══════════════════════════════════════════
     TESTIMONIALS
════════════════════════════════════════════ -->
<section style="padding:80px 0;background:#f0f2ff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-badge">What They Say</div>
      <h2 class="sec-title">Student &amp; Parent Testimonials</h2>
      <div class="sec-divider mx-auto"></div>
    </div>
    <div class="row g-4">
      <?php foreach ($testimonials as $t): ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="testi-card">
          <!-- Stars -->
          <div class="mb-3">
            <?php for ($i=1;$i<=5;$i++): ?>
            <i class="bi bi-star-fill" style="color:<?= $i<=(int)$t['rating']?'#f59e0b':'#cbd5e1' ?>;font-size:.9rem;"></i>
            <?php endfor; ?>
          </div>
          <!-- Quote -->
          <p style="color:#64748b;font-size:.9rem;line-height:1.75;font-style:italic;margin-bottom:20px;">
            &ldquo;<?= htmlspecialchars($t['message'],ENT_QUOTES) ?>&rdquo;
          </p>
          <!-- Person -->
          <div class="d-flex align-items-center gap-3">
            <?php if (!empty($t['photo'])): ?>
            <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($t['photo'],ENT_QUOTES) ?>"
                 class="rounded-circle" width="48" height="48" style="object-fit:cover;border:2px solid rgba(99,102,241,.2);">
            <?php else: ?>
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                 style="width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#8b5cf6);font-size:1.1rem;flex-shrink:0;">
              <?= strtoupper(mb_substr($t['name'],0,1)) ?>
            </div>
            <?php endif; ?>
            <div>
              <div style="font-weight:700;color:#1e293b;font-family:'Poppins',sans-serif;font-size:.9rem;"><?= htmlspecialchars($t['name'],ENT_QUOTES) ?></div>
              <div style="color:#94a3b8;font-size:.8rem;"><?= htmlspecialchars($t['role'],ENT_QUOTES) ?></div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
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
        <a href="<?= SITE_URL ?>/public/admission.php" class="btn-hero-primary">
          <i class="bi bi-pencil-square"></i>Apply Now
        </a>
        <a href="<?= SITE_URL ?>/public/admission-status.php" class="btn-hero-outline">
          <i class="bi bi-search"></i>Track Status
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     CONTACT
════════════════════════════════════════════ -->
<section id="contact" style="padding:80px 0;background:linear-gradient(135deg,rgba(99,102,241,.04),rgba(139,92,246,.03));">
  <div class="container">
    <div class="text-center mb-5">
      <div class="sec-badge">Get In Touch</div>
      <h2 class="sec-title">Contact Us</h2>
      <div class="sec-divider mx-auto"></div>
    </div>
    <div class="row g-4 justify-content-center">
      <?php foreach ([
        ['bi-geo-alt-fill',  '#6366f1','rgba(99,102,241,.12)','Address', lp($s,'contact_address','123 School Street, City')],
        ['bi-telephone-fill','#10b981','rgba(16,185,129,.12)', 'Phone',   lp($s,'contact_phone','+91 9000000000')],
        ['bi-envelope-fill', '#f59e0b','rgba(245,158,11,.12)', 'Email',   lp($s,'contact_email','info@school.com')],
      ] as [$icon,$color,$bg,$label,$value]): ?>
      <div class="col-12 col-md-4">
        <div class="contact-item">
          <div style="width:56px;height:56px;border-radius:16px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:1.5rem;"></i>
          </div>
          <h6 style="font-family:'Poppins',sans-serif;font-weight:700;color:#1e293b;margin-bottom:6px;"><?= $label ?></h6>
          <p style="color:#64748b;font-size:.875rem;margin-bottom:0;"><?= $value ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════
     FOOTER
════════════════════════════════════════════ -->
<footer class="lp-footer pt-5 pb-3">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <?php if (!empty($s['site_logo'])): ?>
            <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($s['site_logo'], ENT_QUOTES) ?>" height="36" class="rounded">
          <?php else: ?>
            <div class="nav-brand-circle" style="width:36px;height:36px;border-radius:10px;">
              <i class="bi bi-mortarboard-fill text-white" style="font-size:.9rem;"></i>
            </div>
          <?php endif; ?>
          <span style="color:#fff;font-weight:700;font-size:1.1rem;font-family:'Poppins',sans-serif;"><?= $site_name ?></span>
        </div>
        <p style="font-size:.875rem;margin-bottom:8px;"><?= lp($s,'site_tagline','Empowering Education') ?></p>
        <p style="font-size:.875rem;"><?= lp($s,'contact_address','123 School Street, City') ?></p>
      </div>
      <div class="col-lg-2 col-6">
        <h6 style="color:rgba(255,255,255,.9);font-family:'Poppins',sans-serif;font-weight:600;margin-bottom:16px;">Quick Links</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="#about">About Us</a></li>
          <li class="mb-2"><a href="#features">Why Choose Us</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/public/admission.php">Admission</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/public/admission-status.php">Track Status</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-6">
        <h6 style="color:rgba(255,255,255,.9);font-family:'Poppins',sans-serif;font-weight:600;margin-bottom:16px;">Portals</h6>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-person-circle"></i> Student Login</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-person-badge"></i> Teacher Login</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-people"></i> Parent Login</a></li>
          <li class="mb-2"><a href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-shield-lock"></i> Admin Login</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h6 style="color:rgba(255,255,255,.9);font-family:'Poppins',sans-serif;font-weight:600;margin-bottom:16px;">Contact</h6>
        <ul class="list-unstyled">
          <li class="mb-2" style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.65);font-size:.875rem;"><i class="bi bi-telephone" style="color:#a5b4fc;"></i><?= lp($s,'contact_phone','+91 9000000000') ?></li>
          <li class="mb-2" style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.65);font-size:.875rem;"><i class="bi bi-envelope" style="color:#a5b4fc;"></i><?= lp($s,'contact_email','info@school.com') ?></li>
        </ul>
      </div>
    </div>
    <div class="lp-footer-divider mb-3"></div>
    <div class="text-center">
      <p style="font-size:.85rem;margin-bottom:0;"><?= lp($s,'footer_text','© 2026 School ERP. All rights reserved.') ?></p>
    </div>
  </div>
</footer>

<!-- Lightbox -->
<div id="lightbox" onclick="if(event.target===this)closeLightbox()">
  <span id="lb-close" onclick="closeLightbox()" title="Close">&times;</span>
  <img id="lb-img" src="" alt="">
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Navbar scroll effect
window.addEventListener('scroll', () => {
  document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 60);
});

// Lightbox
function openLightbox(src, alt) {
  document.getElementById('lb-img').src = src;
  document.getElementById('lb-img').alt = alt || '';
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.getElementById('lb-img').src = '';
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
