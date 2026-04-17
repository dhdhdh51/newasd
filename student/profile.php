<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$student = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$student) redirect(SITE_URL . '/auth/login.php');

$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $phone   = sanitize($_POST['phone']   ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $photo   = $student['photo'];

        if (!empty($_FILES['photo']['name'])) {
            $new_photo = upload_file($_FILES['photo'], 'students', ALLOWED_IMAGES);
            if ($new_photo === false) {
                $errors[] = 'Invalid photo file.';
            } else {
                if ($photo) delete_upload($photo);
                $photo = $new_photo;
            }
        }

        if (empty($errors)) {
            $pdo->prepare("UPDATE students SET phone=?,address=?,photo=? WHERE id=?")
                ->execute([$phone ?: null, $address ?: null, $photo, $student['id']]);
            set_flash('success', 'Profile updated successfully.');
            redirect(SITE_URL . '/student/profile.php');
        }

    } elseif ($action === 'update_parent') {
        $p_name  = sanitize($_POST['parent_name']        ?? '');
        $p_rel   = sanitize($_POST['parent_relation']    ?? 'Father');
        $p_phone = sanitize($_POST['parent_phone']       ?? '');
        $p_email = sanitize($_POST['parent_email']       ?? '');
        $p_occ   = sanitize($_POST['parent_occupation']  ?? '');
        $p_addr  = sanitize($_POST['parent_address']     ?? '');

        if (!$p_name) $errors[] = 'Parent name is required.';

        if (empty($errors)) {
            if ($student['parent_id']) {
                // Update existing parent
                $pdo->prepare(
                    "UPDATE parents SET name=?,relation=?,phone=?,email=?,occupation=?,address=? WHERE id=?"
                )->execute([$p_name, $p_rel, $p_phone ?: null, $p_email ?: null, $p_occ ?: null, $p_addr ?: null, $student['parent_id']]);
            } else {
                // Create new parent record and link
                $pdo->prepare(
                    "INSERT INTO parents (name,relation,phone,email,occupation,address) VALUES (?,?,?,?,?,?)"
                )->execute([$p_name, $p_rel, $p_phone ?: null, $p_email ?: null, $p_occ ?: null, $p_addr ?: null]);
                $new_pid = $pdo->lastInsertId();
                $pdo->prepare("UPDATE students SET parent_id=? WHERE id=?")->execute([$new_pid, $student['id']]);
            }
            set_flash('success', 'Parent information updated successfully.');
            redirect(SITE_URL . '/student/profile.php');
        }

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pwd = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user_stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $user_stmt->execute([(int)$_SESSION['user_id']]);
        $user = $user_stmt->fetch();

        if (!$user || !password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_pwd) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new_pwd !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                ->execute([password_hash($new_pwd, PASSWORD_BCRYPT), (int)$_SESSION['user_id']]);
            set_flash('success', 'Password changed successfully.');
            redirect(SITE_URL . '/student/profile.php');
        }
    }
}

// Reload student (with parent info)
$stmt = $pdo->prepare(
    "SELECT s.*, c.name as class_name, sec.name as section_name,
            p.name as parent_name, p.relation as parent_relation,
            p.phone as parent_phone, p.email as parent_email,
            p.occupation as parent_occupation, p.address as parent_address
     FROM students s
     LEFT JOIN classes c    ON s.class_id=c.id
     LEFT JOIN sections sec ON s.section_id=sec.id
     LEFT JOIN parents p    ON s.parent_id=p.id
     WHERE s.user_id=?"
);
$stmt->execute([(int)$_SESSION['user_id']]);
$student = $stmt->fetch();

$active_tab = sanitize($_GET['tab'] ?? 'profile');

$page_title = 'My Profile';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/student/'],['label'=>'Profile','active'=>true]];
include INCLUDES_PATH . 'header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger alert-dismissible fade show">
  <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4 border-bottom">
  <li class="nav-item">
    <a class="nav-link <?= $active_tab==='profile'?'active':'' ?>" href="?tab=profile">
      <i class="bi bi-person-circle me-1"></i>My Info
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab==='parent'?'active':'' ?>" href="?tab=parent">
      <i class="bi bi-people me-1"></i>Parent / Guardian
      <?php if (!$student['parent_id']): ?>
        <span class="badge bg-warning text-dark ms-1" title="No parent info added">!</span>
      <?php endif; ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $active_tab==='password'?'active':'' ?>" href="?tab=password">
      <i class="bi bi-shield-lock me-1"></i>Password
    </a>
  </li>
