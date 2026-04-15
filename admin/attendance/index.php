<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once INCLUDES_PATH . 'auth_check.php';
require_once INCLUDES_PATH . 'mailer.php';
auth_guard('admin');

$page_title = 'Attendance';
$breadcrumb = [['label'=>'Dashboard','url'=>SITE_URL.'/admin/'],['label'=>'Attendance','active'=>true]];

$classes  = get_classes();
$class_id = sanitize_int($_GET['class_id'] ?? 0);
$section_id = sanitize_int($_GET['section_id'] ?? 0);
$date     = sanitize($_GET['date'] ?? date('Y-m-d'));
$sections = $class_id ? get_sections_by_class($class_id) : [];

$students = [];
$attendance_map = [];

if ($class_id && $date) {
    $where  = ['s.class_id=?', "s.status='active'"];
    $params = [$class_id];
    if ($section_id) { $where[] = 's.section_id=?'; $params[] = $section_id; }
    $wh = implode(' AND ', $where);

    $stmt = $pdo->prepare(
        "SELECT s.id, s.name, s.student_id, s.photo FROM students s WHERE $wh ORDER BY s.name"
    );
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    // Existing attendance for this date
    if (!empty($students)) {
        $ids = array_column($students, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE date=? AND student_id IN ($in)");
        $stmt->execute(array_merge([$date], $ids));
        foreach ($stmt->fetchAll() as $a) {
            $attendance_map[$a['student_id']] = $a['status'];
        }
    }
}

// Save attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    csrf_protect();

    $p_class   = sanitize_int($_POST['class_id']   ?? 0);
    $p_section = sanitize_int($_POST['section_id'] ?? 0);
    $p_date    = sanitize($_POST['date'] ?? '');
    $att_data  = $_POST['attendance'] ?? [];

    if ($p_class && $p_date && !empty($att_data)) {
        $upsert = $pdo->prepare(
            "INSERT INTO attendance (student_id,class_id,section_id,date,status,marked_by)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)"
        );
        $absent_students = [];

        foreach ($att_data as $stu_id => $status) {
            $stu_id = (int)$stu_id;
            $status = in_array($status, ['Present','Absent','Late','Holiday']) ? $status : 'Present';
            $upsert->execute([$stu_id, $p_class, $p_section ?: null, $p_date, $status, $_SESSION['user_id']]);

            if ($status === 'Absent') {
                $absent_students[] = $stu_id;
            }
        }

        // Send absence alerts to parents
        if (!empty($absent_students)) {
            $in  = implode(',', array_fill(0, count($absent_students), '?'));
            $stmt = $pdo->prepare(
                "SELECT s.name as student_name, p.name as parent_name, p.email as parent_email
                 FROM students s JOIN parents p ON s.parent_id=p.id
                 WHERE s.id IN ($in) AND p.email IS NOT NULL"
            );
            $stmt->execute($absent_students);
            foreach ($stmt->fetchAll() as $row) {
                SchoolMailer::sendAbsenceAlert($row['parent_email'], $row['parent_name'], $row['student_name'], $p_date);
            }
        }

        set_flash('success', 'Attendance saved for ' . count($att_data) . ' students.');
    }
    redirect(SITE_URL . "/admin/attendance/?class_id={$p_class}&section_id={$p_section}&date=" . urlencode($p_date));
}

