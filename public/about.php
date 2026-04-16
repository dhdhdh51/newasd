<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title = 'About Us';
$active_nav = 'about';
$meta_desc  = 'Learn about our school — our mission, vision, values, and dedicated faculty.';
$about_img  = get_setting('lp_about_image','');
$mission    = get_setting('about_mission','To provide every child with an exceptional education.');
$vision     = get_setting('about_vision','To be a leading institution nurturing future-ready citizens.');
$about_text = get_setting('lp_about_text','We are committed to providing a nurturing learning environment.');
include INCLUDES_PATH.'public_header.php';
?>

<!-- Page Hero -->
<div class="page-hero text-center text-white">
  <div class="container">
    <div style="display:inline-block;background:rgba(255,255,255,.15);border-radius:50px;padding:6px 20px;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:16px;">
      <i class="bi bi-info-circle me-1"></i>About Us
    </div>
    <h1 class="fw-bold mb-3" style="font-size:clamp(2rem,4vw,3rem);">About Our School</h1>
    <p class="mb-0 opacity-75" style="max-width:560px;margin:0 auto;">Empowering students with knowledge, values, and skills since <?= get_setting('lp_stat_years','25+') ?> years.</p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="bg-light border-bottom py-2">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <li class="breadcrumb-item active">About Us</li>
      </ol>
    </nav>
  </div>
</div>

<!-- Story Section -->
<section class="py-5" style="background:#fff;">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <?php if ($about_img): ?>
        <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($about_img,ENT_QUOTES) ?>"
             class="img-fluid rounded-4 shadow" alt="About School" style="width:100%;object-fit:cover;max-height:420px;">
        <?php else: ?>
        <div class="rounded-4 d-flex align-items-center justify-content-center"
             style="background:linear-gradient(135deg,#0d6efd,#0d3b7a);height:380px;">
          <i class="bi bi-building" style="font-size:5rem;color:rgba(255,255,255,.25);"></i>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-7">
        <div class="section-badge">Our Story</div>
        <h2 class="section-title"><?= htmlspecialchars(get_setting('lp_about_title','About Our School'),ENT_QUOTES) ?></h2>
        <div class="section-divider mb-4"></div>
        <p class="text-muted lh-lg" style="font-size:1.05rem;">
          <?= nl2br(htmlspecialchars($about_text,ENT_QUOTES)) ?>
        </p>
        <!-- Quick stats -->
        <div class="row g-3 mt-2">
          <?php foreach ([
            [get_setting('lp_stat_students','1200+'), get_setting('lp_stat_label1','Students'),'bi-people-fill','text-primary'],
            [get_setting('lp_stat_teachers','80+'),   get_setting('lp_stat_label2','Teachers'), 'bi-person-badge-fill','text-success'],
            [get_setting('lp_stat_years','25+'),      get_setting('lp_stat_label3','Years'),    'bi-award-fill','text-warning'],
            [get_setting('lp_stat_success','98%'),    get_setting('lp_stat_label4','Pass Rate'),'bi-graph-up-arrow','text-danger'],
          ] as [$num,$lbl,$icon,$cls]): ?>
          <div class="col-6 col-md-3">
            <div class="text-center p-3 rounded-3" style="background:#f8f9ff;">
              <i class="bi <?= $icon ?> <?= $cls ?> fs-3 d-block mb-1"></i>
              <div class="fw-bold fs-5"><?= htmlspecialchars($num,ENT_QUOTES) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($lbl,ENT_QUOTES) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Mission & Vision -->