</ul>

<?php if ($active_tab === 'profile'): ?>
<!-- ── PROFILE TAB ──────────────────────────────────────── -->
<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm premium-card">
      <div class="card-header border-0 bg-transparent fw-bold">
        <i class="bi bi-person-circle me-2 text-primary"></i>Personal Information
      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_profile">

          <div class="text-center mb-4">
            <div class="photo-upload-wrap mx-auto" style="position:relative;width:100px">
              <img id="photoPreview"
                   src="<?= $student['photo'] ? get_upload_url($student['photo']) : ASSETS_URL.'/images/avatar.png' ?>"
                   class="rounded-circle border shadow" width="100" height="100" style="object-fit:cover">
              <label for="photo" class="position-absolute bottom-0 end-0 btn btn-primary btn-sm rounded-circle p-1"
                     style="width:28px;height:28px;cursor:pointer" title="Change Photo">
                <i class="bi bi-camera-fill" style="font-size:.75rem"></i>
              </label>
            </div>
            <input type="file" id="photo" name="photo" class="d-none" accept="image/*">
            <div class="small text-muted mt-2">Click the camera to change photo</div>
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted small">Full Name</label>
              <input type="text" class="form-control bg-light" value="<?= sanitize($student['name']) ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted small">Student ID</label>
              <input type="text" class="form-control bg-light font-monospace" value="<?= sanitize($student['student_id']) ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted small">Email</label>
              <input type="text" class="form-control bg-light" value="<?= sanitize($student['email'] ?? '-') ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted small">Class</label>
              <input type="text" class="form-control bg-light"
                     value="<?= sanitize(($student['class_name']??'-').($student['section_name']?' - '.$student['section_name']:'')) ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted small">Date of Birth</label>
              <input type="text" class="form-control bg-light" value="<?= format_date($student['dob']) ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted small">Gender</label>
              <input type="text" class="form-control bg-light" value="<?= sanitize($student['gender'] ?? '-') ?>" readonly>
            </div>

            <!-- Editable -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Phone <span class="badge bg-primary bg-opacity-10 text-primary ms-1">Editable</span></label>
              <input type="tel" name="phone" class="form-control premium-input"
                     value="<?= sanitize($student['phone'] ?? '') ?>" placeholder="+91 9000000000">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Address <span class="badge bg-primary bg-opacity-10 text-primary ms-1">Editable</span></label>
              <textarea name="address" class="form-control premium-input" rows="2"><?= sanitize($student['address'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save me-2"></i>Save Changes
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm premium-card">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Quick Info</h6>
        <?php $info = [
          ['Blood Group',    $student['blood_group'] ?? '-', 'droplet-fill text-danger'],
          ['Admission Date', format_date($student['admission_date']), 'calendar-check text-success'],
          ['Status',         ucfirst($student['status']), 'circle-fill text-'.($student['status']==='active'?'success':'secondary')],
          ['Parent',         $student['parent_name'] ?? 'Not linked', 'people-fill text-info'],
        ]; ?>
        <?php foreach ($info as [$label,$val,$icon]): ?>
        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
          <i class="bi bi-<?= $icon ?> fs-5 flex-shrink-0"></i>
          <div><div class="text-muted small"><?= $label ?></div><div class="fw-semibold small"><?= sanitize((string)$val) ?></div></div>
        </div>
        <?php endforeach; ?>
        <?php if (!$student['parent_id']): ?>
        <a href="?tab=parent" class="btn btn-warning w-100 btn-sm mt-1">
          <i class="bi bi-person-plus me-1"></i>Add Parent Info
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php elseif ($active_tab === 'parent'): ?>
<!-- ── PARENT TAB ──────────────────────────────────────── -->
<div class="row g-3 justify-content-center">
  <div class="col-12 col-lg-7">
    <div class="card border-0 shadow-sm premium-card">
      <div class="card-header border-0 bg-transparent fw-bold">
        <i class="bi bi-people-fill me-2 text-info"></i>
        <?= $student['parent_id'] ? 'Update Parent / Guardian' : 'Add Parent / Guardian' ?>
      </div>
      <div class="card-body">
        <?php if (!$student['parent_id']): ?>
        <div class="alert alert-warning d-flex gap-2 align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill fs-5"></i>
          <div>No parent / guardian has been linked to your profile yet. Fill in the form below to add one. This information will appear on your report card.</div>
        </div>
        <?php endif; ?>

        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_parent">

          <div class="row g-3">
            <div class="col-12 col-md-8">
              <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="parent_name" class="form-control premium-input" required
                     value="<?= sanitize($student['parent_name'] ?? '') ?>" placeholder="e.g. Rajesh Kumar">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold">Relation</label>
              <select name="parent_relation" class="form-select premium-input">
                <?php foreach (['Father','Mother','Guardian'] as $rel): ?>
                <option value="<?= $rel ?>" <?= ($student['parent_relation'] ?? 'Father') === $rel ? 'selected' : '' ?>><?= $rel ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Phone</label>
              <input type="tel" name="parent_phone" class="form-control premium-input"
                     value="<?= sanitize($student['parent_phone'] ?? '') ?>" placeholder="+91 9000000000">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Email</label>
              <input type="email" name="parent_email" class="form-control premium-input"
                     value="<?= sanitize($student['parent_email'] ?? '') ?>" placeholder="parent@email.com">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Occupation</label>
              <input type="text" name="parent_occupation" class="form-control premium-input"
                     value="<?= sanitize($student['parent_occupation'] ?? '') ?>" placeholder="e.g. Engineer">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Address</label>
              <input type="text" name="parent_address" class="form-control premium-input"
                     value="<?= sanitize($student['parent_address'] ?? '') ?>" placeholder="Residential address">
            </div>
            <div class="col-12 d-flex gap-2">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save me-2"></i><?= $student['parent_id'] ? 'Update' : 'Save' ?> Parent Info
              </button>
              <a href="?tab=profile" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <?php if ($student['parent_id']): ?>
    <div class="card border-0 shadow-sm premium-card mt-3">
      <div class="card-body">
        <h6 class="fw-bold mb-3 text-success"><i class="bi bi-check-circle-fill me-2"></i>Current Parent Record</h6>
        <div class="row g-2">
          <?php $pdata = [
            ['Name',       $student['parent_name'],       'person-fill'],
            ['Relation',   $student['parent_relation'],   'heart-fill'],
            ['Phone',      $student['parent_phone'],      'telephone-fill'],
            ['Email',      $student['parent_email'],      'envelope-fill'],
            ['Occupation', $student['parent_occupation'], 'briefcase-fill'],
          ]; ?>
          <?php foreach ($pdata as [$lbl,$val,$ic]): if (!$val) continue; ?>
          <div class="col-12 col-md-6">
            <div class="d-flex gap-2 align-items-center">
              <i class="bi bi-<?= $ic ?> text-primary flex-shrink-0"></i>
              <div><div class="text-muted" style="font-size:.72rem"><?= $lbl ?></div><div class="fw-semibold small"><?= sanitize($val) ?></div></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($active_tab === 'password'): ?>
<!-- ── PASSWORD TAB ──────────────────────────────────────── -->
<div class="row g-3 justify-content-center">
  <div class="col-12 col-lg-5">
    <div class="card border-0 shadow-sm premium-card">
      <div class="card-header border-0 bg-transparent fw-bold">
        <i class="bi bi-shield-lock me-2 text-warning"></i>Change Password
      </div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3">
            <label class="form-label fw-semibold">Current Password</label>
            <input type="password" name="current_password" class="form-control premium-input" required autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">New Password</label>
            <input type="password" name="new_password" class="form-control premium-input" required minlength="8" autocomplete="new-password">
            <div class="form-text">Minimum 8 characters.</div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control premium-input" required autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-warning w-100">
            <i class="bi bi-lock me-2"></i>Change Password
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
document.getElementById('photo')?.addEventListener('change', function() {
  const file = this.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => { document.getElementById('photoPreview').src = e.target.result; };
    reader.readAsDataURL(file);
  }
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
