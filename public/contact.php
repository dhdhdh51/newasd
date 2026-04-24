<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title = 'Contact Us';
$active_nav = 'contact';
$meta_desc  = 'Get in touch with us — address, phone, email, and enquiry form.';
$map_embed  = get_setting('contact_map_embed','');

$sent = false;
$err  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name    = trim(htmlspecialchars($_POST['c_name']    ?? '', ENT_QUOTES));
    $c_email = trim(htmlspecialchars($_POST['c_email']   ?? '', ENT_QUOTES));
    $subject = trim(htmlspecialchars($_POST['c_subject'] ?? '', ENT_QUOTES));
    $msg     = trim(htmlspecialchars($_POST['c_message'] ?? '', ENT_QUOTES));
    if ($name && $c_email && filter_var($_POST['c_email'], FILTER_VALIDATE_EMAIL) && $msg) {
        $pdo->prepare(
            "INSERT INTO notifications (user_id,role,title,message,type,is_read)
             VALUES (NULL,'admin',?,?,?,0)"
        )->execute(["Website Enquiry: $subject", "From: $name <$c_email>\n\n$msg", 'info']);
        try {
            require_once INCLUDES_PATH.'mailer.php';
            $mail = new SchoolMailer();
            $mail->addAddress(get_setting('contact_email','info@school.com'));
            $mail->setSubject("Website Enquiry: $subject");
            $mail->setBody(SchoolMailer::emailTemplate(
                "New Website Enquiry",
                "<p><strong>From:</strong> $name ($c_email)</p>
                 <p><strong>Subject:</strong> $subject</p>
                 <p><strong>Message:</strong><br>".nl2br($msg)."</p>"
            ));
            $mail->send();
        } catch(Exception $e) { /* non-fatal */ }
        $sent = true;
    } else {
        $err = 'Please fill in all required fields with a valid email address.';
    }
}