<section class="py-5" style="background:#f8f9ff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge">Our Purpose</div>
      <h2 class="section-title">Mission &amp; Vision</h2>
      <div class="section-divider mx-auto"></div>
    </div>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="h-100 rounded-4 p-4 p-lg-5" style="background:linear-gradient(135deg,#0d6efd,#0056b3);color:#fff;">
          <div style="width:60px;height:60px;background:rgba(255,255,255,.2);border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <i class="bi bi-bullseye fs-2"></i>
          </div>
          <h3 class="fw-bold mb-3">Our Mission</h3>
          <p class="mb-0 opacity-90 lh-lg"><?= nl2br(htmlspecialchars($mission,ENT_QUOTES)) ?></p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="h-100 rounded-4 p-4 p-lg-5" style="background:linear-gradient(135deg,#198754,#0a5c3a);color:#fff;">
          <div style="width:60px;height:60px;background:rgba(255,255,255,.2);border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;">
            <i class="bi bi-eye fs-2"></i>
          </div>
          <h3 class="fw-bold mb-3">Our Vision</h3>
          <p class="mb-0 opacity-90 lh-lg"><?= nl2br(htmlspecialchars($vision,ENT_QUOTES)) ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Core Values -->
<section class="py-5" style="background:#fff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge">What We Stand For</div>
      <h2 class="section-title">Our Core Values</h2>
      <div class="section-divider mx-auto"></div>
    </div>
    <div class="row g-4">
      <?php foreach ([
        ['bi-heart-fill',       'text-danger',  'Integrity',      'We uphold honesty, transparency, and ethical conduct in everything we do.'],
        ['bi-lightbulb-fill',   'text-warning', 'Excellence',     'We pursue the highest standards in academics, sports, and all activities.'],
        ['bi-people-fill',      'text-primary', 'Inclusivity',    'Every student is valued, respected, and given equal opportunity to succeed.'],
        ['bi-gear-fill',        'text-success', 'Innovation',     'We embrace technology and creative thinking to prepare students for the future.'],
        ['bi-shield-fill',      'text-info',    'Responsibility', 'We foster responsible citizenship and care for the community and environment.'],
        ['bi-star-fill',        'text-warning', 'Empowerment',    'We build confidence, leadership skills, and a growth mindset in every student.'],
      ] as [$icon,$cls,$title,$desc]): ?>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="d-flex gap-3 p-4 rounded-4 border h-100" style="background:#fafbff;">
          <div style="flex-shrink:0;">
            <i class="bi <?= $icon ?> <?= $cls ?> fs-2"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-1"><?= $title ?></h5>
            <p class="text-muted small mb-0"><?= $desc ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Facilities -->
<section class="py-5" style="background:#f8f9ff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-badge">Infrastructure</div>
      <h2 class="section-title">World-Class Facilities</h2>
      <div class="section-divider mx-auto"></div>
    </div>
    <div class="row g-3">
      <?php foreach ([
        ['bi-laptop-fill','Smart Classrooms'],['bi-flask-fill','Science Labs'],
        ['bi-book-fill','Library'],['bi-trophy-fill','Sports Ground'],
        ['bi-music-note-beamed','Music Room'],['bi-palette-fill','Art Studio'],
        ['bi-camera-video-fill','CCTV Security'],['bi-wifi','Hi-Speed WiFi'],
      ] as [$icon,$name]): ?>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3 bg-white border">
          <i class="bi <?= $icon ?> text-primary fs-2 d-block mb-2"></i>
          <span class="fw-semibold small"><?= $name ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section style="background:linear-gradient(135deg,#0d3b7a,#0d6efd);padding:60px 0;">
  <div class="container text-center text-white">
    <h2 class="fw-bold mb-3">Become Part of Our Family</h2>
    <p class="opacity-75 mb-4">Admissions for the new academic session are now open.</p>
    <a href="<?= SITE_URL ?>/public/admission.php" class="btn btn-warning btn-lg px-5 fw-semibold me-2">
      <i class="bi bi-pencil-square me-2"></i>Apply Now
    </a>
    <a href="<?= SITE_URL ?>/public/contact.php" class="btn btn-outline-light btn-lg px-5">
      <i class="bi bi-telephone me-2"></i>Contact Us
    </a>
  </div>
</section>

<?php include INCLUDES_PATH.'public_footer.php'; ?>
