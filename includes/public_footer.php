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

<!-- ═══════════════════════════ FOOTER ═══════════════════════════ -->
<footer style="
  background: linear-gradient(135deg, #0f0c29 0%, #302b63 55%, #24243e 100%);
  color: rgba(255,255,255,.72);
  font-family: 'Poppins', sans-serif;
" class="pt-5 pb-0 mt-0">

  <div class="container">
    <div class="row g-4 pb-5">

      <!-- ── Col 1: Brand + About ── -->
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-3 mb-4">
          <?php if ($site_logo): ?>
            <img src="<?= UPLOADS_URL.'/'.htmlspecialchars($site_logo, ENT_QUOTES) ?>"
                 height="44" class="rounded"
                 style="box-shadow:0 4px 14px rgba(99,102,241,.4);">
          <?php else: ?>
            <div style="
              width:44px; height:44px;
              background: linear-gradient(135deg,#6366f1,#8b5cf6);
              border-radius:12px;
              display:flex; align-items:center; justify-content:center;
              box-shadow:0 4px 14px rgba(99,102,241,.45);
              flex-shrink:0;
            ">
              <i class="bi bi-mortarboard-fill text-white fs-5"></i>
            </div>
          <?php endif; ?>
          <div>
            <div class="text-white fw-bold fs-5 lh-1 mb-1"
                 style="font-family:'Poppins',sans-serif;">
              <?= htmlspecialchars($site_name, ENT_QUOTES) ?>
            </div>
            <div style="font-size:.72rem;color:rgba(165,180,252,.7);font-weight:400;">
              <?= htmlspecialchars(get_setting('site_tagline','Excellence in Education'), ENT_QUOTES) ?>
            </div>
          </div>
        </div>

        <p class="small mb-3" style="color:rgba(255,255,255,.55); line-height:1.7; font-size:.85rem;">
          <?= htmlspecialchars(get_setting('site_description', 'Dedicated to nurturing young minds with quality education, strong values, and limitless opportunity.'), ENT_QUOTES) ?>
        </p>

        <div class="d-flex align-items-center gap-2 mb-3" style="font-size:.85rem;">
          <i class="bi bi-geo-alt-fill" style="color:#8b5cf6;"></i>
          <span style="color:rgba(255,255,255,.6);"><?= htmlspecialchars($address, ENT_QUOTES) ?></span>
        </div>

        <!-- Social Icons -->
        <div class="d-flex gap-2 mt-4">
          <?php
          $socials = [
            [$fb, 'Facebook',  'fa-brands fa-facebook'],
            [$tw, 'Twitter',   'fa-brands fa-twitter'],
            [$ig, 'Instagram', 'fa-brands fa-instagram'],
            [$yt, 'YouTube',   'fa-brands fa-youtube'],
          ];
          foreach ($socials as [$url, $name, $icon]):
            if ($url):
          ?>
          <a href="<?= htmlspecialchars($url, ENT_QUOTES) ?>"
             target="_blank" rel="noopener noreferrer"
             aria-label="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
             class="pub-social-icon"
             style="
               width:38px; height:38px;
               background:rgba(255,255,255,.08);
               border:1px solid rgba(255,255,255,.12);
               border-radius:10px;
               display:flex; align-items:center; justify-content:center;
               color:rgba(255,255,255,.7);
               text-decoration:none;
               font-size:.9rem;
               transition:background .25s, color .25s, transform .2s, box-shadow .25s;
             "
             onmouseover="this.style.background='linear-gradient(135deg,#6366f1,#8b5cf6)';this.style.color='#fff';this.style.borderColor='transparent';this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 18px rgba(99,102,241,.45)'"
             onmouseout="this.style.background='rgba(255,255,255,.08)';this.style.color='rgba(255,255,255,.7)';this.style.borderColor='rgba(255,255,255,.12)';this.style.transform='translateY(0)';this.style.boxShadow='none'">
            <i class="<?= htmlspecialchars($icon, ENT_QUOTES) ?>"></i>
          </a>
          <?php endif; endforeach; ?>

          <?php if (!$fb && !$tw && !$ig && !$yt): ?>
            <?php foreach ([
              ['#', 'Facebook',  'fa-brands fa-facebook'],
              ['#', 'Twitter',   'fa-brands fa-twitter'],
              ['#', 'Instagram', 'fa-brands fa-instagram'],
              ['#', 'YouTube',   'fa-brands fa-youtube'],
            ] as [$url, $name, $icon]): ?>
            <a href="<?= $url ?>" aria-label="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
               style="
                 width:38px; height:38px;
                 background:rgba(255,255,255,.08);
                 border:1px solid rgba(255,255,255,.12);
                 border-radius:10px;
                 display:flex; align-items:center; justify-content:center;
                 color:rgba(255,255,255,.5);
                 text-decoration:none;
                 font-size:.9rem;
                 transition:background .25s, color .25s, transform .2s;
               "
               onmouseover="this.style.background='linear-gradient(135deg,#6366f1,#8b5cf6)';this.style.color='#fff';this.style.transform='translateY(-3px)'"
               onmouseout="this.style.background='rgba(255,255,255,.08)';this.style.color='rgba(255,255,255,.5)';this.style.transform='translateY(0)'">
              <i class="<?= htmlspecialchars($icon, ENT_QUOTES) ?>"></i>
            </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── Col 2: Quick Links ── -->
      <div class="col-lg-2 col-6">
        <h6 class="text-white fw-semibold mb-4" style="font-family:'Poppins',sans-serif;font-size:.875rem;letter-spacing:.04em;text-transform:uppercase;">
          Quick Links
        </h6>
        <ul class="list-unstyled mb-0">
          <?php foreach ([
            ['Home',        SITE_URL.'/'],
            ['About Us',    SITE_URL.'/public/about.php'],
            ['Gallery',     SITE_URL.'/public/gallery-page.php'],
            ['Notices',     SITE_URL.'/public/notices-page.php'],
            ['Contact',     SITE_URL.'/public/contact.php'],
            ['Admissions',  SITE_URL.'/public/admission.php'],
          ] as [$lbl, $url]): ?>
          <li class="mb-2">
            <a href="<?= $url ?>"
               style="
                 color:rgba(255,255,255,.55);
                 text-decoration:none;
                 font-size:.85rem;
                 font-family:'Poppins',sans-serif;
                 display:flex; align-items:center; gap:6px;
                 transition:color .2s, padding-left .2s;
               "
               onmouseover="this.style.color='#a5b4fc';this.style.paddingLeft='4px'"
               onmouseout="this.style.color='rgba(255,255,255,.55)';this.style.paddingLeft='0'">
              <i class="bi bi-chevron-right" style="font-size:.65rem;opacity:.7;"></i>
              <?= htmlspecialchars($lbl, ENT_QUOTES) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- ── Col 3: Student Portals ── -->
      <div class="col-lg-3 col-6">
        <h6 class="text-white fw-semibold mb-4" style="font-family:'Poppins',sans-serif;font-size:.875rem;letter-spacing:.04em;text-transform:uppercase;">
          Student Portals
        </h6>
        <ul class="list-unstyled mb-0">
          <?php foreach ([
            ['Student Login',    'bi-person-circle',  SITE_URL.'/auth/login.php'],
            ['Teacher Login',    'bi-person-badge',   SITE_URL.'/auth/login.php'],
            ['Parent Login',     'bi-people',         SITE_URL.'/auth/login.php'],
            ['Admin Login',      'bi-shield-lock',    SITE_URL.'/auth/login.php'],
            ['Track Admission',  'bi-search',         SITE_URL.'/public/admission-status.php'],
          ] as [$lbl, $icon, $link]): ?>
          <li class="mb-2">
            <a href="<?= $link ?>"
               style="
                 color:rgba(255,255,255,.55);
                 text-decoration:none;
                 font-size:.85rem;
                 font-family:'Poppins',sans-serif;
                 display:flex; align-items:center; gap:8px;
                 transition:color .2s, padding-left .2s;
               "
               onmouseover="this.style.color='#a5b4fc';this.style.paddingLeft='4px'"
               onmouseout="this.style.color='rgba(255,255,255,.55)';this.style.paddingLeft='0'">
              <i class="bi <?= $icon ?>" style="font-size:.85rem;opacity:.8;flex-shrink:0;"></i>
              <?= htmlspecialchars($lbl, ENT_QUOTES) ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- ── Col 4: Contact Info ── -->
      <div class="col-lg-3">
        <h6 class="text-white fw-semibold mb-4" style="font-family:'Poppins',sans-serif;font-size:.875rem;letter-spacing:.04em;text-transform:uppercase;">
          Get In Touch
        </h6>
        <ul class="list-unstyled mb-4">
          <li class="mb-3 d-flex align-items-start gap-3">
            <div style="
              width:34px;height:34px;flex-shrink:0;
              background:rgba(99,102,241,.2);
              border-radius:8px;
              display:flex;align-items:center;justify-content:center;
            ">
              <i class="bi bi-telephone-fill" style="color:#a5b4fc;font-size:.85rem;"></i>
            </div>
            <div>
              <div style="font-size:.7rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px;">Phone</div>
              <a href="tel:<?= htmlspecialchars($phone, ENT_QUOTES) ?>"
                 style="color:rgba(255,255,255,.72);text-decoration:none;font-size:.85rem;"
                 onmouseover="this.style.color='#a5b4fc'" onmouseout="this.style.color='rgba(255,255,255,.72)'">
                <?= htmlspecialchars($phone, ENT_QUOTES) ?>
              </a>
            </div>
          </li>
          <li class="mb-3 d-flex align-items-start gap-3">
            <div style="
              width:34px;height:34px;flex-shrink:0;
              background:rgba(139,92,246,.2);
              border-radius:8px;
              display:flex;align-items:center;justify-content:center;
            ">
              <i class="bi bi-envelope-fill" style="color:#c4b5fd;font-size:.85rem;"></i>
            </div>
            <div>
              <div style="font-size:.7rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px;">Email</div>
              <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES) ?>"
                 style="color:rgba(255,255,255,.72);text-decoration:none;font-size:.85rem;word-break:break-all;"
                 onmouseover="this.style.color='#a5b4fc'" onmouseout="this.style.color='rgba(255,255,255,.72)'">
                <?= htmlspecialchars($email, ENT_QUOTES) ?>
              </a>
            </div>
          </li>
        </ul>

        <a href="<?= SITE_URL ?>/public/admission.php"
           style="
             display:inline-flex; align-items:center; gap:8px;
             background: linear-gradient(135deg,#6366f1,#8b5cf6);
             color:#fff; text-decoration:none;
             font-family:'Poppins',sans-serif; font-weight:600; font-size:.82rem;
             padding:10px 22px; border-radius:10px;
             box-shadow:0 6px 20px rgba(99,102,241,.45);
             transition:box-shadow .2s, transform .15s;
           "
           onmouseover="this.style.boxShadow='0 10px 28px rgba(99,102,241,.65)';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.boxShadow='0 6px 20px rgba(99,102,241,.45)';this.style.transform='translateY(0)'">
          <i class="bi bi-pencil-square"></i>
          Apply for Admission
        </a>
      </div>

    </div><!-- /row -->
  </div><!-- /container -->

  <!-- ── Gradient Divider ── -->
  <div style="height:1px; background:linear-gradient(90deg,transparent,rgba(99,102,241,.5),rgba(139,92,246,.5),transparent);"></div>

  <!-- ── Bottom Bar ── -->
  <div style="background:rgba(0,0,0,.25);" class="py-3">
    <div class="container">
      <div class="row align-items-center g-2">
        <div class="col-md-6 text-center text-md-start">
          <p class="mb-0" style="font-size:.8rem; color:rgba(255,255,255,.45); font-family:'Poppins',sans-serif;">
            <?= htmlspecialchars($footer_text, ENT_QUOTES) ?>
          </p>
        </div>
        <div class="col-md-6 text-center text-md-end">
          <p class="mb-0" style="font-size:.78rem; color:rgba(255,255,255,.35); font-family:'Poppins',sans-serif;">
            Designed with <i class="bi bi-heart-fill" style="color:#8b5cf6;font-size:.7rem;"></i> for quality education
          </p>
        </div>
      </div>
    </div>
  </div>

</footer>
<!-- ═══════════════════════════ /FOOTER ══════════════════════════ -->

<!-- Font Awesome 6 (ensure loaded for footer social icons) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* Navbar scroll-shrink */
window.addEventListener('scroll', function() {
  var nav = document.getElementById('pubNav');
  if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
});

/* Smooth scroll for anchor links */
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
  a.addEventListener('click', function(e) {
    var t = document.querySelector(a.getAttribute('href'));
    if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth' }); }
  });
});
</script>
<?php if (!empty($extra_footer)) echo $extra_footer; ?>
</body>
</html>
