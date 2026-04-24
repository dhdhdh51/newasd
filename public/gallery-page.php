<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title = 'Gallery';
$active_nav = 'gallery';
$meta_desc  = 'Browse our school photo gallery — campus, events, sports, and more.';

$gallery = $pdo->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order ASC, id DESC")->fetchAll();

$categories = ['All'];
foreach ($gallery as $g) {
    if (!empty($g['title']) && str_contains($g['title'],':')) {
        $cat = trim(explode(':',$g['title'])[0]);
        if (!in_array($cat,$categories)) $categories[] = $cat;
    }
}

$extra_head = '<style>
  body { background: #f0f2ff; }

  .pub-bc-strip {
    background:linear-gradient(90deg,rgba(99,102,241,.07),rgba(139,92,246,.07));
    border-bottom:1px solid rgba(99,102,241,.12);
    padding:10px 0;
  }

  /* ── Filter pills ── */
  .gallery-pill {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 20px; border-radius:50px;
    font-size:.82rem; font-weight:500; cursor:pointer;
    font-family:"Poppins",sans-serif;
    border:1.5px solid rgba(99,102,241,.25);
    color:#6366f1; background:rgba(99,102,241,.07);
    transition:background .2s, color .2s, border-color .2s, box-shadow .2s;
  }
  .gallery-pill:hover, .gallery-pill.active {
    background:linear-gradient(135deg,#6366f1,#8b5cf6);
    color:#fff; border-color:transparent;
    box-shadow:0 6px 18px rgba(99,102,241,.35);
  }

  /* ── Gallery cards ── */
  .gallery-card {
    border-radius:16px; overflow:hidden; position:relative; cursor:pointer;
    aspect-ratio:1; box-shadow:0 4px 18px rgba(99,102,241,.12);
    transition:transform .3s, box-shadow .3s;
  }
  .gallery-card:hover { transform:scale(1.03); box-shadow:0 12px 36px rgba(99,102,241,.25); }
  .gallery-card img { width:100%; height:100%; object-fit:cover; transition:transform .4s; display:block; }
  .gallery-card:hover img { transform:scale(1.08); }
  .gallery-card-overlay {
    position:absolute; inset:0;
    background:linear-gradient(135deg,rgba(99,102,241,.75),rgba(139,92,246,.65));
    display:flex; align-items:center; justify-content:center;
    opacity:0; transition:opacity .3s;
    color:#fff; font-size:2rem;
    flex-direction:column; gap:8px;
  }
  .gallery-card:hover .gallery-card-overlay { opacity:1; }
  .gallery-card-caption {
    position:absolute; bottom:0; left:0; right:0;
    background:linear-gradient(transparent,rgba(15,12,41,.85));
    color:#fff; padding:24px 14px 10px;
    font-size:.78rem; font-weight:500;
    opacity:0; transition:opacity .3s;
    font-family:"Poppins",sans-serif;
  }
  .gallery-card:hover .gallery-card-caption { opacity:1; }

  /* ── Empty state ── */
  .empty-gallery {
    background:rgba(255,255,255,.7); backdrop-filter:blur(12px);
    border:1px solid rgba(99,102,241,.15); border-radius:24px;
    padding:60px 40px; text-align:center;
  }

  /* ── Lightbox ── */
  #lightbox {
    display:none; position:fixed; inset:0;
    background:rgba(10,8,30,.95); z-index:9999;
    align-items:center; justify-content:center; flex-direction:column;
    backdrop-filter:blur(8px);
  }
  #lightbox.open { display:flex; }
  #lb-close {
    position:absolute; top:20px; right:28px;
    color:rgba(255,255,255,.7); font-size:2.2rem; cursor:pointer;
    width:44px; height:44px; display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,.1); border-radius:12px;
    transition:background .2s, color .2s;
  }
  #lb-close:hover { background:rgba(255,255,255,.2); color:#fff; }
  #lb-img {
    max-width:90vw; max-height:80vh; border-radius:12px; object-fit:contain;
    box-shadow:0 20px 60px rgba(0,0,0,.5);
  }
  #lb-cap {
    color:rgba(255,255,255,.8); font-family:"Poppins",sans-serif;
    font-size:.9rem; margin-top:16px;
  }

  /* ── CTA ── */
  .cta-gallery {
    background:linear-gradient(135deg,#1e1b4b 0%,#4f46e5 50%,#7c3aed 100%);
    padding:60px 0; position:relative; overflow:hidden;
  }
  .cta-gallery::before {
    content:""; position:absolute; top:-70px; right:-70px;
    width:280px; height:280px; border-radius:50%; background:rgba(255,255,255,.06);
  }
</style>';
include INCLUDES_PATH.'public_header.php';
?>

<!-- Page Hero -->
<div class="page-hero text-center">
  <div class="container">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(8px);border-radius:50px;padding:6px 20px;font-size:.78rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(165,180,252,.9);margin-bottom:20px;font-weight:500;">
      <i class="bi bi-images"></i> Gallery
    </div>
    <h1 class="page-hero-title mb-3">School Gallery</h1>
    <p class="page-hero-sub mb-0" style="max-width:520px;margin:0 auto;">
      A glimpse of life at our school — campus, events, sports, and proud achievements.
    </p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="pub-bc-strip">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb pub-breadcrumb mb-0" style="background:transparent;">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <li class="breadcrumb-item active" style="color:#6366f1;">Gallery</li>
      </ol>
    </nav>
  </div>
</div>

<section class="py-5" style="min-height:60vh;">
  <div class="container">

    <!-- Filter pills -->
    <?php if (count($categories) > 1): ?>
    <div class="d-flex flex-wrap gap-2 justify-content-center mb-5">
      <?php foreach ($categories as $cat): ?>
      <button class="gallery-pill <?= $cat==='All'?'active':'' ?>"
              data-filter="<?= htmlspecialchars($cat,ENT_QUOTES) ?>">
        <?php if ($cat==='All'): ?><i class="bi bi-grid-fill"></i><?php endif; ?>
        <?= htmlspecialchars($cat,ENT_QUOTES) ?>
      </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($gallery)): ?>
    <div class="empty-gallery">
      <i class="bi bi-images" style="font-size:4rem;color:rgba(99,102,241,.3);display:block;margin-bottom:16px;"></i>
      <h4 style="color:#1e293b;font-family:'Poppins',sans-serif;font-weight:700;">Gallery Coming Soon</h4>
      <p style="color:#64748b;margin-bottom:0;">Beautiful images of our campus and events will be added shortly.</p>
    </div>

    <?php else: ?>
    <div class="row g-3" id="galleryGrid">
      <?php foreach ($gallery as $img):
        $rawTitle = $img['title'] ?? '';
        $cat = (str_contains($rawTitle,':')) ? trim(explode(':',$rawTitle)[0]) : 'All';
        $caption = (str_contains($rawTitle,':')) ? trim(explode(':',$rawTitle,2)[1]) : $rawTitle;
        $src = UPLOADS_URL.'/'.htmlspecialchars($img['image'],ENT_QUOTES);
        $capEsc = htmlspecialchars($caption,ENT_QUOTES);
      ?>
      <div class="col-6 col-md-4 col-lg-3 gallery-col"
           data-cat="<?= htmlspecialchars($cat,ENT_QUOTES) ?>">
        <div class="gallery-card"
             onclick="openLightbox('<?= $src ?>','<?= $capEsc ?>')">
          <img src="<?= $src ?>" alt="<?= $capEsc ?>" loading="lazy">
          <div class="gallery-card-overlay">
            <i class="bi bi-zoom-in"></i>
            <?php if ($caption): ?><span style="font-size:.8rem;"><?= $capEsc ?></span><?php endif; ?>
          </div>
          <?php if ($caption): ?>
          <div class="gallery-card-caption"><?= $capEsc ?></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>

