<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
auth_guard('student');

$student = get_student_by_user_id((int)$_SESSION['user_id']);
if (!$student) redirect(SITE_URL . '/auth/login.php');

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();

    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $phone   = sanitize($_POST['phone']   ?? '');
        $address = sanitize($_POST['address'] ?? '');

        $photo = $student['photo'];
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

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pwd = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Verify current password
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
            $hash = password_hash($new_pwd, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                ->execute([$hash, (int)$_SESSION['user_id']]);
            set_flash('success', 'Password changed successfully.');
            redirect(SITE_URL . '/student/profile.php');
        }
    }
}

// Reload student after update
$student = get_student_by_user_id((int)$_SESSION['user_id']);

$page_title = 'My Profile';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/student/'],['label'=>'Profile','active'=>true]];
include INCLUDES_PATH . 'header.php';
?>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row g-3">
  <!-- Profile Info -->
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-person-circle me-2 text-primary"></i>My Information
      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_profile">

          <div class="text-center mb-4">
            <div class="photo-upload-wrap mx-auto">
              <img id="photoPreview"
                   src="<?= $student['photo'] ? get_upload_url($student['photo']) : ASSETS_URL.'/images/avatar.png' ?>"
                   class="rounded-circle border" width="100" height="100" style="object-fit:cover">
              <label class="photo-upload-btn" for="photo">
                <i class="bi bi-camera-fill"></i>
              </label>
            </div>
            <input type="file" id="photo" name="photo" class="d-none" accept="image/*">
            <div class="small text-muted mt-1">Click to change photo</div>
          </div>

          <!-- Read-only fields -->
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted">Full Name</label>
              <input type="text" class="form-control bg-light" value="<?= sanitize($student['name']) ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted">Student ID</label>
              <input type="text" class="form-control bg-light font-monospace" value="<?= sanitize($student['student_id']) ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted">Email</label>
              <input type="text" class="form-control bg-light" value="<?= sanitize($student['email'] ?? '-') ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted">Class</label>
              <input type="text" class="form-control bg-light" value="<?= sanitize(($student['class_name']??'-').($student['section_name']?' - '.$student['section_name']:'')) ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted">Date of Birth</label>
              <input type="text" class="form-control bg-light" value="<?= format_date($student['dob']) ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold text-muted">Gender</label>
              <input type="text" class="form-control bg-light" value="<?= sanitize($student['gender'] ?? '-') ?>" readonly>
            </div>

            <!-- Editable fields -->
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Phone <span class="text-primary small">(editable)</span></label>
              <input type="tel" name="phone" class="form-control"
                     value="<?= sanitize($student['phone'] ?? '') ?>" placeholder="+91 9000000000">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Address <span class="text-primary small">(editable)</span></label>
              <textarea name="address" class="form-control" rows="2"><?= sanitize($student['address'] ?? '') ?></textarea>
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

  <!-- Change Password -->
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">
        <i class="bi bi-shield-lock me-2 text-warning"></i>Change Password
      </div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3">
            <label class="form-label fw-semibold">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="8">
            <div class="form-text">Minimum 8 characters.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-warning w-100">
            <i class="bi bi-lock me-2"></i>Change Password
          </button>
        </form>
      </div>
    </div>

    <!-- Quick info card -->
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-body">
        <h6 class="fw-semibold mb-3">Quick Info</h6>
        <?php $info = [
          ['Blood Group',  $student['blood_group'] ?? '-',     'droplet'],
          ['Admission',    format_date($student['admission_date']), 'calendar'],
          ['Status',       ucfirst($student['status']),        'circle-fill'],
        ]; ?>
        <?php foreach ($info as [$label,$val,$icon]): ?>
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="bi bi-<?= $icon ?> text-primary flex-shrink-0"></i>
          <div class="small"><span class="text-muted"><?= $label ?>: </span><strong><?= sanitize((string)$val) ?></strong></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('photo').addEventListener('change', function() {
  const file = this.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = e => { document.getElementById('photoPreview').src = e.target.result; };
    reader.readAsDataURL(file);
  }
});
</script>

<?php include INCLUDES_PATH . 'footer.php'; ?>
