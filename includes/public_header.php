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
$primary    = get_setting('lp_primary_color', '#0d6efd');
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
  <style>
    :root { --pub-primary: <?= htmlspecialchars($primary, ENT_QUOTES) ?>; }
    #pubNav { transition: background .3s, box-shadow .3s, padding .3s; padding: 14px 0; }
    #pubNav.scrolled { background: #fff !important; box-shadow: 0 2px 18px rgba(0,0,0,.08); padding: 8px 0; }
    #pubNav.scrolled .nav-link,
    #pubNav.scrolled .navbar-brand-text { color: #212529 !important; }
    #pubNav.scrolled .navbar-toggler { border-color: #ccc; }
    #pubNav.scrolled .navbar-toggler-icon { filter: invert(1) brightness(0); }
    .pub-nav-link { color: rgba(255,255,255,.9) !important; font-weight: 500; padding: 6px 12px !important; border-radius: 6px; transition: background .2s; }
    .pub-nav-link:hover, .pub-nav-link.active { color: #fff !important; background: rgba(255,255,255,.15); }
    #pubNav.scrolled .pub-nav-link { color: #212529 !important; }
    #pubNav.scrolled .pub-nav-link:hover, #pubNav.scrolled .pub-nav-link.active { background: rgba(13,110,253,.1); color: var(--pub-primary) !important; }
    .page-hero { padding: 130px 0 70px; background: linear-gradient(135deg,#0d3b7a,var(--pub-primary)); }
    .section-badge { display:inline-block; background:rgba(13,110,253,.1); color:var(--pub-primary); border-radius:50px; padding:5px 16px; font-size:.78rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase; margin-bottom:12px; }
    .section-title { font-size:clamp(1.6rem,3vw,2.4rem); font-weight:800; color:#1a2340; line-height:1.2; }
    .section-divider { width:50px; height:4px; background:var(--pub-primary); border-radius:2px; margin:14px 0 0; }
    @media(max-width:991.98px){ #pubNav { background:rgba(10,30,70,.95) !important; padding:10px 0; } }
  </style>
  <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body style="padding-top:0">

<nav id="pubNav" class="navbar navbar-expand-lg fixed-top" style="background:transparent;">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= SITE_URL ?>/">
      <?php if ($site_logo): ?>
        <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($site_logo,ENT_QUOTES) ?>" height="38" alt="Logo" class="rounded">
      <?php else: ?>
        <div style="width:38px;height:38px;background:rgba(255,255,255,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
          <i class="bi bi-mortarboard-fill text-white fs-5"></i>
        </div>
      <?php endif; ?>
      <span class="fw-bold text-white navbar-brand-text"><?= htmlspecialchars($site_name,ENT_QUOTES) ?></span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#pubNavMenu">
      <i class="bi bi-list text-white fs-3"></i>
    </button>

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
          <a class="nav-link pub-nav-link <?= $active_nav===$key?'active':'' ?>" href="<?= $url ?>">
            <?= $label ?>
          </a>
        </li>
        <?php endforeach; ?>
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
