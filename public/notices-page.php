<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title = 'Notices & Events';
$active_nav = 'notices';
$meta_desc  = 'Latest school notices, exam schedules, events, and holiday announcements.';

$cat_filter = sanitize($_GET['cat'] ?? 'all');
$valid_cats = ['all','general','exam','event','holiday','admission'];
if (!in_array($cat_filter,$valid_cats)) $cat_filter = 'all';

$where  = 'WHERE is_active=1';
$params = [];
if ($cat_filter !== 'all') { $where .= ' AND category=?'; $params[] = $cat_filter; }

$stmt = $pdo->prepare("SELECT * FROM notices $where ORDER BY created_at DESC");
$stmt->execute($params);
$notices = $stmt->fetchAll();

$cat_meta = [
  'all'       => ['All Notices',   'bi-list-ul',         '#6366f1', 'rgba(99,102,241,.1)'],
  'general'   => ['General',       'bi-info-circle',     '#6366f1', 'rgba(99,102,241,.1)'],
  'exam'      => ['Examinations',  'bi-clipboard2-data', '#f59e0b', 'rgba(245,158,11,.1)'],
  'event'     => ['Events',        'bi-calendar-event',  '#10b981', 'rgba(16,185,129,.1)'],
  'holiday'   => ['Holidays',      'bi-calendar-heart',  '#ef4444', 'rgba(239,68,68,.1)'],
  'admission' => ['Admissions',    'bi-person-plus',     '#06b6d4', 'rgba(6,182,212,.1)'],
];