<!-- Lightbox -->
<div id="lightbox" onclick="if(event.target===this)closeLightbox()">
  <span id="lb-close" onclick="closeLightbox()" title="Close">&times;</span>
  <img id="lb-img" src="" alt="">
  <p id="lb-cap"></p>
</div>

<!-- CTA -->
<section class="cta-gallery">
  <div class="container text-center text-white position-relative" style="z-index:2;">
    <h3 style="font-family:'Poppins',sans-serif;font-size:clamp(1.5rem,3vw,2rem);font-weight:800;margin-bottom:12px;">
      Want to be Part of These Memories?
    </h3>
    <p style="color:rgba(255,255,255,.75);max-width:480px;margin:0 auto 28px;">
      Apply for admission and start your unforgettable journey with us.
    </p>
    <a href="<?= SITE_URL ?>/public/admission.php"
       style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:#6366f1;font-weight:700;font-family:'Poppins',sans-serif;text-decoration:none;padding:12px 32px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);transition:transform .2s,box-shadow .2s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,.2)'"
       onmouseout="this.style.transform='';this.style.boxShadow='0 8px 24px rgba(0,0,0,.15)'">
      <i class="bi bi-pencil-square"></i> Apply Now
    </a>
  </div>
</section>

<script>
// Filter
document.querySelectorAll('.gallery-pill').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.gallery-pill').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    const f = this.dataset.filter;
    document.querySelectorAll('.gallery-col').forEach(col => {
      col.style.display = (f === 'All' || col.dataset.cat === f) ? '' : 'none';
    });
  });
});

// Lightbox
function openLightbox(src, cap) {
  document.getElementById('lb-img').src = src;
  document.getElementById('lb-cap').textContent = cap || '';
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.getElementById('lb-img').src = '';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
</script>

<?php include INCLUDES_PATH.'public_footer.php'; ?>
