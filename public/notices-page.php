<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title = 'Notices & Events';
$active_nav = 'notices';
$meta_desc  = 'Latest school notices, exam schedules, events, and holiday announcements.';

$cat_filter = sanitize($_GET['cat'] ?? 'all');
$valid_cats = ['all','general','exam','event','holiday','admission'];
if (!in_array($cat_filter,$valid_cats)) $cat_filter='all';

$where  = 'WHERE is_active=1';
$params = [];
if ($cat_filter !== 'all') { $where .= ' AND category=?'; $params[] = $cat_filter; }

$stmt = $pdo->prepare("SELECT * FROM notices $where ORDER BY created_at DESC");
$stmt->execute($params);
$notices = $stmt->fetchAll();

$cat_colors = ['general'=>'primary','exam'=>'warning','event'=>'success','holiday'=>'danger','admission'=>'info'];

include INCLUDES_PATH.'public_header.php';
?>

<!-- Page Hero -->
<div class="page-hero text-center text-white">
  <div class="container">
    <div style="display:inline-block;background:rgba(255,255,255,.15);border-radius:50px;padding:6px 20px;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:16px;">
      <i class="bi bi-bell me-1"></i>Notices
    </div>
    <h1 class="fw-bold mb-3" style="font-size:clamp(2rem,4vw,3rem);">Notices &amp; Events</h1>
    <p class="mb-0 opacity-75" style="max-width:520px;margin:0 auto;">Stay updated with the latest school announcements, exam schedules, and upcoming events.</p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="bg-light border-bottom py-2">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <li class="breadcrumb-item active">Notices &amp; Events</li>
      </ol>
    </nav>
  </div>
</div>

<section class="py-5" style="background:#f8f9ff;min-height:60vh;">
  <div class="container">
    <div class="row g-4">
      <!-- Sidebar -->
      <div class="col-lg-3">
        <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
          <div class="card-header bg-primary text-white fw-semibold">
            <i class="bi bi-funnel me-2"></i>Filter
          </div>
          <div class="list-group list-group-flush">
            <?php foreach (['all'=>['All Notices','bi-list-ul','secondary'],
              'general'   =>['General',    'bi-info-circle','primary'],
              'exam'      =>['Examinations','bi-clipboard2-data','warning'],
              'event'     =>['Events',      'bi-calendar-event','success'],
              'holiday'   =>['Holidays',    'bi-calendar-heart','danger'],
              'admission' =>['Admissions',  'bi-person-plus','info'],
            ] as $val => [$lbl,$icon,$col]):
              $active = $cat_filter===$val?'active':'';
            ?>
            <a href="?cat=<?= $val ?>"
               class="list-group-item list-group-item-action <?= $active ?> d-flex align-items-center gap-2">
              <i class="bi <?= $icon ?> text-<?= $col ?>"></i>
              <?= $lbl ?>
              <?php if ($val !== 'all'):
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM notices WHERE is_active=1 AND category=?");
                $cnt->execute([$val]);
                $n = (int)$cnt->fetchColumn();
                if ($n): ?>
                <span class="badge bg-<?= $col ?> ms-auto"><?= $n ?></span>
                <?php endif; endif; ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Notices list -->
      <div class="col-lg-9">
        <?php if (empty($notices)): ?>
        <div class="text-center py-5">
          <i class="bi bi-bell-slash display-1 text-muted d-block mb-3"></i>
          <h4 class="text-muted">No notices found</h4>
          <p class="text-muted">Check back later for updates.</p>
        </div>
        <?php else: ?>
        <div class="row g-3">
          <?php foreach ($notices as $n):
            $col = $cat_colors[$n['category']] ?? 'primary';
          ?>
          <div class="col-12">
            <div class="card border-0 shadow-sm"
                 style="border-left:4px solid var(--bs-<?= $col ?>) !important;border-radius:0 12px 12px 0;">
              <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                  <div class="flex-shrink-0 rounded-3 d-flex align-items-center justify-content-center"
                       style="width:48px;height:48px;background:var(--bs-<?= $col ?>-bg-subtle);">
                    <?php $icons=['general'=>'bi-info-circle','exam'=>'bi-clipboard2-data','event'=>'bi-calendar-event','holiday'=>'bi-calendar-heart','admission'=>'bi-person-plus'];?>
                    <i class="bi <?= $icons[$n['category']] ?? 'bi-bell' ?> text-<?= $col ?> fs-4"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                      <span class="badge bg-<?= $col ?> bg-opacity-10 text-<?= $col ?> text-capitalize">
                        <?= htmlspecialchars($n['category'],ENT_QUOTES) ?>
                      </span>
                      <span class="text-muted small">
                        <i class="bi bi-calendar2 me-1"></i>
                        <?= date('d F Y', strtotime($n['created_at'])) ?>
                      </span>
                    </div>
                    <h5 class="fw-bold mb-2"><?= htmlspecialchars($n['title'],ENT_QUOTES) ?></h5>
                    <?php if (!empty($n['body'])): ?>
                    <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($n['body'],ENT_QUOTES)) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
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
