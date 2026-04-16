<?php
require_once dirname(__DIR__).'/config/config.php';
$page_title = 'Contact Us';
$active_nav = 'contact';
$meta_desc  = 'Get in touch with us — address, phone, email, and Google Maps location.';
$map_embed  = get_setting('contact_map_embed','');
include INCLUDES_PATH.'public_header.php';
?>

<!-- Page Hero -->
<div class="page-hero text-center text-white">
  <div class="container">
    <div style="display:inline-block;background:rgba(255,255,255,.15);border-radius:50px;padding:6px 20px;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:16px;">
      <i class="bi bi-telephone me-1"></i>Contact
    </div>
    <h1 class="fw-bold mb-3" style="font-size:clamp(2rem,4vw,3rem);">Get In Touch</h1>
    <p class="mb-0 opacity-75" style="max-width:520px;margin:0 auto;">We'd love to hear from you. Reach out with any questions about admissions, academics, or general enquiries.</p>
  </div>
</div>

<!-- Breadcrumb -->
<div class="bg-light border-bottom py-2">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <li class="breadcrumb-item active">Contact Us</li>
      </ol>
    </nav>
  </div>
</div>

<!-- Contact Info Cards -->
<section class="py-5" style="background:#f8f9ff;">
  <div class="container">
    <div class="row g-4 justify-content-center">
      <?php foreach ([
        ['bi-geo-alt-fill',  'text-primary','Our Address',   get_setting('contact_address','123 School Street, City'),  '#'],
        ['bi-telephone-fill','text-success','Call Us',       get_setting('contact_phone','+91 9000000000'),              'tel:'.preg_replace('/\s+/','',$_=get_setting('contact_phone','+91 9000000000'))],
        ['bi-envelope-fill', 'text-warning','Email Us',      get_setting('contact_email','info@school.com'),             'mailto:'.get_setting('contact_email','info@school.com')],
        ['bi-clock-fill',    'text-info',   'Office Hours',  'Mon–Sat: 8:00 AM – 4:00 PM',                              '#'],
      ] as [$icon,$cls,$label,$value,$href]): ?>
      <div class="col-12 col-md-6 col-lg-3">
        <a href="<?= htmlspecialchars($href,ENT_QUOTES) ?>"
           class="d-block text-decoration-none h-100 text-center p-4 rounded-4 bg-white border shadow-sm"
           style="transition:transform .2s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
          <div class="mx-auto mb-3 rounded-3 d-flex align-items-center justify-content-center"
               style="width:56px;height:56px;background:var(--bs-primary-bg-subtle);">
            <i class="bi <?= $icon ?> <?= $cls ?> fs-3"></i>
          </div>
          <h6 class="fw-bold text-dark"><?= $label ?></h6>
          <p class="text-muted small mb-0"><?= htmlspecialchars($value,ENT_QUOTES) ?></p>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Map + Form -->
<section class="py-5" style="background:#fff;">
  <div class="container">
    <div class="row g-5">
      <!-- Map -->
      <div class="col-lg-6">
        <div class="section-badge">Find Us</div>
        <h2 class="section-title mb-4">Our Location</h2>
        <?php if ($map_embed): ?>
        <div class="rounded-4 overflow-hidden shadow-sm" style="height:380px;">
          <?= $map_embed /* Admin pastes full <iframe> from Google Maps */ ?>
        </div>
        <?php else: ?>
        <div class="rounded-4 d-flex flex-column align-items-center justify-content-center border bg-light"
             style="height:380px;">
          <i class="bi bi-map text-muted" style="font-size:4rem;"></i>
          <p class="text-muted mt-3 mb-1">Map not configured yet.</p>
          <small class="text-muted">Admin can add Google Maps embed via Admin → Settings → General → Contact.</small>
        </div>
        <?php endif; ?>
        <!-- Address box below map -->
        <div class="mt-4 p-4 rounded-4 border bg-light">
          <h6 class="fw-bold mb-2"><i class="bi bi-geo-alt-fill text-primary me-2"></i>Full Address</h6>
          <p class="text-muted mb-0 small"><?= nl2br(htmlspecialchars(get_setting('contact_address','123 School Street, City'),ENT_QUOTES)) ?></p>
        </div>
      </div>

      <!-- Enquiry Form -->
      <div class="col-lg-6">
        <div class="section-badge">Send a Message</div>
        <h2 class="section-title mb-4">Enquiry Form</h2>
        <?php
        $sent = false;
        $err  = '';
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['contact_submit'])) {
            $name    = trim(htmlspecialchars($_POST['c_name']    ?? '', ENT_QUOTES));
            $c_email = trim(htmlspecialchars($_POST['c_email']   ?? '', ENT_QUOTES));
            $subject = trim(htmlspecialchars($_POST['c_subject'] ?? '', ENT_QUOTES));
            $msg     = trim(htmlspecialchars($_POST['c_message'] ?? '', ENT_QUOTES));
            if ($name && $c_email && $msg) {
                // Log as a general notification for admin
                $pdo->prepare(
                    "INSERT INTO notifications (user_id,role,title,message,type,is_read)
                     VALUES (NULL,'admin',?,?,?,0)"
                )->execute([
                    "Website Enquiry: $subject",
                    "From: $name <$c_email>\n\n$msg",
                    'info'
                ]);
                // Attempt email
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
                $err = 'Please fill in all required fields.';
            }
        }
        ?>
        <?php if ($sent): ?>
        <div class="alert alert-success rounded-3">
          <i class="bi bi-check-circle-fill me-2"></i>
          Thank you! Your message has been received. We'll respond within 24 hours.
        </div>
        <?php endif; ?>
        <?php if ($err): ?>
        <div class="alert alert-danger rounded-3"><?= $err ?></div>
        <?php endif; ?>
        <form method="POST" class="needs-validation" novalidate>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
              <input type="text" name="c_name" class="form-control" placeholder="Full name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <input type="email" name="c_email" class="form-control" placeholder="your@email.com" required>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Phone</label>
              <input type="tel" name="c_phone" class="form-control" placeholder="+91 XXXXX XXXXX">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Subject</label>
              <select name="c_subject" class="form-select">
                <option>General Enquiry</option>
                <option>Admission Enquiry</option>
                <option>Fee Related</option>
                <option>Academic Enquiry</option>
                <option>Other</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
              <textarea name="c_message" class="form-control" rows="5"
                        placeholder="Write your message here…" required></textarea>
            </div>
            <div class="col-12">
              <button type="submit" name="contact_submit" class="btn btn-primary btn-lg w-100">
                <i class="bi bi-send me-2"></i>Send Message
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include INCLUDES_PATH.'public_footer.php'; ?>
