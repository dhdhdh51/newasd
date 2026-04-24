<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title = 'About Us';
$active_nav = 'about';
$meta_desc  = 'Learn about our school — our mission, vision, values, and dedicated faculty.';
$about_img  = get_setting('lp_about_image','');
$mission    = get_setting('about_mission','To provide every child with an exceptional education that ignites curiosity, builds character, and opens doors to a limitless future.');
$vision     = get_setting('about_vision','To be a nationally recognised institution celebrated for academic excellence, holistic development, and producing future-ready citizens.');
$about_text = get_setting('lp_about_text','We are committed to providing a nurturing, stimulating learning environment where every student is inspired to discover their potential. With experienced faculty, modern facilities, and a culture of excellence, we prepare young minds for the challenges and opportunities of tomorrow.');
$extra_head = '<style>
  body { background: #f0f2ff; }

  /* ── Premium breadcrumb strip ── */
  .pub-bc-strip {
    background: linear-gradient(90deg,rgba(99,102,241,.07),rgba(139,92,246,.07));
    border-bottom: 1px solid rgba(99,102,241,.12);
    padding: 10px 0;
  }

  /* ── Story section ── */
  .story-img-wrap {
    position:relative; border-radius:24px; overflow:visible;
  }
  .story-img-wrap img, .story-img-placeholder {
    border-radius:24px; box-shadow:0 20px 60px rgba(99,102,241,.2);
  }
  .story-float-badge {
    position:absolute; bottom:-20px; right:-20px;
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff; border-radius:20px; padding:18px 22px;
    box-shadow:0 10px 30px rgba(99,102,241,.4);
    text-align:center; min-width:110px;
    font-family:"Poppins",sans-serif;
  }
  .story-float-badge .big { font-size:1.9rem; font-weight:800; line-height:1; }
  .story-float-badge .sm  { font-size:.72rem; opacity:.85; }

  /* ── Stat chips ── */
  .stat-chip {
    background:rgba(255,255,255,.9);
    backdrop-filter:blur(10px);
    border:1px solid rgba(99,102,241,.15);
    border-radius:16px; padding:16px 12px;
    text-align:center;
    box-shadow:0 4px 20px rgba(99,102,241,.1);
    transition:transform .25s, box-shadow .25s;
  }
  .stat-chip:hover { transform:translateY(-4px); box-shadow:0 10px 30px rgba(99,102,241,.2); }
  .stat-chip .num { font-size:1.6rem; font-weight:800; color:#6366f1; line-height:1; }
  .stat-chip .lbl { font-size:.72rem; color:#64748b; font-weight:500; }

  /* ── Mission / Vision cards ── */
  .mv-card {
    border-radius:24px; padding:40px 36px; color:#fff; position:relative; overflow:hidden;
  }
  .mv-card::before {
    content:""; position:absolute; top:-60px; right:-60px;
    width:200px; height:200px; border-radius:50%;
    background:rgba(255,255,255,.08);
  }
  .mv-icon-box {
    width:64px; height:64px;
    background:rgba(255,255,255,.2); border-radius:18px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.7rem; margin-bottom:24px;
  }

  /* ── Value cards ── */
  .value-card {
    background:rgba(255,255,255,.85);
    backdrop-filter:blur(12px);
    border:1px solid rgba(99,102,241,.12);
    border-radius:20px; padding:28px 24px;
    transition:transform .25s, box-shadow .25s;
    height:100%;
  }
  .value-card:hover { transform:translateY(-5px); box-shadow:0 16px 40px rgba(99,102,241,.18); }
  .value-icon {
    width:52px; height:52px; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; margin-bottom:16px;
    background:rgba(99,102,241,.1); color:#6366f1;
  }

  /* ── Facility grid ── */
  .facility-item {
    background:rgba(255,255,255,.85);
    backdrop-filter:blur(10px);
    border:1px solid rgba(99,102,241,.12);
    border-radius:16px; padding:20px 12px;
    text-align:center;
    transition:transform .25s, box-shadow .25s;
  }
  .facility-item:hover { transform:translateY(-4px); box-shadow:0 10px 28px rgba(99,102,241,.15); }
  .facility-item i { color:#6366f1; font-size:1.8rem; display:block; margin-bottom:8px; }
  .facility-item span { font-size:.82rem; font-weight:600; color:#1e293b; }

  /* ── CTA ── */
  .cta-about {
    background:linear-gradient(135deg,#1e1b4b 0%,#4f46e5 50%,#7c3aed 100%);
    position:relative; overflow:hidden; padding:70px 0;
  }
  .cta-about::before {
    content:""; position:absolute; top:-80px; right:-80px;
    width:320px; height:320px; border-radius:50%;
    background:rgba(255,255,255,.06);
  }
  .cta-about::after {
    content:""; position:absolute; bottom:-60px; left:-60px;
    width:240px; height:240px; border-radius:50%;
    background:rgba(255,255,255,.04);
  }
  .btn-cta-white {
    background:#fff; color:#6366f1; font-weight:700;
    border:none; border-radius:10px; padding:12px 32px;
    box-shadow:0 8px 24px rgba(0,0,0,.15);
    transition:transform .2s, box-shadow .2s;
    font-family:"Poppins",sans-serif;
  }
  .btn-cta-white:hover { transform:translateY(-2px); box-shadow:0 12px 32px rgba(0,0,0,.2); color:#6366f1; }
  .btn-cta-outline {
    background:transparent; color:#fff; font-weight:600;
    border:2px solid rgba(255,255,255,.5); border-radius:10px; padding:11px 28px;
    transition:border-color .2s, background .2s;
    font-family:"Poppins",sans-serif;
  }
  .btn-cta-outline:hover { border-color:#fff; background:rgba(255,255,255,.1); color:#fff; }
</style>';
include INCLUDES_PATH.'public_header.php';
?>

<!-- Page Hero -->
<div class="page-hero text-center">
  <div class="container">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(8px);border-radius:50px;padding:6px 20px;font-size:.78rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(165,180,252,.9);margin-bottom:20px;font-weight:500;">
      <i class="bi bi-info-circle"></i> About Us
    </div>
    <h1 class="page-hero-title mb-3">About Our School</h1>
    <p class="page-hero-sub mb-0" style="max-width:560px;margin:0 auto;">
      Empowering students with knowledge, values, and skills since
      <strong style="color:#a5b4fc;"><?= htmlspecialchars(get_setting('lp_stat_years','25+'),ENT_QUOTES) ?> years.</strong>
    </p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="pub-bc-strip">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb pub-breadcrumb mb-0" style="background:transparent;">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <li class="breadcrumb-item active" style="color:#6366f1;">About Us</li>
      </ol>
    </nav>
  </div>
</div>

<!-- ── Story Section ── -->
<section class="py-5 py-lg-6" style="background:#f0f2ff;">
  <div class="container">
    <div class="row align-items-center g-5">

      <!-- Image col -->
      <div class="col-lg-5">
        <div class="story-img-wrap">
          <?php if ($about_img): ?>
            <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($about_img,ENT_QUOTES) ?>"
                 class="img-fluid" alt="About School" style="width:100%;max-height:440px;object-fit:cover;">
          <?php else: ?>
            <div class="story-img-placeholder d-flex align-items-center justify-content-center"
                 style="background:linear-gradient(135deg,#1e1b4b,#6366f1);height:400px;">
              <i class="bi bi-building" style="font-size:5rem;color:rgba(255,255,255,.2);"></i>
            </div>
          <?php endif; ?>
          <div class="story-float-badge">
            <div class="big"><?= htmlspecialchars(get_setting('lp_stat_years','25+'),ENT_QUOTES) ?></div>
            <div class="sm">Years of<br>Excellence</div>
          </div>
        </div>
      </div>

      <!-- Text col -->
      <div class="col-lg-7">
        <div class="section-badge" style="background:rgba(99,102,241,.1);color:#6366f1;">Our Story</div>
        <h2 class="section-title mt-2"><?= htmlspecialchars(get_setting('lp_about_title','About Our School'),ENT_QUOTES) ?></h2>
        <div class="section-divider mb-4" style="background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
        <p style="color:#475569;line-height:1.85;font-size:1rem;">
          <?= nl2br(htmlspecialchars($about_text,ENT_QUOTES)) ?>
        </p>

        <!-- Stat chips -->
        <div class="row g-3 mt-2">
          <?php foreach ([
            [get_setting('lp_stat_students','1200+'), get_setting('lp_stat_label1','Students'),  'bi-people-fill'],
            [get_setting('lp_stat_teachers','80+'),   get_setting('lp_stat_label2','Teachers'),  'bi-person-badge-fill'],
            [get_setting('lp_stat_years','25+'),      get_setting('lp_stat_label3','Years'),     'bi-award-fill'],
            [get_setting('lp_stat_success','98%'),    get_setting('lp_stat_label4','Pass Rate'), 'bi-graph-up-arrow'],
          ] as [$num,$lbl,$icon]): ?>
          <div class="col-6 col-md-3">
            <div class="stat-chip">
              <i class="bi <?= $icon ?>" style="color:#8b5cf6;font-size:1.3rem;display:block;margin-bottom:6px;"></i>
              <div class="num"><?= htmlspecialchars($num,ENT_QUOTES) ?></div>
              <div class="lbl"><?= htmlspecialchars($lbl,ENT_QUOTES) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ── Mission & Vision ── -->
<section class="py-5 py-lg-6" style="background:linear-gradient(135deg,#0f0c29 0%,#1e1b4b 55%,#312e81 100%);">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge" style="background:rgba(165,180,252,.15);color:#a5b4fc;">Our Purpose</div>
      <h2 class="section-title mt-2" style="color:#fff;">Mission &amp; Vision</h2>
      <div class="section-divider mx-auto" style="background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
    </div>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="mv-card" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
          <div class="mv-icon-box"><i class="bi bi-bullseye"></i></div>
          <h3 class="fw-bold mb-3" style="font-family:'Poppins',sans-serif;">Our Mission</h3>
          <p class="mb-0 lh-lg" style="opacity:.92;"><?= nl2br(htmlspecialchars($mission,ENT_QUOTES)) ?></p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="mv-card" style="background:linear-gradient(135deg,#0f172a,#1e3a5f);">
          <div class="mv-icon-box" style="background:rgba(99,102,241,.3);"><i class="bi bi-eye"></i></div>
          <h3 class="fw-bold mb-3" style="font-family:'Poppins',sans-serif;">Our Vision</h3>
          <p class="mb-0 lh-lg" style="opacity:.92;"><?= nl2br(htmlspecialchars($vision,ENT_QUOTES)) ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Core Values ── -->
<section class="py-5 py-lg-6" style="background:#f0f2ff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge" style="background:rgba(99,102,241,.1);color:#6366f1;">What We Stand For</div>
      <h2 class="section-title mt-2">Our Core Values</h2>
      <div class="section-divider mx-auto" style="background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['bi-heart-fill',     'Integrity',      'We uphold honesty, transparency, and ethical conduct in every aspect of school life.',   'rgba(239,68,68,.1)',  '#ef4444'],
        ['bi-lightbulb-fill', 'Excellence',     'We relentlessly pursue the highest standards in academics, sports, and all activities.',  'rgba(245,158,11,.1)', '#f59e0b'],
        ['bi-people-fill',    'Inclusivity',    'Every student is valued, respected, and given equal opportunity to discover their potential.', 'rgba(99,102,241,.1)', '#6366f1'],
        ['bi-gear-fill',      'Innovation',     'We embrace technology and creative thinking to prepare students for the world of tomorrow.', 'rgba(16,185,129,.1)', '#10b981'],
        ['bi-shield-fill',    'Responsibility', 'We nurture responsible citizenship, care for the community, and stewardship of the environment.', 'rgba(6,182,212,.1)', '#06b6d4'],
        ['bi-star-fill',      'Empowerment',    'We build confidence, leadership skills, and a growth mindset in every student.',            'rgba(139,92,246,.1)', '#8b5cf6'],
      ] as [$icon,$title,$desc,$bg,$color]): ?>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="value-card">
          <div class="value-icon" style="background:<?= $bg ?>;color:<?= $color ?>;">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h5 style="font-weight:700;color:#1e293b;margin-bottom:8px;font-family:'Poppins',sans-serif;"><?= $title ?></h5>
          <p style="color:#64748b;font-size:.875rem;margin-bottom:0;line-height:1.7;"><?= $desc ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Facilities ── -->
<section class="py-5 py-lg-6" style="background:linear-gradient(135deg,rgba(99,102,241,.05),rgba(139,92,246,.05));">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge" style="background:rgba(99,102,241,.1);color:#6366f1;">Infrastructure</div>
      <h2 class="section-title mt-2">World-Class Facilities</h2>
      <div class="section-divider mx-auto" style="background:linear-gradient(90deg,#6366f1,#8b5cf6);"></div>
    </div>
    <div class="row g-3">
      <?php foreach ([
        ['bi-laptop-fill',       'Smart Classrooms'],
        ['bi-flask-fill',        'Science Labs'],
        ['bi-book-fill',         'Library'],
        ['bi-trophy-fill',       'Sports Ground'],
        ['bi-music-note-beamed', 'Music Room'],
        ['bi-palette-fill',      'Art Studio'],
        ['bi-camera-video-fill', 'CCTV Security'],
        ['bi-wifi',              'Hi-Speed WiFi'],
        ['bi-bus-front-fill',    'School Transport'],
        ['bi-cpu-fill',          'Computer Lab'],
        ['bi-hospital-fill',     'Medical Room'],
        ['bi-tree-fill',         'Green Campus'],
      ] as [$icon,$name]): ?>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="facility-item">
          <i class="bi <?= $icon ?>"></i>
          <span><?= $name ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── CTA ── -->
<section class="cta-about">
  <div class="container text-center text-white position-relative" style="z-index:2;">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border-radius:50px;padding:5px 18px;font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;margin-bottom:20px;color:rgba(165,180,252,.9);">
      <i class="bi bi-door-open"></i> Admissions Open
    </div>
    <h2 style="font-family:'Poppins',sans-serif;font-size:clamp(1.8rem,4vw,2.6rem);font-weight:800;margin-bottom:12px;">
      Become Part of Our Family
    </h2>
    <p style="color:rgba(255,255,255,.75);max-width:520px;margin:0 auto 32px;font-size:1rem;">
      Admissions for the new academic session are now open. Join thousands of students building their future with us.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="<?= SITE_URL ?>/public/admission.php" class="btn-cta-white d-inline-flex align-items-center gap-2 text-decoration-none">
        <i class="bi bi-pencil-square"></i> Apply for Admission
      </a>
      <a href="<?= SITE_URL ?>/public/contact.php" class="btn-cta-outline d-inline-flex align-items-center gap-2 text-decoration-none">
        <i class="bi bi-telephone"></i> Contact Us
      </a>
    </div>
  </div>
</section>

<?php include INCLUDES_PATH.'public_footer.php'; ?>