$extra_head = '<style>
  body { background: #f0f2ff; }

  .pub-bc-strip {
    background:linear-gradient(90deg,rgba(99,102,241,.07),rgba(139,92,246,.07));
    border-bottom:1px solid rgba(99,102,241,.12); padding:10px 0;
  }

  /* ── Info cards ── */
  .contact-info-card {
    background:rgba(255,255,255,.88); backdrop-filter:blur(12px);
    border:1px solid rgba(99,102,241,.15); border-radius:20px;
    padding:28px 20px; text-align:center;
    box-shadow:0 4px 18px rgba(99,102,241,.1);
    transition:transform .25s, box-shadow .25s;
    text-decoration:none; display:block;
  }
  .contact-info-card:hover { transform:translateY(-5px); box-shadow:0 14px 36px rgba(99,102,241,.2); }
  .contact-info-icon {
    width:60px; height:60px; border-radius:18px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.5rem; margin:0 auto 16px;
  }
  .contact-info-label {
    font-size:.7rem; letter-spacing:.08em; text-transform:uppercase;
    color:#94a3b8; font-weight:600; margin-bottom:4px;
    font-family:"Poppins",sans-serif;
  }
  .contact-info-val {
    font-size:.9rem; font-weight:600; color:#1e293b;
    font-family:"Poppins",sans-serif;
  }

  /* ── Map section ── */
  .map-wrap { border-radius:20px; overflow:hidden; box-shadow:0 8px 32px rgba(99,102,241,.15); }
  .map-placeholder {
    height:380px; background:linear-gradient(135deg,rgba(99,102,241,.05),rgba(139,92,246,.05));
    border:2px dashed rgba(99,102,241,.2); border-radius:20px;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
  }

  /* ── Form card ── */
  .contact-form-card {
    background:rgba(255,255,255,.88); backdrop-filter:blur(16px);
    border:1px solid rgba(99,102,241,.15); border-radius:24px; padding:36px;
    box-shadow:0 8px 32px rgba(99,102,241,.12);
  }

  /* ── Premium inputs ── */
  .prem-form-control {
    border:1.5px solid rgba(99,102,241,.2); border-radius:12px;
    padding:12px 16px; font-family:"Poppins",sans-serif; font-size:.875rem;
    background:rgba(255,255,255,.9); color:#1e293b;
    transition:border-color .2s, box-shadow .2s;
    width:100%;
  }
  .prem-form-control:focus {
    outline:none; border-color:#6366f1;
    box-shadow:0 0 0 4px rgba(99,102,241,.12);
  }
  .prem-form-control::placeholder { color:#94a3b8; }
  label.prem-label {
    font-size:.8rem; font-weight:600; color:#374151; margin-bottom:6px;
    display:block; font-family:"Poppins",sans-serif;
  }

  /* ── Submit btn ── */
  .btn-contact-submit {
    width:100%; background:linear-gradient(135deg,#6366f1,#8b5cf6);
    border:none; color:#fff; font-family:"Poppins",sans-serif; font-weight:600;
    font-size:.9rem; padding:14px; border-radius:12px;
    box-shadow:0 6px 20px rgba(99,102,241,.4);
    transition:box-shadow .2s, transform .15s, opacity .2s; cursor:pointer;
  }
  .btn-contact-submit:hover {
    box-shadow:0 10px 28px rgba(99,102,241,.55);
    transform:translateY(-2px); opacity:.95;
  }
</style>';
include INCLUDES_PATH.'public_header.php';
?>

<!-- Page Hero -->
<div class="page-hero text-center">
  <div class="container">
    <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(8px);border-radius:50px;padding:6px 20px;font-size:.78rem;letter-spacing:.1em;text-transform:uppercase;color:rgba(165,180,252,.9);margin-bottom:20px;font-weight:500;">
      <i class="bi bi-telephone"></i> Contact
    </div>
    <h1 class="page-hero-title mb-3">Get In Touch</h1>
    <p class="page-hero-sub mb-0" style="max-width:520px;margin:0 auto;">
      We'd love to hear from you. Reach out with any questions about admissions, academics, or general enquiries.
    </p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="pub-bc-strip">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb pub-breadcrumb mb-0" style="background:transparent;">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <li class="breadcrumb-item active" style="color:#6366f1;">Contact Us</li>
      </ol>
    </nav>
  </div>
</div>

<!-- Contact Info Cards -->
<section class="py-5" style="background:linear-gradient(135deg,rgba(99,102,241,.05),rgba(139,92,246,.04));">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <?php foreach ([
        ['bi-geo-alt-fill',  'rgba(99,102,241,.12)',  '#6366f1', 'Address',      get_setting('contact_address','123 School Street, City'), '#'],
        ['bi-telephone-fill','rgba(16,185,129,.12)',   '#10b981', 'Phone',        get_setting('contact_phone','+91 9000000000'),             'tel:'.preg_replace('/\s+/','',get_setting('contact_phone','+91 9000000000'))],
        ['bi-envelope-fill', 'rgba(245,158,11,.12)',   '#f59e0b', 'Email',        get_setting('contact_email','info@school.com'),             'mailto:'.get_setting('contact_email','info@school.com')],
        ['bi-clock-fill',    'rgba(6,182,212,.12)',    '#06b6d4', 'Office Hours', 'Mon–Sat: 8:00 AM – 4:00 PM',                              '#'],
      ] as [$icon,$bg,$color,$label,$value,$href]): ?>
      <div class="col-12 col-md-6 col-lg-3">
        <a href="<?= htmlspecialchars($href,ENT_QUOTES) ?>" class="contact-info-card">
          <div class="contact-info-icon" style="background:<?= $bg ?>;">
            <i class="bi <?= $icon ?>" style="color:<?= $color ?>;"></i>
          </div>
          <div class="contact-info-label"><?= $label ?></div>
          <div class="contact-info-val"><?= htmlspecialchars($value,ENT_QUOTES) ?></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Map + Form -->
<section class="py-5" style="background:#f0f2ff;">
  <div class="container">
    <div class="row g-5">

      <!-- Map -->
      <div class="col-lg-6">
        <div class="section-badge" style="background:rgba(99,102,241,.1);color:#6366f1;">Find Us</div>
        <h2 class="section-title mt-2 mb-4">Our Location</h2>

        <?php if ($map_embed): ?>
        <div class="map-wrap" style="height:380px;">
          <?= $map_embed ?>
        </div>
        <?php else: ?>
        <div class="map-placeholder">
          <i class="bi bi-map" style="font-size:3.5rem;color:rgba(99,102,241,.3);"></i>
          <p style="color:#94a3b8;margin:12px 0 4px;font-weight:500;">Map not configured yet</p>
          <small style="color:#cbd5e1;">Admin → Settings → Contact → Map Embed</small>
        </div>
        <?php endif; ?>

        <div class="mt-4 p-4 rounded-4"
             style="background:rgba(255,255,255,.8);backdrop-filter:blur(10px);border:1px solid rgba(99,102,241,.12);">
          <h6 style="font-family:'Poppins',sans-serif;font-weight:700;color:#1e293b;margin-bottom:8px;">
            <i class="bi bi-geo-alt-fill me-2" style="color:#6366f1;"></i>Full Address
          </h6>
          <p style="color:#64748b;font-size:.875rem;margin-bottom:0;line-height:1.7;">
            <?= nl2br(htmlspecialchars(get_setting('contact_address','123 School Street, City'),ENT_QUOTES)) ?>
          </p>
        </div>
      </div>

      <!-- Form -->
      <div class="col-lg-6">
        <div class="section-badge" style="background:rgba(99,102,241,.1);color:#6366f1;">Send a Message</div>
        <h2 class="section-title mt-2 mb-4">Enquiry Form</h2>

        <div class="contact-form-card">
          <?php if ($sent): ?>
          <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:12px;margin-bottom:24px;">
            <div style="width:40px;height:40px;background:#10b981;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="bi bi-check-lg text-white fs-5"></i>
            </div>
            <div>
              <div style="font-weight:700;color:#065f46;font-family:'Poppins',sans-serif;font-size:.9rem;">Message Sent!</div>
              <div style="color:#047857;font-size:.82rem;">Thank you! We'll respond within 24 hours.</div>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($err): ?>
          <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:14px 18px;color:#b91c1c;font-size:.875rem;margin-bottom:20px;">
            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($err,ENT_QUOTES) ?>
          </div>
          <?php endif; ?>

          <form method="POST">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="prem-label">Your Name <span style="color:#ef4444;">*</span></label>
                <input type="text" name="c_name" class="prem-form-control" placeholder="Full name" required>
              </div>
              <div class="col-md-6">
                <label class="prem-label">Email Address <span style="color:#ef4444;">*</span></label>
                <input type="email" name="c_email" class="prem-form-control" placeholder="you@email.com" required>
              </div>
              <div class="col-12">
                <label class="prem-label">Phone Number</label>
                <input type="tel" name="c_phone" class="prem-form-control" placeholder="+91 XXXXX XXXXX">
              </div>
              <div class="col-12">
                <label class="prem-label">Subject</label>
                <select name="c_subject" class="prem-form-control">
                  <option>General Enquiry</option>
                  <option>Admission Enquiry</option>
                  <option>Fee Related</option>
                  <option>Academic Enquiry</option>
                  <option>Other</option>
                </select>
              </div>
              <div class="col-12">
                <label class="prem-label">Your Message <span style="color:#ef4444;">*</span></label>
                <textarea name="c_message" class="prem-form-control" rows="5"
                          placeholder="Write your message here…" required style="resize:vertical;"></textarea>
              </div>
              <div class="col-12">
                <button type="submit" name="contact_submit" class="btn-contact-submit">
                  <i class="bi bi-send me-2"></i>Send Message
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include INCLUDES_PATH.'public_footer.php'; ?>
