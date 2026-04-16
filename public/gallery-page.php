<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title = 'Gallery';
$active_nav = 'gallery';
$meta_desc  = 'Browse our school photo gallery — campus, events, sports, and more.';

$gallery = $pdo->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order ASC, id DESC")->fetchAll();

// Group by title for tabs (use first word as category if title contains colon, else "All")
$categories = ['All'];
foreach ($gallery as $g) {
    if (!empty($g['title']) && str_contains($g['title'],':')) {
        $cat = trim(explode(':',$g['title'])[0]);
        if (!in_array($cat,$categories)) $categories[] = $cat;
    }
}

include INCLUDES_PATH.'public_header.php';
?>

<!-- Page Hero -->
<div class="page-hero text-center text-white">
  <div class="container">
    <div style="display:inline-block;background:rgba(255,255,255,.15);border-radius:50px;padding:6px 20px;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:16px;">
      <i class="bi bi-images me-1"></i>Gallery
    </div>
    <h1 class="fw-bold mb-3" style="font-size:clamp(2rem,4vw,3rem);">School Gallery</h1>
    <p class="mb-0 opacity-75" style="max-width:520px;margin:0 auto;">A glimpse of life at our school — campus, events, sports, and achievements.</p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="bg-light border-bottom py-2">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <li class="breadcrumb-item active">Gallery</li>
      </ol>
    </nav>
  </div>
</div>

<section class="py-5" style="background:#f8f9ff;min-height:60vh;">
  <div class="container">

    <!-- Filter tabs -->
    <?php if (count($categories) > 1): ?>
    <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
      <?php foreach ($categories as $cat): ?>
      <button class="btn btn-sm gallery-filter <?= $cat==='All'?'btn-primary':'btn-outline-primary' ?>"
              data-filter="<?= htmlspecialchars($cat,ENT_QUOTES) ?>">
        <?= htmlspecialchars($cat,ENT_QUOTES) ?>
      </button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($gallery)): ?>
    <div class="text-center py-5">
      <i class="bi bi-images display-1 text-muted d-block mb-3"></i>
      <h4 class="text-muted">Gallery Coming Soon</h4>
      <p class="text-muted">Images will be added shortly. Please check back later.</p>
    </div>
    <?php else: ?>
    <div class="row g-3" id="galleryGrid">
      <?php foreach ($gallery as $img):
        $rawTitle = $img['title'] ?? '';
        $cat = (str_contains($rawTitle,':')) ? trim(explode(':',$rawTitle)[0]) : 'All';
        $caption = (str_contains($rawTitle,':')) ? trim(explode(':',$rawTitle,2)[1]) : $rawTitle;
      ?>
      <div class="col-6 col-md-4 col-lg-3 gallery-col"
           data-cat="<?= htmlspecialchars($cat,ENT_QUOTES) ?>">
        <div class="gallery-card rounded-3 overflow-hidden shadow-sm"
             style="position:relative;cursor:pointer;aspect-ratio:1;"
             onclick="openLightbox('<?= UPLOADS_URL.'/'.htmlspecialchars($img['image'],ENT_QUOTES) ?>','<?= htmlspecialchars($caption,ENT_QUOTES) ?>')">
          <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($img['image'],ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($caption,ENT_QUOTES) ?>"
               loading="lazy"
               style="width:100%;height:100%;object-fit:cover;transition:transform .4s;">
          <div style="position:absolute;inset:0;background:rgba(13,110,253,.65);opacity:0;transition:opacity .3s;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;"
               class="gallery-overlay">
            <i class="bi bi-zoom-in"></i>
          </div>
          <?php if ($caption): ?>
          <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.7));color:#fff;padding:20px 10px 8px;font-size:.78rem;opacity:0;transition:opacity .3s;"
               class="gallery-caption">
            <?= htmlspecialchars($caption,ENT_QUOTES) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Lightbox -->
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;align-items:center;justify-content:center;flex-direction:column;">
  <span onclick="closeLightbox()" style="position:absolute;top:20px;right:28px;color:#fff;font-size:2.5rem;cursor:pointer;line-height:1;">&times;</span>
  <img id="lightbox-img" src="" alt=""
       style="max-width:90vw;max-height:80vh;border-radius:8px;object-fit:contain;">
  <p id="lightbox-cap" class="text-white mt-3 mb-0"></p>
</div>

<!-- CTA -->
<section style="background:linear-gradient(135deg,#0d3b7a,#0d6efd);padding:50px 0;">
  <div class="container text-center text-white">
    <h3 class="fw-bold mb-2">Want to be Part of These Memories?</h3>
    <p class="opacity-75 mb-4">Apply for admission and start your journey with us.</p>
    <a href="<?= SITE_URL ?>/public/admission.php" class="btn btn-warning btn-lg px-5 fw-semibold">
      <i class="bi bi-pencil-square me-2"></i>Apply Now
    </a>
  </div>
</section>

<script>
// Gallery hover effect
document.querySelectorAll('.gallery-card').forEach(card=>{
  card.addEventListener('mouseenter',()=>{
    card.querySelector('img').style.transform='scale(1.07)';
    card.querySelector('.gallery-overlay').style.opacity='1';
    const cap=card.querySelector('.gallery-caption');
    if(cap) cap.style.opacity='1';
  });
  card.addEventListener('mouseleave',()=>{
    card.querySelector('img').style.transform='scale(1)';
    card.querySelector('.gallery-overlay').style.opacity='0';
    const cap=card.querySelector('.gallery-caption');
    if(cap) cap.style.opacity='0';
  });
});

// Filter
document.querySelectorAll('.gallery-filter').forEach(btn=>{
  btn.addEventListener('click',function(){
    document.querySelectorAll('.gallery-filter').forEach(b=>{
      b.classList.remove('btn-primary'); b.classList.add('btn-outline-primary');
    });
    this.classList.add('btn-primary'); this.classList.remove('btn-outline-primary');
    const f=this.dataset.filter;
    document.querySelectorAll('.gallery-col').forEach(col=>{
      col.style.display=(f==='All'||col.dataset.cat===f)?'':'none';
    });
  });
});

// Lightbox
function openLightbox(src,cap){
  document.getElementById('lightbox-img').src=src;
  document.getElementById('lightbox-cap').textContent=cap||'';
  const lb=document.getElementById('lightbox');
  lb.style.display='flex'; document.body.style.overflow='hidden';
}
function closeLightbox(){
  document.getElementById('lightbox').style.display='none';
  document.body.style.overflow='';
}
document.getElementById('lightbox').addEventListener('click',e=>{if(e.target===e.currentTarget)closeLightbox();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeLightbox();});
</script>

<?php include INCLUDES_PATH.'public_footer.php'; ?>