include INCLUDES_PATH . 'header.php';
?>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white border-0 fw-semibold"><i class="bi bi-calendar-check me-2 text-primary"></i>Select Class & Date</div>
  <div class="card-body">
    <form method="GET" class="row g-3 align-items-end" id="filterForm">
      <div class="col-12 col-md-3">
        <label class="form-label fw-semibold">Class *</label>
        <select name="class_id" class="form-select" id="classSelect" required>
          <option value="">Select Class</option>
          <?php foreach ($classes as $cl): ?>
          <option value="<?= $cl['id'] ?>" <?= $class_id==$cl['id']?'selected':'' ?>><?= sanitize($cl['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label fw-semibold">Section</label>
        <select name="section_id" class="form-select" id="sectionSelect">
          <option value="">All Sections</option>
          <?php foreach ($sections as $sec): ?>
          <option value="<?= $sec['id'] ?>" <?= $section_id==$sec['id']?'selected':'' ?>><?= sanitize($sec['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label fw-semibold">Date *</label>
        <input type="date" name="date" class="form-control" value="<?= sanitize($date) ?>" max="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>Load</button>
      </div>
    </form>
  </div>
</div>

<?php if ($class_id && !empty($students)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><i class="bi bi-calendar-check me-2 text-success"></i>Mark Attendance - <?= format_date($date) ?></span>
    <div class="d-flex gap-2">
      <button class="btn btn-sm btn-success" onclick="markAll('Present')">All Present</button>
      <button class="btn btn-sm btn-danger" onclick="markAll('Absent')">All Absent</button>
    </div>
  </div>
  <div class="card-body">
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="save_attendance" value="1">
      <input type="hidden" name="class_id"   value="<?= $class_id ?>">
      <input type="hidden" name="section_id" value="<?= $section_id ?>">
      <input type="hidden" name="date"       value="<?= sanitize($date) ?>">

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Photo</th>
              <th>Student ID</th>
              <th>Name</th>
              <th>Attendance</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $i => $stu):
              $att_status = $attendance_map[$stu['id']] ?? 'Present';
            ?>
            <tr>
              <td class="text-muted small"><?= $i+1 ?></td>
              <td>
                <?php if ($stu['photo']): ?>
                  <img src="<?= get_upload_url($stu['photo']) ?>" class="rounded-circle" width="32" height="32" style="object-fit:cover">
                <?php else: ?>
                  <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:12px">
                    <?= strtoupper(substr($stu['name'],0,1)) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><code class="small text-primary"><?= sanitize($stu['student_id']) ?></code></td>
              <td class="fw-semibold"><?= sanitize($stu['name']) ?></td>
              <td>
                <div class="d-flex gap-2 flex-wrap att-btn-group" data-id="<?= $stu['id'] ?>">
                  <?php foreach (['Present'=>'success','Absent'=>'danger','Late'=>'warning','Holiday'=>'info'] as $status => $color): ?>
                  <div class="form-check d-none">
                    <input class="form-check-input" type="radio"
                           name="attendance[<?= $stu['id'] ?>]"
                           id="att_<?= $stu['id'] ?>_<?= $status ?>"
                           value="<?= $status ?>"
                           <?= $att_status === $status ? 'checked' : '' ?>>
                  </div>
                  <?php endforeach; ?>

                  <?php foreach (['Present'=>'success','Absent'=>'danger','Late'=>'warning','Holiday'=>'info'] as $status => $color): ?>
                  <button type="button"
                          class="btn btn-sm att-btn btn-<?= $att_status === $status ? $color : 'outline-'.$color ?>"
                          data-status="<?= $status ?>"
                          onclick="setAtt(<?= $stu['id'] ?>, '<?= $status ?>')">
                    <?= $status ?>
                  </button>
                  <?php endforeach; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-save me-2"></i>Save Attendance
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function setAtt(studentId, status) {
  // Update hidden radio
  const radio = document.getElementById(`att_${studentId}_${status}`);
  if (radio) radio.checked = true;

  // Update buttons
  const group = document.querySelector(`.att-btn-group[data-id="${studentId}"]`);
  const colors = { Present:'success', Absent:'danger', Late:'warning', Holiday:'info' };

  group.querySelectorAll('.att-btn').forEach(btn => {
    const s   = btn.dataset.status;
    const col = colors[s];
    btn.className = `btn btn-sm att-btn btn-${s === status ? col : 'outline-'+col}`;
  });
}

function markAll(status) {
  document.querySelectorAll('.att-btn-group').forEach(group => {
    const id = group.dataset.id;
    setAtt(id, status);
  });
}
</script>

<?php elseif ($class_id && empty($students)): ?>
<div class="alert alert-warning">No active students found for this class.</div>
<?php endif; ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
