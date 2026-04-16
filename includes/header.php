<?php
/**
 * School ERP - Universal Dashboard Header
 * Detects role and renders appropriate sidebar
 */

if (!defined('ROOT_PATH')) die('Direct access not allowed.');

$site_name  = get_setting('site_name', 'School ERP');
$site_logo  = get_setting('site_logo', '');
$meta_title = get_setting('meta_title', $site_name);
$page_title = $page_title ?? $site_name;
$role       = get_user_role();

// Build sidebar nav based on role
$nav_items = match($role) {
    'admin' => [
        ['icon' => 'speedometer2',    'label' => 'Dashboard',       'url' => '/admin/'],
        ['icon' => 'people-fill',     'label' => 'Students',        'url' => '/admin/students/'],
        ['icon' => 'person-badge',    'label' => 'Teachers',        'url' => '/admin/teachers/'],
        ['icon' => 'people',          'label' => 'Parents',         'url' => '/admin/parents/'],
        ['icon' => 'mortarboard',     'label' => 'Classes',         'url' => '/admin/classes/'],
        ['icon' => 'grid-1x2',        'label' => 'Sections',        'url' => '/admin/sections/'],
        ['icon' => 'book',            'label' => 'Subjects',        'url' => '/admin/subjects/'],
        ['icon' => 'clipboard2-data', 'label' => 'Exams',           'url' => '/admin/exams/'],
        ['icon' => 'pencil-square',   'label' => 'Marks',           'url' => '/admin/marks/'],
        ['icon' => 'award',           'label' => 'Results',         'url' => '/admin/results/'],
        ['icon' => 'calendar-check',  'label' => 'Attendance',      'url' => '/admin/attendance/'],
        ['icon' => 'file-earmark-person', 'label' => 'Admissions',  'url' => '/admin/admissions/'],
        ['icon' => 'receipt',         'label' => 'Fees',            'url' => '/admin/fees/'],
        ['icon' => 'bell',            'label' => 'Notifications',   'url' => '/admin/notifications/'],
        ['icon' => 'layout-text-window-reverse', 'label' => 'Landing Page',  'url' => '/admin/landing/'],
        ['icon' => 'chat-quote',                 'label' => 'Testimonials',   'url' => '/admin/testimonials/'],
        ['icon' => 'gear',            'label' => 'Settings',        'url' => '/admin/settings/'],
    ],
    'student' => [
        ['icon' => 'speedometer2',    'label' => 'Dashboard',       'url' => '/student/'],
        ['icon' => 'person-circle',   'label' => 'My Profile',      'url' => '/student/profile.php'],
        ['icon' => 'card-text',       'label' => 'ID Card',         'url' => '/student/id-card.php'],
        ['icon' => 'calendar-check',  'label' => 'Attendance',      'url' => '/student/attendance.php'],
        ['icon' => 'award',           'label' => 'Results',         'url' => '/student/results.php'],
        ['icon' => 'receipt',         'label' => 'Fee Payment',     'url' => '/student/fees.php'],
    ],
    'teacher' => [
        ['icon' => 'speedometer2',    'label' => 'Dashboard',       'url' => '/teacher/'],
        ['icon' => 'calendar-check',  'label' => 'Attendance',      'url' => '/teacher/attendance.php'],
        ['icon' => 'pencil-square',   'label' => 'Enter Marks',     'url' => '/teacher/marks.php'],
        ['icon' => 'award',           'label' => 'Report Cards',    'url' => '/teacher/report-cards.php'],
    ],
    'parent' => [
        ['icon' => 'speedometer2',    'label' => 'Dashboard',       'url' => '/parent/'],
        ['icon' => 'person-circle',   'label' => 'My Child',        'url' => '/parent/child.php'],
        ['icon' => 'receipt',         'label' => 'Fee Status',      'url' => '/parent/fees.php'],
    ],
    default => [],
};

$current_path = $_SERVER['REQUEST_URI'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= sanitize(get_setting('meta_description','')) ?>">
  <meta name="keywords"    content="<?= sanitize(get_setting('meta_keywords','')) ?>">
  <meta property="og:title"       content="<?= sanitize($page_title) ?> - <?= sanitize($site_name) ?>">
  <meta property="og:description" content="<?= sanitize(get_setting('meta_description','')) ?>">
  <title><?= sanitize($page_title) ?> - <?= sanitize($site_name) ?></title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link href="<?= ASSETS_URL ?>/css/style.css" rel="stylesheet">
  <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
  <div class="container-fluid">
    <!-- Hamburger toggle for sidebar -->
    <button class="btn btn-link text-white me-2 d-lg-none sidebar-toggle p-0" type="button" id="sidebarToggle">
      <i class="bi bi-list fs-4"></i>
    </button>

    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= SITE_URL ?>/<?= $role ?>/">
      <?php if ($site_logo): ?>
        <img src="<?= get_upload_url($site_logo) ?>" alt="Logo" height="32" class="rounded">
      <?php else: ?>
        <i class="bi bi-mortarboard-fill fs-4"></i>
      <?php endif; ?>
      <span class="fw-semibold d-none d-sm-inline"><?= sanitize($site_name) ?></span>
    </a>

    <!-- Right side -->
    <div class="ms-auto d-flex align-items-center gap-1">

      <!-- Notifications bell -->
      <ul class="navbar-nav flex-row">
        <?= render_notification_bell() ?>
      </ul>

      <!-- User menu -->
      <div class="dropdown">
        <button class="btn btn-link text-white dropdown-toggle p-1 text-decoration-none" type="button"
                data-bs-toggle="dropdown">
          <i class="bi bi-person-circle fs-5"></i>
          <span class="d-none d-md-inline ms-1 small"><?= sanitize($_SESSION['user_name'] ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li><div class="dropdown-item-text">
            <div class="fw-semibold"><?= sanitize($_SESSION['user_name'] ?? '') ?></div>
            <small class="text-muted"><?= ucfirst($role) ?></small>
          </div></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= SITE_URL ?>/auth/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
          </a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <span class="fw-semibold text-uppercase small text-muted ls-wide">
      <?= ucfirst($role) ?> Menu
    </span>
  </div>
  <nav class="sidebar-nav">
    <?php foreach ($nav_items as $item):
        $active = str_contains($current_path, $item['url']) ? 'active' : '';
    ?>
    <a href="<?= SITE_URL . $item['url'] ?>" class="sidebar-link <?= $active ?>">
      <i class="bi bi-<?= $item['icon'] ?>"></i>
      <span><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
    <hr class="my-2 border-secondary">
    <a href="<?= SITE_URL ?>/auth/logout.php" class="sidebar-link text-danger-light">
      <i class="bi bi-box-arrow-right"></i>
      <span>Logout</span>
    </a>
  </nav>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<main class="main-content" id="mainContent">
  <div class="container-fluid py-3">

    <!-- Breadcrumb / Page Title -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div>
        <h5 class="mb-0 fw-semibold"><?= sanitize($page_title) ?></h5>
        <?php if (!empty($breadcrumb)): ?>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb small mb-0">
            <?php foreach ($breadcrumb as $bc): ?>
            <li class="breadcrumb-item <?= isset($bc['active']) ? 'active' : '' ?>">
              <?php if (isset($bc['url'])): ?>
                <a href="<?= $bc['url'] ?>"><?= sanitize($bc['label']) ?></a>
              <?php else: ?>
                <?= sanitize($bc['label']) ?>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ol>
        </nav>
        <?php endif; ?>
      </div>
      <?php if (!empty($page_action)) echo $page_action; ?>
    </div>

    <!-- Flash message -->
    <?= get_flash() ?>
