<?php
$site_name   = get_setting('site_name',   'School ERP');
$footer_text = get_setting('footer_text', '© '.date('Y').' School ERP. All rights reserved.');
$phone       = get_setting('contact_phone',   '+91 9000000000');
$email       = get_setting('contact_email',   'info@school.com');
$address     = get_setting('contact_address', '123 School Street, City');
$fb          = get_setting('social_facebook',  '');
$tw          = get_setting('social_twitter',   '');
$ig          = get_setting('social_instagram', '');
$yt          = get_setting('social_youtube',   '');
$site_logo   = get_setting('site_logo', '');
?>
<!-- FOOTER -->
<footer style="background:#0f1b2d;color:rgba(255,255,255,.7);" class="pt-5 pb-3 mt-0">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <?php if ($site_logo): ?>
            <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($site_logo,ENT_QUOTES) ?>" height="36" class="rounded">
          <?php else: ?>
            <div style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-mortarboard-fill text-white"></i>
            </div>
          <?php endif; ?>
          <span class="text-white fw-bold fs-5"><?= htmlspecialchars($site_name,ENT_QUOTES) ?></span>
        </div>
        <p class="small mb-3"><?= htmlspecialchars(get_setting('site_tagline','Empowering Education'),ENT_QUOTES) ?></p>
        <p class="small mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i><?= htmlspecialchars($address,ENT_QUOTES) ?></p>
        <!-- Social links -->
        <?php if ($fb || $tw || $ig || $yt): ?>
        <div class="d-flex gap-2 mt-3">
          <?php foreach ([[$fb,'facebook','bi-facebook'],[$tw,'twitter','bi-twitter-x'],[$ig,'instagram','bi-instagram'],[$yt,'youtube','bi-youtube']] as [$url,$name,$icon]): ?>
          <?php if ($url): ?>
          <a href="<?= htmlspecialchars($url,ENT_QUOTES) ?>" target="_blank" rel="noopener"
             style="width:36px;height:36px;background:rgba(255,255,255,.1);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;"
             aria-label="<?= $name ?>">
            <i class="bi <?= $icon ?>"></i>
          </a>
          <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-2 col-6">
        <h6 class="text-white fw-semibold mb-3">Quick Links</h6>
        <ul class="list-unstyled small">
          <?php foreach ([
            ['Home',        SITE_URL.'/'],
            ['About Us',    SITE_URL.'/public/about.php'],
            ['Gallery',     SITE_URL.'/public/gallery-page.php'],
            ['Notices',     SITE_URL.'/public/notices-page.php'],
            ['Contact',     SITE_URL.'/public/contact.php'],
            ['Admissions',  SITE_URL.'/public/admission.php'],
          ] as [$lbl,$url]): ?>
          <li class="mb-2">
            <a href="<?= $url ?>" style="color:rgba(255,255,255,.6);text-decoration:none;"
               onmouseover="this.style.color='#7ec8ff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">
              <i class="bi bi-chevron-right small me-1"></i><?= $lbl ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="col-lg-3 col-6">
        <h6 class="text-white fw-semibold mb-3">Student Portals</h6>
        <ul class="list-unstyled small">
          <?php foreach ([
            ['Student Login',    'person-circle'],
            ['Teacher Login',    'person-badge'],
            ['Parent Login',     'people'],
            ['Admin Login',      'shield-lock'],
            ['Track Admission',  'search'],
          ] as [$lbl,$icon]): ?>
          <li class="mb-2">
            <a href="<?= $lbl==='Track Admission' ? SITE_URL.'/public/admission-status.php' : SITE_URL.'/auth/login.php' ?>"
               style="color:rgba(255,255,255,.6);text-decoration:none;"
               onmouseover="this.style.color='#7ec8ff'" onmouseout="this.style.color='rgba(255,255,255,.6)'">
              <i class="bi bi-<?= $icon ?> me-1"></i><?= $lbl ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="col-lg-3">
        <h6 class="text-white fw-semibold mb-3">Contact Us</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><i class="bi bi-telephone text-primary me-2"></i><?= htmlspecialchars($phone,ENT_QUOTES) ?></li>
          <li class="mb-2"><i class="bi bi-envelope text-primary me-2"></i><?= htmlspecialchars($email,ENT_QUOTES) ?></li>
        </ul>
        <a href="<?= SITE_URL ?>/public/admission.php"
           class="btn btn-primary btn-sm mt-2">
          <i class="bi bi-pencil-square me-1"></i>Apply for Admission
        </a>
      </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,.08);" class="pt-3 text-center">
      <p class="small mb-0"><?= htmlspecialchars($footer_text,ENT_QUOTES) ?></p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.addEventListener('scroll',()=>{
  document.getElementById('pubNav')?.classList.toggle('scrolled', window.scrollY > 50);
});
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const t=document.querySelector(a.getAttribute('href'));
    if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth'});}
  });
});
</script>
<?php if (!empty($extra_footer)) echo $extra_footer; ?>
</body>
</html>