$extra_head = '<style>
  body { background: #f0f2ff; }

  .pub-bc-strip {
    background:linear-gradient(90deg,rgba(99,102,241,.07),rgba(139,92,246,.07));
    border-bottom:1px solid rgba(99,102,241,.12); padding:10px 0;
  }

  /* ── Filter sidebar ── */
  .filter-card {
    background:rgba(255,255,255,.85); backdrop-filter:blur(12px);
    border:1px solid rgba(99,102,241,.15); border-radius:20px; overflow:hidden;
    box-shadow:0 4px 20px rgba(99,102,241,.1);
  }
  .filter-head {
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff; padding:14px 20px; font-weight:600;
    font-family:"Poppins",sans-serif; font-size:.875rem;
  }
  .filter-item {
    display:flex; align-items:center; gap:10px;
    padding:12px 18px; text-decoration:none;
    color:#475569; font-size:.875rem; font-family:"Poppins",sans-serif;
    border-bottom:1px solid rgba(99,102,241,.07);
    transition:background .2s, color .2s, padding-left .2s;
  }
  .filter-item:last-child { border-bottom:none; }
  .filter-item:hover { background:rgba(99,102,241,.06); color:#6366f1; padding-left:22px; }
  .filter-item.active {
    background:rgba(99,102,241,.1); color:#6366f1; font-weight:600;
    border-left:3px solid #6366f1;
  }
  .filter-badge {
    margin-left:auto; padding:2px 10px; border-radius:50px;
    font-size:.7rem; font-weight:600;
  }

  /* ── Notice cards ── */
  .notice-card {
    background:rgba(255,255,255,.88); backdrop-filter:blur(12px);
    border:1px solid rgba(99,102,241,.1); border-radius:16px;
    box-shadow:0 4px 16px rgba(99,102,241,.08);
    transition:transform .25s, box-shadow .25s;
    overflow:hidden;
  }
  .notice-card:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(99,102,241,.18); }
  .notice-card-inner { padding:22px 24px; display:flex; gap:18px; }
  .notice-icon-box {
    width:50px; height:50px; border-radius:14px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:1.3rem;
  }

  /* ── Empty state ── */
  .empty-notices {
    background:rgba(255,255,255,.7); backdrop-filter:blur(12px);
    border:1px solid rgba(99,102,241,.15); border-radius:24px;
    padding:60px 40px; text-align:center;
  }
</style>';
include INCLUDES_PATH.'public_header.php';
?>

<!-- Page Hero -->
<div class="page-hero text-center">
  <div class="container">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(8px);border-radius:50px;padding:6px 20px;font-size:.78rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(165,180,252,.9);margin-bottom:20px;font-weight:500;">
      <i class="bi bi-bell"></i> Notices
    </div>
    <h1 class="page-hero-title mb-3">Notices &amp; Events</h1>
    <p class="page-hero-sub mb-0" style="max-width:520px;margin:0 auto;">
      Stay updated with the latest announcements, exam schedules, and upcoming events.
    </p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="pub-bc-strip">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb pub-breadcrumb mb-0" style="background:transparent;">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <li class="breadcrumb-item active" style="color:#6366f1;">Notices &amp; Events</li>
      </ol>
    </nav>
  </div>
</div>

<section class="py-5" style="min-height:60vh;">
  <div class="container">
    <div class="row g-4">

      <!-- ── Sidebar filter ── -->
      <div class="col-lg-3">
        <div class="filter-card sticky-top" style="top:82px;">
          <div class="filter-head"><i class="bi bi-funnel me-2"></i>Filter by Category</div>
          <?php foreach ($cat_meta as $val => [$lbl,$icon,$color,$bg]): ?>
          <?php
            $isActive = ($cat_filter === $val);
            if ($val !== 'all') {
              $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM notices WHERE is_active=1 AND category=?");
              $cntStmt->execute([$val]);
              $cnt = (int)$cntStmt->fetchColumn();
            }
          ?>
          <a href="?cat=<?= $val ?>" class="filter-item <?= $isActive?'active':'' ?>"
             <?= $isActive ? 'style="border-left:3px solid '.$color.';"' : '' ?>>
            <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:1rem;"></i>
            <span><?= $lbl ?></span>
            <?php if ($val !== 'all' && !empty($cnt)): ?>
            <span class="filter-badge" style="background:<?= $bg ?>;color:<?= $color ?>;"><?= $cnt ?></span>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ── Notices list ── -->
      <div class="col-lg-9">

        <!-- Active filter label -->
        <?php if ($cat_filter !== 'all'): ?>
        <div class="d-flex align-items-center gap-2 mb-4">
          <?php [$lbl,$icon,$color,$bg] = $cat_meta[$cat_filter]; ?>
          <span style="background:<?= $bg ?>;color:<?= $color ?>;padding:5px 16px;border-radius:50px;font-size:.8rem;font-weight:600;font-family:'Poppins',sans-serif;">
            <i class="bi <?= $icon ?> me-1"></i><?= $lbl ?>
          </span>
          <span style="color:#64748b;font-size:.85rem;"><?= count($notices) ?> notice<?= count($notices)!==1?'s':'' ?></span>
          <a href="?" style="color:#6366f1;font-size:.82rem;text-decoration:none;margin-left:auto;">
            <i class="bi bi-x-circle me-1"></i>Clear filter
          </a>
        </div>
        <?php endif; ?>

        <?php if (empty($notices)): ?>
        <div class="empty-notices">
          <i class="bi bi-bell-slash" style="font-size:3.5rem;color:rgba(99,102,241,.25);display:block;margin-bottom:16px;"></i>
          <h5 style="color:#1e293b;font-family:'Poppins',sans-serif;font-weight:700;">No notices found</h5>
          <p style="color:#64748b;margin-bottom:0;">Check back later for the latest updates.</p>
        </div>

        <?php else: ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($notices as $n):
            [$lbl2,$icon2,$color2,$bg2] = $cat_meta[$n['category']] ?? $cat_meta['general'];
          ?>
          <div class="notice-card" style="border-left:4px solid <?= $color2 ?>;">
            <div class="notice-card-inner">
              <div class="notice-icon-box" style="background:<?= $bg2 ?>;">
                <i class="bi <?= $icon2 ?>" style="color:<?= $color2 ?>;"></i>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                  <span style="background:<?= $bg2 ?>;color:<?= $color2 ?>;padding:2px 12px;border-radius:50px;font-size:.72rem;font-weight:600;font-family:'Poppins',sans-serif;text-transform:capitalize;">
                    <?= htmlspecialchars($n['category'],ENT_QUOTES) ?>
                  </span>
                  <span style="color:#94a3b8;font-size:.78rem;">
                    <i class="bi bi-calendar2 me-1"></i>
                    <?= date('d F Y', strtotime($n['created_at'])) ?>
                  </span>
                </div>
                <h5 style="font-family:'Poppins',sans-serif;font-weight:700;color:#1e293b;margin-bottom:6px;font-size:1rem;">
                  <?= htmlspecialchars($n['title'],ENT_QUOTES) ?>
                </h5>
                <?php if (!empty($n['body'])): ?>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:0;line-height:1.7;">
                  <?= nl2br(htmlspecialchars($n['body'],ENT_QUOTES)) ?>
                </p>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<?php include INCLUDES_PATH.'public_footer.php'; ?>
