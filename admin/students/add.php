<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');

$page_title = 'Add Student';
$breadcrumb = [
    ['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],
    ['label'=>'Students','url'=>SITE_URL.'/admin/students/'],
    ['label'=>'Add Student','active'=>true]
];

$errors  = [];
$classes = get_classes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $name       = sanitize($_POST['name']       ?? '');
    $email      = sanitize_email($_POST['email'] ?? '');
    $phone      = sanitize($_POST['phone']       ?? '');
    $dob        = sanitize($_POST['dob']         ?? '');
    $gender     = sanitize($_POST['gender']      ?? '');
    $blood      = sanitize($_POST['blood_group'] ?? '');
    $address    = sanitize($_POST['address']     ?? '');
    $class_id   = sanitize_int($_POST['class_id']   ?? 0);
    $section_id = sanitize_int($_POST['section_id'] ?? 0);
    $adm_date   = sanitize($_POST['admission_date'] ?? date('Y-m-d'));
    $pwd        = $_POST['password']             ?? '';
    $status     = sanitize($_POST['status']      ?? 'active');

    // Validation
    if (empty($name))    $errors[] = 'Name is required.';
    if (empty($class_id)) $errors[] = 'Class is required.';
    if (!empty($email) && !validate_email($email)) $errors[] = 'Invalid email.';
    if (!empty($pwd) && strlen($pwd) < 6) $errors[] = 'Password must be at least 6 characters.';

    // Check email uniqueness
    if (!empty($email)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) $errors[] = 'Email already exists.';
    }

    // Photo upload
    $photo = null;
    if (!empty($_FILES['photo']['name'])) {
        $photo = upload_file($_FILES['photo'], 'students', ALLOWED_IMAGES);
        if ($photo === false) $errors[] = 'Invalid photo. Allowed: JPG, PNG, GIF (max 5MB).';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $student_id  = generate_student_id();
            $user_id     = null;

            // Create user account if email provided
            if (!empty($email) && !empty($pwd)) {
                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                $ustmt = $pdo->prepare(
                    "INSERT INTO users (name,email,password,role) VALUES (?,?,?,'student')"
                );
                $ustmt->execute([$name, $email, $hash]);
                $user_id = (int)$pdo->lastInsertId();
            }

            $sstmt = $pdo->prepare(
                "INSERT INTO students
                 (user_id,student_id,name,email,phone,dob,gender,blood_group,
                  address,class_id,section_id,photo,admission_date,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $sstmt->execute([
                $user_id, $student_id, $name, $email ?: null, $phone ?: null,
                $dob ?: null, $gender ?: null, $blood ?: null,
                $address ?: null, $class_id ?: null, $section_id ?: null,
                $photo, $adm_date, $status
            ]);
            $student_db_id = (int)$pdo->lastInsertId();

            $pdo->commit();

            // Notification
            create_notification(
                $user_id, 'student',
                'Welcome to ' . get_setting('site_name','School ERP'),
                "Your student account ({$student_id}) has been created.",
                'success'
            );

            // Email welcome
            if ($user_id && !empty($email)) {
                SchoolMailer::sendWelcome($email, $name, 'student', $pwd);
            }

            set_flash('success', "Student '{$name}' added successfully! ID: {$student_id}");
            redirect(SITE_URL . '/admin/students/view.php?id=' . $student_db_id);

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

include INCLUDES_PATH . 'header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <strong>Please fix the following errors:</strong>
  <ul class="mb-0 mt-1">
    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 fw-semibold">
    <i class="bi bi-person-plus me-2 text-primary"></i>Student Information
  </div>
  <div class="card-body">
    <form method="POST" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>

      <div class="row g-3">
        <!-- Photo upload -->
        <div class="col-12 text-center mb-2">
          <div class="photo-upload-wrap mx-auto">
            <img id="photoPreview" src="<?= ASSETS_URL ?>/images/avatar.png"
                 class="rounded-circle border" width="100" height="100" style="object-fit:cover">
            <label class="photo-upload-btn" for="photo">
              <i class="bi bi-camera-fill"></i>
            </label>
          </div>
          <input type="file" id="photo" name="photo" class="d-none"
                 accept="image/jpeg,image/png,image/gif">
          <div class="small text-muted mt-1">Click to upload photo (optional)</div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control"
                 value="<?= sanitize($_POST['name'] ?? '') ?>" required placeholder="Student's full name">
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label fw-semibold">Email Address</label>
          <input type="email" name="email" class="form-control"
                 value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="student@email.com">
          <div class="form-text">Required for portal access.</div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label fw-semibold">Phone</label>
          <input type="tel" name="phone" class="form-control"
                 value="<?= sanitize($_POST['phone'] ?? '') ?>" placeholder="+91 9000000000">
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label fw-semibold">Date of Birth</label>
          <input type="date" name="dob" class="form-control"
                 value="<?= sanitize($_POST['dob'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label fw-semibold">Gender</label>
          <select name="gender" class="form-select">
            <option value="">Select</option>
            <?php foreach (['Male','Female','Other'] as $g): ?>
            <option value="<?= $g ?>" <?= ($_POST['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label fw-semibold">Blood Group</label>
          <select name="blood_group" class="form-select">
            <option value="">Select</option>
            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
            <option value="<?= $bg ?>" <?= ($_POST['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
          <select name="class_id" class="form-select" id="classSelect" required>
            <option value="">Select Class</option>
            <?php foreach ($classes as $cl): ?>
            <option value="<?= $cl['id'] ?>"
              <?= (sanitize_int($_POST['class_id'] ?? 0)) == $cl['id'] ? 'selected' : '' ?>>
              <?= sanitize($cl['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label fw-semibold">Section</label>
          <select name="section_id" class="form-select" id="sectionSelect">
            <option value="">Select Section</option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label fw-semibold">Admission Date</label>
          <input type="date" name="admission_date" class="form-control"
                 value="<?= sanitize($_POST['admission_date'] ?? date('Y-m-d')) ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Address</label>
          <textarea name="address" class="form-control" rows="2"
                    placeholder="Full address"><?= sanitize($_POST['address'] ?? '') ?></textarea>
        </div>

        <div class="col-12"><hr class="text-muted"></div>
        <div class="col-12"><h6 class="fw-semibold text-muted">Portal Login Credentials</h6></div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-semibold">Login Password</label>
          <input type="password" name="password" class="form-control"
                 placeholder="Min 6 characters (leave blank = no login)">
          <div class="form-text">Set only if student needs portal access.</div>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="active"   <?= ($_POST['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($_POST['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="col-12 d-flex gap-2 pt-2">
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-2"></i>Add Student
          </button>
          <a href="<?= SITE_URL ?>/admin/students/" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Photo preview
document.getElementById('photo').addEventListener('change', function() {
  const file = this.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => { document.getElementById('photoPreview').src = e.target.result; };
    reader.readAsDataURL(file);
  }
});

// Load sections via AJAX when class changes
document.getElementById('classSelect').addEventListener('change', function() {
  const classId = this.value;
  const secSel  = document.getElementById('sectionSelect');
  secSel.innerHTML = '<option value="">Loading...</option>';

  if (!classId) {
    secSel.innerHTML = '<option value="">Select Section</option>';
    return;
  }

  fetch('<?= SITE_URL ?>/admin/ajax/get-sections.php?class_id=' + classId)
    .then(r => r.json())
    .then(data => {
      secSel.innerHTML = '<option value="">Select Section</option>';
      data.forEach(s => {
        secSel.innerHTML += `<option value="${s.id}">${s.name}</option>`;
      });
    })
    .catch(() => { secSel.innerHTML = '<option value="">Error loading</option>'; });
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
