<?php
/**
 * Public website shared header
 * Include AFTER loading config. Expects $page_title and optionally $meta_desc.
 */
$site_name  = get_setting('site_name', 'School ERP');
$site_logo  = get_setting('site_logo', '');
$meta_desc  = $meta_desc  ?? get_setting('meta_description', 'Quality education for every student.');
$page_title = $page_title ?? $site_name;
$full_title = ($page_title !== $site_name) ? "$page_title — $site_name" : $site_name;
$primary    = get_setting('lp_primary_color', '#6366f1');
$active_nav = $active_nav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($full_title, ENT_QUOTES) ?></title>
  <meta name="description" content="<?= htmlspecialchars($meta_desc, ENT_QUOTES) ?>">
  <meta property="og:title"       content="<?= htmlspecialchars($full_title, ENT_QUOTES) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($meta_desc,  ENT_QUOTES) ?>">
  <meta property="og:type"        content="website">

  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Site CSS -->
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">

  <style>
    /* ── Global Font & Root ─────────────────────────────────────────────── */
    :root {
      --pub-primary:   <?= htmlspecialchars($primary, ENT_QUOTES) ?>;
      --pub-indigo:    #6366f1;
      --pub-purple:    #8b5cf6;
      --pub-dark:      #1e1b4b;
      --pub-darker:    #312e81;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body, html {
      font-family: 'Poppins', sans-serif !important;
    }
    body * {
      font-family: 'Poppins', sans-serif;
    }

    /* ── Navbar Base ────────────────────────────────────────────────────── */
    #pubNav {
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%) !important;
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(255,255,255,.08);
      transition: background .35s ease, box-shadow .35s ease, padding .35s ease;
      padding: 14px 0;
      font-family: 'Poppins', sans-serif;
    }

    /* ── Navbar Scrolled (shrink) ───────────────────────────────────────── */
    #pubNav.scrolled {
      background: linear-gradient(135deg, rgba(30,27,75,.97) 0%, rgba(49,46,129,.97) 100%) !important;
      box-shadow: 0 4px 32px rgba(99,102,241,.25);
      padding: 8px 0;
    }
    #pubNav.scrolled .pub-nav-link         { color: rgba(255,255,255,.92) !important; }
    #pubNav.scrolled .pub-nav-link:hover,
    #pubNav.scrolled .pub-nav-link.active  { color: #fff !important; background: rgba(255,255,255,.15); }
    #pubNav.scrolled .navbar-brand-text    { color: #fff !important; }

    /* ── Brand Logo Circle ──────────────────────────────────────────────── */
    .pub-brand-circle {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 15px rgba(99,102,241,.5);
      flex-shrink: 0;
    }
    .pub-brand-circle i {
      font-size: 1.15rem;
      color: #fff;
    }
    .navbar-brand-text {
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      font-size: 1.1rem;
      color: #fff !important;
      letter-spacing: -.01em;
    }
    .navbar-brand-sub {
      font-size: .65rem;
      color: rgba(165,180,252,.8);
      font-weight: 400;
      line-height: 1;
      display: block;
      margin-top: -2px;
    }

    /* ── Nav Links ──────────────────────────────────────────────────────── */
    .pub-nav-link {
      font-family: 'Poppins', sans-serif !important;
      color: rgba(255,255,255,.85) !important;
      font-weight: 500;
      font-size: .875rem;
      padding: 7px 14px !important;
      border-radius: 8px;
      transition: background .2s ease, color .2s ease, transform .15s ease;
      letter-spacing: .01em;
      position: relative;
    }
    .pub-nav-link::after {
      content: '';
      position: absolute;
      bottom: 3px;
      left: 50%;
      transform: translateX(-50%) scaleX(0);
      width: 20px;
      height: 2px;
      background: linear-gradient(90deg, #6366f1, #8b5cf6);
      border-radius: 2px;
      transition: transform .25s ease;
    }
    .pub-nav-link:hover::after,
    .pub-nav-link.active::after {
      transform: translateX(-50%) scaleX(1);
    }
    .pub-nav-link:hover,
    .pub-nav-link.active {
      color: #fff !important;
      background: rgba(255,255,255,.12);
      transform: translateY(-1px);
    }

    /* ── Nav Action Buttons ─────────────────────────────────────────────── */
    .pub-btn-login {
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      font-size: .8rem;
      color: rgba(255,255,255,.9) !important;
      border: 1px solid rgba(255,255,255,.3) !important;
      border-radius: 8px;
      padding: 6px 16px !important;
      transition: background .2s, border-color .2s, color .2s;
    }
    .pub-btn-login:hover {
      background: rgba(255,255,255,.12) !important;
      border-color: rgba(255,255,255,.6) !important;
      color: #fff !important;
    }
    .pub-btn-apply {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: .8rem;
      background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
      border: none !important;
      border-radius: 8px;
      padding: 7px 18px !important;
      color: #fff !important;
      box-shadow: 0 4px 15px rgba(99,102,241,.45);
      transition: box-shadow .2s, transform .15s, opacity .2s;
    }
    .pub-btn-apply:hover {
      box-shadow: 0 6px 22px rgba(99,102,241,.65);
      transform: translateY(-1px);
      opacity: .95;
    }

    /* ── Navbar Toggler ─────────────────────────────────────────────────── */
    .navbar-toggler {
      border: 1px solid rgba(255,255,255,.25) !important;
      border-radius: 8px !important;
      padding: 6px 10px !important;
    }
    .navbar-toggler:focus { box-shadow: none !important; }

    /* ── Mobile Collapse ────────────────────────────────────────────────── */
    @media (max-width: 991.98px) {
      #pubNav {
        background: rgba(22,18,64,.97) !important;
        padding: 10px 0;
      }
      #pubNavMenu.show,
      #pubNavMenu.collapsing {
        background: rgba(15,12,41,.98);
        border-radius: 0 0 16px 16px;
        padding: 12px 16px;
        margin-top: 8px;
        border-top: 1px solid rgba(255,255,255,.07);
      }
      .pub-nav-link { padding: 10px 14px !important; }
      .pub-btn-login, .pub-btn-apply { width: 100%; text-align: center; margin-top: 6px; }
    }

    /* ── Page Hero (default) ────────────────────────────────────────────── */
    .page-hero {
      min-height: 220px;
      padding: 130px 0 70px;
      background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 100%);
      position: relative;
      overflow: hidden;
      font-family: 'Poppins', sans-serif;
    }
    .page-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse at 70% 50%, rgba(139,92,246,.3) 0%, transparent 60%),
                  radial-gradient(ellipse at 20% 80%, rgba(99,102,241,.25) 0%, transparent 50%);
    }
    .page-hero .container { position: relative; z-index: 2; }
    .page-hero-title {
      font-family: 'Poppins', sans-serif;
      font-size: clamp(1.8rem, 4vw, 2.8rem);
      font-weight: 800;
      color: #fff;
      line-height: 1.15;
      letter-spacing: -.02em;
    }
    .page-hero-sub {
      font-family: 'Poppins', sans-serif;
      color: rgba(165,180,252,.85);
      font-size: .95rem;
      font-weight: 400;
    }

    /* ── Breadcrumb ─────────────────────────────────────────────────────── */
    .pub-breadcrumb { font-family: 'Poppins', sans-serif; font-size: .8rem; }
    .pub-breadcrumb .breadcrumb-item a { color: rgba(165,180,252,.85); text-decoration: none; }
    .pub-breadcrumb .breadcrumb-item a:hover { color: #fff; }
    .pub-breadcrumb .breadcrumb-item.active { color: #fff; }
    .pub-breadcrumb .breadcrumb-item+.breadcrumb-item::before { color: rgba(165,180,252,.5); }

    /* ── Global Helpers ─────────────────────────────────────────────────── */
    .section-badge {
      display: inline-block;
      background: rgba(99,102,241,.1);
      color: #6366f1;
      border-radius: 50px;
      padding: 5px 18px;
      font-size: .75rem;
      font-weight: 600;
      letter-spacing: .08em;
      text-transform: uppercase;
      margin-bottom: 12px;
      font-family: 'Poppins', sans-serif;
    }
    .section-title {
      font-family: 'Poppins', sans-serif;
      font-size: clamp(1.6rem, 3vw, 2.4rem);
      font-weight: 800;
      color: #1a2340;
      line-height: 1.2;
      letter-spacing: -.02em;
    }
    .section-divider {
      width: 50px;
      height: 4px;
      background: linear-gradient(90deg, #6366f1, #8b5cf6);
      border-radius: 2px;
      margin: 14px 0 0;
    }
    .glass-card {
      background: rgba(255,255,255,.7);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,.6);
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(99,102,241,.12);
      transition: transform .3s ease, box-shadow .3s ease;
    }
    .glass-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 48px rgba(99,102,241,.22);
    }
    .btn-gradient {
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      border: none;
      color: #fff;
      box-shadow: 0 6px 20px rgba(99,102,241,.4);
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      transition: box-shadow .2s, transform .15s, opacity .2s;
    }
    .btn-gradient:hover {
      box-shadow: 0 10px 28px rgba(99,102,241,.55);
      transform: translateY(-2px);
      opacity: .95;
      color: #fff;
    }
  </style>
  <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body style="padding-top:0; font-family:'Poppins',sans-serif;">

<nav id="pubNav" class="navbar navbar-expand-lg fixed-top">
  <div class="container">

    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= SITE_URL ?>/">
      <?php if ($site_logo): ?>
        <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($site_logo, ENT_QUOTES) ?>" height="40" alt="Logo"
             style="border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.25);">
      <?php else: ?>
        <div class="pub-brand-circle">
          <i class="bi bi-mortarboard-fill"></i>
        </div>
      <?php endif; ?>
      <div>
        <span class="navbar-brand-text"><?= htmlspecialchars($site_name, ENT_QUOTES) ?></span>
        <span class="navbar-brand-sub"><?= htmlspecialchars(get_setting('site_tagline', 'Excellence in Education'), ENT_QUOTES) ?></span>
      </div>
    </a>

    <!-- Toggler -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pubNavMenu"
            aria-controls="pubNavMenu" aria-expanded="false" aria-label="Toggle navigation">
      <i class="bi bi-list text-white fs-3"></i>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="pubNavMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1 py-2 py-lg-0">
        <?php $navLinks = [
          'home'    => ['Home',       SITE_URL.'/'],
          'about'   => ['About Us',   SITE_URL.'/public/about.php'],
          'gallery' => ['Gallery',    SITE_URL.'/public/gallery-page.php'],
          'notices' => ['Notices',    SITE_URL.'/public/notices-page.php'],
          'contact' => ['Contact',    SITE_URL.'/public/contact.php'],
        ];
        foreach ($navLinks as $key => [$label, $url]): ?>
        <li class="nav-item">
          <a class="nav-link pub-nav-link <?= $active_nav === $key ? 'active' : '' ?>"
             href="<?= $url ?>">
            <?= $label ?>
          </a>
        </li>
        <?php endforeach; ?>

        <li class="nav-item ms-lg-2">
          <a class="btn pub-btn-login btn-sm px-3" href="<?= SITE_URL ?>/auth/login.php">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login
          </a>
        </li>
        <li class="nav-item ms-lg-1">
          <a class="btn pub-btn-apply btn-sm px-3" href="<?= SITE_URL ?>/public/admission.php">
            <i class="bi bi-pencil-square me-1"></i>Apply Now
          </a>
        </li>
      </ul>
    </div>

  </div>
</nav>
